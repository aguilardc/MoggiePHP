<?php
declare(strict_types=1);

namespace Moggie\Config;

use ArrayAccess;
use Countable;

/**
 * Class Repository
 *
 * @package \Moggie\Config
 */
class Repository implements ArrayAccess, Countable
{
    protected array $items = [];

    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function get(string $key, $default = null)
    {
        if ($this->hasDirectKey($key)) {
            return $this->items[$key];
        }

        return $this->getNestedValue($key, $default);
    }

    protected function hasDirectKey(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    protected function getNestedValue(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->items;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public function set(string $key, $value): void
    {
        if (!str_contains($key, '.')) {
            $this->items[$key] = $value;
            return;
        }

        $keys = explode('.', $key);
        $array = &$this->items;

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;
    }

    public function push(string $key, $value): void
    {
        $array = $this->get($key, []);

        if (!is_array($array)) {
            $array = [$array];
        }

        $array[] = $value;

        $this->set($key, $array);
    }

    public function prepend(string $key, $value): void
    {
        $array = $this->get($key, []);

        if (!is_array($array)) {
            $array = [$array];
        }

        array_unshift($array, $value);

        $this->set($key, $array);
    }

    public function forget(string $key): void
    {
        if ($this->hasDirectKey($key)) {
            unset($this->items[$key]);
            return;
        }

        $keys = explode('.', $key);
        $array = &$this->items;

        while (count($keys) > 1) {
            $segment = array_shift($keys);

            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return;
            }

            $array = &$array[$segment];
        }

        unset($array[array_shift($keys)]);
    }

    public function all(): array
    {
        return $this->items;
    }

    public function merge(array $items): void
    {
        $this->items = array_merge($this->items, $items);
    }

    public function replace(array $items): void
    {
        $this->items = $items;
    }

    public function only(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }

    public function except(array $keys): array
    {
        $result = $this->all();

        foreach ($keys as $key) {
            if (str_contains($key, '.')) {
                $this->forgetFromArray($result, $key);
            } else {
                unset($result[$key]);
            }
        }

        return $result;
    }

    protected function forgetFromArray(array &$array, string $key): void
    {
        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $segment = array_shift($keys);

            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return;
            }

            $array = &$array[$segment];
        }

        unset($array[array_shift($keys)]);
    }

    public function macro(string $name, callable $callback): void
    {
        static::$macros[$name] = $callback;
    }

    public function hasMacro(string $name): bool
    {
        return isset(static::$macros[$name]);
    }

    public function __call(string $method, array $parameters)
    {
        if (static::hasMacro($method)) {
            $macro = static::$macros[$method];

            if ($macro instanceof \Closure) {
                $macro = $macro->bindTo($this, static::class);
            }

            return $macro(...$parameters);
        }

        throw new \BadMethodCallException("Method {$method} does not exist.");
    }

    // ArrayAccess implementation
    public function offsetExists($key): bool
    {
        return $this->has($key);
    }

    public function offsetGet($key)
    {
        return $this->get($key);
    }

    public function offsetSet($key, $value): void
    {
        $this->set($key, $value);
    }

    public function offsetUnset($key): void
    {
        $this->forget($key);
    }

    // Countable implementation
    public function count(): int
    {
        return count($this->items);
    }

    public function toArray(): array
    {
        return $this->all();
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->items, $options);
    }

    public function __toString(): string
    {
        return $this->toJson();
    }

    // Static macros storage
    protected static array $macros = [];
}
