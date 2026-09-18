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
    $router->get('/dev/modules', [DevController::class, 'modules'])->middleware(AuthMiddleware::class)->permission('dev.access');
    $router->post('/dev/seed', [DevController::class, 'seedDatabase'])->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class)->permission('dev.access');
    $router->post('/dev/demo/clear', [DevController::class, 'clearDemo'])->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class)->permission('dev.access');
    $router->get('/dev/entity-builder', [DevController::class, 'entityBuilder'])->middleware(AuthMiddleware::class)->permission('dev.access');
    $router->post('/dev/entity-builder', [DevController::class, 'storeEntity'])->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class)->permission('dev.access');
    $router->get('/dev/modules/{slug}/edit', [DevController::class, 'editEntity'])->middleware(AuthMiddleware::class)->permission('dev.access');
    $router->post('/dev/modules/{slug}/edit', [DevController::class, 'updateEntity'])->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class)->permission('dev.access');
    $router->post('/dev/modules/{slug}/delete', [DevController::class, 'deleteModule'])->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class)->permission('dev.access');
};
