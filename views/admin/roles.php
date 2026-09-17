<a href="<?= url($app, '/admin') ?>" class="back-link">&larr; Voltar para Administração</a>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1>Perfis de Acesso (RBAC)</h1>
        <p class="muted">Defina grupos e vincule permissões para controlar o acesso às funcionalidades.</p>
    </div>
    <a class="btn btn-primary" href="<?= url($app, '/admin/roles/create') ?>">
        + Novo Perfil
    </a>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Nome do Perfil</th>
                <th>Identificador</th>
                <th>Descrição</th>
                <th>Tipo</th>
                <th style="text-align: right;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($roles as $role): ?>
                <tr>
                    <td>
                        <strong><?= e($role['name']) ?></strong>
                    </td>
                    <td>
                        <code><?= e($role['id']) ?></code>
                    </td>
                    <td class="muted">
                        <?= e($role['description'] ?? '-') ?>
                    </td>
                    <td>
                        <?php if (($role['protected'] ?? '') === '1'): ?>
                            <span class="badge badge-purple">Protegido</span>
                        <?php else: ?>
                            <span class="badge badge-gray">Personalizado</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right;">
                        <a class="btn btn-secondary btn-sm" href="<?= url($app, '/admin/roles/' . $role['id'] . '/edit') ?>">
                            Editar Permissões
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>