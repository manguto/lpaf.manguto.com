<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $name, array $data = []): void
    {
        $data += ['error' => null, 'installed' => null, 'csrf' => '', 'users' => [], 'roles' => [], 'permissions' => [], 'status' => [], 'edit' => null];
        extract($data, EXTR_SKIP);
        $root = dirname(__DIR__, 2);
        $view = $root . '/views/' . $name . '.php';
        if (!is_file($view)) Response::error(500, 'View não encontrada.');
        include $root . '/views/layouts/header.php';
        include $view;
        include $root . '/views/layouts/footer.php';
    }
}
