<?php

declare(strict_types=1);

namespace Moggie\Routing;

class RouteRegistrar
{
    protected Router $router;
    protected array $attributes = [];
    protected array $passthrough = [
        'as', 'domain', 'middleware', 'name', 'namespace', 'prefix', 'where'
    ];

    // Resource action names and methods
    protected array $resourceDefaults = [
        'index' => ['GET', ''],
        'create' => ['GET', '/create'],
        'store' => ['POST', ''],
        'show' => ['GET', '/{id}'],
        'edit' => ['GET', '/{id}/edit'],
        'update' => ['PUT', '/{id}'],
        'destroy' => ['DELETE', '/{id}'],
    ];

    // API resource actions (without create/edit)
    protected array $apiResourceDefaults = [
        'index' => ['GET', ''],
        'store' => ['POST', ''],
        'show' => ['GET', '/{id}'],
        'update' => ['PUT', '/{id}'],
        'destroy' => ['DELETE', '/{id}'],
    ];

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function resource(string $name, string $controller, array $options = []): self
    {
        $defaults = $this->resourceDefaults;

        // Handle 'only' option
        if (isset($options['only'])) {
            $defaults = array_intersect_key($defaults, array_flip((array)$options['only']));
        }

        // Handle 'except' option
        if (isset($options['except'])) {
            $defaults = array_diff_key($defaults, array_flip((array)$options['except']));
        }

        // Handle custom parameters
        $parameters = $options['parameters'] ?? [];
        $parameter = $parameters[$name] ?? 'id';

        foreach ($defaults as $action => [$method, $uri]) {
            $this->addResourceRoute($name, $controller, $action, $method, $uri, $parameter, $options);
        }

        return $this;
    }

    public function apiResource(string $name, string $controller, array $options = []): self
    {
        $options['except'] = array_merge($options['except'] ?? [], ['create', 'edit']);

        return $this->resource($name, $controller, $options);
    }

    protected function addResourceRoute(string $resource, string $controller, string $action, string $method, string $uri, string $parameter, array $options): void
    {
        $name = $this->getResourceRouteName($resource, $action, $options);
        $uri = $this->getResourceUri($resource, $uri, $parameter);
        $action = $this->getResourceAction($controller, $action, $options);

        $route = $this->router->match([$method], $uri, $action);

        if (isset($options['names'][$action]) || isset($options['names']['*'])) {
            $route->name($options['names'][$action] ?? $options['names']['*']);
        } else {
            $route->name($name);
        }

        // Apply middleware
        if (isset($options['middleware'])) {
            $route->middleware($options['middleware']);
        }

        // Apply where constraints
        if (isset($options['where'])) {
            $route->where($options['where']);
        }

        // Apply group attributes
        if (!empty($this->attributes)) {
            $route->setGroupAttributes($this->attributes);
        }
    }

    protected function getResourceRouteName(string $resource, string $action, array $options): string
    {
        $name = $resource;

        if (isset($options['as'])) {
            $name = $options['as'];
        }

        return "{$name}.{$action}";
    }

    protected function getResourceUri(string $resource, string $uri, string $parameter): string
    {
        if (!str_contains($uri, '{id}')) {
            return "/{$resource}{$uri}";
        }

        return str_replace('{id}', "{{$parameter}}", "/{$resource}{$uri}");
    }

    protected function getResourceAction(string $controller, string $action, array $options): string
    {
        if (isset($options['controller'])) {
            $controller = $options['controller'];
        }

        return "{$controller}@{$action}";
    }

    // Nested resource support
    public function nestedResource(string $parent, string $child, string $controller, array $options = []): self
    {
        $parentParameter = $options['parent_parameters'][$parent] ?? $parent . '_id';
        $childParameter = $options['parameters'][$child] ?? 'id';

        $defaults = $this->resourceDefaults;

        if (isset($options['only'])) {
            $defaults = array_intersect_key($defaults, array_flip((array)$options['only']));
        }

        if (isset($options['except'])) {
            $defaults = array_diff_key($defaults, array_flip((array)$options['except']));
        }

        foreach ($defaults as $action => [$method, $uri]) {
            $this->addNestedResourceRoute($parent, $child, $controller, $action, $method, $uri, $parentParameter, $childParameter, $options);
        }

        return $this;
    }

    protected function addNestedResourceRoute(string $parent, string $child, string $controller, string $action, string $method, string $uri, string $parentParameter, string $childParameter, array $options): void
    {
        $name = $this->getNestedResourceRouteName($parent, $child, $action, $options);
        $uri = $this->getNestedResourceUri($parent, $child, $uri, $parentParameter, $childParameter);
        $action = $this->getResourceAction($controller, $action, $options);

        $route = $this->router->match([$method], $uri, $action);
        $route->name($name);

        if (isset($options['middleware'])) {
            $route->middleware($options['middleware']);
        }

        if (isset($options['where'])) {
            $route->where($options['where']);
        }

        if (!empty($this->attributes)) {
            $route->setGroupAttributes($this->attributes);
        }
    }

    protected function getNestedResourceRouteName(string $parent, string $child, string $action, array $options): string
    {
        $parentName = $parent;
        $childName = $child;

        if (isset($options['as'])) {
            if (is_array($options['as'])) {
                $parentName = $options['as'][$parent] ?? $parent;
                $childName = $options['as'][$child] ?? $child;
            } else {
                $childName = $options['as'];
            }
        }

        return "{$parentName}.{$childName}.{$action}";
    }

    protected function getNestedResourceUri(string $parent, string $child, string $uri, string $parentParameter, string $childParameter): string
    {
        $parentUri = "/{$parent}/{{$parentParameter}}";

        if (!str_contains($uri, '{id}')) {
            return "{$parentUri}/{$child}{$uri}";
        }

        return str_replace('{id}', "{{$childParameter}}", "{$parentUri}/{$child}{$uri}");
    }

    // Shallow nested resources
    public function shallowResource(string $parent, string $child, string $controller, array $options = []): self
    {
        $options['shallow'] = true;
        return $this->nestedResource($parent, $child, $controller, $options);
    }

    // Route model binding support
    public function bind(string $key, string $class): self
    {
        $this->router->bind($key, $class);
        return $this;
    }

    public function model(string $key, string $class, ?Closure $callback = null): self
    {
        $this->router->model($key, $class, $callback);
        return $this;
    }

    // Dynamic method handling for route attributes
    public function __call(string $method, array $parameters): self
    {
        if (in_array($method, $this->passthrough)) {
            return $this->setAttribute($method, $parameters[0]);
        }

        throw new \BadMethodCallException("Method [{$method}] does not exist.");
    }

    protected function setAttribute(string $key, $value): self
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    public function group(Closure $callback): self
    {
        $this->router->group($this->attributes, $callback);
        return $this;
    }

    // Macros support for custom methods
    protected static array $macros = [];

    public static function macro(string $name, Closure $macro): void
    {
        static::$macros[$name] = $macro;
    }

    public static function hasMacro(string $name): bool
    {
        return isset(static::$macros[$name]);
    }

    public function __callStatic(string $method, array $parameters)
    {
        if (static::hasMacro($method)) {
            return call_user_func_array(static::$macros[$method], $parameters);
        }

        throw new \BadMethodCallException("Method [{$method}] does not exist.");
    }
}
