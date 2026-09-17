<?php $user = $user ?? (isset($_SESSION['user_id']) ? $app->users->find($_SESSION['user_id']) : null); ?>
<!doctype html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($app->setting('app_name', $app->config->get('app_name'))) ?></title>
    <link rel="stylesheet" href="<?= url($app, '/assets/css/app.css') ?>">
</head>

<body>
    <header><strong><a href="<?= url($app) ?>"> <?= e($app->setting('app_name', $app->config->get('app_name'))) ?></a></strong>
        <nav><?php if ($user): ?><a href="<?= url($app, '/app') ?>">Aplicação</a><?php if (can($app, 'users.view')): ?><a href="<?= url($app, '/admin') ?>">Administração</a><?php endif; ?><?php if (can($app, 'dev.access')): ?><a href="<?= url($app, '/dev') ?>">Dev-End</a><?php endif; ?><form method="post" action="<?= url($app, '/logout') ?>" style="display:inline;background:transparent;border:0;padding:0"><input type="hidden" name="_csrf" value="<?= e($csrf) ?>"><button type="submit">Sair</button></form><?php else: ?><a href="<?= url($app, '/login') ?>">Entrar</a><?php endif; ?></nav>
    </header>
    <main><?php if ($message = App\Core\Session::flash('message')): ?><p class="success"><?= e($message) ?></p><?php endif; ?>