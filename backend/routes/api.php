<?php

declare(strict_types=1);

use App\Controllers\BeehiveController;
use App\Controllers\EspController;
use App\Controllers\MeasurementController;
use App\Controllers\ThresholdController;
use App\Core\Request;
use App\Core\Router;

/**
 * Register every API route. Returned to the front controller for dispatch.
 */
return static function (Router $router): void {
    // ESP boards
    $router->add('GET', '/esp', static fn(Request $r) => (new EspController())->index());
    $router->add('GET', '/esp/{id}', static fn(Request $r, array $p) => (new EspController())->show($r, $p));
    $router->add('POST', '/esp', static fn(Request $r) => (new EspController())->store($r));
    $router->add('PUT', '/esp/{id}', static fn(Request $r, array $p) => (new EspController())->update($r, $p));
    $router->add('DELETE', '/esp/{id}', static fn(Request $r, array $p) => (new EspController())->destroy($r, $p));

    // Beehives
    $router->add('GET', '/beehives', static fn(Request $r) => (new BeehiveController())->index());
    $router->add('GET', '/beehives/{id}', static fn(Request $r, array $p) => (new BeehiveController())->show($r, $p));
    $router->add('POST', '/beehives', static fn(Request $r) => (new BeehiveController())->store($r));
    $router->add('PUT', '/beehives/{id}', static fn(Request $r, array $p) => (new BeehiveController())->update($r, $p));
    $router->add('DELETE', '/beehives/{id}', static fn(Request $r, array $p) => (new BeehiveController())->destroy($r, $p));

    // Measurements
    $router->add('GET', '/measurements', static fn(Request $r) => (new MeasurementController())->index());
    $router->add('GET', '/measurements/{id}', static fn(Request $r, array $p) => (new MeasurementController())->show($r, $p));
    $router->add('POST', '/measurements', static fn(Request $r) => (new MeasurementController())->store($r));

    // Thresholds
    $router->add('GET', '/thresholds', static fn(Request $r) => (new ThresholdController())->index());
    $router->add('GET', '/thresholds/{metric}', static fn(Request $r, array $p) => (new ThresholdController())->show($r, $p));
    $router->add('PUT', '/thresholds/{metric}', static fn(Request $r, array $p) => (new ThresholdController())->update($r, $p));
};
