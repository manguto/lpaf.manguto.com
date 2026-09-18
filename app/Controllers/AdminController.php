<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuditService;

final class AdminController extends Controller
{
    public function index(Request $request): void
    {
        $this->view('admin/index');
    }
    public function users(Request $request): void
    {
        $this->view('admin/users', ['users' => $this->app->users->all(), 'roles' => $this->app->roles->all()]);
    }
    public function createUser(Request $request): void
    {
        $this->view('admin/user-form', ['roles' => $this->app->roles->all()]);
    }
    public function storeUser(Request $request): void
    {
        $username = trim((string) $request->input('username'));
        if (str_contains($username, '@') || !preg_match('/^[a-zA-Z0-9._-]{3,30}$/', $username)) {
            \App\Core\Session::flash('error', 'O login não pode ser um e-mail. Utilize entre 3 e 30 caracteres (letras, números, ponto, traço ou sublinhado).');
            Response::redirect('/admin/users/create');
        }
        if ($this->app->users->findByUsername($username)) {
            \App\Core\Session::flash('error', 'Este login já está cadastrado.');
            Response::redirect('/admin/users/create');
        }
        $now = date('c');
        $user = $this->app->users->insert(['id' => $this->app->users->nextId(), 'name' => trim((string) $request->input('name')), 'username' => $username, 'password_hash' => password_hash((string) $request->input('password'), PASSWORD_DEFAULT), 'active' => '1', 'created_at' => $now, 'updated_at' => $now]);
        $relations = new \App\Repositories\RelationRepository($this->app->storage, 'user_roles.csv', ['user_id', 'role_id']);
        foreach ($this->allowedRoles((array) $request->input('roles', [])) as $role) $relations->add(['user_id' => $user['id'], 'role_id' => $role]);
        (new AuditService($this->app->storage))->log('user_created', $this->user()['id'], $user['id']);
        Response::redirect('/admin/users');
    }
    public function editUser(Request $request, array $params): void
    {
        $this->view('admin/user-form', ['edit' => $this->app->users->find($params['id']), 'roles' => $this->app->roles->all()]);
    }
    public function updateUser(Request $request, array $params): void
    {
        $data = ['name' => trim((string) $request->input('name')), 'active' => $request->input('active') ? '1' : '0', 'updated_at' => date('c')];
        $newPass = (string) $request->input('password');
        if ($newPass !== '') $data['password_hash'] = password_hash($newPass, PASSWORD_DEFAULT);
        $this->app->users->update($params['id'], $data);
        $rel = new \App\Repositories\RelationRepository($this->app->storage, 'user_roles.csv', ['user_id', 'role_id']);
        $rows = array_values(array_filter($rel->all(), fn($row) => $row['user_id'] !== $params['id']));
        foreach ($this->allowedRoles((array) $request->input('roles', [])) as $role) $rows[] = ['user_id' => $params['id'], 'role_id' => $role];
        $rel->replace($rows);
        (new AuditService($this->app->storage))->log('user_updated', $this->user()['id'], $params['id']);
        Response::redirect('/admin/users');
    }
    public function roles(Request $request): void
    {
        $this->view('admin/roles', ['roles' => $this->app->roles->all()]);
    }
    public function createRole(Request $request): void
    {
        $this->view('admin/role-form', ['permissions' => $this->app->permissions->all()]);
    }
    public function storeRole(Request $request): void
    {
        $id = 'role_' . preg_replace('/[^a-z0-9]+/i', '_', strtolower((string) $request->input('name')));
        $this->app->roles->insert(['id' => $id, 'name' => trim((string) $request->input('name')), 'description' => trim((string) $request->input('description')), 'protected' => '0', 'created_at' => date('c'), 'updated_at' => date('c')]);
        $this->saveRolePermissions($id, (array) $request->input('permissions', []));
        (new AuditService($this->app->storage))->log('role_created', $this->user()['id'], $id);
        Response::redirect('/admin/roles');
    }
    public function editRole(Request $request, array $params): void
    {
        $this->view('admin/role-form', ['edit' => $this->app->roles->find($params['id']), 'permissions' => $this->app->permissions->all()]);
    }
    public function updateRole(Request $request, array $params): void
    {
        if ($params['id'] === 'role_dev' && !can($this->app, '*')) \App\Core\Response::error(403, 'Somente Desenvolvedor pode alterar esse perfil.');
        if ($params['id'] !== 'role_dev') $this->app->roles->update($params['id'], ['name' => trim((string) $request->input('name')), 'description' => trim((string) $request->input('description')), 'updated_at' => date('c')]);
        $this->saveRolePermissions($params['id'], (array) $request->input('permissions', []));
        (new AuditService($this->app->storage))->log('role_updated', $this->user()['id'], $params['id']);
        Response::redirect('/admin/roles');
    }
    private function saveRolePermissions(string $roleId, array $permissions): void
    {
        $rel = new \App\Repositories\RelationRepository($this->app->storage, 'role_permissions.csv', ['role_id', 'permission_id']);
        $rows = array_values(array_filter($rel->all(), fn($row) => $row['role_id'] !== $roleId));
        foreach ($permissions as $permission) $rows[] = ['role_id' => $roleId, 'permission_id' => $permission];
        $rel->replace($rows);
    }
    private function allowedRoles(array $roles): array
    {
        return can($this->app, '*') ? $roles : array_values(array_filter($roles, fn($role) => $role !== 'role_dev'));
    }
}
