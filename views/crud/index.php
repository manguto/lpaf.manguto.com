<a href="<?= url($app, '/app') ?>" class="back-link">&larr; Voltar para a Aplicação</a>

<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1><?= e($module['icon'] ?? '📁') ?> <?= e($module['name']) ?></h1>
        <p class="muted"><?= e($module['description'] ?: "Gerenciamento de {$module['name']}") ?></p>
    </div>
    <?php if (can($app, ($module['permission_prefix'] ?? $module['slug']) . '.create')): ?>
        <a class="btn btn-primary" href="<?= url($app, '/app/' . $module['slug'] . '/create') ?>">
            + Novo(a) <?= e($module['entity']) ?>
        </a>
    <?php endif; ?>
</div>

<?php
$hasActiveFilters = !empty($query) || !empty(array_filter($activeFilters ?? []));
?>

<!-- Barra de Busca e Filtros -->
<div class="card" style="margin-bottom: 1.5rem; padding: 1rem;">
    <form method="get" action="<?= url($app, '/app/' . $module['slug']) ?>" style="display: flex; gap: 0.75rem; align-items: center; padding: 0; border: 0; box-shadow: none; flex-wrap: wrap;">
        <div style="flex: 2; min-width: 200px;">
            <input type="text" name="q" value="<?= e($query ?? '') ?>" placeholder="Pesquisar por qualquer campo..." style="margin-top: 0;">
        </div>

        <?php if (!empty($relationMaps)): ?>
            <?php foreach ($relationMaps as $relKey => $relInfo): ?>
                <div style="flex: 1; min-width: 180px;">
                    <select name="<?= e($relKey) ?>" onchange="this.form.submit()" style="margin-top: 0;">
                        <option value="">Todos(as) <?= e($relInfo['target_entity']) ?>...</option>
                        <?php foreach ($relInfo['items'] as $opt): ?>
                            <option value="<?= e($opt['id']) ?>" <?= (($activeFilters[$relKey] ?? '') === $opt['id']) ? 'selected' : '' ?>>
                                <?= e($opt['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
        <?php if ($hasActiveFilters): ?>
            <a href="<?= url($app, '/app/' . $module['slug']) ?>" class="btn btn-secondary btn-sm">Limpar Filtros</a>
        <?php endif; ?>
    </form>
</div>

<!-- Listagem -->
<?php if (empty($items)): ?>
    <div class="card">
        <p class="muted" style="margin-bottom: 0;">
            <?= $hasActiveFilters ? 'Nenhum registro encontrado para os filtros aplicados.' : "Nenhum registro de {$module['entity']} cadastrado até o momento." ?>
        </p>
    </div>
<?php else: ?>
    <?php
    $listFields = [];
    foreach ($module['fields'] as $key => $f) {
        if (!isset($f['list']) || $f['list'] === true) {
            $listFields[$key] = $f;
        }
    }
    ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th style="width: 100px;">ID</th>
                    <?php foreach ($listFields as $key => $f): ?>
                        <th><?= e($f['label'] ?? ucfirst($key)) ?></th>
                    <?php endforeach; ?>
                    <th style="text-align: right; min-width: 160px;">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><code><?= e($item['id'] ?? '') ?></code></td>
                        <?php foreach ($listFields as $key => $f): ?>
                            <?php
                            $val = (string) ($item[$key] ?? '');
                            $type = $f['type'] ?? 'string';
                            ?>
                            <td>
                                <?php if ($type === 'boolean'): ?>
                                    <?php if ($val === '1' || $val === 'true'): ?>
                                        <span class="badge badge-success"><?= $key === 'ativo' ? 'Ativo' : 'Sim' ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><?= $key === 'ativo' ? 'Inativo' : 'Não' ?></span>
                                    <?php endif; ?>
                                <?php elseif ($type === 'select'): ?>
                                    <span class="badge badge-gray"><?= e($val ?: '-') ?></span>
                                <?php elseif ($type === 'relation'): ?>
                                    <?php
                                    $relData = $relationMaps[$key]['map'][$val] ?? null;
                                    $targetSlug = $relationMaps[$key]['target'] ?? '';
                                    $isFiltered = ($activeFilters[$key] ?? '') === $val;
                                    ?>
                                    <?php if ($val !== '' && $relData !== null): ?>
                                        <div style="display: flex; align-items: center; gap: 0.35rem;">
                                            <a href="<?= url($app, '/app/' . $targetSlug . '/' . $val) ?>" style="font-weight: 600; text-decoration: none; color: var(--primary);">
                                                <?= e($relData) ?>
                                            </a>
                                            <a href="<?= url($app, '/app/' . $module['slug'] . '?' . $key . '=' . urlencode($val)) ?>" 
                                               class="badge <?= $isFiltered ? 'badge-primary' : 'badge-gray' ?>" 
                                               style="font-size: 0.7rem; padding: 0.15rem 0.35rem; text-decoration: none;" 
                                               title="Filtrar listagem por este(a) <?= e($relationMaps[$key]['target_entity'] ?? 'registro') ?>">
                                                🔍
                                            </a>
                                        </div>
                                    <?php elseif ($val !== ''): ?>
                                        <code><?= e($val) ?></code>
                                    <?php else: ?>
                                        <span class="muted">-</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?= e($val ?: '-') ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td style="text-align: right; white-space: nowrap;">
                            <a class="btn btn-secondary btn-sm" href="<?= url($app, '/app/' . $module['slug'] . '/' . $item['id']) ?>">
                                Ver
                            </a>
                            <?php if (can($app, ($module['permission_prefix'] ?? $module['slug']) . '.edit')): ?>
                                <a class="btn btn-secondary btn-sm" href="<?= url($app, '/app/' . $module['slug'] . '/' . $item['id'] . '/edit') ?>">
                                    Editar
                                </a>
                            <?php endif; ?>
                            <?php if (can($app, ($module['permission_prefix'] ?? $module['slug']) . '.delete')): ?>
                                <?php
                                $refCounts = $reverseCounts[$item['id']] ?? [];
                                $hasRefs = !empty($refCounts);
                                $refSummaries = [];
                                foreach ($refCounts as $mName => $c) {
                                    $refSummaries[] = "{$c} em {$mName}";
                                }
                                $refText = implode(', ', $refSummaries);
                                $alertMsg = "Não é possível excluir este(a) {$module['entity']} pois possui vínculos ativos ({$refText}).\n\nPara excluir, primeiro remova ou altere os registros vinculados.";
                                ?>
                                <?php if ($hasRefs): ?>
                                    <button type="button" 
                                            class="btn btn-secondary btn-sm" 
                                            style="color: #64748b; border-color: #cbd5e1; cursor: not-allowed;" 
                                            data-alert="<?= e($alertMsg) ?>"
                                            onclick="alert(this.getAttribute('data-alert'));" 
                                            title="Protegido por integridade referencial: <?= e($refText) ?>">
                                        🔒 Excluir
                                    </button>
                                <?php else: ?>
                                    <form method="post" action="<?= url($app, '/app/' . $module['slug'] . '/' . $item['id'] . '/delete') ?>" style="display:inline; background:transparent; border:0; padding:0; box-shadow:none;" onsubmit="return confirm('Deseja realmente excluir este registro de <?= e($module['entity']) ?>?');">
                                        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Excluir</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
