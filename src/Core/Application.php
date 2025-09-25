<?php

namespace Moggie\Core;

use Moggie\Container\Container;
use Moggie\Http\Request;
use Moggie\Http\Response;
use Moggie\Middleware\MiddlewareStack;
use Moggie\Routing\Router;
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

    protected function getInstance(): ?Application
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
        $this->singleton('config', function () {
            $config = new ConfigRepository();
            $this->loadConfiguration($config);
            return $config;
        });

        $this->singleton('router', fn() => new Router($this));

        $this->singleton('middleware', fn() => new MiddlewareStack($this));

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
            $dotenv = \Dotenv\Dotenv::createImmutable($envPath);
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
        } catch (Throwable $e) {
            $this->handleException($e);
        }
    }

    public function handle(Request $request): Response
    {
        try {
            $this->instance('request', $request);
            $router = $this->get('router');
            $middleware = $this->get('middleware');

            return $middleware->handle($request, function ($request) use ($router) {
                return $router->dispatch($request);
            });
        } catch (\HttpException $e) {
            return $this->renderHttpException($e);
        } catch (\Throwable $e) {
            return $this->renderException($e);
        }
    }
}
