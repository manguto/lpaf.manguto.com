<p><a href="<?= url($app, '/dev') ?>" class="back-link">&larr; Voltar para o Dev-End</a></p>

<h1>Logs de Auditoria</h1>

<div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
    <form method="get" action="<?= url($app, '/dev/logs') ?>" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap; padding: 0; border: 0; box-shadow: none;">
        <div style="flex: 1; min-width: 240px;">
            <label for="action" style="margin-top: 0;">Filtrar por Ação:</label>
            <select id="action" name="action" onchange="this.form.submit()">
                <option value="">Todas as ações (<?= count($logs) ?> registros)</option>
                <?php foreach ($actions as $act): ?>
                    <option value="<?= e($act) ?>" <?= $filterAction === $act ? 'selected' : '' ?>>
                        <?= e($act) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top: 0;">Filtrar</button>
        <?php if ($filterAction !== ''): ?>
            <a href="<?= url($app, '/dev/logs') ?>" class="btn btn-secondary" style="margin-top: 0;">Limpar Filtro</a>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($logs)): ?>
    <div class="card">
        <p class="muted">Nenhum registro de auditoria encontrado <?= $filterAction !== '' ? 'para a ação selecionada' : '' ?>.</p>
    </div>
<?php else: ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 170px;">Data / Hora</th>
                    <th>Ação</th>
                    <th>Usuário</th>
                    <th>Detalhes</th>
                    <th style="width: 140px; text-align: right;">ID do Registro</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <?php
                    $act = (string) ($log['action'] ?? '');
                    $badgeClass = 'badge-gray';

                    if (str_contains($act, 'failed')) {
                        $badgeClass = 'badge-danger';
                    } elseif ($act === 'login' || $act === 'backup_created') {
                        $badgeClass = 'badge-success';
                    } elseif (str_contains($act, 'created') || str_contains($act, 'updated')) {
                        $badgeClass = 'badge-purple';
                    } elseif (str_contains($act, 'restore')) {
                        $badgeClass = 'badge-warning';
                    } elseif (str_contains($act, 'download')) {
                        $badgeClass = 'badge-info';
                    }

                    $uid = (string) ($log['user_id'] ?? '');
                    $userInfo = $users[$uid] ?? null;
                    ?>
                    <tr>
                        <td><?= !empty($log['created_at']) ? date('d/m/Y H:i:s', strtotime($log['created_at'])) : '-' ?></td>
                        <td>
                            <span class="badge <?= $badgeClass ?>">
                                <?= e($act) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($userInfo): ?>
                                <strong><?= e($userInfo['name']) ?></strong> <span class="muted">(<?= e($userInfo['username']) ?>)</span>
                            <?php elseif ($uid !== ''): ?>
                                <code><?= e($uid) ?></code>
                            <?php else: ?>
                                <span class="muted">Sistema / Convidado</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= e($log['details'] ?? '') ?: '<span class="muted">-</span>' ?>
                        </td>
                        <td style="text-align: right;">
                            <code style="font-size: 0.8rem; color: var(--text-muted);"><?= e($log['id'] ?? '') ?></code>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
