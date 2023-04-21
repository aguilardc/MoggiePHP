<?php

# ============================================================================ #

/**
 *  M O G G I E: a PHP micro-framework.
 *
 * @copyright   Copyright (c) 2023, Nevison Aguilar <aguilardc1105@gmail.com>
 * @license     http://opensource.org/licenses/mit-license.php The MIT License
 *
 */

return [

    /**
     * Application Name
     *
     * This value is the name of your application.
     */
    env('NAME', 'Moggie'),

    /**
     * Application Environment
     *
     * This value determines the "environment" your application is currently
     * running in. Set this in your ".env" file.
     */
    env('APP_ENV', 'production'),

    /**
     * Application Debug Mode
     *
     * When your application is in debug mode, detailed error messages with
     * stack traces will be shown on every error that occurs within your
     * application. If disabled, a simple generic error page is shown.
     */
    env('APP_DEBUG', false),

    /**
     * Application URL
     *
     * This value determines the base url your application is currently
     * running in. Set this in your ".env" file.
     */
    env('APP_URL', 'http://localhost'),

    /**
     * Application PHP Version
     *
     * This value determines the current php version.
     */
    env('PHP_VERSION', phpversion()),

    /**
     * Application PHP Version
     *
     * This value determines the minimum required php version.
     */
    env('PHP_REQUIRED', 8.1),


    /**
     * Application Director Separator
     */
    env('DS', DIRECTORY_SEPARATOR)
];
