<?php

# ============================================================================ #

/**
 *  M O G G I E: a PHP micro-framework.
 *
 * @copyright   Copyright (c) 2023, Nevison Aguilar <aguilardc1105@gmail.com>
 * @license     http://opensource.org/licenses/mit-license.php The MIT License
 *
 */

namespace Core;


use http\Exception\InvalidArgumentException;
use RuntimeException;

class Env
{
    /**
     * The directory where the .env file can be located.
     *
     * @var string
     */
    protected static string $path;

    /** @var string */
    protected static string $name = '/.env';

    /**
     * @param string $path
     * @param string|null $name
     * @return void
     */
    public static function create(string $path, string $name = null): void
    {
        if (!file_exists($path)) {
            throw new InvalidArgumentException("{$path} does not exist");
        }
        self::$path = $path;
        if (!is_null($name)) {
            self::$name = $name;
        }

        (new self())->load();
    }

    /**
     * @return void
     */
    public function load(): void
    {
        if (!is_readable(self::$path . self::$name)) {
            throw new RuntimeException(self::$path . self::$name . ' file is not readable');
        }

        $lines = file(self::$path . self::$name, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (str_starts_with(trim($line), '#')) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $this->set($name, $value);
        }
    }

    /**
     * @param string $key
     * @param string $default
     * @return void
     */
    protected function set(string $key, string $default): void
    {
        $name = trim($key);
        $value = trim($default);

        if (!array_key_exists($name, $_SERVER) &&
            !array_key_exists($name, $_ENV)) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }

    /**
     * @param $key
     * @param string $default
     * @return string
     */
    public static function get($key, string $default = ''): string
    {
        $env = (new self());
        $value = $env->getValueByKey($key, $default);
        if (is_null($value)) {
            $env->set($key, $default);
            return $env::get($key, $default);
        }
        return $value;
    }

    /**
     * @param $key
     * @param $default
     * @return mixed
     */
    protected function getValueByKey($key, $default): mixed
    {

        $value = null;
        if (getenv($key) !== false) {
            $value = getenv($key);
        }

        if (in_array($key, $_ENV)) {
            $value = $_ENV[$key];
        }

        if (in_array($key, $_SERVER)) {
            $value = $_SERVER[$key];
        }

        return $value;
    }
}
