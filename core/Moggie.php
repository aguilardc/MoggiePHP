<?php

# ============================================================================ #

/**
 *  M O G G I E: a PHP micro-framework.
 *
 *  For more information: {@link aguilardc1105@gmail.com}
 *
 * @copyright   Copyright (c) 2023, Nevison Aguilar <aguilardc1105@gmail.com>
 * @license     http://opensource.org/licenses/mit-license.php The MIT License
 *
 */

namespace Core;


use Dotenv\Dotenv;

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
        $this->loadEnvironmentVariables();
        $this->loadConfig();
        $this->loadRoutes();
    }

    /**
     * Initialize composer
     *
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
    private function loadEnvironmentVariables(): void
    {
        try {
            Dotenv::createImmutable(__DIR__ . '/..')->load();
        } catch (\Throwable $th) {
            trigger_error($th);
            die();
        }
    }

    /**
     * Quickly use our environment variables
     *
     * @return void
     */
    private function loadConfig(): void
    {
        $config = realpath(__DIR__ . '/config.php');
        if (!is_file($config)) {
            die("No se encontró el archivo config. El archivo es requerido para que {$this->framework} funcione");
        }
        require_once $config;

        $this->validPHPVersion();
        $this->displayErrors();
    }

    /**
     * @return void
     */
    private function validPHPVersion(): void
    {
        if (PHP_VERSION < PHP_REQUIRED) {
            die('PHP Version not Supported');
        }
    }

    /**
     * @return void
     */
    private function displayErrors(): void
    {
        ini_set('display_errors', DISPLAY_ERRORS);
    }


    /**
     * @return void
     */
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