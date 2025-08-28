<?php

use App\Controllers\HomeController;
use App\Controllers\LoginController;
use App\Controllers\RoleController;
use App\Controllers\UserController;
use CodeIgniter\Router\RouteCollection;

/**
 * Rutas API
 * @var RouteCollection $routes
 */
$routes->group('api', function ($routes) {
    // Login
    $routes->group('auth', function ($routes) {
        $routes->post('login', [LoginController::class, 'login']);
    });

    // Usuarios
    $routes->group('users', ['filter' => 'verifyToken'], function ($routes) {
        $routes->get('/(:segment)', [UserController::class, 'getUserById']);
        $routes->post('', [UserController::class, 'create']);
        $routes->put('/(:segment)', [UserController::class, 'updateUser']);
        $routes->delete('/(:segment)', [UserController::class, 'deleteUser']);
        $routes->get('', [UserController::class, 'getAllUsers']);
    });

    // Roles
    $routes->group('roles', ['filter' => 'verifyToken'], function ($routes) {
        $routes->get('', [RoleController::class, 'getAllRoles']);
    });
});


/**
 * Ruta inicial e interpretación de rutas vue
 * @var RouteCollection $routes
 */
$routes->get('/', [HomeController::class, 'index']);
$routes->get('{any}', [HomeController::class, 'index'], ['where' => ['any' => '.*']]);
