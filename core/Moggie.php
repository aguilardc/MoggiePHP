<?php

namespace core;

use core\Route;

class Moggie
{
    private string $framework = 'Moggie Framework';
    private string $version = '1.0.0';
    private string $lng = 'es';

    public function __construct()
    {
        $this->init();
    }

    /**
     * @return void
     */
    public static function run(): void
    {
        new self();
    }

    /**
     * @return void
     */
    private function init(): void
    {
        $this->loadComposer();
        $this->loadConfig();
        $this->loadRoutes();
    }

    /**
     * Initialize composer
     * @return void
     */
    private function loadComposer(): void
    {
        $composer = realpath(__DIR__ . '/../vendor/autoload.php');

        if (!is_file($composer)) {
            die("No se encontró el archivo autoload de composer. El archivo es requerido para que {$this->framework} funcione");
        }

        require_once $composer;
    }

    /**
     * @return void
     */
    private function loadConfig(): void
    {
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();

        $config = realpath(__DIR__ . '/config.php');
        if (!is_file($config)) {
            die("No se encontró el archivo config. El archivo es requerido para que {$this->framework} funcione");
        }
        require_once $config;
    }

    private function loadRoutes(): void
    {
        $routes = realpath(__DIR__ . '/../src/routes/web.php');
        if (!is_file($routes)) {
            die("No se encontró el archivo config. El archivo es requerido para que {$this->framework} funcione");
        }
        require_once $routes;
        Route::run();
    }
}