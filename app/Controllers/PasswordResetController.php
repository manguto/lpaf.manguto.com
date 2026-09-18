<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\PasswordPolicyService;
use App\Services\PasswordResetService;

final class PasswordResetController extends Controller
{
    public function showForgot(Request $request): void
    {
        $this->view('auth/forgot-password', [
            'error' => null,
            'success' => false,
            'devLink' => null,
            'username' => '',
        ]);
    }

    public function sendResetLink(Request $request): void
    {
        $throttleKey = 'forgot_pass_' . md5($request->ip());

        if ($this->app->rateLimiter->tooManyAttempts($throttleKey, 5)) {
            $seconds = $this->app->rateLimiter->availableIn($throttleKey);
            $minutes = max(1, (int) ceil($seconds / 60));
            $this->view('auth/forgot-password', [
                'error' => "Muitas solicitações recentes. Tente novamente em {$minutes} minuto(s).",
                'success' => false,
                'devLink' => null,
                'username' => (string) $request->input('username', ''),
            ]);
            return;
        }

        $username = trim((string) $request->input('username', ''));

        if ($username === '') {
            $this->view('auth/forgot-password', [
                'error' => 'Por favor, informe seu nome de usuário ou login.',
                'success' => false,
                'devLink' => null,
                'username' => '',
            ]);
            return;
        }

        $this->app->rateLimiter->hit($throttleKey, 900, 5);

        $service = new PasswordResetService($this->app);
        $result = $service->createToken($username, $request->ip());

        $isLocal = ($this->app->config->get('app_env') === 'local');
        $devLink = ($isLocal && $result !== null) ? $result['url'] : null;

        $this->view('auth/forgot-password', [
            'error' => null,
            'success' => true,
            'devLink' => $devLink,
            'username' => $username,
        ]);
    }

    public function showReset(Request $request): void
    {
        $token = trim((string) $request->input('token', ''));
        $service = new PasswordResetService($this->app);
        $data = $service->validateToken($token);
        $policy = new PasswordPolicyService($this->app);
        $rules = $policy->getRulesSummary();

        if (!$data) {
            $this->view('auth/reset-password', [
                'invalid' => true,
                'token' => $token,
                'user' => null,
                'rules' => $rules,
                'error' => 'O link de recuperação é inválido, expirou ou já foi utilizado.',
            ]);
            return;
        }

        $this->view('auth/reset-password', [
            'invalid' => false,
            'token' => $token,
            'user' => $data['user'],
            'rules' => $rules,
            'error' => null,
        ]);
    }

    public function resetPassword(Request $request): void
    {
        $token = trim((string) $request->input('token', ''));
        $password = (string) $request->input('password', '');
        $confirm = (string) $request->input('password_confirmation', '');

        $service = new PasswordResetService($this->app);
        $data = $service->validateToken($token);
        $policy = new PasswordPolicyService($this->app);
        $rules = $policy->getRulesSummary();

        if (!$data) {
            $this->view('auth/reset-password', [
                'invalid' => true,
                'token' => $token,
                'user' => null,
                'rules' => $rules,
                'error' => 'O link de recuperação é inválido, expirou ou já foi utilizado.',
            ]);
            return;
        }

        if ($password === '') {
            $this->view('auth/reset-password', [
                'invalid' => false,
                'token' => $token,
                'user' => $data['user'],
                'rules' => $rules,
                'error' => 'Por favor, digite a nova senha.',
            ]);
            return;
        }

        if ($password !== $confirm) {
            $this->view('auth/reset-password', [
                'invalid' => false,
                'token' => $token,
                'user' => $data['user'],
                'rules' => $rules,
                'error' => 'A confirmação de senha não confere com a nova senha informada.',
            ]);
            return;
        }

        $result = $service->resetPassword($token, $password);

        if (!$result['success']) {
            $this->view('auth/reset-password', [
                'invalid' => false,
                'token' => $token,
                'user' => $data['user'],
                'rules' => $rules,
                'error' => $result['error'],
            ]);
            return;
        }

        Session::flash('message', 'Sua senha foi redefinida com sucesso! Faça login com a nova senha.');
        Response::redirect('/login?reset=1');
    }
}
