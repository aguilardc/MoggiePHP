<?php

use Moggie\Core\Application;
use Moggie\Http\Response;
use Moggie\Http\JsonResponse;

if (!function_exists('app')) {
    function app(?string $abstract = null)
    {
        if ($abstract === null) {
            return Application::getInstance();
        }
    }
}
