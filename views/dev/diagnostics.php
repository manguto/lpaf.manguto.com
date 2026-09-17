<a href="<?= url($app, '/dev') ?>" class="back-link">&larr; Voltar para o Dev-End</a>

<div style="margin-bottom: 2rem;">
    <h1>Diagnóstico do Sistema</h1>
    <p class="muted">Parâmetros de execução e integridade física dos arquivos de armazenamento CSV.</p>
</div>

<div class="grid-4" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
    <div class="card" style="margin-bottom: 0;">
        <span class="muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">PHP Runtime</span>
        <div style="font-size: 1.4rem; font-weight: 800; color: #0f172a; margin-top: 0.25rem;">PHP <?= e(PHP_VERSION) ?></div>
    </div>

    <div class="card" style="margin-bottom: 0;">
        <span class="muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Ambiente</span>
        <div style="font-size: 1.4rem; font-weight: 800; color: #0f172a; margin-top: 0.25rem; text-transform: uppercase;"><?= e($app->config->get('app_env', 'local')) ?></div>
    </div>

    <div class="card" style="margin-bottom: 0;">
        <span class="muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Aplicação</span>
        <div style="font-size: 1.15rem; font-weight: 700; color: #0f172a; margin-top: 0.25rem;"><?= e($app->setting('app_name', 'PHP Admin Framework')) ?></div>
    </div>

    <div class="card" style="margin-bottom: 0;">
        <span class="muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700;">Versão LPAF</span>
        <div style="font-size: 1.4rem; font-weight: 800; color: var(--primary); margin-top: 0.25rem;">v<?= e($app->setting('app_version', '0.1.0')) ?></div>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem;">
    <h3 style="font-size: 0.95rem; color: var(--text-muted); margin-bottom: 0.25rem;">Diretório de Armazenamento (storage_data):</h3>
    <code><?= e($app->config->get('storage_data')) ?></code>
</div>

<h2>Integridade dos Arquivos CSV</h2>
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Arquivo CSV</th>
                <th>Presença</th>
                <th>Total de Registros</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($status as $file => $info): ?>
                <tr>
                    <td>
                        <code><?= e($file) ?></code>
                    </td>
                    <td>
                        <?php if ($info['exists']): ?>
                            <span class="badge badge-success">Presente</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Ausente</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <strong><?= e($info['rows']) ?></strong> linhas
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>