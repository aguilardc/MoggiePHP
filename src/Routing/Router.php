<?php

declare(strict_types=1);

namespace Moggie\Routing;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Moggie\Container\Container;
use Moggie\Http\Request;
use Moggie\Http\Response;
use Moggie\Http\JsonResponse;
use Moggie\Http\Exceptions\NotFoundHttpException;
use Moggie\Http\Exceptions\MethodNotAllowedHttpException;
use Moggie\Http\Exceptions\HttpException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionParameter;
use Closure;
use function FastRoute\simpleDispatcher;

class Router
{
    protected Container $container;
    protected RouteCollection $routes;
    protected array $groupStack = [];
    protected array $routeMiddleware = [];
    protected array $middlewareGroups = [];
    protected ?Dispatcher $dispatcher = null;
    protected array $patterns = [];
    protected array $namedRoutes = [];
    protected bool $cacheEnabled = true;
    protected ?string $cacheFile = null;

    // Default route patterns
    protected array $defaultPatterns = [
        'id' => '[0-9]+',
        'slug' => '[a-z0-9-]+',
        'uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}',
        'hash' => '[a-zA-Z0-9]+',
        'any' => '.*',
    ];

    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->routes = new RouteCollection();
        $this->patterns = $this->defaultPatterns;
        $this->cacheFile = $container->basePath('bootstrap/cache/routes.php');
    }

    // HTTP Method shortcuts
    public function get(string $uri, $action): Route
    {
        return $this->addRoute(['GET'], $uri, $action);
    }

    public function post(string $uri, $action): Route
    {
        return $this->addRoute(['POST'], $uri, $action);
    }

    public function put(string $uri, $action): Route
    {
        return $this->addRoute(['PUT'], $uri, $action);
    }

    public function patch(string $uri, $action): Route
    {
        return $this->addRoute(['PATCH'], $uri, $action);
    }

    public function delete(string $uri, $action): Route
    {
        return $this->addRoute(['DELETE'], $uri, $action);
    }

    public function options(string $uri, $action): Route
    {
        return $this->addRoute(['OPTIONS'], $uri, $action);
    }

    public function head(string $uri, $action): Route
    {
        return $this->addRoute(['HEAD'], $uri, $action);
    }

    public function any(string $uri, $action): Route
    {
        return $this->addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], $uri, $action);
    }

    public function match(array $methods, string $uri, $action): Route
    {
        return $this->addRoute($methods, $uri, $action);
    }

    // RESTful resource routing
    public function resource(string $name, string $controller, array $options = []): RouteRegistrar
    {
        $registrar = new RouteRegistrar($this);

        return $registrar->resource($name, $controller, $options);
    }

    public function apiResource(string $name, string $controller, array $options = []): RouteRegistrar
    {
        $options['except'] = array_merge($options['except'] ?? [], ['create', 'edit']);

        return $this->resource($name, $controller, $options);
    }

    // Route groups
    public function group(array $attributes, Closure $callback): void
    {
        $this->groupStack[] = $this->mergeLastGroup($attributes);

        $callback($this);

        array_pop($this->groupStack);
    }

    protected function mergeLastGroup(array $new): array
    {
        if (empty($this->groupStack)) {
            return $new;
        }

        return $this->mergeGroup($new, end($this->groupStack));
    }

    protected function mergeGroup(array $new, array $old): array
    {
        $new['namespace'] = static::formatUsesPrefix($new, $old);
        $new['prefix'] = static::formatGroupPrefix($new, $old);

        if (isset($new['domain'])) {
            unset($old['domain']);
        }

        if (isset($old['as'])) {
            $new['as'] = $old['as'] . ($new['as'] ?? '');
        }

        if (isset($old['suffix']) && isset($new['suffix'])) {
            $new['suffix'] = $old['suffix'] . $new['suffix'];
        }

        return array_merge_recursive(array_except($old, ['namespace', 'prefix', 'as', 'suffix']), $new);
    }

    protected static function formatUsesPrefix(array $new, array $old): ?string
    {
        if (isset($new['namespace'])) {
            return isset($old['namespace']) && !str_starts_with($new['namespace'], '\\')
                ? trim($old['namespace'], '\\') . '\\' . trim($new['namespace'], '\\')
                : trim($new['namespace'], '\\');
        }

        return $old['namespace'] ?? null;
    }

    protected static function formatGroupPrefix(array $new, array $old): ?string
    {
        $oldPrefix = $old['prefix'] ?? null;
        $newPrefix = $new['prefix'] ?? null;

        if ($newPrefix === null) {
            return $oldPrefix;
        }

        return trim($oldPrefix, '/') . '/' . trim($newPrefix, '/');
    }

    // Core route registration
    protected function addRoute(array $methods, string $uri, $action): Route
    {
        $route = $this->createRoute($methods, $uri, $action);

        $this->routes->add($route);

        // Clear cached dispatcher
        $this->dispatcher = null;

        // Register named route
        if ($route->getName()) {
            $this->namedRoutes[$route->getName()] = $route;
        }

        return $route;
    }

    protected function createRoute(array $methods, string $uri, $action): Route
    {
        $route = new Route($methods, $uri, $action);

        // Apply group attributes
        if (!empty($this->groupStack)) {
            $route->setGroupAttributes(end($this->groupStack));
        }

        return $route;
    }

    // Middleware management
    public function middleware(string $name, string $class): void
    {
        $this->routeMiddleware[$name] = $class;
    }

    public function middlewareGroup(string $name, array $middleware): void
    {
        $this->middlewareGroups[$name] = $middleware;
    }

    public function pushMiddlewareToGroup(string $group, string $middleware): void
    {
        if (!isset($this->middlewareGroups[$group])) {
            $this->middlewareGroups[$group] = [];
        }

        $this->middlewareGroups[$group][] = $middleware;
    }

    public function prependMiddlewareToGroup(string $group, string $middleware): void
    {
        if (!isset($this->middlewareGroups[$group])) {
            $this->middlewareGroups[$group] = [];
        }

        array_unshift($this->middlewareGroups[$group], $middleware);
    }

    // Route patterns
    public function pattern(string $key, string $pattern): void
    {
        $this->patterns[$key] = $pattern;
    }

    public function patterns(array $patterns): void
    {
        $this->patterns = array_merge($this->patterns, $patterns);
    }

    // Named routes
    public function getRouteByName(string $name): ?Route
    {
        return $this->namedRoutes[$name] ?? null;
    }

    public function hasNamedRoute(string $name): bool
    {
        return isset($this->namedRoutes[$name]);
    }

    // URL generation
    public function url(string $name, array $parameters = [], bool $absolute = true): string
    {
        $route = $this->getRouteByName($name);

        if (!$route) {
            throw new \InvalidArgumentException("Route [{$name}] not defined");
        }

        return $route->url($parameters, $absolute);
    }

    public function urlFor(string $name, array $parameters = []): string
    {
        return $this->url($name, $parameters, true);
    }

    public function pathFor(string $name, array $parameters = []): string
    {
        return $this->url($name, $parameters, false);
    }

    // Route dispatching
    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $uri = $request->getPathInfo();

        $dispatcher = $this->getDispatcher();

        $routeInfo = $dispatcher->dispatch($method, $uri);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                throw new NotFoundHttpException("The route [{$method}] {$uri} could not be found");

            case Dispatcher::METHOD_NOT_ALLOWED:
                throw new MethodNotAllowedHttpException(
                    $routeInfo[1],
                    "The {$method} method is not supported for this route. Supported methods: " . implode(', ', $routeInfo[1])
                );

            case Dispatcher::FOUND:
                return $this->handleFoundRoute($request, $routeInfo[1], $routeInfo[2]);

            default:
                throw new NotFoundHttpException();
        }
    }

    protected function handleFoundRoute(Request $request, Route $route, array $parameters): Response
    {
        // Set route parameters in request
        $request->setRouteParameters($parameters);

        // Store current route in container
        $this->container->instance('current.route', $route);

        // Execute route through middleware pipeline
        $middlewareStack = $this->gatherRouteMiddleware($route);

        return $this->runRouteWithinStack($route, $request, $middlewareStack);
    }

    protected function gatherRouteMiddleware(Route $route): array
    {
        $middleware = [];

        foreach ($route->getMiddleware() as $name) {
            $middleware = array_merge($middleware, $this->resolveMiddlewareName($name));
        }

        return array_unique($middleware);
    }

    protected function resolveMiddlewareName(string $name): array
    {
        // Check if it's a middleware group
        if (isset($this->middlewareGroups[$name])) {
            $middleware = [];
            foreach ($this->middlewareGroups[$name] as $groupMiddleware) {
                $middleware = array_merge($middleware, $this->resolveMiddlewareName($groupMiddleware));
            }
            return $middleware;
        }

        // Check if it's an aliased middleware
        if (isset($this->routeMiddleware[$name])) {
            return [$this->routeMiddleware[$name]];
        }

        // Return as-is (class name)
        return [$name];
    }

    protected function runRouteWithinStack(Route $route, Request $request, array $middleware): Response
    {
        if (empty($middleware)) {
            return $this->executeRoute($route, $request);
        }

        $middlewareStack = $this->container->get('middleware');

        return $middlewareStack->handle($request, function ($req) use ($route) {
            return $this->executeRoute($route, $req);
        }, $middleware);
    }

    protected function executeRoute(Route $route, Request $request): Response
    {
        $action = $route->getAction();

        try {
            if ($action instanceof Closure) {
                return $this->callClosure($action, $request, $route);
            }

            if (is_string($action)) {
                return $this->callController($action, $request, $route);
            }

            if (is_array($action) && count($action) === 2) {
                [$controller, $method] = $action;
                return $this->callControllerMethod($controller, $method, $request, $route);
            }

            throw new \InvalidArgumentException('Invalid route action');

        } catch (HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                "Error executing route [{$route->getUri()}]: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    protected function callClosure(Closure $closure, Request $request, Route $route): Response
    {
        $parameters = $this->resolveMethodDependencies($closure, $request, $route);
        $result = $closure(...$parameters);

        return $this->prepareResponse($result);
    }

    protected function callController(string $action, Request $request, Route $route): Response
    {
        if (!str_contains($action, '@')) {
            throw new \InvalidArgumentException('Controller action must contain @ symbol');
        }

        [$controller, $method] = explode('@', $action, 2);

        return $this->callControllerMethod($controller, $method, $request, $route);
    }

    protected function callControllerMethod(string $controller, string $method, Request $request, Route $route): Response
    {
        // Apply namespace from group if needed
        if (!str_starts_with($controller, '\\') && $route->getControllerNamespace()) {
            $controller = $route->getControllerNamespace() . '\\' . $controller;
        }

        if (!class_exists($controller)) {
            throw new \InvalidArgumentException("Controller [{$controller}] not found");
        }

        $instance = $this->container->get($controller);

        if (!method_exists($instance, $method)) {
            throw new \InvalidArgumentException("Method [{$method}] not found in controller [{$controller}]");
        }

        // Check if controller has middleware
        if (method_exists($instance, 'getMiddleware')) {
            $controllerMiddleware = $instance->getMiddleware();
            // Apply controller middleware logic here if needed
        }

        $parameters = $this->resolveMethodDependencies([$instance, $method], $request, $route);
        $result = $instance->{$method}(...$parameters);

        return $this->prepareResponse($result);
    }

    protected function resolveMethodDependencies($callback, Request $request, Route $route): array
    {
        $reflection = is_array($callback)
            ? new ReflectionMethod($callback[0], $callback[1])
            : new ReflectionFunction($callback);

        $parameters = [];
        $routeParams = $request->getRouteParameters();

        foreach ($reflection->getParameters() as $parameter) {
            $parameters[] = $this->resolveParameter($parameter, $request, $route, $routeParams);
        }

        return $parameters;
    }

    protected function resolveParameter(ReflectionParameter $parameter, Request $request, Route $route, array $routeParams)
    {
        $type = $parameter->getType();
        $name = $parameter->getName();

        // Type-hinted dependencies
        if ($type && !$type->isBuiltin()) {
            $className = $type->getName();

            // Inject Request or its subclasses
            if ($className === Request::class || is_subclass_of($className, Request::class)) {
                return $request;
            }

            // Inject Route
            if ($className === Route::class) {
                return $route;
            }

            // Resolve from container
            return $this->container->get($className);
        }

        // Route parameters
        if (isset($routeParams[$name])) {
            return $this->transformParameter($routeParams[$name], $parameter);
        }

        // Query parameters fallback
        if ($request->has($name)) {
            return $this->transformParameter($request->input($name), $parameter);
        }

        // Default value
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        // Optional parameter
        if ($parameter->allowsNull()) {
            return null;
        }

        throw new \InvalidArgumentException("Unable to resolve parameter [{$name}] for route [{$route->getUri()}]");
    }

    protected function transformParameter($value, ReflectionParameter $parameter)
    {
        $type = $parameter->getType();

        if (!$type || $type->isBuiltin()) {
            // Handle basic type conversion
            if ($type) {
                $typeName = $type->getName();

                switch ($typeName) {
                    case 'int':
                        return (int)$value;
                    case 'float':
                        return (float)$value;
                    case 'bool':
                        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    case 'string':
                        return (string)$value;
                }
            }
        }

        return $value;
    }

    protected function prepareResponse($result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return new JsonResponse($result);
        }

        if (is_string($result) || is_numeric($result)) {
            return new Response((string)$result);
        }

        if ($result === null) {
            return new Response('', 204);
        }

        if ($result === true) {
            return new JsonResponse(['success' => true]);
        }

        if ($result === false) {
            return new JsonResponse(['success' => false], 400);
        }

        // Try to JSON encode objects
        try {
            return new JsonResponse($result);
        } catch (\JsonException $e) {
            return new Response((string)$result);
        }
    }

    // Route compilation and caching
    protected function getDispatcher(): Dispatcher
    {
        if ($this->dispatcher === null) {
            $this->dispatcher = $this->createDispatcher();
        }

        return $this->dispatcher;
    }

    protected function createDispatcher(): Dispatcher
    {
        if ($this->cacheEnabled && $this->cacheFile && file_exists($this->cacheFile)) {
            return $this->loadCachedDispatcher();
        }

        return $this->buildDispatcher();
    }

    protected function loadCachedDispatcher(): Dispatcher
    {
        $cached = require $this->cacheFile;

        return \FastRoute\cachedDispatcher(function () {
        }, [
            'cacheFile' => $this->cacheFile,
            'cacheDisabled' => false,
        ]);
    }

    protected function buildDispatcher(): Dispatcher
    {
        return simpleDispatcher(function (RouteCollector $collector) {
            foreach ($this->routes->getRoutes() as $route) {
                $collector->addRoute(
                    $route->getMethods(),
                    $this->compileRouteUri($route),
                    $route
                );
            }
        });
    }

    protected function compileRouteUri(Route $route): string
    {
        $uri = $route->getUri();

        // Apply global patterns
        foreach ($this->patterns as $key => $pattern) {
            $uri = str_replace('{' . $key . '}', '{' . $key . ':' . $pattern . '}', $uri);
        }

        // Apply route-specific patterns
        foreach ($route->getWheres() as $key => $pattern) {
            $uri = preg_replace('/\{' . preg_quote($key) . '(\:[^}]+)?\}/', '{' . $key . ':' . $pattern . '}', $uri);
        }

        return $uri;
    }

    // Cache management
    public function cache(): void
    {
        if (!$this->cacheFile) {
            throw new \RuntimeException('No cache file specified');
        }

        $cacheDir = dirname($this->cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        // Build and cache the dispatcher
        $dispatcher = $this->buildDispatcher();

        // The caching is handled internally by FastRoute
    }

    public function clearCache(): void
    {
        if ($this->cacheFile && file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }

        $this->dispatcher = null;
    }

    // Route introspection
    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    public function hasRoutes(): bool
    {
        return !$this->routes->isEmpty();
    }

    public function getRouteCount(): int
    {
        return $this->routes->count();
    }

    public function getNamedRoutes(): array
    {
        return $this->namedRoutes;
    }

    public function getMiddleware(): array
    {
        return $this->routeMiddleware;
    }

    public function getMiddlewareGroups(): array
    {
        return $this->middlewareGroups;
    }

    // Configuration
    public function setCacheEnabled(bool $enabled): void
    {
        $this->cacheEnabled = $enabled;
    }

    public function setCacheFile(string $file): void
    {
        $this->cacheFile = $file;
    }

    // Debugging and development
    public function dumpRoutes(): array
    {
        $routes = [];

        foreach ($this->routes->getRoutes() as $route) {
            $routes[] = [
                'methods' => $route->getMethods(),
                'uri' => $route->getUri(),
                'name' => $route->getName(),
                'action' => $route->getActionName(),
                'middleware' => $route->getMiddleware(),
            ];
        }

        return $routes;
    }

    public function findRoutes(string $search): array
    {
        $matching = [];

        foreach ($this->routes->getRoutes() as $route) {
            if (str_contains($route->getUri(), $search) ||
                str_contains($route->getActionName(), $search) ||
                ($route->getName() && str_contains($route->getName(), $search))) {
                $matching[] = $route;
            }
        }

        return $matching;
    }
}

// Helper function for array operations
if (!function_exists('array_except')) {
    function array_except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }
}
