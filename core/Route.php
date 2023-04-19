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

class Route
{
    private const GET = 'GET';
    private const POST = 'POST';
    private const PUT = 'PUT';
    private const DELETE = 'DELETE';

    private static array $routes = [];

    /**
     * @return void
     */
    public static function run(): void
    {
        $uri = trim($_SERVER['REQUEST_URI'], '/');

        foreach (self::$routes[$_SERVER['REQUEST_METHOD']] as $route => $callback) {
            if (str_contains($route, ':')) {
                $route = preg_replace('#:[a-zA-Z0-9]+#', '([a-zA-Z0-9]+)', $route);
            }

            if (preg_match("#^$route$#", $uri, $matches)) {
                $params = array_slice($matches, 1);

                if (is_callable($callback)) {
                    $response = $callback(...$params);
                }

                if (is_string($callback)) {
                    $router = explode('@', $callback);
                    $class = "\\App\\Controllers\\$router[0]";
                    if (class_exists($class)) {
                        $controller = new $class();
                        if (method_exists($class, $router[1])) {
                            $response = $controller->{$router[1]}(...$params);
                        }
                    }
                }
                header('Content-type', 'application/json');
                echo (is_array($response) || is_object($response)) ? json_encode($response) : $response;

                return;
            }
        }
        echo json_encode(['code' => 404, 'path' => $_SERVER['REQUEST_URI'], 'message' => 'Resource Not found']);
    }

    /**
     * @param $uri
     * @param $callback
     * @param string $method
     * @return void
     */
    private function add($uri, $callback, string $method): void
    {
        $uri = trim($uri, '/');
        self::$routes[$method][$uri] = $callback;
    }

    /**
     * @param $uri
     * @param $callback
     * @return void
     */
    public static function get($uri, $callback): void
    {
        (new self())->add($uri, $callback, self::GET);
    }

    /**
     * @param $uri
     * @param $callback
     * @return void
     */
    public static function post($uri, $callback): void
    {
        (new self())->add($uri, $callback, self::POST);
    }

    /**
     * @param $uri
     * @param $callback
     * @return void
     */
    public static function put($uri, $callback): void
    {
        (new self())->add($uri, $callback, self::PUT);
    }

    /**
     * @param $uri
     * @param $callback
     * @return void
     */
    public static function delete($uri, $callback): void
    {
        (new self())->add($uri, $callback, self::DELETE);
    }
}