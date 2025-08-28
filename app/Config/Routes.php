<?php

use App\Controllers\HomeController;
use App\Controllers\LoginController;
use CodeIgniter\Router\RouteCollection;

/**
 * Rutas API
 * @var RouteCollection $routes
 */
$routes->group('api', function ($routes) {
    // Login
    $routes->group('auth', function ($routes) {
        $routes->post('login', [LoginController::class, 'login']);
        // $routes->post('remember-password', [LoginController::class, 'sendRememberPassword']);
        // $routes->post(
        //     'verify-token',
        //     [LoginController::class, 'verifyTokenEmail'],
        //     ['filter' => 'verifyToken']
        // );
    });
});


/**
 * Ruta inicial e interpretación de rutas vue
 * @var RouteCollection $routes
 */
$routes->get('/', [HomeController::class, 'index']);
$routes->get('{any}', [HomeController::class, 'index'], ['where' => ['any' => '.*']]);
