<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;

final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Application $app): void
    {
        if (empty($_SESSION['user_id'])) Response::redirect('/login');
    }
}
