<?php

namespace Moggie\Middleware;

class PhpSession
{
    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public function put(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
    }
}
