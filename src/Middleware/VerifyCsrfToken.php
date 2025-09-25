<?php

declare(strict_types=1);

namespace Moggie\Middleware;

use Moggie\Container\Exceptions\BindingResolutionException;
use Moggie\Container\Exceptions\EntryNotFoundException;
use Moggie\Http\Request;
use Moggie\Http\Response;
use Moggie\Http\JsonResponse;
use Moggie\Http\Exceptions\HttpException;
use Moggie\Container\Container;
use Closure;
use Random\RandomException;
use ReflectionException;

class VerifyCsrfToken
{
    protected Container $container;
    protected array $config;

    // URIs that should be excluded from CSRF verification
    protected array $except = [];

    // HTTP methods that should be verified
    protected array $verifiableMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    // Token header names to check
    protected array $tokenHeaders = [
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
    ];

    /**
     * @throws ReflectionException
     * @throws EntryNotFoundException
     * @throws BindingResolutionException
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->config = $container->get('config')->get('csrf', []);
        $this->loadConfiguration();
    }

    protected function loadConfiguration(): void
    {
        // Override default except URIs from config
        if (isset($this->config['except'])) {
            $this->except = array_merge($this->except, $this->config['except']);
        }

        // Override verifiable methods from config
        if (isset($this->config['verifiable_methods'])) {
            $this->verifiableMethods = $this->config['verifiable_methods'];
        }

        // Override token headers from config
        if (isset($this->config['token_headers'])) {
            $this->tokenHeaders = $this->config['token_headers'];
        }
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Skip CSRF verification for certain conditions
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        // Verify CSRF token
        if (!$this->verifyToken($request)) {
            return $this->handleTokenMismatch($request);
        }

        // Add CSRF token to response if needed
        $response = $next($request);

        return $this->addTokenToResponse($request, $response);
    }

    protected function shouldSkip(Request $request): bool
    {
        // Skip if method doesn't require verification
        if (!in_array($request->getMethod(), $this->verifiableMethods)) {
            return true;
        }

        // Skip if URI is in except list
        if ($this->isExceptUri($request)) {
            return true;
        }

        // Skip if running in console
        if ($this->runningUnitTests()) {
            return true;
        }

        // Skip for API requests if configured
        if ($this->shouldSkipApiRequests() && $this->isApiRequest($request)) {
            return true;
        }

        return false;
    }

    protected function isExceptUri(Request $request): bool
    {
        $uri = $request->getPathInfo();

        foreach ($this->except as $pattern) {
            if ($this->matchesPattern($pattern, $uri)) {
                return true;
            }
        }

        return false;
    }

    protected function matchesPattern(string $pattern, string $uri): bool
    {
        // Exact match
        if ($pattern === $uri) {
            return true;
        }

        // Wildcard matching
        if (str_contains($pattern, '*')) {
            $regexPattern = str_replace('*', '.*', preg_quote($pattern, '/'));
            return (bool)preg_match('/^' . $regexPattern . '$/', $uri);
        }

        return false;
    }

    protected function verifyToken(Request $request): bool
    {
        $token = $this->getTokenFromRequest($request);

        if (!$token) {
            return false;
        }

        $sessionToken = $this->getTokenFromSession($request);

        if (!$sessionToken) {
            return false;
        }

        return $this->tokensMatch($token, $sessionToken);
    }

    protected function getTokenFromRequest(Request $request): ?string
    {
        // Check form data first
        $token = $request->input('_token');

        if ($token) {
            return $token;
        }

        // Check headers
        foreach ($this->tokenHeaders as $header) {
            $token = $request->header($header);
            if ($token) {
                return $token;
            }
        }

        return null;
    }

    protected function getTokenFromSession(Request $request): ?string
    {
        // Get session token from various possible sources
        $session = $this->getSession($request);

        if (!$session) {
            return $this->generateToken($request);
        }

        return $session->get('_token') ?? $this->generateToken($request);
    }

    protected function getSession(Request $request)
    {
        // Try to get session from container
        try {
            return $this->container->get('session');
        } catch (\Exception $e) {
            // Fallback to PHP session if no session service
            return $this->getPhpSession($request);
        }
    }

    protected function getPhpSession(Request $request): ?PhpSession
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return new PhpSession();
    }

    protected function tokensMatch(string $token, string $sessionToken): bool
    {
        // Use hash_equals to prevent timing attacks
        return hash_equals($sessionToken, $token);
    }

    /**
     * @throws RandomException
     */
    protected function generateToken(Request $request): string
    {
        // Generate a cryptographically secure token
        $token = bin2hex(random_bytes(32));

        // Store in session
        $session = $this->getSession($request);
        if ($session) {
            $session->put('_token', $token);
        }

        return $token;
    }

    protected function handleTokenMismatch(Request $request): Response
    {
        if ($request->expectsJson()) {
            return new JsonResponse([
                'error' => 'CSRF token mismatch',
                'message' => 'The provided CSRF token is invalid or expired'
            ], 419);
        }

        // For non-JSON requests, throw HTTP exception
        throw new HttpException(419, 'CSRF token mismatch');
    }

    protected function addTokenToResponse(Request $request, Response $response): Response
    {
        // Add CSRF token to JSON responses if needed
        if ($response instanceof JsonResponse && $this->shouldAddTokenToResponse($request)) {
            $token = $this->getTokenFromSession($request);

            $data = $response->getData();
            $data['csrf_token'] = $token;

            return $response->setData($data);
        }

        // Add CSRF token as cookie for SPA applications
        if ($this->shouldAddTokenCookie($request)) {
            $token = $this->getTokenFromSession($request);
            $response->cookie('XSRF-TOKEN', $token, 0, '/', (string)null, false, false);
        }

        return $response;
    }

    protected function shouldAddTokenToResponse(Request $request): bool
    {
        return $this->config['add_to_response'] ?? false;
    }

    protected function shouldAddTokenCookie(Request $request): bool
    {
        return $this->config['add_cookie'] ?? true;
    }

    protected function shouldSkipApiRequests(): bool
    {
        return $this->config['skip_api'] ?? true;
    }

    protected function isApiRequest(Request $request): bool
    {
        // Check if request path starts with API prefix
        $apiPrefixes = $this->config['api_prefixes'] ?? ['api/', '/api/'];
        $uri = $request->getPathInfo();

        foreach ($apiPrefixes as $prefix) {
            if (str_starts_with($uri, $prefix)) {
                return true;
            }
        }

        // Check Accept header for JSON
        return $request->expectsJson();
    }

    protected function runningUnitTests(): bool
    {
        return defined('PHPUNIT_RUNNING') ||
            (function_exists('app') && app()->environment('testing'));
    }

    // Public methods for manual token management
    public function getToken(Request $request): string
    {
        return $this->getTokenFromSession($request);
    }

    /**
     * @throws RandomException
     */
    public function regenerateToken(Request $request): string
    {
        return $this->generateToken($request);
    }

    public function addExceptUri(string $uri): void
    {
        $this->except[] = $uri;
    }

    public function addExceptUris(array $uris): void
    {
        $this->except = array_merge($this->except, $uris);
    }

    public function removeExceptUri(string $uri): void
    {
        $this->except = array_filter($this->except, fn($except) => $except !== $uri);
    }

    // Static methods for global configuration
    public static function except(array $uris): string
    {
        return static::class . ':' . implode(',', $uris);
    }
}
