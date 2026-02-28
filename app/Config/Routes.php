<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(true);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.



$routes->post('api/login', 'AuthController::login');
$routes->post('api/logout', 'AuthController::logout');

$routes->group('api', ['namespace' => 'App\Controllers'], function($routes) {
    $routes->get('employees', 'EmployeeController::index');
    $routes->get('employees/(:num)', 'EmployeeController::show/$1');
    $routes->post('employees', 'EmployeeController::create');
    $routes->put('employees/(:num)', 'EmployeeController::update/$1');
    $routes->delete('employees/(:num)', 'EmployeeController::delete/$1');
    $routes->post('employees/permissions/(:num)', 'EmployeeController::setPermissions/$1');
    
    $routes->get('roomtypes', 'RoomTypeController::index');
    $routes->get('roomtypes/(:num)', 'RoomTypeController::show/$1');
    $routes->post('roomtypes', 'RoomTypeController::create');
    $routes->put('roomtypes/(:num)', 'RoomTypeController::update/$1');
    $routes->delete('roomtypes/(:num)', 'RoomTypeController::delete/$1');
    
    $routes->get('tariffs', 'TariffController::index');
    $routes->get('tariffs/(:num)', 'TariffController::show/$1');
    $routes->post('tariffs', 'TariffController::create');
    $routes->put('tariffs/(:num)', 'TariffController::update/$1');
    
    $routes->get('customers', 'CustomerController::index');
    $routes->get('customers/(:num)', 'CustomerController::show/$1');
    $routes->post('customers', 'CustomerController::create');
    $routes->put('customers/(:num)', 'CustomerController::update/$1');
    $routes->delete('customers/(:num)', 'CustomerController::delete/$1');
    
    $routes->get('bookings', 'BookingController::index');
    $routes->get('bookings/(:num)', 'BookingController::show/$1');
    
    $routes->get('bookings/(:num)/guests', 'BookingController::guests/$1');
    $routes->post('bookings', 'BookingController::create');
    $routes->put('bookings/(:num)', 'BookingController::update/$1');
    $routes->post('bookings/(:num)/cancel', 'BookingController::cancel/$1');
    $routes->delete('bookings/(:num)', 'BookingController::delete/$1');
    
    $routes->get('availability/check', 'AvailabilityController::check');
    $routes->get('availability/calendar', 'AvailabilityController::calendar');
    $routes->get('availability/summary', 'AvailabilityController::summary');
    $routes->get('availability/available-inventories', 'AvailabilityController::availableInventories');
    
    $routes->get('availability/room-map', 'AvailabilityController::roomMap');
    $routes->get('availability/available-inventories', 'AvailabilityController::availableInventories');

    
    $routes->get('availabilitychart', 'AvailabilityChartController::index');
    $routes->get('availabilitychart/details', 'AvailabilityChartController::details');
    
    
    $routes->get('roominventory', 'RoomInventoryController::index');
    $routes->get('roominventory/(:num)', 'RoomInventoryController::show/$1');
    $routes->post('roominventory', 'RoomInventoryController::create');
    $routes->put('roominventory/(:num)', 'RoomInventoryController::update/$1');
    $routes->delete('roominventory/(:num)', 'RoomInventoryController::delete/$1');
    $routes->delete('roominventory/(:num)', 'RoomInventoryController::delete/$1');
    $routes->get('currentinventory/(:num)', 'RoomInventoryController::getInventoryBy/$1');
    
    
    ####### booking front end api ####
    $routes->get('room-categories', 'RoomTypeController::index');
    
    $routes->get('countries', 'CountryController::index');
    
    
    $routes->get('proforma', 'ProformaController::index');
    $routes->get('proformas/(:num)', 'ProformaController::show/$1');
    $routes->post('proforma', 'ProformaController::create');
    $routes->put('proformas/(:num)', 'ProformaController::update/$1');
    $routes->delete('proforma/(:num)', 'ProformaController::delete/$1');

    $routes->get('proforma/suggest', 'ProformaController::suggest'); 
    // ?booking_id=11  OR ?customer_id=5
    $routes->get('proforma/view/(:num)', 'ProformaController::viewPdf/$1');
    

    $routes->get('customers/(:num)/bookings/uninvoiced', 'ProformaController::uninvoicedBookings/$1');

    $routes->get('bookings/(:num)/proforma', 'ProformaController::bookingProforma/$1');
    
    // Amenities
    $routes->get('amenities', 'RoomAmenityController::index');
    $routes->get('amenities/(:num)', 'RoomAmenityController::show/$1');
    $routes->post('amenities', 'RoomAmenityController::create');
    $routes->put('amenities/(:num)', 'RoomAmenityController::update/$1');
    $routes->delete('amenities/(:num)', 'RoomAmenityController::delete/$1');

    // Minibar
    $routes->get('minibar', 'MinibarItemController::index');
    $routes->get('minibar/(:num)', 'MinibarItemController::show/$1');
    $routes->post('minibar', 'MinibarItemController::create');
    $routes->put('minibar/(:num)', 'MinibarItemController::update/$1');
    $routes->delete('minibar/(:num)', 'MinibarItemController::delete/$1');



});


$routes->group('api/frontdesk', function($routes) {
    $routes->get('checkin/(:num)', 'FrontDeskController::getCheckin/$1');
    $routes->post('checkin/(:num)', 'FrontDeskController::confirmCheckin/$1');

    $routes->get('checkout/(:num)', 'CheckoutController::show/$1');
    $routes->post('checkout/(:num)', 'CheckoutController::complete/$1');
    $routes->post('checkout/(:num)/minibar/consume', 'CheckoutController::consumeMinibar/$1');

    $routes->get('minibar-items', 'FrontDeskController::minibarItems');
    
    $routes->post('guests/upload-passport', 'FrontDeskController::uploadPassport');
});


$routes->resource('api/register');

// Optionally, group:
$routes->group('api', ['namespace' => 'App\Controllers'], function($r){
  $r->resource('register');
  //$r->resource('reservations');
});

$routes->group('api', ['filter' => 'cors'], static function ($routes) {
    $routes->options('(:any)', static function () {
        // No content needed; the CORS filter will handle the response.
    });
    
    $routes->resource('register');
    // Define your API routes here
});


$routes->get('(:any)', 'Home::index');


/*$routes->get('/', 'Home::index');



$routes->get('/dashboard', 'Dashboard::index');
$routes->get('/orders/add', 'Orders::add');
$routes->post('/orders/add', 'Orders::add');
$routes->get('/orders/all', 'Orders::all');
$routes->get('/orders/edit/(:num)', 'Orders::edit/$1');
$routes->post('/orders/edit/(:num)', 'Orders::edit/$1');
$routes->post('/orders/update-status/(:num)', 'Orders::updateStatus/$1');
$routes->post('api/orders/create', 'Api\Orders::create');

$routes->group('users', ['filter' => 'auth'], function($routes) {
    $routes->get('/', 'Users::index');
    $routes->get('add', 'Users::create');
    $routes->post('add', 'Users::store');
    $routes->get('edit/(:num)', 'Users::edit/$1');
    $routes->post('update/(:num)', 'Users::update/$1');
    $routes->get('delete/(:num)', 'Users::delete/$1');
});

$routes->group('', ['filter' => 'auth'], function($routes) {
    $routes->get('/dashboard', 'Dashboard::index');
    $routes->get('/orders', 'Orders::index');
    $routes->get('/orders/add', 'Orders::create');
    $routes->get('/users', 'Users::index');
    // add other protected routes
});*/



/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
