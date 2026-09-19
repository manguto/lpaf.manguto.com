<?php
$user = $user ?? (isset($_SESSION['user_id']) ? $app->users->find($_SESSION['user_id']) : null);
$appName = $app->setting('app_name', $app->config->get('app_name'));
$initials = '';
if ($user && !empty($user['name'])) {
    $parts = explode(' ', trim((string) $user['name']));
    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
}

$currentPath = $app->request->path();

// Detecção das áreas ativas para o menu principal e submenu horizontal
$isDevArea = str_starts_with($currentPath, '/dev');
$isAdminArea = str_starts_with($currentPath, '/admin');
$isAppArea = str_starts_with($currentPath, '/app') || $currentPath === '/profile';

$subnavItems = [];
$subnavTitle = '';

if ($user) {
    if ($isDevArea && can($app, 'dev.access')) {
        $subnavTitle = 'Dev-End';
        $subnavItems = [
            ['label' => 'Painel Geral', 'icon' => '⚡', 'url' => '/dev', 'active' => $currentPath === '/dev'],
            ['label' => 'Diagnóstico', 'icon' => '🩺', 'url' => '/dev/diagnostics', 'active' => str_starts_with($currentPath, '/dev/diagnostics')],
            ['label' => 'Backups', 'icon' => '💾', 'url' => '/dev/backups', 'active' => str_starts_with($currentPath, '/dev/backups')],
            ['label' => 'Logs & Auditoria', 'icon' => '📜', 'url' => '/dev/logs', 'active' => str_starts_with($currentPath, '/dev/logs')],
            ['label' => 'Módulos & Seed', 'icon' => '🌱', 'url' => '/dev/modules', 'active' => str_starts_with($currentPath, '/dev/modules')],
            ['label' => 'Entity Builder', 'icon' => '🏗️', 'url' => '/dev/entity-builder', 'active' => str_starts_with($currentPath, '/dev/entity-builder')],
            ['label' => 'Política de Senhas', 'icon' => '🔒', 'url' => '/dev/password-policy', 'active' => str_starts_with($currentPath, '/dev/password-policy')],
        ];
    } elseif ($isAdminArea && (can($app, 'users.view') || can($app, 'roles.view'))) {
        $subnavTitle = 'Administração';
        $subnavItems = [
            ['label' => 'Painel Geral', 'icon' => '📊', 'url' => '/admin', 'active' => $currentPath === '/admin'],
        ];
        if (can($app, 'users.view')) {
            $subnavItems[] = ['label' => 'Usuários', 'icon' => '👥', 'url' => '/admin/users', 'active' => str_starts_with($currentPath, '/admin/users')];
        }
        if (can($app, 'roles.view')) {
            $subnavItems[] = ['label' => 'Perfis de Acesso', 'icon' => '🛡️', 'url' => '/admin/roles', 'active' => str_starts_with($currentPath, '/admin/roles')];
        }
    } elseif ($isAppArea) {
        $subnavTitle = 'Aplicação';
        $subnavItems = [
            ['label' => 'Visão Geral', 'icon' => '📊', 'url' => '/app', 'active' => $currentPath === '/app'],
        ];

        // Módulos dinâmicos da aplicação (com verificação de permissão RBAC)
        foreach ($app->modules->all() as $slug => $module) {
            $perm = ($module['permission_prefix'] ?? $slug) . '.view';
            if (can($app, $perm)) {
                $subnavItems[] = [
                    'label' => $module['name'] ?? ucfirst($slug),
                    'icon' => $module['icon'] ?? '📁',
                    'url' => '/app/' . $slug,
                    'active' => str_starts_with($currentPath, '/app/' . $slug),
                ];
            }
        }

        if ($currentPath === '/profile') {
            $subnavItems[] = [
                'label' => 'Meu Perfil',
                'icon' => '👤',
                'url' => '/profile',
                'active' => true,
            ];
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($appName) ?></title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>⚡</text></svg>">
    <link rel="stylesheet" href="<?= url($app, '/assets/css/app.css') ?>">
</head>
<body>
    <header>
        <div class="header-main">
            <div class="brand-wrapper">
                <a href="<?= url($app) ?>" class="brand-link">
                    <?= e($appName) ?>
                    <span class="brand-badge">LPAF</span>
                </a>
            </div>
            <nav>
                <?php if ($user): ?>
                    <a href="<?= url($app, '/app') ?>" class="<?= $isAppArea ? 'nav-link-active' : '' ?>">Aplicação</a>
                    <?php if (can($app, 'users.view')): ?>
                        <a href="<?= url($app, '/admin') ?>" class="<?= $isAdminArea ? 'nav-link-active' : '' ?>">Administração</a>
                    <?php endif; ?>
                    <?php if (can($app, 'dev.access')): ?>
                        <a href="<?= url($app, '/dev') ?>" class="<?= $isDevArea ? 'nav-link-active' : '' ?>">Dev-End</a>
                    <?php endif; ?>
                    <div class="user-profile-chip">
                        <a href="<?= url($app, '/profile') ?>" class="user-profile-link" title="Meu Perfil">
                            <div class="user-avatar"><?= e($initials ?: 'U') ?></div>
                            <div class="user-details">
                                <span class="user-name"><?= e($user['name']) ?></span>
                                <span class="user-role">@<?= e($user['username']) ?></span>
                            </div>
                        </a>
                        <form method="post" action="<?= url($app, '/logout') ?>" style="display:inline; background:transparent; border:0; padding:0; box-shadow:none; margin:0;">
                            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                            <button type="submit" class="btn btn-logout btn-sm">Sair</button>
                        </form>
                    </div>
                <?php else: ?>
                    <a href="<?= url($app, '/login') ?>" class="btn btn-sm" style="color:#ffffff;">Entrar</a>
                <?php endif; ?>
            </nav>
        </div>
        <?php if (!empty($subnavItems)): ?>
            <nav class="subnav" aria-label="Submenu <?= e($subnavTitle) ?>">
                <div class="subnav-container">
                    <div class="subnav-list">
                        <?php foreach ($subnavItems as $item): ?>
                            <a href="<?= url($app, $item['url']) ?>" class="subnav-item <?= $item['active'] ? 'active' : '' ?>" <?= $item['active'] ? 'aria-current="page"' : '' ?>>
                                <span class="subnav-icon"><?= $item['icon'] ?></span>
                                <span class="subnav-label"><?= e($item['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div class="subnav-area-badge">
                        <span><?= e($subnavTitle) ?></span>
                    </div>
                </div>
            </nav>
        <?php endif; ?>
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