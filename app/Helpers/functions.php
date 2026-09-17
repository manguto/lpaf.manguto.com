<?php
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
function url(App\Core\Application $app, string $path = '/'): string
{
    return e($app->url($path));
}
function can(App\Core\Application $app, string $needed): bool
{
    if (empty($_SESSION['user_id'])) return false;
    $roleIds = [];
    foreach ($app->storage->read('user_roles.csv', ['user_id', 'role_id']) as $row) if ($row['user_id'] === $_SESSION['user_id']) $roleIds[] = $row['role_id'];
    foreach ($app->storage->read('role_permissions.csv', ['role_id', 'permission_id']) as $row) if (in_array($row['role_id'], $roleIds, true) && ($row['permission_id'] === '*' || $row['permission_id'] === $needed)) return true;
    return false;
}
