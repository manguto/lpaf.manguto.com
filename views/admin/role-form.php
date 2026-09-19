<?php
$edit = $edit ?? null;
$assigned = [];
if ($edit) {
    foreach ($app->storage->read('role_permissions.csv', ['role_id', 'permission_id']) as $row) {
        if ($row['role_id'] === $edit['id']) {
            $assigned[] = $row['permission_id'];
        }
    }
}
?>
<a href="<?= url($app, '/admin/roles') ?>" class="back-link">&larr; Voltar para Perfis</a>

<div class="card" style="max-width: 680px; margin: 0 auto; padding: 2.25rem;">
    <div style="margin-bottom: 1.75rem;">
        <span class="badge badge-purple" style="margin-bottom: 0.5rem;"><?= $edit ? 'Edição' : 'Novo Perfil' ?></span>
        <h1><?= $edit ? 'Editar Perfil' : 'Novo Perfil' ?></h1>
        <p class="muted"><?= $edit ? 'Altere as permissões atribuídas a este papel de acesso.' : 'Defina o nome do perfil e selecione as permissões permitidas.' ?></p>
    </div>

    <form method="post" action="<?= url($app, $edit ? '/admin/roles/' . $edit['id'] : '/admin/roles') ?>" style="border: 0; padding: 0; box-shadow: none;">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <?php if ($edit): ?>
            <input type="hidden" name="_method" value="PATCH">
        <?php endif; ?>

        <label for="name">Nome do Perfil</label>
        <input type="text" id="name" name="name" value="<?= e($edit['name'] ?? '') ?>" placeholder="Ex: Supervisor" <?= $edit && $edit['id'] === 'role_dev' ? 'readonly style="background-color: var(--bg-muted); cursor: not-allowed;"' : 'required' ?>>

        <label for="description">Descrição</label>
        <textarea id="description" name="description" rows="2" placeholder="Finalidade deste perfil"><?= e($edit['description'] ?? '') ?></textarea>

        <div style="margin-top: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <label style="margin: 0;">Permissões de Acesso</label>
                <span class="muted" style="font-size: 0.8rem;">Clique em <strong>Ver tela ↗</strong> para inspecionar a rota prática</span>
            </div>
            <div class="checkbox-group" style="max-height: 320px; padding: 0.5rem;">
                <?php foreach ($permissions as $permission): 
                    $targetPath = $app->permissions->resolveRoute($permission['id'], $app);
                    $targetUrl = $targetPath ? url($app, $targetPath) : null;
                    $desc = $app->permissions->friendlyDescription($permission['id'], $permission['description'] ?? '', $app);
                ?>
                    <div class="permission-item">
                        <label>
                            <input type="checkbox" name="permissions[]" value="<?= e($permission['id']) ?>" <?= in_array($permission['id'], $assigned, true) ? 'checked' : '' ?>>
                            <code><?= e($permission['name']) ?></code>
                            <span class="muted" style="font-size: 0.8rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= e($desc) ?>">
                                (<?= e($desc) ?>)
                            </span>
                        </label>
                        <?php if ($targetUrl): ?>
                            <a href="<?= e($targetUrl) ?>" 
                               target="_blank" 
                               rel="noopener noreferrer" 
                               title="Abrir rota prática em nova aba: <?= e($targetPath) ?>" 
                               class="permission-preview-btn">
                                <span>Ver tela</span>
                                <span style="font-size: 0.85em;">↗</span>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="display: flex; gap: 1rem; margin-top: 2rem; justify-content: flex-end;">
            <a href="<?= url($app, '/admin/roles') ?>" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">
                <?= $edit ? 'Salvar Alterações' : 'Criar Perfil' ?>
            </button>
        </div>
    </form>
</div>