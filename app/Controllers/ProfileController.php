<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuditService;

final class ProfileController extends Controller
{
    public function show(Request $request): void
    {
        $user = $this->user();
        if (!$user) {
            Response::redirect('/login');
        }

        $roles = $this->app->roles->forUser($user['id']);

        $this->view('app/profile', [
            'profileUser' => $user,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request): void
    {
        $user = $this->user();
        if (!$user) {
            Response::redirect('/login');
        }

        $currentUser = $this->app->users->find($user['id']);
        if (!$currentUser) {
            Response::redirect('/login');
        }

        $name = trim((string) $request->input('name'));
        if ($name === '') {
            Session::flash('error', 'O nome não pode ficar em branco.');
            Response::redirect('/profile');
        }

        $currentPassword = (string) $request->input('current_password');
        $newPassword = (string) $request->input('new_password');
        $confirmPassword = (string) $request->input('new_password_confirmation');
        $passwordChanged = false;

        if ($currentPassword !== '' || $newPassword !== '' || $confirmPassword !== '') {
            if (!password_verify($currentPassword, (string) ($currentUser['password_hash'] ?? ''))) {
                Session::flash('error', 'A senha atual informada está incorreta.');
                Response::redirect('/profile');
            }

            if ($newPassword === '') {
                Session::flash('error', 'A nova senha não pode ficar em branco.');
                Response::redirect('/profile');
            }

            if ($newPassword !== $confirmPassword) {
                Session::flash('error', 'A confirmação da nova senha não confere.');
                Response::redirect('/profile');
            }

            $policyError = (new \App\Services\PasswordPolicyService($this->app))->validate($newPassword);
            if ($policyError !== null) {
                Session::flash('error', $policyError);
                Response::redirect('/profile');
            }

            $passwordChanged = true;
        }

        $data = [
            'name' => $name,
            'updated_at' => date('c'),
        ];

        if ($passwordChanged) {
            $data['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $this->app->users->update($currentUser['id'], $data);

        $auditDetail = $passwordChanged ? 'Nome e senha atualizados' : 'Nome atualizado';
        (new AuditService($this->app->storage))->log('profile_updated', $currentUser['id'], $auditDetail);

        Session::flash('message', 'Perfil atualizado com sucesso.');
        Response::redirect('/profile');
    }
}
