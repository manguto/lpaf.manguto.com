<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Application;
use App\Core\Request;
use App\Core\Response;

final class CsrfMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Application $app): void
    {
        if (!isset($_SESSION['_csrf'])) $_SESSION['_csrf'] = bin2hex(random_bytes(16));
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true) && !hash_equals($_SESSION['_csrf'], (string) $request->input('_csrf'))) Response::error(419, 'Token CSRF inválido.');
    }
}
