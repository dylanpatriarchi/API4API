<?php

declare(strict_types=1);

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;

require dirname(__DIR__) . '/src/bootstrap.php';

$router = new Router();
(require dirname(__DIR__) . '/routes/api.php')($router);

try {
    $router->dispatch(Request::capture());
} catch (HttpException $e) {
    Response::error($e->getMessage(), $e->status());
} catch (Throwable $e) {
    error_log((string) $e);
    Response::error('Internal server error', 500);
}
