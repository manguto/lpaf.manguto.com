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
        $username = trim((string) $request->input('username'));
        $password = (string) $request->input('password');
        $throttleKey = 'login_' . md5($request->ip() . '|' . strtolower($username));

        if ($this->app->rateLimiter->tooManyAttempts($throttleKey, 5)) {
            $seconds = $this->app->rateLimiter->availableIn($throttleKey);
            $minutes = max(1, (int) ceil($seconds / 60));
            Response::redirect('/login?error=' . urlencode("Muitas tentativas falhas. Tente novamente em {$minutes} minuto(s)."));
        }

        $authService = new AuthService($this->app, new AuditService($this->app->storage));
        if (!$authService->login($username, $password)) {
            $attempts = $this->app->rateLimiter->hit($throttleKey, 300, 5);
            $remaining = max(0, 5 - $attempts);
            if ($remaining === 0) {
                Response::redirect('/login?error=' . urlencode("Conta bloqueada temporariamente por 5 minutos devido a excesso de tentativas inválidas."));
            }
            Response::redirect('/login?error=' . urlencode("Credenciais inválidas. Restam {$remaining} tentativa(s)."));
        }

        $this->app->rateLimiter->clear($throttleKey);
        Response::redirect('/app');
    }
    public function logout(Request $request): void
    {
        (new AuthService($this->app, new AuditService($this->app->storage)))->logout();
        Session::start();
        Response::redirect('/');
    }
}
