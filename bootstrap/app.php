<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Moggie\Core\Application;

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| First we need to get an application instance. This creates an instance
| of the application / container and binds all the basic services.
|
*/
$app = new Application(dirname(__DIR__));

/*
|--------------------------------------------------------------------------
| Register Container Bindings
|--------------------------------------------------------------------------
|
| Now we will register a few bindings in the service container. We will
| register the exception handler and the console kernel. You may add
| your own bindings here if you like or they will be added elsewhere.
|
*/
$app->singleton('Psr\Http\Message\ServerRequestInterface', function () {
    return \Moggie\Http\Request::capture();
});

/*
|--------------------------------------------------------------------------
| Register Config Repositories
|--------------------------------------------------------------------------
|
| Now we will register the configuration repository. This will allow us
| to access configuration values throughout the application using the
| "config" helper function or by injecting the Config repository class.
|
*/
$app->configure('app');
$app->configure('database');
$app->configure('cache');
$app->configure('cors');

/*
|--------------------------------------------------------------------------
| Register Middleware
|--------------------------------------------------------------------------
|
| Next, we will register the middleware with the application. These can
| be global middleware that runs before and after each request into the
| application, or middleware that'll be assigned to specific routes.
|
*/
// Global Middleware
$app->globalMiddleware([
    \Moggie\Middleware\HandleCors::class,
    \Moggie\Middleware\ValidatePostSize::class,
    \Moggie\Middleware\TrimStrings::class,
    \Moggie\Middleware\ConvertEmptyStringsToNull::class,
]);

// Route Middleware
$app->middleware([
    'auth' => \Moggie\Middleware\Authenticate::class,
    'cors' => \Moggie\Middleware\HandleCors::class,
    'throttle' => \Moggie\Middleware\ThrottleRequests::class,
]);

/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/

// $app->register(App\Providers\AppServiceProvider::class);
// $app->register(App\Providers\EventServiceProvider::class);

/*
|--------------------------------------------------------------------------
| Load The Application Routes
|--------------------------------------------------------------------------
|
| Next we will include the routes file so that they can be added to the
| application. This will provide all of the URLs the application can
| respond to, along with the controller it will call when that URL is
| requested.
|
*/
$app->router->group(['namespace' => 'App\Http\Controllers'], function ($router) {
    require __DIR__ . '/../routes/web.php';
});

return $app;
