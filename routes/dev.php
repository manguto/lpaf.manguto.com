<?php

use App\Controllers\DevController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;

return static function (Router $router): void {
    $router->get('/dev', [DevController::class, 'index'])->middleware(AuthMiddleware::class)->permission('dev.access');
    $router->get('/dev/diagnostics', [DevController::class, 'diagnostics'])->middleware(AuthMiddleware::class)->permission('dev.access');
};
