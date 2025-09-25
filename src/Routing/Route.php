<?php

declare(strict_types=1);

namespace Moggie\Routing;

use Closure;

class Route extends \Moggie\Http\Request
{
    protected array $methods;
    protected string $uri;
    protected $action;
    protected array $middleware = [];
    protected ?string $name = null;
    protected array $wheres = [];
    protected ?string $prefix = null;
    protected ?string $namespace = null;
    protected ?string $domain = null;
    protected array $parameters = [];
    protected bool $compiled = false;
    protected ?string $compiledRoute = null;

    public function __construct(array $methods, string $uri, $action)
    {
        $this->methods = array_map('strtoupper', $methods);
        $this->uri = $uri;
        $this->action = $action;
    }

    // Getters
    public function getMethods(): array
    {
        return $this->methods;
    }

    public function getUri(): string
    {
        return $this->getFullUri();
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getActionName(): string
    {
        if ($this->action instanceof Closure) {
            return 'Closure';
        }

        if (is_string($this->action)) {
            return $this->action;
        }

        if (is_array($this->action)) {
            return (is_string($this->action[0]) ? $this->action[0] : get_class($this->action[0]))
                . '@' . $this->action[1];
        }

        return 'Unknown';
    }

    public function getControllerClass(): ?string
    {
        if (is_string($this->action) && str_contains($this->action, '@')) {
            [$controller] = explode('@', $this->action, 2);

            if ($this->namespace) {
                return $this->namespace . '\\' . $controller;
            }

            return $controller;
        }

        if (is_array($this->action) && isset($this->action[0])) {
            return is_string($this->action[0]) ? $this->action[0] : get_class($this->action[0]);
        }

        return null;
    }

    public function getControllerMethod(): ?string
    {
        if (is_string($this->action) && str_contains($this->action, '@')) {
            [, $method] = explode('@', $this->action, 2);
            return $method;
        }

        if (is_array($this->action) && isset($this->action[1])) {
            return $this->action[1];
        }

        return null;
    }

    public function getControllerNamespace(): ?string
    {
        return $this->namespace;
    }

    // Middleware methods
    public function middleware(...$middleware): self
    {
        if (is_array($middleware[0])) {
            $this->middleware = array_merge($this->middleware, $middleware[0]);
        } else {
            $this->middleware = array_merge($this->middleware, $middleware);
        }

        return $this;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function hasMiddleware(string $middleware): bool
    {
        return in_array($middleware, $this->middleware);
    }

    public function removeMiddleware(string $middleware): self
    {
        $this->middleware = array_diff($this->middleware, [$middleware]);
        return $this;
    }

    public function prependMiddleware(string $middleware): self
    {
        array_unshift($this->middleware, $middleware);
        return $this;
    }

    // Route naming
    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function hasName(): bool
    {
        return $this->name !== null;
    }

    // Route constraints (where conditions)
    public function where(string|array $parameter, ?string $pattern = null): self
    {
        if (is_array($parameter)) {
            foreach ($parameter as $key => $value) {
                $this->wheres[$key] = $value;
            }
        } else {
            $this->wheres[$parameter] = $pattern;
        }

        return $this;
    }

    public function whereNumber(string $parameter): self
    {
        return $this->where($parameter, '[0-9]+');
    }

    public function whereAlpha(string $parameter): self
    {
        return $this->where($parameter, '[a-zA-Z]+');
    }

    public function whereAlphaNumeric(string $parameter): self
    {
        return $this->where($parameter, '[a-zA-Z0-9]+');
    }

    public function whereUuid(string $parameter): self
    {
        return $this->where($parameter, '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
    }

    public function whereIn(string $parameter, array $values): self
    {
        return $this->where($parameter, '(' . implode('|', $values) . ')');
    }

    public function getWheres(): array
    {
        return $this->wheres;
    }

    public function hasWhere(string $parameter): bool
    {
        return isset($this->wheres[$parameter]);
    }

    // Domain handling
    public function domain(?string $domain = null): self
    {
        if ($domain === null) {
            return $this;
        }

        $this->domain = $domain;
        return $this;
    }

    public function getDomain(): ?string
    {
        return $this->domain ?? '';
    }

    public function hasDomain(): bool
    {
        return $this->domain !== null;
    }

    // Group attribute handling
    public function setGroupAttributes(array $attributes): void
    {
        if (isset($attributes['middleware'])) {
            if (is_array($attributes['middleware'])) {
                $this->middleware(...$attributes['middleware']);
            } else {
                $this->middleware($attributes['middleware']);
            }
        }

        if (isset($attributes['prefix'])) {
            $this->prefix = $attributes['prefix'];
        }

        if (isset($attributes['namespace'])) {
            $this->namespace = $attributes['namespace'];
        }

        if (isset($attributes['domain'])) {
            $this->domain = $attributes['domain'];
        }

        if (isset($attributes['name'])) {
            $this->name = $attributes['name'] . ($this->name ? '.' . $this->name : '');
        }

        if (isset($attributes['as'])) {
            $this->name = $attributes['as'] . ($this->name ? '.' . $this->name : '');
        }

        if (isset($attributes['where'])) {
            foreach ($attributes['where'] as $parameter => $pattern) {
                $this->where($parameter, $pattern);
            }
        }
    }

    // URI compilation and matching
    protected function getFullUri(): string
    {
        $uri = $this->uri;

        // Add prefix if set
        if ($this->prefix) {
            $prefix = trim($this->prefix, '/');
            $uri = trim($uri, '/');
            $uri = $prefix . '/' . $uri;
        }

        // Ensure leading slash
        if (!str_starts_with($uri, '/')) {
            $uri = '/' . $uri;
        }

        return $uri;
    }

    public function matches(string $method, string $uri): bool
    {
        if (!in_array(strtoupper($method), $this->methods)) {
            return false;
        }

        return $this->matchesUri($uri);
    }

    protected function matchesUri(string $uri): bool
    {
        $pattern = $this->getCompiledPattern();
        return (bool)preg_match($pattern, $uri);
    }

    protected function getCompiledPattern(): string
    {
        if ($this->compiled) {
            return $this->compiledRoute;
        }

        $uri = $this->getFullUri();

        // Convert route parameters to regex patterns
        $pattern = preg_replace_callback('/\{([^}:]+)(:([^}]+))?\}/', function ($matches) {
            $parameter = $matches[1];
            $constraint = $matches[3] ?? '[^/]+';

            // Check if we have a specific constraint for this parameter
            if (isset($this->wheres[$parameter])) {
                $constraint = $this->wheres[$parameter];
            }

            return '(?P<' . $parameter . '>' . $constraint . ')';
        }, $uri);

        // Escape special regex characters except our parameter patterns
        $specialChars = ['(', ')', '[', ']', '.', '+', '*', '?', '^', '$', '|', '{', '}'];
        $escapedChars = ['\(', '\)', '\[', '\]', '\.', '\+', '\*', '\?', '\^', '\$', '\|', '\{', '\}'];

        // Temporarily replace our named groups to avoid escaping them
        $namedGroups = [];
        $pattern = preg_replace_callback('/\(\?P<[^>]+>[^)]+\)/', function ($matches) use (&$namedGroups) {
            $placeholder = '___NAMED_GROUP_' . count($namedGroups) . '___';
            $namedGroups[$placeholder] = $matches[0];
            return $placeholder;
        }, $pattern);

        // Escape special characters
        $pattern = str_replace($specialChars, $escapedChars, $pattern);

        // Restore named groups
        $pattern = str_replace(array_keys($namedGroups), array_values($namedGroups), $pattern);

        $this->compiledRoute = '#^' . $pattern . '$#';
        $this->compiled = true;

        return $this->compiledRoute;
    }

    public function extractParameters(string $uri): array
    {
        $pattern = $this->getCompiledPattern();

        if (preg_match($pattern, $uri, $matches)) {
            return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        }

        return [];
    }

    // Parameter handling
    public function hasParameter(string $parameter): bool
    {
        return str_contains($this->getFullUri(), '{' . $parameter . '}');
    }

    public function getParameterNames(): array
    {
        preg_match_all('/\{([^}:]+)/', $this->getFullUri(), $matches);
        return $matches[1] ?? [];
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function getParameter(string $name, $default = null)
    {
        return $this->parameters[$name] ?? $default;
    }

    // URL generation
    public function url(array $parameters = [], bool $absolute = true): string
    {
        $uri = $this->getFullUri();

        // Replace parameters in URL
        foreach ($parameters as $key => $value) {
            $uri = str_replace('{' . $key . '}', $value, $uri);
            $uri = preg_replace('/\{' . preg_quote($key) . ':[^}]+\}/', $value, $uri);
        }

        // Check for unreplaced parameters
        if (preg_match('/\{([^}]+)\}/', $uri, $matches)) {
            throw new \InvalidArgumentException("Missing required parameter: {$matches[1]}");
        }

        if (!$absolute) {
            return $uri;
        }

        // For absolute URLs, we'd need access to the current request
        // This is a simplified version
        $scheme = ($_SERVER['HTTPS'] ?? null) ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $scheme . '://' . $host . $uri;
    }

    // Route type checks
    public function isFallback(): bool
    {
        return $this->uri === '{fallback}' || $this->uri === '/{fallback}';
    }

    public function isWildcard(): bool
    {
        return str_contains($this->getFullUri(), '{');
    }

    // HTTP method checks
    public function supportsMethod(string $method): bool
    {
        return in_array(strtoupper($method), $this->methods);
    }

    public function httpOnly(): bool
    {
        return !in_array('HTTPS', $this->methods);
    }

    public function httpsOnly(): bool
    {
        return !in_array('HTTP', $this->methods);
    }

    // Route caching
    public function getCacheKey(): string
    {
        return md5(serialize([
            'methods' => $this->methods,
            'uri' => $this->getFullUri(),
            'domain' => $this->domain,
            'wheres' => $this->wheres,
        ]));
    }

    // Debugging and introspection
    public function toArray(): array
    {
        return [
            'methods' => $this->methods,
            'uri' => $this->getFullUri(),
            'name' => $this->name,
            'action' => $this->getActionName(),
            'domain' => $this->domain,
            'middleware' => $this->middleware,
            'wheres' => $this->wheres,
            'parameters' => $this->getParameterNames(),
        ];
    }

    public function __toString(): string
    {
        return sprintf(
            '%s %s %s',
            implode('|', $this->methods),
            $this->getFullUri(),
            $this->getActionName()
        );
    }

    public function __debugInfo(): array
    {
        return $this->toArray();
    }

    // Magic method for dynamic properties
    public function __get(string $name)
    {
        switch ($name) {
            case 'uri':
                return $this->getUri();
            case 'methods':
                return $this->getMethods();
            case 'action':
                return $this->getAction();
            case 'middleware':
                return $this->getMiddleware();
            case 'name':
                return $this->getName();
            case 'wheres':
                return $this->getWheres();
            case 'domain':
                return $this->getDomain();
            default:
                throw new \InvalidArgumentException("Property [{$name}] does not exist on Route.");
        }
    }

    // Cloning support
    public function __clone()
    {
        $this->compiled = false;
        $this->compiledRoute = null;
    }
}
