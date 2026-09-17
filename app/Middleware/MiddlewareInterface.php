<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Application;
use App\Core\Request;

interface MiddlewareInterface
{
    public function handle(Request $request, Application $app): void;
}
