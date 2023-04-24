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

use Core\Request\Parameters;

class Route
{
    private const GET = 'GET';
    private const POST = 'POST';
    private const PUT = 'PUT';
    private const DELETE = 'DELETE';

    private static array $routes = [];

    /**
     * @param Request $request
     * @return void
     */
    public static function run(Request $request): void
    {
        $data = empty($_POST) ? json_decode(file_get_contents('php://input'), true) : $_POST;
        foreach (self::$routes[$request->getMethod()] as $route => $callback) {
            $uri = explode('/', $route);
            $idParams = [];
            foreach ($uri as $url) {
                if (stripos(strtolower($url), ':') !== false) {
                    $idParams[] = ltrim($url, ':');
                }
            }
            if (str_contains($route, ':')) {
                $route = preg_replace('/:[a-zA-Z0-9]+/i', '([a-zA-Z0-9]+)', $route);
            }
            if (preg_match("#^$route$#i", $request->getUri()->getPath(), $matches)) {
                $params = array_slice($matches, 1);
                foreach ($params as $key => $param) {
                    $data[$idParams[$key]] = $param;
                }
                $request->setParameters(new Parameters($data));
                if (is_callable($callback)) {
                    $response = $callback(...$params);
                }
                if (is_string($callback)) {
                    $router = explode('@', $callback);
                    $class = "\\App\\Controllers\\$router[0]";
                    if (class_exists($class)) {
                        $controller = new $class();
                        if (method_exists($class, $router[1])) {
                            $response = $controller->{$router[1]}($request);
                        }
                    }
                }
                header('Content-type', 'application/json');
                echo (is_array($response) || is_object($response)) ? json_encode($response) : $response;
                return;
            }
        }
        echo json_encode(['code' => 404, 'path' => $request->getUri()->getPath(), 'message' => 'Resource Not found']);
    }

    /**
     * @param $uri
     * @param $callback
     * @param string $method
     * @return void
     */
    private static function add($uri, $callback, string $method): void
    {
        self::$routes[$method][rtrim($uri, '/')] = $callback;
    }

    /**
     * @param $uri
     * @param $callback
     * @return void
     */
    public static function get($uri, $callback): void
    {
        self::add($uri, $callback, self::GET);
    }

    /**
     * @param $uri
     * @param $callback
     * @return void
     */
    public static function post($uri, $callback): void
    {
        self::add($uri, $callback, self::POST);
    }

    /**
     * @param $uri
     * @param $callback
     * @return void
     */
    public static function put($uri, $callback): void
    {
        self::add($uri, $callback, self::PUT);
    }

    /**
     * @param $uri
     * @param $callback
     * @return void
     */
    public static function delete($uri, $callback): void
    {
        self::add($uri, $callback, self::DELETE);
    }
}
