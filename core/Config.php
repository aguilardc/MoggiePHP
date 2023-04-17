<?php
if (!defined('DISPLAY_ERRORS')) {
    define('DISPLAY_ERRORS', $_ENV['__DISPLAY_ERRORS__']);
}

if (!defined('PHP_REQUIRED')) {
    define('PHP_REQUIRED', 8.1);
}

if (!defined('NOT_FOUND')) {
    define('NOT_FOUND', 404);
}

if (!defined('SERVER_ERROR')) {
    define('SERVER_ERROR', 500);
}

if (!defined('DS')) {
    define("DS", DIRECTORY_SEPARATOR);
}

##Database Credentials
define('DB_ENGINE', $_ENV['___DB_ENGINE___']);
define('DB_HOST', $_ENV['___DB_HOST___']);
define('DB_NAME', $_ENV['___DB_NAME___']);
define('DB_USER', $_ENV['___DB_USER___']);
define('DB_PASSWORD', $_ENV['___DB_PASS___']);
define('DB_CHARSET', $_ENV['___DB_CHARSET___']);
