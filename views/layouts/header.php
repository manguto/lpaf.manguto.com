<?php
$user = $user ?? (isset($_SESSION['user_id']) ? $app->users->find($_SESSION['user_id']) : null);
$appName = $app->setting('app_name', $app->config->get('app_name'));
$initials = '';
if ($user && !empty($user['name'])) {
    $parts = explode(' ', trim((string) $user['name']));
    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($appName) ?></title>
    <link rel="stylesheet" href="<?= url($app, '/assets/css/app.css') ?>">
</head>
<body>
    <header>
        <div class="brand-wrapper">
            <a href="<?= url($app) ?>" class="brand-link">
                <?= e($appName) ?>
                <span class="brand-badge">LPAF</span>
            </a>
        </div>
        <nav>
            <?php if ($user): ?>
                <a href="<?= url($app, '/app') ?>">Aplicação</a>
                <?php if (can($app, 'users.view')): ?>
                    <a href="<?= url($app, '/admin') ?>">Administração</a>
                <?php endif; ?>
                <?php if (can($app, 'dev.access')): ?>
                    <a href="<?= url($app, '/dev') ?>">Dev-End</a>
                <?php endif; ?>
                <div class="user-profile-chip">
                    <div class="user-avatar"><?= e($initials ?: 'U') ?></div>
                    <div class="user-details">
                        <span class="user-name"><?= e($user['name']) ?></span>
                        <span class="user-role">@<?= e($user['username']) ?></span>
                    </div>
                    <form method="post" action="<?= url($app, '/logout') ?>" style="display:inline; background:transparent; border:0; padding:0; box-shadow:none; margin:0;">
                        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                        <button type="submit" class="btn btn-logout btn-sm">Sair</button>
                    </form>
                </div>
            <?php else: ?>
                <a href="<?= url($app, '/login') ?>" class="btn btn-sm" style="color:#ffffff;">Entrar</a>
            <?php endif; ?>
        </nav>
    </header>
    <main>
        <?php if ($message = App\Core\Session::flash('message')): ?>
            <div class="alert alert-success">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <span><?= e($message) ?></span>
            </div>
        <?php endif; ?>
        <?php if ($errorMsg = App\Core\Session::flash('error')): ?>
            <div class="alert alert-error">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <span><?= e($errorMsg) ?></span>
            </div>
        <?php endif; ?>