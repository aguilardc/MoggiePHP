<?php

namespace Moggie\Middleware;

use Moggie\Http\Request;
use Moggie\Http\Response;

/**
 * Class HandleCors
 *
 * @package \Moggie\Middleware
 */
class HandleCors
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'allowed_origins' => ['*'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['*'],
            'exposed_headers' => [],
            'max_age' => 0,
            'supports_credentials' => false,
        ], $config);
    }

    public function handle(Request $request, \Closure $next): Response
    {
        if ($this->isPreflightRequest($request)) {
            return $this->handlePreflightRequest($request);
        }

        $response = $next($request);

        return $this->addCorsHeaders($request, $response);
    }

    protected function isPreflightRequest(Request $request): bool
    {
        return $request->isMethod('OPTIONS') &&
            $request->hasHeader('Access-Control-Request-Method');
    }

    protected function handlePreflightRequest(Request $request): Response
    {
        $response = new Response('', 204);

        return $this->addCorsHeaders($request, $response);
    }

    protected function addCorsHeaders(Request $request, Response $response): Response
    {
        $origin = $request->header('Origin');

        if ($this->isOriginAllowed($origin)) {
            $response->setHeader('Access-Control-Allow-Origin', $origin ?: '*');
        }

        if ($this->config['supports_credentials']) {
            $response->setHeader('Access-Control-Allow-Credentials', 'true');
        }

        if ($request->isMethod('OPTIONS')) {
            $response->setHeader(
                'Access-Control-Allow-Methods',
                implode(', ', $this->config['allowed_methods'])
            );

            $requestHeaders = $request->header('Access-Control-Request-Headers');
            if ($requestHeaders && $this->areHeadersAllowed($requestHeaders)) {
                $response->setHeader('Access-Control-Allow-Headers', $requestHeaders);
            }

            if ($this->config['max_age'] > 0) {
                $response->setHeader('Access-Control-Max-Age', (string)$this->config['max_age']);
            }
        }

        if (!empty($this->config['exposed_headers'])) {
            $response->setHeader(
                'Access-Control-Expose-Headers',
                implode(', ', $this->config['exposed_headers'])
            );
        }

        return $response;
    }

    protected function isOriginAllowed(?string $origin): bool
    {
        if (!$origin) {
            return false;
        }

        if (in_array('*', $this->config['allowed_origins'])) {
            return true;
        }

        return in_array($origin, $this->config['allowed_origins']);
    }

    protected function areHeadersAllowed(string $headers): bool
    {
        if (in_array('*', $this->config['allowed_headers'])) {
            return true;
        }

        $requestedHeaders = array_map('trim', explode(',', strtolower($headers)));
        $allowedHeaders = array_map('strtolower', $this->config['allowed_headers']);

        foreach ($requestedHeaders as $header) {
            if (!in_array($header, $allowedHeaders)) {
                return false;
            }
        }

        return true;
    }
}
