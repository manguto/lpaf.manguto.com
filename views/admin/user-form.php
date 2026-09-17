<?php
$edit = $edit ?? null;
$assigned = [];
if ($edit) {
    foreach ($app->storage->read('user_roles.csv', ['user_id', 'role_id']) as $row) {
        if ($row['user_id'] === $edit['id']) {
            $assigned[] = $row['role_id'];
        }
    }
}
?>
<a href="<?= url($app, '/admin/users') ?>" class="back-link">&larr; Voltar para Usuários</a>

<div class="card" style="max-width: 680px; margin: 0 auto; padding: 2.25rem;">
    <div style="margin-bottom: 1.75rem;">
        <span class="badge badge-info" style="margin-bottom: 0.5rem;"><?= $edit ? 'Edição' : 'Novo Cadastro' ?></span>
        <h1><?= $edit ? 'Editar Usuário' : 'Novo Usuário' ?></h1>
        <p class="muted"><?= $edit ? 'Atualize os dados e papéis de acesso do usuário.' : 'Preencha os campos abaixo para criar um novo acesso.' ?></p>
    </div>

    <form method="post" action="<?= url($app, $edit ? '/admin/users/' . $edit['id'] : '/admin/users') ?>" style="border: 0; padding: 0; box-shadow: none;">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <?php if ($edit): ?>
            <input type="hidden" name="_method" value="PATCH">
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem;">
            <div>
                <label for="name">Nome Completo</label>
                <input type="text" id="name" name="name" value="<?= e($edit['name'] ?? '') ?>" placeholder="Ex: João da Silva" required>
            </div>

            <div>
                <label for="username">Login de Acesso</label>
                <input type="text" id="username" name="username" value="<?= e($edit['username'] ?? '') ?>" placeholder="Ex: j.silva" pattern="[a-zA-Z0-9._-]{3,30}" title="O login deve conter entre 3 e 30 caracteres (letras, números, '.', '_' ou '-'), sem @" <?= $edit ? 'readonly style="background-color: var(--bg-muted); cursor: not-allowed;"' : 'required' ?>>
            </div>
        </div>

        <div style="margin-top: 1rem;">
            <label for="password">Senha <?= $edit ? '<span class="muted" style="font-weight: normal;">(deixe em branco para não alterar)</span>' : '' ?></label>
            <input type="password" id="password" name="password" minlength="8" placeholder="Mínimo 8 caracteres" <?= $edit ? '' : 'required' ?>>
        </div>

        <?php if ($edit): ?>
            <div style="margin-top: 1.25rem; padding: 0.75rem 1rem; background: var(--bg-muted); border-radius: var(--radius-sm);">
                <label style="margin: 0; display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" name="active" value="1" <?= ($edit['active'] ?? '') === '1' ? 'checked' : '' ?>>
                    <span>Conta ativa (permite login no sistema)</span>
                </label>
            </div>
        <?php endif; ?>

        <div style="margin-top: 1.5rem;">
            <label style="margin-bottom: 0.5rem;">Perfis de Acesso</label>
            <div class="checkbox-group">
                <?php foreach ($roles as $role): ?>
                    <label>
                        <input type="checkbox" name="roles[]" value="<?= e($role['id']) ?>" <?= in_array($role['id'], $assigned, true) ? 'checked' : '' ?>>
                        <strong><?= e($role['name']) ?></strong>
                        <span class="muted" style="margin-left: 0.5rem; font-size: 0.8rem;">(<?= e($role['description']) ?>)</span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 2rem; justify-content: flex-end;">
            <a href="<?= url($app, '/admin/users') ?>" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <?= $edit ? 'Salvar Alterações' : 'Criar Usuário' ?>
            </button>
        </div>
    </form>
</div>