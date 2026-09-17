<?php
$usersCount = count($app->users->all());
$rolesCount = count($app->roles->all());
?>
<div style="margin-bottom: 2rem;">
    <h1>Painel de Administração</h1>
    <p class="muted">Gerencie o controle de acesso, contas de usuários e permissões da aplicação.</p>
</div>

<div class="grid-2">
    <a href="<?= url($app, '/admin/users') ?>" class="card card-interactive">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <span class="badge badge-info" style="margin-bottom: 0.5rem;"><?= $usersCount ?> Cadastrados</span>
                <h2>Usuários do Sistema</h2>
                <p class="muted" style="font-size: 0.9rem; margin-top: 0.35rem;">
                    Cadastre novos operadores, altere senhas, ative/desative contas e vincule papéis de acesso.
                </p>
            </div>
            <span style="font-size: 1.5rem; color: var(--primary);">&rarr;</span>
        </div>
    </a>

    <a href="<?= url($app, '/admin/roles') ?>" class="card card-interactive">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <span class="badge badge-purple" style="margin-bottom: 0.5rem;"><?= $rolesCount ?> Perfis</span>
                <h2>Perfis & Permissões</h2>
                <p class="muted" style="font-size: 0.9rem; margin-top: 0.35rem;">
                    Configure as regras de Role-Based Access Control (RBAC) e defina quais funcionalidades cada grupo pode acessar.
                </p>
            </div>
            <span style="font-size: 1.5rem; color: var(--purple);">&rarr;</span>
        </div>
    </a>
</div>