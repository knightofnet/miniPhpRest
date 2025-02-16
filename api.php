<?php

use MiniPhpRest\Runner;

require_once ('vendor/autoload.php');
$routes = [
    'GET' => [
        '/api/v1/users' => 'UserController@index',
        '/api/v1/users/{id}' => 'UserController@show',
    ],
    'POST' => [
        '/api/v1/login' => 'UserController@login',
    ],
    'PUT' => [
        '/api/v1/users/{id}' => 'UserController@update',
    ],
    'DELETE' => [
        '/api/v1/users/{id}' => 'UserController@destroy',
    ],
];

Runner::followRoute($routes);