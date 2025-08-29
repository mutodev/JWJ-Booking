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
        $routes->get('(:any)', [UserController::class, 'getUserById']);
        $routes->post('', [UserController::class, 'create']);
        $routes->put('(:any)', [UserController::class, 'updateUser']);
        $routes->delete('(:any)', [UserController::class, 'deleteUser']);
        $routes->get('', [UserController::class, 'getAllUsers']);
    });

    // Roles
    $routes->group('roles', ['filter' => 'verifyToken'], function ($routes) {
        $routes->get('', [RoleController::class, 'getAllRoles']);
        $routes->put('(:any)', [RoleController::class, 'updateRole']);
        $routes->post('', [RoleController::class, 'createRole']);
        $routes->delete('(:any)', [RoleController::class, 'deleteRole']);
    });
});


/**
 * Ruta inicial e interpretación de rutas vue
 * @var RouteCollection $routes
 */
$routes->get('/', [HomeController::class, 'index']);
$routes->get('{any}', [HomeController::class, 'index'], ['where' => ['any' => '.*']]);
