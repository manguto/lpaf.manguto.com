<a href="<?= url($app, '/admin') ?>" class="back-link">&larr; Voltar para Administração</a>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1>Usuários Cadastrados</h1>
        <p class="muted">Lista de todos os operadores com acesso ao sistema.</p>
    </div>
    <a class="btn btn-primary" href="<?= url($app, '/admin/users/create') ?>">
        + Novo Usuário
    </a>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Usuário</th>
                <th>Login</th>
                <th>Status</th>
                <th style="text-align: right;">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $item): ?>
                <?php
                $initials = strtoupper(substr($item['name'], 0, 1));
                ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <div class="user-avatar" style="background: linear-gradient(135deg, #475569, #1e293b);">
                                <?= e($initials) ?>
                            </div>
                            <div>
                                <strong style="display: block; font-size: 0.925rem;"><?= e($item['name']) ?></strong>
                                <span class="muted" style="font-size: 0.775rem;">ID: <?= e($item['id']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td>
                        <code><?= e($item['username']) ?></code>
                    </td>
                    <td>
                        <?php if ($item['active'] === '1'): ?>
                            <span class="badge badge-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right;">
                        <a class="btn btn-secondary btn-sm" href="<?= url($app, '/admin/users/' . $item['id'] . '/edit') ?>">
                            Editar
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>