<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Config;
use App\Core\CsvStorage;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/app/Helpers/functions.php';
$config = new Config(dirname(__DIR__));
Session::start();
$request = new Request();
Response::setBasePath($request->basePath());
$app = new Application($config, new CsvStorage($config), $request);
$router = new Router($request);
foreach (['web.php', 'admin.php', 'dev.php'] as $file) (require dirname(__DIR__) . '/routes/' . $file)($router);
if (!$app->installed() && $request->path() !== '/setup') Response::redirect('/setup');
try {
    $router->dispatch($app);
} catch (Throwable $exception) {
    if ($config->get('app_debug')) {
        http_response_code(500);
        echo '<pre>' . e($exception) . '</pre>';
    } else Response::error(500, 'Erro interno.');
}
