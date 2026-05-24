<?php
defined('BASEPATH') or exit('No direct script access allowed');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  header("Access-Control-Allow-Origin: *");
  header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
  header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
  exit;
}

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'welcome';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;

// API v1 Routes
$route['api/v1/auth/login'] = 'api/v1/auth/login';
$route['api/v1/auth/register'] = 'api/v1/auth/register';
$route['api/v1/auth/profile'] = 'api/v1/auth/profile';
$route['api/v1/auth/update-profile'] = 'api/v1/auth/update_profile';

$route['api/v1/categories'] = 'api/v1/categories/index';

$route['api/v1/settings'] = 'api/v1/settings/index';

$route['api/v1/news'] = 'api/v1/news/index';
$route['api/v1/news/detail/(:any)'] = 'api/v1/news/detail/$1';

$route['api/v1/products'] = 'api/v1/products/index';
$route['api/v1/products/(:any)'] = 'api/v1/products/detail/$1';

$route['api/v1/cart'] = 'api/v1/cart/index';
$route['api/v1/cart/add'] = 'api/v1/cart/add';
$route['api/v1/cart/update/(:num)'] = 'api/v1/cart/update/$1';
$route['api/v1/cart/delete/(:num)'] = 'api/v1/cart/delete/$1';
$route['api/v1/cart/sync'] = 'api/v1/cart/sync';

$route['api/v1/orders'] = 'api/v1/orders/index';
$route['api/v1/orders/checkout'] = 'api/v1/orders/checkout';
$route['api/v1/orders/detail/(:num)'] = 'api/v1/orders/detail/$1';
$route['api/v1/orders/upload-proof/(:num)'] = 'api/v1/orders/upload_payment_proof/$1';
$route['api/v1/transactions'] = 'api/v1/orders/transactions';

$route['api/v1/shop-accounts'] = 'api/v1/shopaccounts/index';
$route['api/v1/banners'] = 'api/v1/banners/index';
$route['api/v1/shipping'] = 'api/v1/shipping/index';
$route['api/v1/payments'] = 'api/v1/payments/index';
