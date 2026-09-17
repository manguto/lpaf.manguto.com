<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\SetupService;

final class SetupController extends Controller
{
    public function show(Request $request): void
    {
        if ($this->app->installed()) Response::error(404, 'Setup já concluído.');
        $this->view('auth/setup');
    }
    public function install(Request $request): void
    {
        if ($this->app->installed()) Response::error(404, 'Setup já concluído.');
        $key = trim((string) $request->input('setup_key'));
        $required = (string) $this->app->config->get('app_setup_key');
        if ($required !== '' && !hash_equals($required, $key)) {
            View::render('auth/setup', ['error' => 'Chave de setup inválida.', 'app' => $this->app, 'csrf' => $_SESSION['_csrf']]);
            return;
        }
        $name = trim((string) $request->input('name'));
        $username = trim((string) $request->input('username'));
        $password = (string) $request->input('password');
        if (str_contains($username, '@') || !preg_match('/^[a-zA-Z0-9._-]{3,30}$/', $username)) {
            View::render('auth/setup', ['error' => 'O login não pode ser um e-mail. Utilize entre 3 e 30 caracteres (letras, números, ponto, traço ou sublinhado).', 'app' => $this->app, 'csrf' => $_SESSION['_csrf']]);
            return;
        }
        if ($name === '' || $username === '' || strlen($password) < 8 || $password !== (string) $request->input('password_confirmation')) {
            View::render('auth/setup', ['error' => 'Preencha os campos e use uma senha com pelo menos 8 caracteres.', 'app' => $this->app, 'csrf' => $_SESSION['_csrf']]);
            return;
        }
        (new SetupService($this->app))->install((string) $request->input('app_name'), $name, $username, $password);
        Response::redirect('/login?installed=1');
    }
}
