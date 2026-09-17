<?php

use App\Controllers\DevController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;

return static function (Router $router): void {
    $router->get('/dev', [DevController::class, 'index'])->middleware(AuthMiddleware::class)->permission('dev.access');
    $router->get('/dev/diagnostics', [DevController::class, 'diagnostics'])->middleware(AuthMiddleware::class)->permission('dev.access');
    $router->get('/dev/backups', [DevController::class, 'backups'])->middleware(AuthMiddleware::class)->permission('dev.access');
    $router->post('/dev/backups', [DevController::class, 'createBackup'])->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class)->permission('dev.access');
    $router->post('/dev/backups/{id}/restore', [DevController::class, 'restoreBackup'])->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class)->permission('dev.access');
    $router->get('/dev/backups/{id}/download', [DevController::class, 'downloadBackup'])->middleware(AuthMiddleware::class)->permission('dev.access');
    $router->get('/dev/logs', [DevController::class, 'logs'])->middleware(AuthMiddleware::class)->permission('dev.logs');
};
