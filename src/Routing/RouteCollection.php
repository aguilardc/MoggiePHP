<?php

declare(strict_types=1);

namespace Moggie\Routing;

use Countable;
use IteratorAggregate;
use ArrayIterator;

class RouteCollection implements Countable, IteratorAggregate
{
    protected array $routes = [];
    protected array $allRoutes = [];
    protected array $nameList = [];
    protected array $actionList = [];

    public function add(Route $route): void
    {
        $this->addToCollections($route);
        $this->addLookups($route);
    }

    protected function addToCollections(Route $route): void
    {
        $domainAndUri = $route->getDomain() . $route->getUri();

        foreach ($route->getMethods() as $method) {
            $this->routes[$method][$domainAndUri] = $route;
        }

        $this->allRoutes[$method . $domainAndUri] = $route;
    }

    protected function addLookups(Route $route): void
    {
        if ($name = $route->getName()) {
            $this->nameList[$name] = $route;
        }

        $action = $route->getActionName();
        if (!isset($this->actionList[$action])) {
            $this->actionList[$action] = [];
        }

        $this->actionList[$action][] = $route;
    }

    public function getRoutes(?string $method = null): array
    {
        if ($method === null) {
            return array_values($this->allRoutes);
        }

        return array_values($this->routes[$method] ?? []);
    }

    public function getRoutesByMethod(): array
    {
        return $this->routes;
    }

    public function getRoutesByName(): array
    {
        return $this->nameList;
    }

    public function getByName(string $name): ?Route
    {
        return $this->nameList[$name] ?? null;
    }

    public function getByAction(string $action): array
    {
        return $this->actionList[$action] ?? [];
    }

    public function match(string $method, string $uri, ?string $domain = null): ?Route
    {
        $domainAndUri = ($domain ?? '') . $uri;

        return $this->routes[$method][$domainAndUri] ?? null;
    }

    public function count(): int
    {
        return count($this->allRoutes);
    }

    public function isEmpty(): bool
    {
        return empty($this->allRoutes);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->getRoutes());
    }

    public function toArray(): array
    {
        $routes = [];

        foreach ($this->getRoutes() as $route) {
            $routes[] = [
                'methods' => $route->getMethods(),
                'uri' => $route->getUri(),
                'name' => $route->getName(),
                'action' => $route->getActionName(),
                'domain' => $route->getDomain(),
                'middleware' => $route->getMiddleware(),
                'wheres' => $route->getWheres(),
            ];
        }

        return $routes;
    }

    public function refresh(): void
    {
        $this->routes = [];
        $this->allRoutes = [];
        $this->nameList = [];
        $this->actionList = [];

        foreach ($this->getRoutes() as $route) {
            $this->addToCollections($route);
            $this->addLookups($route);
        }
    }
}
