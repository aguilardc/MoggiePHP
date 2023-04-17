<?php

use core\Route;

Route::get('/', function () {
    echo "hola desde el main GET";
});

//Route::get('/users/:id/', function ($id) {
////    echo "hola desde users con parametro id => {$id}";
//    return [
//        'controller' => 'user',
//        'id' => $id
//    ];
//});

Route::get('/users', 'UsersController@read');

Route::get('/users/:id', 'UsersController@readById');

Route::post('/users', 'UsersController@create');

Route::put('/users/:id', 'UsersController@update');

Route::delete('/users/:id', 'UsersController@delete');