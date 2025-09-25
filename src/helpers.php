<?php

use Moggie\Core\Application;
use Moggie\Http\Response;
use Moggie\Http\JsonResponse;
use Moggie\Http\Request;
use Moggie\Validation\Validator;
use Moggie\Container\Container;

// =============================================================================
// APPLICATION HELPERS
// =============================================================================

if (!function_exists('app')) {
    /**
     * Get the application instance or resolve a binding
     */
    function app(?string $abstract = null, array $parameters = [])
    {
        if ($abstract === null) {
            return Application::getInstance();
        }

        return Application::getInstance()->make($abstract, $parameters);
    }
}

if (!function_exists('resolve')) {
    /**
     * Resolve a service from the container
     */
    function resolve(string $name, array $parameters = [])
    {
        return app($name, $parameters);
    }
}

if (!function_exists('container')) {
    /**
     * Get the container instance
     */
    function container(): Container
    {
        return app();
    }
}

/ =============================================================================
// CONFIGURATION HELPERS
// =============================================================================

if (!function_exists('config')) {
    /**
     * Get / set configuration value
     */
    function config(?string $key = null, $default = null)
    {
        if ($key === null) {
            return app('config');
        }

        if (is_array($key)) {
            foreach ($key as $configKey => $configValue) {
                app('config')->set($configKey, $configValue);
            }
            return null;
        }

        return app('config')->get($key, $default);
    }
}

if (!function_exists('env')) {
    /**
     * Get environment variable with type casting
     */
    function env(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false) {
            return value($default);
        }

        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => preg_match('/\A([\'"])(.*)\1\z/', $value, $matches) ? $matches[2] : $value
        };
    }
}

// =============================================================================
// HTTP HELPERS
// =============================================================================

if (!function_exists('request')) {
    /**
     * Get the current request instance
     */
    function request(?string $key = null, $default = null)
    {
        $request = app('request');

        if ($key === null) {
            return $request;
        }

        return $request->input($key, $default);
    }
}

if (!function_exists('response')) {
    /**
     * Create a response instance
     */
    function response($content = '', int $status = 200, array $headers = []): Response
    {
        if (is_array($content)) {
            return new JsonResponse($content, $status, $headers);
        }

        return Response::make($content, $status, $headers);
    }
}

if (!function_exists('json')) {
    /**
     * Create a JSON response
     */
    function json(array $data = [], int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, $headers);
    }
}

if (!function_exists('redirect')) {
    /**
     * Create a redirect response
     */
    function redirect(string $to, int $status = 302, array $headers = []): Response
    {
        return Response::redirect($to, $status, $headers);
    }
}

// =============================================================================
// ROUTING HELPERS
// =============================================================================

if (!function_exists('route')) {
    /**
     * Generate a URL for a named route
     */
    function route(string $name, array $parameters = [], bool $absolute = true): string
    {
        return app('router')->url($name, $parameters, $absolute);
    }
}

if (!function_exists('url')) {
    /**
     * Generate a URL for a path
     */
    function url(string $path = '', array $parameters = [], ?bool $secure = null): string
    {
        $request = app('request');
        $scheme = $secure ?? $request->isSecure() ? 'https' : 'http';
        $host = $request->getHost();
        $port = $request->getPort();

        $url = $scheme . '://' . $host;

        if (($scheme === 'http' && $port !== 80) || ($scheme === 'https' && $port !== 443)) {
            $url .= ':' . $port;
        }

        $url .= '/' . ltrim($path, '/');

        if (!empty($parameters)) {
            $url .= '?' . http_build_query($parameters);
        }

        return $url;
    }
}

if (!function_exists('secure_url')) {
    /**
     * Generate a secure URL for a path
     */
    function secure_url(string $path, array $parameters = []): string
    {
        return url($path, $parameters, true);
    }
}

if (!function_exists('asset')) {
    /**
     * Generate an asset URL
     */
    function asset(string $path, ?bool $secure = null): string
    {
        return url($path, [], $secure);
    }
}

if (!function_exists('secure_asset')) {
    /**
     * Generate a secure asset URL
     */
    function secure_asset(string $path): string
    {
        return asset($path, true);
    }
}

// =============================================================================
// ERROR HANDLING HELPERS
// =============================================================================

if (!function_exists('abort')) {
    /**
     * Throw an HTTP exception
     */
    function abort(int $code, string $message = '', array $headers = []): never
    {
        $exceptionClass = match ($code) {
            400 => \Moggie\Http\Exceptions\BadRequestHttpException::class,
            401 => \Moggie\Http\Exceptions\UnauthorizedHttpException::class,
            403 => \Moggie\Http\Exceptions\ForbiddenHttpException::class,
            404 => \Moggie\Http\Exceptions\NotFoundHttpException::class,
            422 => \Moggie\Http\Exceptions\UnprocessableEntityHttpException::class,
            429 => \Moggie\Http\Exceptions\TooManyRequestsHttpException::class,
            500 => \Moggie\Http\Exceptions\InternalServerErrorHttpException::class,
            503 => \Moggie\Http\Exceptions\ServiceUnavailableHttpException::class,
            default => \Moggie\Http\Exceptions\HttpException::class,
        };

        if ($exceptionClass === \Moggie\Http\Exceptions\HttpException::class) {
            throw new $exceptionClass($code, $message, $headers);
        }

        throw new $exceptionClass($message, $headers);
    }
}

if (!function_exists('abort_if')) {
    /**
     * Throw an HTTP exception if condition is true
     */
    function abort_if(bool $condition, int $code, string $message = '', array $headers = []): void
    {
        if ($condition) {
            abort($code, $message, $headers);
        }
    }
}

if (!function_exists('abort_unless')) {
    /**
     * Throw an HTTP exception unless condition is true
     */
    function abort_unless(bool $condition, int $code, string $message = '', array $headers = []): void
    {
        if (!$condition) {
            abort($code, $message, $headers);
        }
    }
}

if (!function_exists('throw_if')) {
    /**
     * Throw exception if condition is true
     */
    function throw_if(bool $condition, string|\Throwable $exception, string $message = ''): void
    {
        if (!$condition) {
            return;
        }

        if (is_string($exception)) {
            throw new $exception($message);
        }

        throw $exception;
    }
}

if (!function_exists('throw_unless')) {
    /**
     * Throw exception unless condition is true
     */
    function throw_unless(bool $condition, string|\Throwable $exception, string $message = ''): void
    {
        throw_if(!$condition, $exception, $message);
    }
}

/ =============================================================================
// VALIDATION HELPERS
// =============================================================================

if (!function_exists('validator')) {
    /**
     * Create a validator instance
     */
    function validator(array $data, array $rules, array $messages = []): Validator
    {
        return Validator::make($data, $rules, $messages);
    }
}

if (!function_exists('validate')) {
    /**
     * Validate data and return validated data
     */
    function validate(array $data, array $rules, array $messages = []): array
    {
        return validator($data, $rules, $messages)->validate();
    }
}

// =============================================================================
// CACHE HELPERS
// =============================================================================

if (!function_exists('cache')) {
    /**
     * Get cache manager or retrieve/store cached value
     */
    function cache(?string $key = null, $value = null, ?int $ttl = null)
    {
        $cache = app('cache');

        if ($key === null) {
            return $cache;
        }

        if (func_num_args() === 1) {
            return $cache->get($key);
        }

        if ($value instanceof \Closure) {
            return $cache->remember($key, $ttl ?? 3600, $value);
        }

        return $cache->put($key, $value, $ttl ?? 3600);
    }
}
if (!function_exists('cache_remember')) {
    /**
     * Remember a value in cache
     */
    function cache_remember(string $key, int $ttl, \Closure $callback)
    {
        return cache()->remember($key, $ttl, $callback);
    }
}

if (!function_exists('cache_forget')) {
    /**
     * Remove an item from cache
     */
    function cache_forget(string $key): bool
    {
        return cache()->forget($key);
    }
}

if (!function_exists('cache_flush')) {
    /**
     * Clear all cache
     */
    function cache_flush(): bool
    {
        return cache()->flush();
    }
}

// =============================================================================
// LOGGING HELPERS
// =============================================================================

if (!function_exists('logger')) {
    /**
     * Get logger instance or log a message
     */
    function logger(?string $message = null, array $context = [])
    {
        $logger = app('log');

        if ($message === null) {
            return $logger;
        }

        return $logger->info($message, $context);
    }
}

if (!function_exists('log_info')) {
    /**
     * Log an info message
     */
    function log_info(string $message, array $context = []): void
    {
        logger()->info($message, $context);
    }
}

if (!function_exists('log_error')) {
    /**
     * Log an error message
     */
    function log_error(string $message, array $context = []): void
    {
        logger()->error($message, $context);
    }
}

if (!function_exists('log_warning')) {
    /**
     * Log a warning message
     */
    function log_warning(string $message, array $context = []): void
    {
        logger()->warning($message, $context);
    }
}

if (!function_exists('log_debug')) {
    /**
     * Log a debug message
     */
    function log_debug(string $message, array $context = []): void
    {
        logger()->debug($message, $context);
    }
}

// =============================================================================
// ENCRYPTION HELPERS
// =============================================================================

if (!function_exists('encrypt')) {
    /**
     * Encrypt a value
     */
    function encrypt(string $value): string
    {
        return app('encrypter')->encrypt($value);
    }
}

if (!function_exists('decrypt')) {
    /**
     * Decrypt a value
     */
    function decrypt(string $encrypted): string
    {
        return app('encrypter')->decrypt($encrypted);
    }
}

if (!function_exists('bcrypt')) {
    /**
     * Hash a password using bcrypt
     */
    function bcrypt(string $password, array $options = []): string
    {
        $cost = $options['rounds'] ?? config('app.bcrypt_rounds', 10);
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);
    }
}

if (!function_exists('hash_check')) {
    /**
     * Check a password against a hash
     */
    function hash_check(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}

// =============================================================================
// DATE AND TIME HELPERS
// =============================================================================

if (!function_exists('now')) {
    /**
     * Get current datetime
     */
    function now(?string $timezone = null): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $timezone ? new DateTimeZone($timezone) : null);
    }
}

if (!function_exists('today')) {
    /**
     * Get today's date
     */
    function today(?string $timezone = null): DateTimeImmutable
    {
        return now($timezone)->setTime(0, 0, 0);
    }
}

if (!function_exists('carbon')) {
    /**
     * Create a Carbon instance (if Carbon is available)
     */
    function carbon($time = null, $timezone = null)
    {
        if (class_exists(\Carbon\Carbon::class)) {
            return new \Carbon\Carbon($time, $timezone);
        }

        return new DateTime($time ?? 'now', $timezone ? new DateTimeZone($timezone) : null);
    }
}

// =============================================================================
// COLLECTION HELPERS
// =============================================================================

if (!function_exists('collect')) {
    /**
     * Create a collection from array
     */
    function collect(array $items = []): \Moggie\Support\Collection
    {
        return new \Moggie\Support\Collection($items);
    }
}

// =============================================================================
// STRING HELPERS
// =============================================================================

if (!function_exists('str_slug')) {
    /**
     * Generate a URL friendly slug
     */
    function str_slug(string $title, string $separator = '-'): string
    {
        $slug = preg_replace('/[^A-Za-z0-9-]+/', $separator, $title);
        $slug = preg_replace('/[' . preg_quote($separator) . ']+/', $separator, $slug);
        return trim($slug, $separator);
    }
}

if (!function_exists('str_random')) {
    /**
     * Generate a random string
     */
    function str_random(int $length = 16): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $result;
    }
}

if (!function_exists('str_limit')) {
    /**
     * Limit the number of characters in a string
     */
    function str_limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strwidth($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_strimwidth($value, 0, $limit, '', 'UTF-8')) . $end;
    }
}

if (!function_exists('str_contains')) {
    /**
     * Check if string contains substring
     */
    function str_contains(string $haystack, string $needle): bool
    {
        return strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('str_starts_with')) {
    /**
     * Check if string starts with substring
     */
    function str_starts_with(string $haystack, string $needle): bool
    {
        return strpos($haystack, $needle) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    /**
     * Check if string ends with substring
     */
    function str_ends_with(string $haystack, string $needle): bool
    {
        return substr($haystack, -strlen($needle)) === $needle;
    }
}

if (!function_exists('str_camel')) {
    /**
     * Convert string to camelCase
     */
    function str_camel(string $value): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value))));
    }
}

if (!function_exists('str_snake')) {
    /**
     * Convert string to snake_case
     */
    function str_snake(string $value, string $delimiter = '_'): string
    {
        if (!ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));
            $value = strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value));
        }

        return $value;
    }
}

if (!function_exists('str_studly')) {
    /**
     * Convert string to StudlyCase
     */
    function str_studly(string $value): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $value)));
    }
}

if (!function_exists('str_kebab')) {
    /**
     * Convert string to kebab-case
     */
    function str_kebab(string $value): string
    {
        return str_snake($value, '-');
    }
}

// =============================================================================
// ARRAY HELPERS
// =============================================================================

if (!function_exists('array_get')) {
    /**
     * Get an item from an array using dot notation
     */
    function array_get(array $array, string $key, $default = null)
    {
        if (isset($array[$key])) {
            return $array[$key];
        }

        if (strpos($key, '.') === false) {
            return $default;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return value($default);
            }
            $array = $array[$segment];
        }

        return $array;
    }
}

if (!function_exists('array_set')) {
    /**
     * Set an array item using dot notation
     */
    function array_set(array &$array, string $key, $value): void
    {
        if (strpos($key, '.') === false) {
            $array[$key] = $value;
            return;
        }

        $keys = explode('.', $key);
        $current = &$array;

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($current[$key]) || !is_array($current[$key])) {
                $current[$key] = [];
            }

            $current = &$current[$key];
        }

        $current[array_shift($keys)] = $value;
    }
}

if (!function_exists('array_forget')) {
    /**
     * Remove an item from array using dot notation
     */
    function array_forget(array &$array, string $key): void
    {
        if (strpos($key, '.') === false) {
            unset($array[$key]);
            return;
        }

        $keys = explode('.', $key);
        $current = &$array;

        while (count($keys) > 1) {
            $segment = array_shift($keys);

            if (!isset($current[$segment]) || !is_array($current[$segment])) {
                return;
            }

            $current = &$current[$segment];
        }

        unset($current[array_shift($keys)]);
    }
}

if (!function_exists('array_has')) {
    /**
     * Check if array has key using dot notation
     */
    function array_has(array $array, string $key): bool
    {
        return array_get($array, $key) !== null;
    }
}

if (!function_exists('array_only')) {
    /**
     * Get only specified keys from array
     */
    function array_only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }
}

if (!function_exists('array_except')) {
    /**
     * Get array except specified keys
     */
    function array_except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }
}

if (!function_exists('array_flatten')) {
    /**
     * Flatten a multi-dimensional array
     */
    function array_flatten(array $array): array
    {
        $result = [];

        array_walk_recursive($array, function ($value) use (&$result) {
            $result[] = $value;
        });

        return $result;
    }
}

if (!function_exists('array_wrap')) {
    /**
     * Wrap value in array if not already an array
     */
    function array_wrap($value): array
    {
        if (is_null($value)) {
            return [];
        }

        return is_array($value) ? $value : [$value];
    }
}

// =============================================================================
// UTILITY HELPERS
// =============================================================================

if (!function_exists('value')) {
    /**
     * Return the default value of the given value
     */
    function value($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}

if (!function_exists('with')) {
    /**
     * Return the given value
     */
    function with($value, ?callable $callback = null)
    {
        return $callback === null ? $value : $callback($value);
    }
}

if (!function_exists('tap')) {
    /**
     * Call the given callback with the given value then return the value
     */
    function tap($value, ?callable $callback = null)
    {
        if ($callback === null) {
            return new \Moggie\Support\HigherOrderTapProxy($value);
        }

        $callback($value);
        return $value;
    }
}

if (!function_exists('optional')) {
    /**
     * Provide access to optional objects
     */
    function optional($value = null, ?callable $callback = null)
    {
        if ($callback === null) {
            return new \Moggie\Support\Optional($value);
        }

        if ($value !== null) {
            return $callback($value);
        }

        return null;
    }
}

if (!function_exists('rescue')) {
    /**
     * Catch exceptions and return default value
     */
    function rescue(callable $callback, $rescue = null, bool $report = true)
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            if ($report && function_exists('report')) {
                report($e);
            }

            return value($rescue, $e);
        }
    }
}

if (!function_exists('retry')) {
    /**
     * Retry a callback a given number of times
     */
    function retry(int $times, callable $callback, int $sleepMilliseconds = 0, ?callable $when = null)
    {
        $attempts = 0;

        beginning:
        $attempts++;
        $times--;

        try {
            return $callback($attempts);
        } catch (Exception $e) {
            if ($times < 1 || ($when && !$when($e))) {
                throw $e;
            }

            if ($sleepMilliseconds > 0) {
                usleep($sleepMilliseconds * 1000);
            }

            goto beginning;
        }
    }
}

// =============================================================================
// DEBUGGING HELPERS
// =============================================================================

if (!function_exists('dd')) {
    /**
     * Dump variables and die
     */
    function dd(...$vars): never
    {
        foreach ($vars as $var) {
            var_dump($var);
        }

        exit(1);
    }
}

if (!function_exists('dump')) {
    /**
     * Dump variables
     */
    function dump(...$vars): void
    {
        foreach ($vars as $var) {
            var_dump($var);
        }
    }
}

if (!function_exists('debug')) {
    /**
     * Debug variables with better formatting
     */
    function debug(...$vars): void
    {
        if (!config('app.debug')) {
            return;
        }

        echo '<pre style="background: #1a1a1a; color: #f8f8f2; padding: 20px; margin: 10px; border-radius: 5px; font-family: monospace; font-size: 14px; line-height: 1.5; overflow-x: auto;">';

        foreach ($vars as $var) {
            print_r($var);
            echo "\n" . str_repeat('-', 80) . "\n";
        }

        echo '</pre>';
    }
}

if (!function_exists('info')) {
    /**
     * Get information about a variable
     */
    function info($var): array
    {
        return [
            'type' => gettype($var),
            'value' => $var,
            'size' => is_string($var) ? strlen($var) : (is_array($var) ? count($var) : null),
            'class' => is_object($var) ? get_class($var) : null,
            'memory' => memory_get_usage(true),
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ];
    }
}
