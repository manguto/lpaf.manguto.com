<p><a href="<?= url($app, '/dev') ?>" class="muted">&larr; Voltar para o Dev-End</a></p>

<h1>Backups do Sistema</h1>

<section style="margin-bottom: 1.5rem;">
    <h2>Criar Novo Backup</h2>
    <p class="muted">Os backups copiam integralmente os arquivos CSV de persistência para uma área segura com carimbo de data e hora.</p>
    <form method="post" action="<?= url($app, '/dev/backups') ?>">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <label for="label">Rótulo / Descrição opcional:</label>
        <input type="text" id="label" name="label" placeholder="Ex: antes_de_alterar_usuarios">
        <button type="submit">Gerar Backup Agora</button>
    </form>
</section>

<h2>Backups Existentes</h2>
<?php if (empty($backups)): ?>
    <section>
        <p class="muted">Nenhum backup gerado até o momento.</p>
    </section>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Identificador</th>
                <th>Rótulo</th>
                <th>Data</th>
                <th>Arquivos</th>
                <th>Tamanho</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($backups as $backup): ?>
                <tr>
                    <td><code><?= e($backup['id']) ?></code></td>
                    <td><?= e($backup['label']) ?></td>
                    <td><?= e(date('d/m/Y H:i:s', strtotime($backup['created_at']))) ?></td>
                    <td><?= e((string) $backup['files_count']) ?> CSVs</td>
                    <td><?= e($backup['formatted_size']) ?></td>
                    <td style="white-space: nowrap;">
                        <a class="button" style="margin-top:0; padding: .35rem .6rem; font-size: .85rem;" href="<?= url($app, '/dev/backups/' . urlencode($backup['id']) . '/download') ?>">Baixar (.zip)</a>
                        <form method="post" action="<?= url($app, '/dev/backups/' . urlencode($backup['id']) . '/restore') ?>" style="display:inline; background:transparent; border:0; padding:0;" onsubmit="return confirm('ATENÇÃO: Deseja restaurar o backup <?= e($backup['id']) ?>?\n\nTodos os dados atuais serão substituídos pelos dados deste snapshot. Um backup de salvaguarda será criado automaticamente.');">
                            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                            <button type="submit" style="background:#b33939; margin-top:0; padding: .35rem .6rem; font-size: .85rem;">Restaurar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
