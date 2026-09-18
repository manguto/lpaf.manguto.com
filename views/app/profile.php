<?php
$initials = '';
if (!empty($profileUser['name'])) {
    $parts = explode(' ', trim((string) $profileUser['name']));
    $initials = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
}
?>
<p><a href="<?= url($app, '/app') ?>" class="back-link">&larr; Voltar para a Aplicação</a></p>

<div style="margin-bottom: 2rem;">
    <h1>Meu Perfil</h1>
    <p class="muted">Gerencie suas informações cadastrais e credenciais de acesso à plataforma.</p>
</div>

<!-- Resumo do Perfil -->
<div class="card" style="margin-bottom: 1.5rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.25rem;">
    <div style="display: flex; align-items: center; gap: 1.25rem;">
        <div style="width: 64px; height: 64px; border-radius: var(--radius-full); background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800; text-transform: uppercase; box-shadow: var(--shadow-sm);">
            <?= e($initials ?: 'U') ?>
        </div>
        <div>
            <div style="font-size: 1.25rem; font-weight: 700; color: var(--text-main);"><?= e($profileUser['name']) ?></div>
            <div class="muted" style="font-size: 0.9rem;">@<?= e($profileUser['username']) ?> &bull; Cadastrado em <?= !empty($profileUser['created_at']) ? date('d/m/Y', strtotime($profileUser['created_at'])) : '-' ?></div>
            <div style="margin-top: 0.5rem; display: flex; gap: 0.4rem; flex-wrap: wrap;">
                <?php foreach ($roles as $role): ?>
                    <span class="badge badge-purple"><?= e($role['name']) ?></span>
                <?php endforeach; ?>
                <span class="badge badge-success">Ativo</span>
            </div>
        </div>
    </div>
    <div>
        <code style="font-size: 0.85rem; color: var(--text-muted); background: var(--bg-muted); padding: 0.35rem 0.65rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
            ID: <?= e($profileUser['id']) ?>
        </code>
    </div>
</div>

<!-- Formulário de Atualização -->
<form method="post" action="<?= url($app, '/profile') ?>">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <div class="grid-2" style="margin-bottom: 1.5rem; gap: 1.5rem;">
        <!-- Bloco 1: Dados Pessoais -->
        <div>
            <h2 style="font-size: 1.15rem; margin-bottom: 0.5rem;">Dados Cadastrais</h2>
            <p class="muted" style="font-size: 0.85rem; margin-bottom: 1rem;">Informações básicas de identificação na plataforma.</p>

            <div>
                <label for="name">Nome Completo:</label>
                <input type="text" id="name" name="name" value="<?= e($profileUser['name']) ?>" required>
            </div>

            <div>
                <label for="username">Login / Nome de Usuário:</label>
                <input type="text" id="username" value="<?= e($profileUser['username']) ?>" readonly style="background-color: var(--bg-muted); color: var(--text-muted); cursor: not-allowed;">
                <span class="muted" style="font-size: 0.775rem; display: block; margin-top: 0.25rem;">
                    O login é permanente e fixo para manter a consistência dos registros de auditoria.
                </span>
            </div>
        </div>

        <!-- Bloco 2: Segurança e Senha -->
        <div>
            <h2 style="font-size: 1.15rem; margin-bottom: 0.5rem;">Segurança e Senha</h2>
            <p class="muted" style="font-size: 0.85rem; margin-bottom: 1rem;">Preencha somente se desejar alterar sua senha de acesso.</p>

            <div>
                <label for="current_password">Senha Atual:</label>
                <input type="password" id="current_password" name="current_password" placeholder="Digite sua senha atual" autocomplete="current-password">
            </div>

            <div>
                <label for="new_password">Nova Senha:</label>
                <input type="password" id="new_password" name="new_password" placeholder="Digite a nova senha" autocomplete="new-password">
            </div>

            <div>
                <label for="new_password_confirmation">Confirmar Nova Senha:</label>
                <input type="password" id="new_password_confirmation" name="new_password_confirmation" placeholder="Repita a nova senha" autocomplete="new-password">
            </div>
        </div>
    </div>

    <div style="display: flex; gap: 0.75rem; align-items: center; border-top: 1px solid var(--border-color); padding-top: 1.25rem; margin-top: 1rem;">
        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        <a href="<?= url($app, '/app') ?>" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
