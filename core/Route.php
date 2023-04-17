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

    private static array $routes = [];

    public static function run(): void
    {
        $uri = $_SERVER['REQUEST_URI'];
        $uri = trim($uri, '/');

        $method = $_SERVER['REQUEST_METHOD'];
        foreach (self::$routes[$method] as $route => $callback) {
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

    public static function get($uri, $callback): void
    {
        $uri = trim($uri, '/');
        self::$routes['GET'][$uri] = $callback;
    }

    public static function post($uri, $callback): void
    {
        $uri = trim($uri, '/');
        self::$routes['POST'][$uri] = $callback;
    }

    public static function put($uri, $callback): void
    {
        $uri = trim($uri, '/');
        self::$routes['PUT'][$uri] = $callback;
    }

    public static function delete($uri, $callback): void
    {
        $uri = trim($uri, '/');
        self::$routes['DELETE'][$uri] = $callback;
    }
}