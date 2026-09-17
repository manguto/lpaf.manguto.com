<?php
$usersCount = count($app->users->all());
$rolesCount = count($app->roles->all());
?>
<div style="margin-bottom: 2rem;">
    <h1>Painel de Controle</h1>
    <p class="muted">Bem-vindo(a) de volta, <strong><?= e($user['name'] ?? 'Usuário') ?></strong>. Visão geral do seu ambiente.</p>
</div>

<div class="grid-3" style="margin-bottom: 2rem;">
    <div class="card" style="margin-bottom: 0;">
        <span class="badge badge-info" style="margin-bottom: 0.5rem;">Cadastros</span>
        <div style="font-size: 2rem; font-weight: 800; color: #0f172a; line-height: 1.2;"><?= $usersCount ?></div>
        <div class="muted" style="font-size: 0.85rem; margin-top: 0.25rem;">Usuários cadastrados no sistema</div>
    </div>

    <div class="card" style="margin-bottom: 0;">
        <span class="badge badge-purple" style="margin-bottom: 0.5rem;">Segurança</span>
        <div style="font-size: 2rem; font-weight: 800; color: #0f172a; line-height: 1.2;"><?= $rolesCount ?></div>
        <div class="muted" style="font-size: 0.85rem; margin-top: 0.25rem;">Perfis de acesso configurados</div>
    </div>

    <div class="card" style="margin-bottom: 0;">
        <span class="badge badge-success" style="margin-bottom: 0.5rem;">Ambiente</span>
        <div style="font-size: 2rem; font-weight: 800; color: #0f172a; line-height: 1.2;">Online</div>
        <div class="muted" style="font-size: 0.85rem; margin-top: 0.25rem;">Persistência CSV íntegra</div>
    </div>
</div>

<h2>Acesso Rápido</h2>
<div class="grid-2" style="margin-bottom: 2rem;">
    <?php if (can($app, 'users.view')): ?>
        <a href="<?= url($app, '/admin') ?>" class="card card-interactive">
            <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                <div>
                    <h3 style="color: var(--primary);">Administração do Sistema</h3>
                    <p class="muted" style="font-size: 0.875rem; margin-top: 0.35rem;">
                        Gerencie contas de usuários, redefina senhas e configure perfis com controle de acesso granular.
                    </p>
                </div>
                <span style="font-size: 1.5rem; color: var(--primary);">&rarr;</span>
            </div>
        </a>
    <?php endif; ?>

    <?php if (can($app, 'dev.access')): ?>
        <a href="<?= url($app, '/dev') ?>" class="card card-interactive">
            <div style="display: flex; align-items: flex-start; justify-content: space-between;">
                <div>
                    <h3 style="color: #7c3aed;">Dev-End</h3>
                    <p class="muted" style="font-size: 0.875rem; margin-top: 0.35rem;">
                        Acesse ferramentas estruturais: geração e restauração de backups, diagnósticos e trilha de auditoria.
                    </p>
                </div>
                <span style="font-size: 1.5rem; color: #7c3aed;">&rarr;</span>
            </div>
        </a>
    <?php endif; ?>
</div>

<?php
$modules = $app->modules->all();
?>
<?php if (!empty($modules)): ?>
    <h2>Módulos da Aplicação</h2>
    <div class="grid-2">
        <?php foreach ($modules as $slug => $mod): ?>
            <?php
            $perm = ($mod['permission_prefix'] ?? $slug) . '.view';
            if (!can($app, $perm)) continue;
            $repo = $app->modules->repository($slug);
            $count = $repo ? $repo->count() : 0;
            ?>
            <a href="<?= url($app, '/app/' . $slug) ?>" class="card card-interactive">
                <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 1rem;">
                    <div>
                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.35rem;">
                            <span style="font-size: 1.4rem;"><?= e($mod['icon'] ?? '📁') ?></span>
                            <h3 style="margin-bottom: 0; color: var(--text-main);"><?= e($mod['name']) ?></h3>
                            <span class="badge badge-info" style="font-size: 0.75rem;"><?= $count ?> <?= $count === 1 ? 'registro' : 'registros' ?></span>
                        </div>
                        <p class="muted" style="font-size: 0.875rem;">
                            <?= e($mod['description'] ?: "Gestão declarativa de {$mod['entity']}.") ?>
                        </p>
                    </div>
                    <span style="font-size: 1.5rem; color: var(--primary);">&rarr;</span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>