<?php

use App\Controllers\AddonController;
use App\Controllers\CityController;
use App\Controllers\CountyController;
use App\Controllers\CustomerController;
use App\Controllers\HomeController;
use App\Controllers\LoginController;
use App\Controllers\MetropolitanAreaController;
use App\Controllers\ReservationAddonController;
use App\Controllers\ReservationController;
use App\Controllers\RoleController;
use App\Controllers\ServiceController;
use App\Controllers\ServicePriceController;
use App\Controllers\UserController;
use App\Controllers\ZipCodeController;
use CodeIgniter\Router\RouteCollection;

/**
 * Rutas API
 * @var RouteCollection $routes
 */
$routes->group('api', function ($routes) {

    //home
    $routes->group('home', function ($routes) {
        $routes->get('metropolitan-areas', [MetropolitanAreaController::class, 'getAllActive']);
        $routes->get('counties/(:any)', [CountyController::class, 'getByMetropolitan']);
        $routes->get('cities/(:any)', [CityController::class, 'getByCounty']);
        $routes->get('zipcode/(:any)/(:any)', [ZipCodeController::class, 'getByCityAndCode']);
        $routes->get('services/(:any)', [ServicePriceController::class, 'getAllByCounty']);
    });


    // Login
    $routes->group('auth', function ($routes) {
        $routes->post('login', [LoginController::class, 'login']);
        $routes->post('reset-password', [LoginController::class, 'resetPassword']);
    });

    // Usuarios
    $routes->group('users', ['filter' => 'verifyToken'], function ($routes) {
        $routes->get('by-id/(:any)', [UserController::class, 'getUserById']);
        $routes->post('', [UserController::class, 'create']);
        $routes->put('(:any)', [UserController::class, 'updateUser']);
        $routes->delete('(:any)', [UserController::class, 'deleteUser']);
        $routes->get('by-rol/(:any)', [UserController::class, 'getUserByRole']);
    });

    // Roles
    $routes->group('roles', ['filter' => 'verifyToken'], function ($routes) {
        $routes->get('', [RoleController::class, 'getAllRoles']);
        $routes->put('(:any)', [RoleController::class, 'updateRole']);
        $routes->post('', [RoleController::class, 'createRole']);
        $routes->delete('(:any)', [RoleController::class, 'deleteRole']);
    });

    // Metropolitan Areas
    $routes->group('metropolitan-areas', ['filter' => 'verifyToken'], function ($routes) {
        $routes->get('', [MetropolitanAreaController::class, 'getAll']);
        $routes->get('list-active', [MetropolitanAreaController::class, 'getAllActive']);
        $routes->get('(:any)', [MetropolitanAreaController::class, 'getById']);
        $routes->post('', [MetropolitanAreaController::class, 'create']);
        $routes->put('(:any)', [MetropolitanAreaController::class, 'updateData']);
        $routes->delete('(:any)', [MetropolitanAreaController::class, 'deleteData']);
    });

    // Counties
    $routes->group('counties', ['filter' => 'verifyToken'], function ($routes) {
        $routes->get('', [CountyController::class, 'getAll']);
        $routes->get('get-all-and-metropolitan', [CountyController::class, 'getAllAndMetrpolitan']);
        $routes->get('get-all-active', [CountyController::class, 'getAllActive']);
        $routes->get('get-by-metropolitan/(:any)', [CountyController::class, 'getByMetropolitan']);
        $routes->get('(:any)', [CountyController::class, 'getById']);
        $routes->post('', [CountyController::class, 'create']);
        $routes->put('(:any)', [CountyController::class, 'updateData']);
        $routes->delete('(:any)', [CountyController::class, 'deleteData']);
    });

    // Cities
    $routes->group('cities', ['filter' => 'verifyToken'], function ($routes) {
        $routes->get('', [CityController::class, 'getAll']);
        $routes->get('get-all-and-county', [CityController::class, 'getAllAndCounty']);
        $routes->get('get-all-active', [CityController::class, 'getAllActive']);
        $routes->get('get-by-county/(:any)', [CityController::class, 'getByCounty']);
        $routes->get('(:any)', [CityController::class, 'getById']);
        $routes->post('', [CityController::class, 'create']);
        $routes->put('(:any)', [CityController::class, 'updateData']);
        $routes->delete('(:any)', [CityController::class, 'deleteData']);
    });

    // Zipcodes
    $routes->group('zipcodes', ['filter' => 'verifyToken'], function ($routes) {
        $routes->get('', [ZipCodeController::class, 'getAll']);
        $routes->get('get-all-and-city', [ZipCodeController::class, 'getAllAndCity']);
        $routes->get('get-by-city/(:any)', [ZipCodeController::class, 'getByCity']);
        $routes->get('(:any)', [ZipCodeController::class, 'getById']);
        $routes->post('', [ZipCodeController::class, 'create']);
        $routes->put('(:any)', [ZipCodeController::class, 'updateData']);
        $routes->delete('(:any)', [ZipCodeController::class, 'deleteData']);
    });

    $routes->group('services', ['filter' => 'verifyToken'], function ($routes) {
        $routes->get('/', [ServiceController::class, 'getAll']);
        $routes->get('get-all-active', [ServiceController::class, 'getAllActive']);
        $routes->get('(:any)', [ServiceController::class, 'getById']);
        $routes->post('/', [ServiceController::class, 'create']);
        $routes->put('(:any)', [ServiceController::class, 'updateData']);
        $routes->delete('(:any)', [ServiceController::class, 'deleteData']);
    });

    $routes->group('service-prices', ['filter' => 'verifyToken'], function ($routes) {
        $routes->get('', [ServicePriceController::class, 'getAll']);
        $routes->get('get-by-service-and-county/(:segment)/(:segment)', [ServicePriceController::class, 'getByServiceAndCounty']);
        $routes->get('(:segment)', [ServicePriceController::class, 'getById']);
        $routes->post('', [ServicePriceController::class, 'createData']);
        $routes->put('(:segment)', [ServicePriceController::class, 'updateData']);
        $routes->delete('(:segment)', [ServicePriceController::class, 'delete']);
    });

    $routes->group('addons', ['filter' => 'verifyToken'], function ($routes) {
        $routes->get('/', [AddonController::class, 'getAll']);
        $routes->get('active', [AddonController::class, 'getAllActive']);
        $routes->get('search/(:any)', [AddonController::class, 'search']);
        $routes->get('(:segment)', [AddonController::class, 'getById']);
        $routes->post('/', [AddonController::class, 'create']);
        $routes->put('(:segment)', [AddonController::class, 'updateData']);
        $routes->delete('(:segment)', [AddonController::class, 'deleteData']);
    });

    $routes->group('customers', ['filter' => 'verifyToken'], function ($routes) {
        $routes->get('/', [CustomerController::class, 'getAll']);
        $routes->get('(:segment)', [CustomerController::class, 'getById']);
        $routes->get('search/(:segment)', [CustomerController::class, 'searchByName']);
        $routes->post('/', [CustomerController::class, 'create']);
        $routes->put('(:segment)', [CustomerController::class, 'updateData']);
        $routes->delete('(:segment)', [CustomerController::class, 'deleteData']);
    });

    $routes->group('reservations', ['filter' => 'verifyToken'], function ($routes) {
        $routes->get('/', [ReservationController::class, 'getAll']);
        $routes->get('(:segment)', [ReservationController::class, 'getById']);
        $routes->post('/', [ReservationController::class, 'create']);
        $routes->put('(:segment)', [ReservationController::class, 'updateData']);
        $routes->delete('(:segment)', [ReservationController::class, 'deleteData']);
    });

    $routes->group('reservation-addons', ['filter' => 'verifyToken'], function ($routes) {
        $routes->get('/', [ReservationAddonController::class, 'getAll']);
        $routes->get('by-reservation/(:uuid)', [ReservationAddonController::class, 'getByReservation/$1']);
        $routes->get('(:uuid)', [ReservationAddonController::class, 'getById/$1']);
        $routes->post('/', [ReservationAddonController::class, 'create']);
        $routes->put('(:uuid)', [ReservationAddonController::class, 'updateData/$1']);
        $routes->delete('(:uuid)', [ReservationAddonController::class, 'deleteData/$1']);
    });
});


/**
 * Ruta inicial e interpretación de rutas vue
 * @var RouteCollection $routes
 */
$routes->get('/', [HomeController::class, 'index']);
$routes->get('{any}', [HomeController::class, 'index'], ['where' => ['any' => '.*']]);
