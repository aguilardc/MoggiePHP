<?php

namespace Moggie\Container;

use Closure;
use Exception;
use InvalidArgumentException;
use LogicException;
use Moggie\Container\Exceptions\BindingResolutionException;
use Moggie\Container\Exceptions\EntryNotFoundException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

class Container implements ContainerInterface
{
    protected array $bindings = [];
    protected array $instances = [];
    protected array $aliases = [];
    protected array $abstractAliases = [];
    protected array $tags = [];
    protected array $buildStack = [];
    protected array $with = [];
    private array $resolved = [];

    /**
     * @throws ReflectionException
     * @throws EntryNotFoundException
     * @throws BindingResolutionException
     */
    public function get(string $id)
    {
        try {
            return $this->resolve($id);
        } catch (Exception $e) {
            if ($this->has($id) || $this->isAlias($id)) {
                throw $e;
            }
            throw new EntryNotFoundException($id, $e->getCode(), $e);
        }
    }

    public function has(string $id): bool
    {
        return $this->bound($id);
    }

    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) ||
            isset($this->instances[$abstract]) ||
            $this->isAlias($abstract);
    }

    public function resolved(string $abstract): bool
    {
        if ($this->isAlias($abstract)) {
            $abstract = $this->getAlias($abstract);
        }

        return isset($this->instances[$abstract]);
    }

    public function isAlias(string $name): bool
    {
        return isset($this->aliases[$name]);
    }

    public function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        $this->dropStaleInstances($abstract);

        if ($concrete === null) {
            $concrete = $abstract;
        }

        if (!$concrete instanceof Closure) {
            if (!is_string($concrete)) {
                throw new InvalidArgumentException(self::class . "::bind(): Argument #2 ($concrete) must be of type Closure|string|null");
            }

            $concrete = $this->getClosure($abstract, $concrete);
        }
        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    protected function getClosure(string $abstract, string $concrete): Closure
    {
        return function (Container $container, array $parameters = []) use ($abstract, $concrete) {
            if ($abstract === $concrete) {
                return $container->build($concrete);
            }

            return $container->resolve($concrete, $parameters, $raiseEvents = false);
        };
    }

    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * @throws ReflectionException
     * @throws BindingResolutionException
     */
    public function instance(string $abstract, $instance): void
    {
        $this->removeAbstractAlias($abstract);

        $isBound = $this->bound($abstract);

        unset($this->aliases[$abstract]);

        $this->instances[$abstract] = $instance;

        if ($isBound) {
            $this->rebound($abstract);
        }
    }

    public function when(string|array $concrete): ContextualBindingBuilder
    {
        $aliases = [];

        if (is_array($concrete)) {
            $aliases = $concrete;
            $concrete = $aliases[0];
        }

        return new ContextualBindingBuilder($this, $this->getAlias($concrete), $aliases);
    }

    public function factory(string $abstract): Closure
    {
        return function () use ($abstract) {
            return $this->make($abstract);
        };
    }

    public function flush(): void
    {
        $this->aliases = [];
        $this->resolved = [];
        $this->bindings = [];
        $this->instances = [];
        $this->abstractAliases = [];
    }

    /**
     * @throws ReflectionException
     * @throws BindingResolutionException
     */
    public function make(string $abstract, array $parameters = [])
    {
        return $this->resolve($abstract, $parameters);
    }

    /**
     * @throws ReflectionException
     * @throws BindingResolutionException
     */
    protected function resolve(string $abstract, array $parameters = [], bool $raiseEvents = true)
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->instances[$abstract]) && empty($parameters)) {
            return $this->instances[$abstract];
        }

        $this->with[] = $parameters;

        $concrete = $this->getConcrete($abstract);

        $object = $this->isBuildable($concrete, $abstract)
            ? $this->build($concrete)
            : $this->make($concrete);

        if ($this->isShared($abstract) && empty($parameters)) {
            $this->instances[$abstract] = $object;
        }

        array_pop($this->with);

        return $object;
    }

    protected function getConcrete(string $abstract)
    {
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    protected function getAlias(string $abstract): string
    {
        return isset($this->aliases[$abstract])
            ? $this->getAlias($this->aliases[$abstract])
            : $abstract;
    }

    /**
     * @throws BindingResolutionException
     * @throws ReflectionException
     */
    public function build($concrete)
    {
        if ($concrete instanceof Closure) {
            return $concrete($this, $this->getLastParameterOverride());
        }

        $reflector = new ReflectionClass($concrete);

        if (!$reflector->isInstantiable()) {
            return $this->notInstantiable($concrete);
        }

        $this->buildStack[] = $concrete;

        $constructor = $reflector->getConstructor();
        if (is_null($constructor)) {
            array_pop($this->buildStack);
            return new $concrete();
        }

        $dependencies = $constructor->getParameters();

        try {
            $instances = $this->resolveDependencies($dependencies);
        } catch (BindingResolutionException $e) {
            array_pop($this->buildStack);
            throw $e;
        }

        array_pop($this->buildStack);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * @throws ReflectionException
     * @throws BindingResolutionException
     */
    protected function resolveDependencies(array $dependencies): array
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            if ($this->hasParameterOverride($dependency)) {
                $results[] = $this->getParameterOverride($dependency);
                continue;
            }

            $result = is_null($dependency->getClass())
                ? $this->resolvePrimitive($dependency)
                : $this->resolveClass($dependency);

            if ($dependency->isVariadic()) {
                $results = array_merge($results, $result);
            } else {
                $results[] = $result;
            }
        }
        return $results;
    }

    protected function hasParameterOverride(ReflectionParameter $dependency): bool
    {
        return array_key_exists($dependency->name, $this->getLastParameterOverride());
    }

    protected function getParameterOverride(ReflectionParameter $dependency)
    {
        return $this->getLastParameterOverride()[$dependency->name];
    }

    protected function getLastParameterOverride(): array
    {
        return count($this->with) ? end($this->with) : [];
    }

    /**
     * @throws BindingResolutionException
     */
    protected function resolvePrimitive(ReflectionParameter $parameter)
    {
        if (($context = $this->getContextualConcrete('$' . $parameter->getName())) !== null) {
            return $context instanceof Closure ? $context($this) : $context;
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->isVariadic()) {
            return [];
        }

        $this->unresolvablePrimitive($parameter);
    }

    /**
     * @throws ReflectionException
     * @throws BindingResolutionException
     */
    protected function resolveClass(ReflectionParameter $parameter): ?array
    {
        try {
            return $parameter->isVariadic()
                ? $this->resolveVariadicClass($parameter)
                : $this->make($parameter->getClass()->name);
        } catch (BindingResolutionException $e) {
            if ($parameter->isOptional()) {
                return $parameter->getDefaultValue();
            }

            if ($parameter->isVariadic()) {
                return [];
            }

            throw $e;
        }
    }

    /**
     * @throws ReflectionException
     * @throws BindingResolutionException
     */
    protected function resolveVariadicClass(ReflectionParameter $parameter): array
    {
        $className = $parameter->getClass()->name;

        $abstract = $this->getAlias($className);

        if (!is_array($concrete = $this->getContextualConcrete($abstract))) {
            return $this->make($abstract);
        }

        return array_map(function ($abstract) {
            return $this->resolve($abstract);
        }, $concrete);
    }

    protected function getContextualConcrete(string $abstract)
    {
        if (isset($this->contextual[end($this->buildStack)][$abstract])) {
            return $this->contextual[end($this->buildStack)][$abstract];
        }

        if (empty($this->abstractAliases[$abstract])) {
            return null;
        }

        foreach ($this->abstractAliases[$abstract] as $alias) {
            if (isset($this->contextual[end($this->buildStack)][$alias])) {
                return $this->contextual[end($this->buildStack)][$alias];
            }
        }

        return null;
    }

    /**
     * @throws BindingResolutionException
     */
    protected function notInstantiable(string $concrete)
    {
        if (!empty($this->buildStack)) {
            $previous = implode(', ', $this->buildStack);

            $message = "Target [$concrete] is not instantiable while building [$previous].";
        } else {
            $message = "Target [$concrete] is not instantiable.";
        }

        throw new BindingResolutionException($message);
    }

    /**
     * @throws BindingResolutionException
     */
    protected function unresolvablePrimitive(ReflectionParameter $parameter): void
    {
        $message = "Unresolvable dependency resolving [$parameter] in class {$parameter->getDeclaringClass()->getName()}";

        throw new BindingResolutionException($message);
    }

    protected function isBuildable($concrete, string $abstract): bool
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    protected function isShared(string $abstract): bool
    {
        return isset($this->instances[$abstract]) ||
            (isset($this->bindings[$abstract]['shared']) &&
                $this->bindings[$abstract]['shared'] === true);
    }

    protected function dropStaleInstances(string $abstract): void
    {
        unset($this->instances[$abstract], $this->aliases[$abstract]);
    }

    protected function removeAbstractAlias(string $searched): void
    {
        if (!isset($this->aliases[$searched])) {
            return;
        }

        foreach ($this->abstractAliases as $abstract => $aliases) {
            foreach ($aliases as $index => $alias) {
                if ($alias === $searched) {
                    unset($this->abstractAliases[$abstract][$index]);
                }
            }
        }
    }

    public function alias(string $abstract, string $alias): void
    {
        if ($alias === $abstract) {
            throw new LogicException("[{$abstract}] is aliased to itself.");
        }

        $this->aliases[$alias] = $abstract;

        $this->abstractAliases[$abstract][] = $alias;
    }

    public function tag(array|string $abstracts, array|string $tags): void
    {
        $tags = is_array($tags) ? $tags : [$tags];

        foreach ($tags as $tag) {
            if (!isset($this->tags[$tag])) {
                $this->tags[$tag] = [];
            }

            foreach ((array)$abstracts as $abstract) {
                $this->tags[$tag][] = $abstract;
            }
        }
    }

    public function tagged(string $tag): array|RewindableGenerator
    {
        if (!isset($this->tags[$tag])) {
            return [];
        }

        return new RewindableGenerator(function () use ($tag) {
            foreach ($this->tags[$tag] as $abstract) {
                yield $this->make($abstract);
            }
        }, count($this->tags[$tag]));
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * @throws ReflectionException
     * @throws BindingResolutionException
     */
    protected function rebound(string $abstract): void
    {
        $instance = $this->make($abstract);

        foreach ($this->getReboundCallbacks($abstract) as $callback) {
            $callback($this, $instance);
        }
    }

    protected function getReboundCallbacks(string $abstract): array
    {
        return $this->reboundCallbacks[$abstract] ?? [];
    }

    public function wrap(Closure $callback, array $parameters = []): Closure
    {
        return function () use ($callback, $parameters) {
            return $this->call($callback, $parameters);
        };
    }

    public function call($callback, array $parameters = [], ?string $defaultMethod = null)
    {
        return BoundMethod::call($this, $callback, $parameters, $defaultMethod);
    }

    /**
     * @throws ReflectionException
     */
    public function methodParameterTypes(callable $callback): array
    {
        $dependencies = [];

        foreach ($this->getCallReflector($callback)->getParameters() as $parameter) {
            $this->addDependencyForCallParameter($parameter, $dependencies);
        }

        return $dependencies;
    }

    /**
     * @throws ReflectionException
     */
    protected function getCallReflector($callback): \ReflectionFunctionAbstract
    {
        if (is_string($callback) && str_contains($callback, '::')) {
            $callback = explode('::', $callback);
        } elseif (is_object($callback) && !$callback instanceof Closure) {
            $callback = [$callback, '__invoke'];
        }

        return is_array($callback)
            ? new \ReflectionMethod($callback[0], $callback[1])
            : new \ReflectionFunction($callback);
    }

    protected function addDependencyForCallParameter(ReflectionParameter $parameter, array &$dependencies): void
    {
        if ($parameter->getClass() && !$this->alreadyInParameters($parameter->getClass()->name, $dependencies)) {
            $dependencies[] = $parameter->getClass()->name;
        }
    }

    protected function alreadyInParameters(string $class, array $parameters): bool
    {
        return !is_null(Arr::first($parameters, function ($value) use ($class) {
            return $value instanceof $class;
        }));
    }

    public function __get(string $key)
    {
        return $this[$key];
    }

    public function __set(string $key, $value): void
    {
        $this[$key] = $value;
    }

    public function offsetExists($key): bool
    {
        return $this->bound($key);
    }

    /**
     * @throws ReflectionException
     * @throws BindingResolutionException
     */
    public function offsetGet($key): null
    {
        return $this->make($key);
    }

    public function offsetSet($key, $value): void
    {
        $this->bind($key, $value instanceof Closure ? $value : fn() => $value);
    }

    public function offsetUnset($key): void
    {
        unset($this->bindings[$key], $this->instances[$key], $this->resolved[$key]);
    }
}





