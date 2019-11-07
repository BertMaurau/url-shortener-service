<?php

namespace BertMaurau\URLShortener;

use BertMaurau\URLShortener\Core AS Core;
use BertMaurau\URLShortener\Models AS Models;

// -----------------------------------------------------------------------------
//  Handle CORS
// -----------------------------------------------------------------------------
// This must be done before any output has been sent from the server..
// Modify to your own specifications
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Credentials: true");
header('Access-Control-Allow-Methods: '
        . 'POST, '
        . 'GET, '
        . 'DELETE, '
        . 'PUT, '
        . 'PATCH, '
        . 'OPTIONS');
header('Access-Control-Allow-Headers: '
        . 'Authorization, '
        . 'X-PINGOTHER, '
        . 'Origin, '
        . 'X-Requested-With, '
        . 'Content-Type, '
        . 'Accept, '
        . 'Cache-Control, '
        . 'Pragma, '
        . 'Accept-Encoding,'
        . 'ResponseType');
header('Access-Control-Max-Age: 1728000');

// If the client side requested a pre-flight OPTIONS request due to custom headers
// of some sort.
if (filter_input(INPUT_SERVER, 'REQUEST_METHOD') === 'OPTIONS') {
    header('Content-Length: 0');
    header('Content-Type: text/plain');

    // end the script here
    die();
}

//  Load all the required classes and files.
// -----------------------------------------------------------------------------
require 'require.php';

// log any incoming requests
try {
    Models\LogRequest::register();
} catch (\Exception $ex) {
    // do something
}

// -----------------------------------------------------------------------------
// Start the routing build-up
// -----------------------------------------------------------------------------
$container = new \League\Container\Container;

$container -> share('response', \Zend\Diactoros\Response::class);
$container -> share('request', function () {

    // change Constants API ROOT if the "api" is not running on the root of the
    // domain. For ex. if this is hosted within a subdirectory API then set this
    // to "/api" so that it matches the actual url: http://domain.com/api for ex.
    $_SERVER['REQUEST_URI'] = str_replace(Core\Config::getInstance() -> API() -> root, '', filter_input(INPUT_SERVER, 'REQUEST_URI'));

    return \Zend\Diactoros\ServerRequestFactory::fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
});

$container -> share('emitter', \Zend\Diactoros\Response\SapiEmitter::class);

// start the Router
$route = new \League\Route\RouteCollection;

// require the list of available routes
require __DIR__ . '/routes/routes-v1.php';

try {
    $response = $route -> dispatch(
            $container -> get('request'), $container -> get('response'));
} catch (\League\Route\Http\Exception\NotFoundException $exception) {
    $response = Core\Output::NotFound(Core\Output::Clear(), 'Route not found.');
} catch (\League\Route\Http\Exception\MethodNotAllowedException $exception) {
    $response = Core\Output::Conflict(Core\Output::Clear(), 'Method not allowed.');
}

// sends headers and output using PHPs standard SAPI mechanisms
$container -> get('emitter') -> emit(
        $response -> withHeader('Content-Type', 'application/json'));

// -----------------------------------------------------------------------------
// end of script

