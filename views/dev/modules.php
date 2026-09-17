<a href="<?= url($app, '/dev') ?>" class="back-link">&larr; Voltar para o Dev-End</a>

<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1>Módulos e Entidades</h1>
        <p class="muted">Acesso direto e gerenciamento declarativo dos módulos da aplicação.</p>
    </div>
    <a class="btn btn-primary" href="<?= url($app, '/dev/entity-builder') ?>">
        + Nova Entidade (Entity Builder)
    </a>
</div>

<?php if (empty($modules)): ?>
    <div class="card">
        <p class="muted">Nenhum módulo encontrado no diretório <code>modules/</code>.</p>
    </div>
<?php else: ?>
    <!-- Barra de Acesso e Filtro Rápido -->
    <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
        <div style="flex: 1; max-width: 360px;">
            <input type="text" id="module-filter" placeholder="Filtrar entidade por nome ou slug..." oninput="filterModules(this.value)" style="margin-top: 0; padding: 0.5rem 0.8rem; font-size: 0.9rem;">
        </div>
        <div class="muted" style="font-size: 0.85rem;">
            Total: <strong><?= count($modules) ?></strong> <?= count($modules) === 1 ? 'módulo ativo' : 'módulos ativos' ?>
        </div>
    </div>

    <!-- Grade Pontual de Entidades -->
    <div class="grid-2" style="gap: 1.25rem;" id="modules-grid">
        <?php foreach ($modules as $slug => $mod): ?>
            <?php
            $stat = $stats[$slug] ?? ['count' => 0, 'storage_exists' => false];
            $count = $stat['count'];
            $fieldsCount = count($mod['fields']);
            ?>
            <div class="card module-item" data-search="<?= strtolower(e($mod['name'] . ' ' . $mod['entity'] . ' ' . $slug)) ?>" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <!-- Cabeçalho do Card -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.75rem; margin-bottom: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <span style="font-size: 2rem; line-height: 1;"><?= e($mod['icon'] ?? '📁') ?></span>
                            <div>
                                <h2 style="font-size: 1.25rem; margin-bottom: 0.15rem;"><?= e($mod['name']) ?></h2>
                                <div class="muted" style="font-size: 0.825rem;">
                                    Entidade: <strong><?= e($mod['entity']) ?></strong> &bull; <code>/app/<?= e($slug) ?></code>
                                </div>
                            </div>
                        </div>
                        <span class="badge <?= $count > 0 ? 'badge-success' : 'badge-gray' ?>" style="font-size: 0.75rem; white-space: nowrap;">
                            <?= $count ?> <?= $count === 1 ? 'registro' : 'registros' ?>
                        </span>
                    </div>

                    <!-- Descrição Pontual -->
                    <p class="muted" style="font-size: 0.875rem; margin-bottom: 1rem; line-height: 1.45; min-height: 2.5rem;">
                        <?= e($mod['description'] ?: "Gerenciamento e persistência declarativa de {$mod['name']}.") ?>
                    </p>

                    <!-- Pílulas de Metadados -->
                    <div style="display: flex; gap: 0.4rem; flex-wrap: wrap; margin-bottom: 1rem;">
                        <span class="badge badge-purple" style="font-size: 0.72rem;">Prefixo: <?= e($mod['prefix']) ?>_</span>
                        <span class="badge badge-info" style="font-size: 0.72rem;"><?= $fieldsCount ?> <?= $fieldsCount === 1 ? 'campo' : 'campos' ?></span>
                        <span class="badge badge-gray" style="font-size: 0.72rem;"><?= e($mod['storage']) ?></span>
                    </div>
                </div>

                <div>
                    <!-- Ações Diretas -->
                    <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; padding-top: 0.85rem; border-top: 1px solid var(--border-color);">
                        <a class="btn btn-primary btn-sm" href="<?= url($app, '/app/' . $slug) ?>" style="flex: 1; text-align: center; font-weight: 700;">
                            Acessar Entidade &rarr;
                        </a>
                        <a class="btn btn-secondary btn-sm" href="<?= url($app, '/dev/modules/' . $slug . '/edit') ?>" title="Editar campos e configurações no Entity Builder">
                            ⚙ Editar Estrutura
                        </a>
                        <form method="post" action="<?= url($app, '/dev/modules/' . $slug . '/delete') ?>" style="display:inline; background:transparent; border:0; padding:0; box-shadow:none; margin:0;" onsubmit="return confirm('ATENÇÃO: Deseja realmente excluir o módulo <?= e($mod['name']) ?> (<?= e($slug) ?>)?\n\nOs arquivos de definição serão removidos e um backup de salvaguarda será gerado automaticamente.');">
                            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                            <button type="submit" class="btn btn-danger btn-sm" style="padding: 0.35rem 0.55rem;" title="Excluir módulo">&times;</button>
                        </form>
                    </div>

                    <!-- Detalhes de Campos Sob Demanda (não polui a tela) -->
                    <details style="margin-top: 0.75rem;">
                        <summary style="cursor: pointer; font-size: 0.78rem; font-weight: 600; color: var(--text-muted); list-style: none; display: flex; align-items: center; justify-content: space-between; user-select: none;">
                            <span>▸ Ver campos declarados (<?= $fieldsCount ?>)</span>
                            <span class="muted" style="font-size: 0.7rem; font-weight: normal;">Expandir</span>
                        </summary>
                        <div style="margin-top: 0.5rem; background: var(--bg-muted); border-radius: var(--radius-sm); padding: 0.6rem; font-size: 0.78rem;">
                            <div style="display: flex; flex-wrap: wrap; gap: 0.35rem;">
                                <span style="background: #ffffff; padding: 0.2rem 0.45rem; border-radius: 4px; border: 1px solid var(--border-color); font-family: monospace;">id (pk)</span>
                                <?php foreach ($mod['fields'] as $fname => $f): ?>
                                    <span style="background: #ffffff; padding: 0.2rem 0.45rem; border-radius: 4px; border: 1px solid var(--border-color);" title="<?= e($f['label'] ?? $fname) ?>">
                                        <code style="font-weight: 600;"><?= e($fname) ?></code>: <span style="color: var(--primary);"><?= e($f['type'] ?? 'string') ?></span>
                                    </span>
                                <?php endforeach; ?>
                                <span style="background: #ffffff; padding: 0.2rem 0.45rem; border-radius: 4px; border: 1px solid var(--border-color); color: var(--text-muted);">created_at</span>
                                <span style="background: #ffffff; padding: 0.2rem 0.45rem; border-radius: 4px; border: 1px solid var(--border-color); color: var(--text-muted);">updated_at</span>
                            </div>
                        </div>
                    </details>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
    function filterModules(query) {
        const q = query.toLowerCase().trim();
        const items = document.querySelectorAll('.module-item');
        items.forEach(el => {
            const text = el.dataset.search || '';
            el.style.display = text.includes(q) ? 'flex' : 'none';
        });
    }
    </script>
<?php endif; ?>
