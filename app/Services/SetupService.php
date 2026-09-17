<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Repositories\SettingsRepository;

final class SetupService
{
    public function __construct(private Application $app) {}
    public function install(string $appName, string $name, string $username, string $password): void
    {
        $now = date('c');
        $storage = $this->app->storage;
        $permissions = ['*', 'dashboard.view', 'profile.edit', 'users.view', 'users.create', 'users.edit', 'users.manage', 'roles.view', 'roles.create', 'roles.edit', 'roles.manage', 'settings.manage', 'audit.view', 'dev.access', 'dev.logs', 'dev.settings'];
        $permissionRows = [];
        foreach ($permissions as $id) $permissionRows[] = ['id' => $id, 'name' => $id, 'description' => $id === '*' ? 'Acesso integral' : 'Permissão ' . $id];
        $storage->write('permissions.csv', ['id', 'name', 'description'], $permissionRows);
        $roles = [['id' => 'role_user', 'name' => 'Usuário', 'description' => 'Acesso básico', 'protected' => '1', 'created_at' => $now, 'updated_at' => $now], ['id' => 'role_admin', 'name' => 'Administrador', 'description' => 'Acesso administrativo', 'protected' => '1', 'created_at' => $now, 'updated_at' => $now], ['id' => 'role_dev', 'name' => 'Desenvolvedor', 'description' => 'Acesso integral', 'protected' => '1', 'created_at' => $now, 'updated_at' => $now]];
        $storage->write('roles.csv', ['id', 'name', 'description', 'protected', 'created_at', 'updated_at'], $roles);
        $storage->write('users.csv', ['id', 'name', 'username', 'password_hash', 'active', 'created_at', 'updated_at'], [['id' => 'usr_001', 'name' => $name, 'username' => $username, 'password_hash' => password_hash($password, PASSWORD_DEFAULT), 'active' => '1', 'created_at' => $now, 'updated_at' => $now]]);
        $storage->write('user_roles.csv', ['user_id', 'role_id'], [['user_id' => 'usr_001', 'role_id' => 'role_dev']]);
        $rolePermissions = [['role_id' => 'role_dev', 'permission_id' => '*']];
        foreach (['dashboard.view', 'profile.edit'] as $permission) $rolePermissions[] = ['role_id' => 'role_user', 'permission_id' => $permission];
        foreach (['dashboard.view', 'profile.edit', 'users.view', 'users.create', 'users.edit', 'users.manage', 'roles.view', 'roles.create', 'roles.edit', 'roles.manage', 'audit.view'] as $permission) $rolePermissions[] = ['role_id' => 'role_admin', 'permission_id' => $permission];
        $storage->write('role_permissions.csv', ['role_id', 'permission_id'], $rolePermissions);
        $storage->write('settings.csv', ['key', 'value'], [['key' => 'installed', 'value' => '1'], ['key' => 'app_name', 'value' => trim($appName)], ['key' => 'app_version', 'value' => '0.1.0']]);
        $storage->write('audit_log.csv', ['id', 'created_at', 'user_id', 'action', 'details'], []);
    }
}
