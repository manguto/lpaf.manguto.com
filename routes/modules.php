<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Router;

return static function (Router $router, ?Application $app = null): void {
    if ($app) {
        $app->modules->registerRoutes($router);
        $app->modules->ensurePermissions();
    }
};
