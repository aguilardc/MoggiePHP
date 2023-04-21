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


use Dotenv\Dotenv;
use Exception;
use Symfony\Component\Finder\Finder;
use Core\Env;

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
     * Initialize composer Auto Loader
     *
     * Composer provides a convenient, automatically generated class loader
     * for our application. We just need to utilize it! We'll require it
     * into the script here so that we do not have to worry about the
     * loading of our classes 'manually'. Feels great to relax.
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
            Env::create(__DIR__ . '/..');
        } catch (\Throwable $th) {
            trigger_error($th);
            die();
        }
    }

    /**
     * Quickly use our environment variables
     *
     * @return void
     * @throws Exception
     */
    private function loadConfig(): void
    {
        $files = $this->getFiles();

        if (!is_file($files['app'])) {
            throw new Exception('Unable to load the "app" configuration file.');
        }

        foreach ($files as $path) {
            require $path;
        }

        $this->validPHPVersion();
        $this->displayErrors();
    }

    /**
     * Dynamic find and return of configuration files.
     *
     * @return array
     */
    private function getFiles(): array
    {
        $files = [];
        $configPath = realpath(__DIR__ . '/Config');
        foreach (Finder::create()->files()->name('*.php')->in($configPath)->files() as $file) {
            $files[basename($file->getRealPath(), '.php')] = $file->getRealPath();
        }

        return array_reverse($files);
    }

    /**
     * @return void
     * @throws Exception
     */
    private function validPHPVersion(): void
    {
        if (env('PHP_VERSION') < env('PHP_REQUIRED')) {
            throw new Exception('PHP Version not Supported');
        }
    }

    /**
     * @return void
     */
    private function displayErrors(): void
    {
        ini_set('display_errors', env('DISPLAY_ERRORS'));
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
