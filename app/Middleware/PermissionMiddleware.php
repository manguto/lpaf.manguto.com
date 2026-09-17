<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;

final class PermissionMiddleware implements MiddlewareInterface
{
    public function __construct(private string $permission) {}
    public function handle(Request $request, Application $app): void
    {
        if (empty($_SESSION['user_id'])) Response::redirect('/login');
        $userId = (string) $_SESSION['user_id'];
        $roles = $app->storage->read('user_roles.csv', ['user_id', 'role_id']);
        $roleIds = [];
        foreach ($roles as $row) if ($row['user_id'] === $userId) $roleIds[] = $row['role_id'];
        $rolePermissions = $app->storage->read('role_permissions.csv', ['role_id', 'permission_id']);
        $permissionIds = [];
        foreach ($rolePermissions as $row) if (in_array($row['role_id'], $roleIds, true)) $permissionIds[] = $row['permission_id'];
        foreach ($app->permissions->all() as $permission) if (in_array($permission['id'], $permissionIds, true) && ($permission['name'] === '*' || $permission['name'] === $this->permission)) return;
        Response::error(403, 'Acesso negado.');
    }
}
