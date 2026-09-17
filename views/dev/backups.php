<p><a href="<?= url($app, '/dev') ?>" class="back-link">&larr; Voltar para o Dev-End</a></p>

<h1>Backups do Sistema</h1>

<div class="card" style="margin-bottom: 2rem;">
    <h2>Criar Novo Backup</h2>
    <p class="muted" style="margin-bottom: 1.25rem;">Os backups copiam integralmente os arquivos CSV de persistência para uma área segura com carimbo de data e hora.</p>
    <form method="post" action="<?= url($app, '/dev/backups') ?>" style="border: 0; padding: 0; box-shadow: none; display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div style="flex: 1; min-width: 260px;">
            <label for="label" style="margin-top: 0;">Rótulo / Descrição opcional:</label>
            <input type="text" id="label" name="label" placeholder="Ex: antes_de_alterar_usuarios">
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top: 0;">Gerar Backup Agora</button>
    </form>
</div>

<h2>Backups Existentes</h2>
<?php if (empty($backups)): ?>
    <div class="card">
        <p class="muted">Nenhum backup gerado até o momento.</p>
    </div>
<?php else: ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Identificador</th>
                    <th>Rótulo</th>
                    <th>Data</th>
                    <th>Arquivos</th>
                    <th>Tamanho</th>
                    <th style="text-align: right;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($backups as $backup): ?>
                    <tr>
                        <td><code><?= e($backup['id']) ?></code></td>
                        <td><strong><?= e($backup['label']) ?></strong></td>
                        <td><?= e(date('d/m/Y H:i:s', strtotime($backup['created_at']))) ?></td>
                        <td><span class="badge badge-info"><?= e((string) $backup['files_count']) ?> CSVs</span></td>
                        <td><?= e($backup['formatted_size']) ?></td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a class="btn btn-secondary btn-sm" href="<?= url($app, '/dev/backups/' . urlencode($backup['id']) . '/download') ?>">Baixar (.zip)</a>
                            <form method="post" action="<?= url($app, '/dev/backups/' . urlencode($backup['id']) . '/restore') ?>" style="display:inline; background:transparent; border:0; padding:0; box-shadow:none;" onsubmit="return confirm('ATENÇÃO: Deseja restaurar o backup <?= e($backup['id']) ?>?\n\nTodos os dados atuais serão substituídos pelos dados deste snapshot. Um backup de salvaguarda será criado automaticamente.');">
                                <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Restaurar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
