<?php

use App\Controllers\AdminController;
use App\Core\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;

return static function (Router $router): void {
    $router->get('/admin', [AdminController::class, 'index'])->middleware(AuthMiddleware::class)->permission('users.view');
    $router->get('/admin/users', [AdminController::class, 'users'])->middleware(AuthMiddleware::class)->permission('users.view');
    $router->get('/admin/users/create', [AdminController::class, 'createUser'])->middleware(AuthMiddleware::class)->permission('users.create');
    $router->post('/admin/users', [AdminController::class, 'storeUser'])->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class)->permission('users.create');
    $router->get('/admin/users/{id}/edit', [AdminController::class, 'editUser'])->middleware(AuthMiddleware::class)->permission('users.edit');
    $router->patch('/admin/users/{id}', [AdminController::class, 'updateUser'])->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class)->permission('users.edit');
    $router->get('/admin/roles', [AdminController::class, 'roles'])->middleware(AuthMiddleware::class)->permission('roles.view');
    $router->get('/admin/roles/create', [AdminController::class, 'createRole'])->middleware(AuthMiddleware::class)->permission('roles.create');
    $router->post('/admin/roles', [AdminController::class, 'storeRole'])->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class)->permission('roles.create');
    $router->get('/admin/roles/{id}/edit', [AdminController::class, 'editRole'])->middleware(AuthMiddleware::class)->permission('roles.edit');
    $router->patch('/admin/roles/{id}', [AdminController::class, 'updateRole'])->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class)->permission('roles.edit');
};
