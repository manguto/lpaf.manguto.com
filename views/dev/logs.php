<p><a href="<?= url($app, '/dev') ?>" class="muted">&larr; Voltar para o Dev-End</a></p>

<h1>Logs de Auditoria</h1>

<section style="margin-bottom: 1.5rem;">
    <form method="get" action="<?= url($app, '/dev/logs') ?>" style="display: flex; gap: .75rem; align-items: flex-end; flex-wrap: wrap; padding: .75rem; border: 0; background: transparent;">
        <div style="flex: 1; min-width: 220px;">
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
        <button type="submit" style="margin-top: 0;">Filtrar</button>
        <?php if ($filterAction !== ''): ?>
            <a href="<?= url($app, '/dev/logs') ?>" class="button" style="margin-top: 0; background: #64748b;">Limpar Filtro</a>
        <?php endif; ?>
    </form>
</section>

<?php if (empty($logs)): ?>
    <section>
        <p class="muted">Nenhum registro de auditoria encontrado <?= $filterAction !== '' ? 'para a ação selecionada' : '' ?>.</p>
    </section>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th style="width: 170px;">Data / Hora</th>
                <th>Ação</th>
                <th>Usuário</th>
                <th>Detalhes</th>
                <th style="width: 140px;">ID do Registro</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <?php
                $act = (string) ($log['action'] ?? '');
                $badgeBg = '#e2e8f0';
                $badgeColor = '#334155';

                if (str_contains($act, 'failed')) {
                    $badgeBg = '#fee2e2';
                    $badgeColor = '#991b1b';
                } elseif ($act === 'login' || $act === 'backup_created') {
                    $badgeBg = '#dcfce7';
                    $badgeColor = '#166534';
                } elseif (str_contains($act, 'created') || str_contains($act, 'updated')) {
                    $badgeBg = '#e0e7ff';
                    $badgeColor = '#3730a3';
                } elseif (str_contains($act, 'restore')) {
                    $badgeBg = '#fef3c7';
                    $badgeColor = '#92400e';
                }

                $uid = (string) ($log['user_id'] ?? '');
                $userInfo = $users[$uid] ?? null;
                ?>
                <tr>
                    <td><?= !empty($log['created_at']) ? date('d/m/Y H:i:s', strtotime($log['created_at'])) : '-' ?></td>
                    <td>
                        <span style="display: inline-block; padding: .2rem .55rem; border-radius: 4px; font-size: .825rem; font-weight: bold; background: <?= $badgeBg ?>; color: <?= $badgeColor ?>;">
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
                    <td>
                        <code style="font-size: .85rem; color: #64748b;"><?= e($log['id'] ?? '') ?></code>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
