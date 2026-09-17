<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;

final class DevController extends Controller
{
    public function index(Request $request): void
    {
        $this->view('dev/index');
    }
    public function diagnostics(Request $request): void
    {
        $files = ['users.csv', 'roles.csv', 'permissions.csv', 'user_roles.csv', 'role_permissions.csv', 'settings.csv'];
        $status = [];
        foreach ($files as $file) $status[$file] = ['exists' => $this->app->storage->exists($file), 'rows' => count($this->app->storage->read($file))];
        $this->view('dev/diagnostics', ['status' => $status]);
    }
}
