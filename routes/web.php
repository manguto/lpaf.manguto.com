<?php

use App\Controllers\AuthController;
use App\Controllers\ProfileController;
use App\Controllers\PublicController;
use App\Controllers\SetupController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;

return static function (Router $router): void {
    $router->get('/', [PublicController::class, 'home']);
    $router->get('/login', [AuthController::class, 'showLogin'])->middleware(GuestMiddleware::class);
    $router->post('/login', [AuthController::class, 'login'])->middleware(GuestMiddleware::class)->middleware(CsrfMiddleware::class);
    $router->post('/logout', [AuthController::class, 'logout'])->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class);
    $router->get('/setup', [SetupController::class, 'show']);
    $router->post('/setup', [SetupController::class, 'install'])->middleware(CsrfMiddleware::class);
    $router->get('/app', [PublicController::class, 'app'])->middleware(AuthMiddleware::class)->permission('dashboard.view');
    $router->get('/profile', [ProfileController::class, 'show'])->middleware(AuthMiddleware::class)->permission('profile.edit');
    $router->post('/profile', [ProfileController::class, 'update'])->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class)->permission('profile.edit');
};
