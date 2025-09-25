<?php

declare(strict_types=1);

namespace Moggie\Core;

use Moggie\Container\Container;
use Moggie\Http\Request;
use Moggie\Http\Response;
use Moggie\Http\JsonResponse;
use Moggie\Routing\Router;
use Moggie\Middleware\MiddlewareStack;
use Moggie\Config\Repository as ConfigRepository;
use Moggie\Http\Exceptions\HttpException;
use Throwable;

class Application extends Container
{
    protected static ?Application $instance = null;
    protected string $basePath;
    protected string $version = '2.0.0';
    protected bool $booted = false;
    protected array $serviceProviders = [];
    protected array $loadedProviders = [];

    public function __construct(?string $basePath = null)
    {
        static::$instance = $this;

        $this->basePath = $basePath ?: getcwd();

        $this->registerBaseBindings();
        $this->registerCoreServices();
    }

    public static function getInstance(): ?Application
    {
        return static::$instance;
    }

    protected function registerBaseBindings(): void
    {
        $this->singleton('app', fn() => $this);
        $this->singleton(Application::class, fn() => $this);
        $this->singleton(Container::class, fn() => $this);
    }

    protected function registerCoreServices(): void
    {
        // Config
        $this->singleton('config', function () {
            $config = new ConfigRepository();
            $this->loadConfiguration($config);
            return $config;
        });

        // Router
        $this->singleton('router', fn() => new Router($this));

        // Middleware Stack
        $this->singleton('middleware', fn() => new MiddlewareStack($this));

        // Request (será creado dinámicamente)
        $this->bind('request', fn() => Request::capture());
    }

    protected function loadConfiguration(ConfigRepository $config): void
    {
        $configPath = $this->configPath();

        if (!is_dir($configPath)) {
            return;
        }

        foreach (glob($configPath . '/*.php') as $file) {
            $key = basename($file, '.php');
            $config->set($key, require $file);
        }
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->loadEnvironmentVariables();
        $this->bootServiceProviders();

        $this->booted = true;
    }

    protected function loadEnvironmentVariables(): void
    {
        $envPath = $this->basePath() . '/.env';

        if (file_exists($envPath)) {
            $dotenv = \Dotenv\Dotenv::createImmutable($this->basePath());
            $dotenv->load();
        }
    }

    protected function bootServiceProviders(): void
    {
        foreach ($this->serviceProviders as $provider) {
            if (!isset($this->loadedProviders[$provider])) {
                $instance = new $provider($this);
                $instance->boot();
                $this->loadedProviders[$provider] = true;
            }
        }
    }

    public function register(string $provider): void
    {
        $this->serviceProviders[] = $provider;

        if ($this->booted) {
            $instance = new $provider($this);
            $instance->register();
            $instance->boot();
            $this->loadedProviders[$provider] = true;
        }
    }

    public function run(): void
    {
        $this->boot();

        try {
            $request = $this->get('request');
            $response = $this->handle($request);
            $this->sendResponse($response);
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function handle(Request $request): Response
    {
        try {
            // Store request in container
            $this->instance('request', $request);

            // Get router and middleware
            $router = $this->get('router');
            $middleware = $this->get('middleware');

            // Handle through middleware stack
            return $middleware->handle($request, function ($request) use ($router) {
                return $router->dispatch($request);
            });

        } catch (HttpException $e) {
            return $this->renderHttpException($e);
        } catch (Throwable $e) {
            return $this->renderException($e);
        }
    }

    protected function sendResponse(Response $response): void
    {
        $response->send();
    }

    protected function handleException(Throwable $e): void
    {
        if ($e instanceof HttpException) {
            $response = $this->renderHttpException($e);
        } else {
            $response = $this->renderException($e);
        }

        $this->sendResponse($response);
    }

    protected function renderHttpException(HttpException $e): JsonResponse
    {
        return new JsonResponse([
            'error' => $e->getMessage() ?: 'An error occurred',
            'code' => $e->getStatusCode()
        ], $e->getStatusCode(), $e->getHeaders());
    }

    protected function renderException(Throwable $e): JsonResponse
    {
        $debug = $this->get('config')->get('app.debug', false);

        $data = [
            'error' => $debug ? $e->getMessage() : 'Internal Server Error',
            'code' => 500
        ];

        if ($debug) {
            $data['trace'] = $e->getTraceAsString();
            $data['file'] = $e->getFile();
            $data['line'] = $e->getLine();
        }

        return new JsonResponse($data, 500);
    }

    // Router convenience methods
    public function get(string $uri, $action): \Moggie\Routing\Route
    {
        return $this->router()->get($uri, $action);
    }

    public function post(string $uri, $action): \Moggie\Routing\Route
    {
        return $this->router()->post($uri, $action);
    }

    public function put(string $uri, $action): \Moggie\Routing\Route
    {
        return $this->router()->put($uri, $action);
    }

    public function patch(string $uri, $action): \Moggie\Routing\Route
    {
        return $this->router()->patch($uri, $action);
    }

    public function delete(string $uri, $action): \Moggie\Routing\Route
    {
        return $this->router()->delete($uri, $action);
    }

    public function options(string $uri, $action): \Moggie\Routing\Route
    {
        return $this->router()->options($uri, $action);
    }

    public function group(array $attributes, \Closure $callback): void
    {
        $this->router()->group($attributes, $callback);
    }

    protected function router(): Router
    {
        return $this->get('router');
    }

    // Middleware convenience methods
    public function middleware(string $name, string $class): void
    {
        $this->get('middleware')->alias($name, $class);
    }

    public function globalMiddleware(string $middleware): void
    {
        $this->get('middleware')->push($middleware);
    }

    // Path helpers
    public function basePath(?string $path = null): string
    {
        return $this->basePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    public function configPath(?string $path = null): string
    {
        return $this->basePath('config' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    public function appPath(?string $path = null): string
    {
        return $this->basePath('app' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    public function storagePath(?string $path = null): string
    {
        return $this->basePath('storage' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    public function publicPath(?string $path = null): string
    {
        return $this->basePath('public' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }

    // Version
    public function version(): string
    {
        return $this->version;
    }

    // Environment helpers
    public function environment(): string
    {
        return env('APP_ENV', 'production');
    }

    public function isLocal(): bool
    {
        return $this->environment() === 'local';
    }

    public function isProduction(): bool
    {
        return $this->environment() === 'production';
    }

    public function runningInConsole(): bool
    {
        return php_sapi_name() === 'cli' || php_sapi_name() === 'phpdbg';
    }
}
