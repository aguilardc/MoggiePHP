<?php

namespace Moggie\Middleware;

class MiddlewareStack
{
    protected Container $container;
    protected array $globalMiddleware = [];
    protected array $middlewareAliases = [];
    protected array $middlewareGroups = [];
    protected array $middlewarePriority = [
        \Moggie\Middleware\TrustProxies::class,
        \Moggie\Middleware\HandleCors::class,
        \Moggie\Middleware\CheckForMaintenanceMode::class,
        \Moggie\Middleware\ValidatePostSize::class,
        \Moggie\Middleware\TrimStrings::class,
        \Moggie\Middleware\ConvertEmptyStringsToNull::class,
        \Moggie\Middleware\SubstituteBindings::class,
        \Moggie\Middleware\Authenticate::class,
        \Moggie\Middleware\ThrottleRequests::class,
        \Moggie\Middleware\VerifyCsrfToken::class,
    ];

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function push(string $middleware): void
    {
        $this->globalMiddleware[] = $middleware;
    }

    public function alias(string $alias, string $middleware): void
    {
        $this->middlewareAliases[$alias] = $middleware;
    }

    public function group(string $name, array $middleware): void
    {
        $this->middlewareGroups[$name] = $middleware;
    }

    public function prependToGroup(string $group, string $middleware): void
    {
        if (!isset($this->middlewareGroups[$group])) {
            $this->middlewareGroups[$group] = [];
        }

        array_unshift($this->middlewareGroups[$group], $middleware);
    }

    public function pushToGroup(string $group, string $middleware): void
    {
        if (!isset($this->middlewareGroups[$group])) {
            $this->middlewareGroups[$group] = [];
        }

        $this->middlewareGroups[$group][] = $middleware;
    }

    public function handle(Request $request, Closure $destination, array $middleware = []): Response
    {
        $pipeline = $this->buildPipeline($middleware);

        return $this->sendThroughPipeline($request, $destination, $pipeline);
    }

    protected function buildPipeline(array $middleware): array
    {
        $pipeline = array_merge($this->globalMiddleware, $middleware);
        $pipeline = $this->expandMiddleware($pipeline);
        $pipeline = $this->sortMiddleware($pipeline);

        return array_reverse($pipeline);
    }

    protected function expandMiddleware(array $middleware): array
    {
        $expanded = [];

        foreach ($middleware as $name) {
            if (isset($this->middlewareAliases[$name])) {
                $expanded[] = $this->middlewareAliases[$name];
            } elseif (isset($this->middlewareGroups[$name])) {
                $expanded = array_merge($expanded, $this->expandMiddleware($this->middlewareGroups[$name]));
            } else {
                $expanded[] = $name;
            }
        }

        return $expanded;
    }

    protected function sortMiddleware(array $middleware): array
    {
        $priority = array_flip($this->middlewarePriority);

        usort($middleware, function ($a, $b) use ($priority) {
            $aPriority = $priority[$a] ?? 999;
            $bPriority = $priority[$b] ?? 999;

            return $aPriority <=> $bPriority;
        });

        return $middleware;
    }

    protected function sendThroughPipeline(Request $request, Closure $destination, array $pipeline): Response
    {
        return array_reduce($pipeline, function ($carry, $middleware) {
            return function ($request) use ($carry, $middleware) {
                return $this->executeMiddleware($middleware, $request, $carry);
            };
        }, $destination)($request);
    }

    protected function executeMiddleware(string $middleware, Request $request, Closure $next): Response
    {
        [$name, $parameters] = $this->parseMiddleware($middleware);

        $instance = $this->resolveMiddleware($name);

        if (method_exists($instance, 'handle')) {
            return $instance->handle($request, $next, ...$parameters);
        }

        throw new \InvalidArgumentException("Middleware {$name} does not have a handle method");
    }

    protected function parseMiddleware(string $middleware): array
    {
        [$name, $parameters] = array_pad(explode(':', $middleware, 2), 2, []);

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }

        return [$name, $parameters];
    }

    protected function resolveMiddleware(string $middleware)
    {
        if (class_exists($middleware)) {
            return $this->container->get($middleware);
        }

        if (isset($this->middlewareAliases[$middleware])) {
            return $this->container->get($this->middlewareAliases[$middleware]);
        }

        throw new \InvalidArgumentException("Middleware {$middleware} not found");
    }

    public function getGlobalMiddleware(): array
    {
        return $this->globalMiddleware;
    }

    public function getMiddlewareAliases(): array
    {
        return $this->middlewareAliases;
    }

    public function getMiddlewareGroups(): array
    {
        return $this->middlewareGroups;
    }

    public function hasMiddlewareGroup(string $name): bool
    {
        return isset($this->middlewareGroups[$name]);
    }

    public function getMiddlewareGroup(string $name): array
    {
        return $this->middlewareGroups[$name] ?? [];
    }

    public function removeMiddleware(string $middleware): void
    {
        $this->globalMiddleware = array_filter(
            $this->globalMiddleware,
            fn($m) => $m !== $middleware
        );
    }

    public function prependMiddleware(string $middleware): void
    {
        array_unshift($this->globalMiddleware, $middleware);
    }

    public function replaceMiddleware(string $search, string $replace): void
    {
        $this->globalMiddleware = array_map(
            fn($middleware) => $middleware === $search ? $replace : $middleware,
            $this->globalMiddleware
        );
    }

    public function skipMiddleware(array $middleware): SkippableMiddleware
    {
        return new SkippableMiddleware($this, $middleware);
    }

    public function onlyMiddleware(array $middleware): SkippableMiddleware
    {
        $skip = array_diff($this->globalMiddleware, $middleware);
        return new SkippableMiddleware($this, $skip);
    }
}
