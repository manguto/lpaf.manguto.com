<a href="<?= url($app, '/dev') ?>" class="back-link">&larr; Voltar para o Dev-End</a>

<div style="margin-bottom: 2rem;">
    <h1>Módulos do Sistema</h1>
    <p class="muted">Entidades declarativas descobertas em <code>modules/</code> e gerenciadas pelo motor CRUD.</p>
</div>

<?php if (empty($modules)): ?>
    <div class="card">
        <p class="muted">Nenhum módulo encontrado no diretório <code>modules/</code>.</p>
    </div>
<?php else: ?>
    <?php foreach ($modules as $slug => $mod): ?>
        <?php
        $stat = $stats[$slug] ?? ['count' => 0, 'storage_exists' => false];
        ?>
        <div class="card" style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.25rem;">
                <div style="display: flex; align-items: center; gap: 0.75rem;">
                    <span style="font-size: 2rem;"><?= e($mod['icon'] ?? '📁') ?></span>
                    <div>
                        <h2 style="margin-bottom: 0.25rem; font-size: 1.35rem;"><?= e($mod['name']) ?></h2>
                        <div class="muted" style="font-size: 0.875rem;">
                            Slug: <code><?= e($slug) ?></code> &bull; Entidade: <strong><?= e($mod['entity']) ?></strong>
                        </div>
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <a class="btn btn-secondary btn-sm" href="<?= url($app, '/app/' . $slug) ?>">
                        Acessar Módulo &rarr;
                    </a>
                    <a class="btn btn-primary btn-sm" href="<?= url($app, '/app/' . $slug . '/create') ?>">
                        + Novo(a) <?= e($mod['entity']) ?>
                    </a>
                </div>
            </div>

            <p style="margin-bottom: 1.25rem; font-size: 0.925rem; color: var(--text-main);">
                <?= e($mod['description'] ?: 'Sem descrição fornecida.') ?>
            </p>

            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
                <span class="badge badge-purple">Prefixo: <?= e($mod['prefix']) ?>_</span>
                <span class="badge badge-info">Armazenamento: <?= e($mod['storage']) ?></span>
                <span class="badge badge-success"><?= $stat['count'] ?> <?= $stat['count'] === 1 ? 'registro' : 'registros' ?></span>
                <span class="badge badge-gray"><?= count($mod['fields']) ?> campos declarados</span>
            </div>

            <h3 style="font-size: 1rem; margin-bottom: 0.75rem;">Campos Declarados</h3>
            <div class="table-container" style="margin-bottom: 0;">
                <table>
                    <thead>
                        <tr>
                            <th>Campo</th>
                            <th>Rótulo</th>
                            <th>Tipo</th>
                            <th>Obrigatório</th>
                            <th>Único</th>
                            <th>Visível na Lista</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>id</code></td>
                            <td>Identificador Único</td>
                            <td><span class="badge badge-purple">primary_key</span></td>
                            <td><span class="badge badge-success">Sim</span></td>
                            <td><span class="badge badge-success">Sim</span></td>
                            <td><span class="badge badge-success">Sim</span></td>
                        </tr>
                        <?php foreach ($mod['fields'] as $fname => $f): ?>
                            <tr>
                                <td><code><?= e($fname) ?></code></td>
                                <td><strong><?= e($f['label'] ?? ucfirst($fname)) ?></strong></td>
                                <td><span class="badge badge-info"><?= e($f['type'] ?? 'string') ?></span></td>
                                <td><?= !empty($f['required']) ? '<span class="badge badge-danger">Sim</span>' : '<span class="muted">Não</span>' ?></td>
                                <td><?= !empty($f['unique']) ? '<span class="badge badge-warning">Sim</span>' : '<span class="muted">Não</span>' ?></td>
                                <td><?= (!isset($f['list']) || $f['list'] === true) ? '<span class="badge badge-success">Sim</span>' : '<span class="muted">Não</span>' ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td><code>created_at</code></td>
                            <td>Data de Criação</td>
                            <td><span class="badge badge-gray">datetime</span></td>
                            <td><span class="muted">Auto</span></td>
                            <td><span class="muted">Não</span></td>
                            <td><span class="muted">Não</span></td>
                        </tr>
                        <tr>
                            <td><code>updated_at</code></td>
                            <td>Data de Atualização</td>
                            <td><span class="badge badge-gray">datetime</span></td>
                            <td><span class="muted">Auto</span></td>
                            <td><span class="muted">Não</span></td>
                            <td><span class="muted">Não</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
