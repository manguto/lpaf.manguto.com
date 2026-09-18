<a href="<?= url($app, '/dev') ?>" class="back-link">&larr; Voltar para o Dev-End</a>

<?php $flashMessage = \App\Core\Session::flash('message'); ?>
<?php if (!empty($flashMessage)): ?>
    <div class="alert alert-success" style="margin-bottom: 1.5rem;">
        <span><?= e($flashMessage) ?></span>
    </div>
<?php endif; ?>

<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1>Módulos e Entidades</h1>
        <p class="muted">Acesso direto e gerenciamento declarativo dos módulos da aplicação.</p>
    </div>
    <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
        <form method="post" action="<?= url($app, '/dev/seed') ?>" onsubmit="return confirm('Deseja popular a base com os dados demonstrativos de Catálogo & Vendas?\n\nIsso criará registros realistas e conectados de Clientes, Produtos, Etiquetas, Pedidos e Avaliações.');" style="margin: 0; padding: 0; background: transparent; border: 0; box-shadow: none;">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <button type="submit" class="btn btn-secondary" style="border: 1.5px solid #0284c7; color: #0284c7; background: #f0f9ff; font-weight: 600;">
                🌱 Popular Dados de Demonstração (Seed)
            </button>
        </form>
        <a class="btn btn-primary" href="<?= url($app, '/dev/entity-builder') ?>">
            + Nova Entidade (Entity Builder)
        </a>
    </div>
</div>

<?php if (empty($modules)): ?>
    <div class="card">
        <p class="muted">Nenhum módulo encontrado no diretório <code>modules/</code>.</p>
    </div>
<?php else: ?>
    <!-- Barra de Acesso e Filtro Rápido -->
    <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
        <div style="flex: 1; max-width: 360px;">
            <input type="text" id="module-filter" placeholder="Filtrar entidade por nome ou slug..." oninput="filterModules(this.value)" style="margin-top: 0; padding: 0.5rem 0.8rem; font-size: 0.9rem; border: 1.5px solid #94a3b8;">
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
            <div class="card module-item" data-search="<?= strtolower(e($mod['name'] . ' ' . $mod['entity'] . ' ' . $slug)) ?>" style="margin-bottom: 0; border: 1.5px solid #cbd5e1; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <!-- Cabeçalho do Card -->
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 0.75rem; margin-bottom: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <span style="font-size: 2rem; line-height: 1;"><?= e($mod['icon'] ?? '📁') ?></span>
                            <div>
                                <h2 style="font-size: 1.25rem; margin-bottom: 0.15rem; color: #0f172a;"><?= e($mod['name']) ?></h2>
                                <div class="muted" style="font-size: 0.825rem;">
                                    Entidade: <strong style="color: #0f172a;"><?= e($mod['entity']) ?></strong> &bull; <code>/app/<?= e($slug) ?></code>
                                </div>
                            </div>
                        </div>
                        <span class="badge <?= $count > 0 ? 'badge-success' : 'badge-gray' ?>" style="font-size: 0.75rem; white-space: nowrap;">
                            <?= $count ?> <?= $count === 1 ? 'registro' : 'registros' ?>
                        </span>
                    </div>

                    <!-- Descrição Pontual -->
                    <p style="font-size: 0.875rem; color: #334155; margin-bottom: 1rem; line-height: 1.45; min-height: 2.5rem;">
                        <?= e($mod['description'] ?: "Gerenciamento e persistência declarativa de {$mod['name']}.") ?>
                    </p>

                    <!-- Bloco de Campos em Alto Contraste -->
                    <div style="background: #f1f5f9; border: 1.5px solid #cbd5e1; border-radius: 8px; padding: 0.75rem; margin-bottom: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <span style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; color: #1e293b;">
                                Campos Declarados (<?= $fieldsCount ?>)
                            </span>
                            <span style="font-size: 0.75rem; color: #475569; font-weight: 600;">
                                Prefixo: <code><?= e($mod['prefix']) ?>_001</code>
                            </span>
                        </div>
                        <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;">
                            <?php foreach ($mod['fields'] as $fname => $f): ?>
                                <?php
                                $type = $f['type'] ?? 'string';
                                $typeBadge = match($type) {
                                    'number' => 'badge-purple',
                                    'select' => 'badge-warning',
                                    'boolean' => 'badge-success',
                                    'date' => 'badge-gray',
                                    'text' => 'badge-info',
                                    default => 'badge-info',
                                };
                                $typeLabel = match($type) {
                                    'string' => 'texto',
                                    'text' => 'longo',
                                    'number' => 'número',
                                    'select' => 'select',
                                    'boolean' => 'sim/não',
                                    'date' => 'data',
                                    default => $type,
                                };
                                ?>
                                <span style="display: inline-flex; align-items: center; gap: 0.4rem; background: #ffffff; border: 1.5px solid #94a3b8; border-radius: 6px; padding: 0.3rem 0.55rem; box-shadow: 0 1px 2px rgba(0,0,0,0.06);" title="Rótulo: <?= e($f['label'] ?? $fname) ?>">
                                    <strong style="color: #0f172a; font-size: 0.825rem; font-family: monospace;"><?= e($fname) ?></strong>
                                    <span class="badge <?= $typeBadge ?>" style="font-size: 0.68rem; padding: 0.15rem 0.4rem; text-transform: lowercase; font-weight: 700;"><?= $typeLabel ?></span>
                                    <?php if (!empty($f['required'])): ?>
                                        <span style="color: var(--danger); font-weight: 900; font-size: 0.85rem;" title="Campo Obrigatório">*</span>
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div>
                    <!-- Ações Diretas -->
                    <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; padding-top: 0.85rem; border-top: 1px solid var(--border-color);">
                        <a class="btn btn-primary btn-sm" href="<?= url($app, '/app/' . $slug) ?>" style="flex: 1; text-align: center; font-weight: 700;">
                            Acessar Entidade &rarr;
                        </a>
                        <a class="btn btn-secondary btn-sm" href="<?= url($app, '/dev/modules/' . $slug . '/edit') ?>" title="Editar campos e configurações no Entity Builder" style="border-color: #94a3b8; color: #1e293b; font-weight: 600;">
                            ⚙ Editar Estrutura
                        </a>
                        <form method="post" action="<?= url($app, '/dev/modules/' . $slug . '/delete') ?>" style="display:inline; background:transparent; border:0; padding:0; box-shadow:none; margin:0;" onsubmit="return confirm('ATENÇÃO: Deseja realmente excluir o módulo <?= e($mod['name']) ?> (<?= e($slug) ?>)?\n\nOs arquivos de definição serão removidos e um backup de salvaguarda será gerado automaticamente.');">
                            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                            <button type="submit" class="btn btn-danger btn-sm" style="padding: 0.35rem 0.55rem;" title="Excluir módulo">&times;</button>
                        </form>
                    </div>
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
