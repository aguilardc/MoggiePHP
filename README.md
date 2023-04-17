<h1 align="center">Moggie PHP</h1>

<p align="center">
<img alt="PHP Version required" src="https://img.shields.io/badge/php-%3E%3D8.1-blue">
<img alt="Moggie PHP Version" src="https://img.shields.io/badge/version-1.0.0-yellowgreen">
<img alt="Licence" src="https://img.shields.io/badge/licence-MIT-brightgreen">
</p>

## About

Moggie PHP is a lightweight, syntax elegant web micro-framework.

## Requirements

Flight requires PHP 8.1 or greater.

## Installation

1\. Download the files.

If you're using [Composer](https://getcomposer.org/), you can run the following command:

```
composer require aguilarn/moggie
```

OR you can download them directly and extract them to your web directory.

## Routing

Routing in Moggie PHP is done by matching a URL pattern with a callback function.

```php
Route::get('/', function () {
    echo "Hello world!";
});
```

## Named Parameters

You can specify named parameters in your routes which will be passed along to your callback function.

```php
Route::get('/users/:name/:id', function($name, $id){
    echo "hello, $name ($id)!";
});
```

## Working with controllers

You can specify the name of a controller and the method to execute as a string.

the controller name must be separated from the action name with an @.

```php
Route::get('/users', 'UsersController@read');

Route::get('/users/:id', 'UsersController@readById');

Route::post('/users', 'UsersController@create');

Route::put('/users/:id', 'UsersController@update');

Route::delete('/users/:id', 'UsersController@delete');
```

You can make use of static methods like _GET, POST, PUT_ and _DELETE_

**Note**: The controller must be created in the **/src/controllers** directory and the called function must exist within
it.

## Security Vulnerabilities

If you discover a security vulnerability within Moggie PHP, please send an e-mail to Nevison Aguilar
via [aguilardc1105@gmail.com](mailto:aguilardc1105@gmail.com). All security vulnerabilities will be promptly addressed.

## License

The Moggie PHP micro-framework is open-sourced software licensed under
the [MIT license](https://opensource.org/licenses/MIT).

