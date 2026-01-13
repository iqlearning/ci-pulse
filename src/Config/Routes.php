<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Route collection for Pulse.
 */
$routes->group('pulse', static function (RouteCollection $routes) {
    $routes->get('/', '\Iqlearning\Pulse\Controllers\PulseController::index');
    $routes->get('check', '\Iqlearning\Pulse\Controllers\PulseController::check'); // For Scheduled Tasks / Cron
    $routes->get('stats', '\Iqlearning\Pulse\Controllers\PulseController::stats'); // Dashboard Data
});

