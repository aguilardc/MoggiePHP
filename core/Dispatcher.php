<?php

namespace core;

use core\Request;
use core\Response;

class Dispatcher
{

    public static function run()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = isset($_SERVER['PATH_INFO']) ? explode('/', $_SERVER['PATH_INFO']) : explode('/', $_SERVER['REQUEST_URI']);
        $script = explode('/', $_SERVER['SCRIPT_NAME']);
        $controller = array_values(array_diff($uri, $script));

        $request = new Request([
            'controller' => ucfirst($controller[0]),
            'method' => $method,
            'parameters' => []
        ]);

        (new Dispatcher())->submit($request);
    }


    private function doPost(Request $request, Response $response): void
    {
        $request->getController()->create();
    }

    private function doGet(Request $request, Response $response): void
    {
        $request->getController()->read();

    }

    private function doPut(Request $request, Response $response): void
    {
        $request->getController()->update();

    }

    private function doPatch(Request $request, Response $response): void
    {
        $request->getController()->update();
    }

    private function doDelete(Request $request, Response $response): void
    {
        $request->getController()->delete();

    }

    protected function submit(Request $request): void
    {
        switch ($request->getMethod()) {
            case "POST":
                $this->doPost($request, new Response());
                break;
            case "PUT":
                $this->doPut($request, new Response());
                break;
            case "PATCH":
                $this->doPatch($request, new Response());
                break;
            case "DELETE":
                $this->doDelete($request, new Response());
                break;
            case "GET":
            default:
                $this->doGet($request, new Response());
        }
    }
}