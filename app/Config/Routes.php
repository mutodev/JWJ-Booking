<?php

use App\Controllers\HomeController;
use CodeIgniter\Router\RouteCollection;

/**
 * Rutas API
 * @var RouteCollection $routes
 */
$routes->group('api', function ($routes) {});


/**
 * Ruta inicial e interpretación de rutas vue
 * @var RouteCollection $routes
 */
$routes->get('/', [HomeController::class, 'index']);
$routes->get('{any}', [HomeController::class, 'index'], ['where' => ['any' => '.*']]);
