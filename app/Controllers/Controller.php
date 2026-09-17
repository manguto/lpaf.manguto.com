<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Application;
use App\Core\Session;
use App\Core\View;

abstract class Controller
{
    public function __construct(protected Application $app) {}
    protected function view(string $name, array $data = []): void
    {
        $data['app'] = $this->app;
        $data['csrf'] = $_SESSION['_csrf'] ?? '';
        View::render($name, $data);
    }
    protected function user(): ?array
    {
        return isset($_SESSION['user_id']) ? $this->app->users->find((string) $_SESSION['user_id']) : null;
    }
}
