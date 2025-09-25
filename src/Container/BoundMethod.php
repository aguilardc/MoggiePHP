<?php

namespace Moggie\Container;

/**
 * Class BoundMethod
 *
 * @package \Moggie\Container
 */
class BoundMethod
{
    public static function call(Container $container, $callback, array $parameters = [], ?string $defaultMethod = null)
    {
        if (static::isCallableWithAtSign($callback) || $defaultMethod) {
            return static::callClass($container, $callback, $parameters, $defaultMethod);
        }

        return static::callBoundMethod($container, $callback, function () use ($container, $callback, $parameters) {
            return $callback(...array_values(static::getMethodDependencies($container, $callback, $parameters)));
        });
    }

    protected static function callClass(Container $container, string $target, array $parameters = [], ?string $defaultMethod = null)
    {
        $segments = explode('@', $target);

        $method = count($segments) === 2
            ? $segments[1] : $defaultMethod;

        if (is_null($method)) {
            throw new InvalidArgumentException('Method not provided.');
        }

        return static::call(
            $container, [$container->make($segments[0]), $method], $parameters
        );
    }

    protected static function callBoundMethod(Container $container, $callback, $default)
    {
        if (!is_array($callback)) {
            return $default instanceof Closure ? $default() : $default;
        }

        $method = static::normalizeMethod($callback);

        if ($container->hasMethodBinding($method)) {
            return $container->callMethodBinding($method, $callback[0]);
        }

        return $default instanceof Closure ? $default() : $default;
    }

    protected static function normalizeMethod($callback): string
    {
        $class = is_string($callback[0]) ? $callback[0] : get_class($callback[0]);

        return "{$class}@{$callback[1]}";
    }

    protected static function getMethodDependencies(Container $container, $callback, array $parameters = []): array
    {
        $dependencies = [];

        foreach (static::getCallReflector($callback)->getParameters() as $parameter) {
            static::addDependencyForCallParameter($container, $parameter, $parameters, $dependencies);
        }

        return array_merge($dependencies, array_values($parameters));
    }

    protected static function getCallReflector($callback): \ReflectionFunctionAbstract
    {
        if (is_string($callback) && str_contains($callback, '::')) {
            $callback = explode('::', $callback);
        } elseif (is_object($callback) && !$callback instanceof Closure) {
            $callback = [$callback, '__invoke'];
        }

        return is_array($callback)
            ? new ReflectionMethod($callback[0], $callback[1])
            : new ReflectionFunction($callback);
    }

    protected static function addDependencyForCallParameter(Container $container, ReflectionParameter $parameter, array &$parameters, array &$dependencies): void
    {
        if (array_key_exists($parameter->name, $parameters)) {
            $dependencies[] = $parameters[$parameter->name];

            unset($parameters[$parameter->name]);
        } elseif ($parameter->getClass() && !static::alreadyInParameters($parameter->getClass()->name, $dependencies)) {
            $dependencies[] = $container->make($parameter->getClass()->name);
        } elseif ($parameter->isDefaultValueAvailable()) {
            $dependencies[] = $parameter->getDefaultValue();
        } elseif (!$parameter->isOptional() && !array_key_exists($parameter->name, $parameters)) {
            $message = "Unable to resolve dependency [{$parameter}] in class {$parameter->getDeclaringClass()->getName()}";

            throw new BindingResolutionException($message);
        }
    }

    protected static function alreadyInParameters(string $class, array $parameters): bool
    {
        foreach ($parameters as $parameter) {
            if ($parameter instanceof $class) {
                return true;
            }
        }

        return false;
    }

    protected static function isCallableWithAtSign($callback): bool
    {
        return is_string($callback) && str_contains($callback, '@');
    }
}
