<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuditService;
use App\Services\AuthService;

final class AuthController extends Controller
{
    public function showLogin(Request $request): void
    {
        $this->view('auth/login', ['error' => $request->input('error'), 'installed' => $request->input('installed')]);
    }
    public function login(Request $request): void
    {
        if (!(new AuthService($this->app, new AuditService($this->app->storage)))->login(trim((string) $request->input('username')), (string) $request->input('password'))) {
            Response::redirect('/login?error=Credenciais inválidas.');
        }
        Response::redirect('/app');
    }
    public function logout(Request $request): void
    {
        (new AuthService($this->app, new AuditService($this->app->storage)))->logout();
        Session::start();
        Response::redirect('/');
    }
}
