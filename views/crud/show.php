<a href="<?= url($app, '/app/' . $module['slug']) ?>" class="back-link">&larr; Voltar para <?= e($module['name']) ?></a>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1><?= e($module['entity']) ?>: <code><?= e($item['id'] ?? '') ?></code></h1>
        <p class="muted">Detalhes do registro cadastrado no módulo <?= e($module['name']) ?>.</p>
    </div>
    <div style="display: flex; gap: 0.5rem; align-items: center;">
        <?php if (can($app, ($module['permission_prefix'] ?? $module['slug']) . '.edit')): ?>
            <a class="btn btn-primary btn-sm" href="<?= url($app, '/app/' . $module['slug'] . '/' . $item['id'] . '/edit') ?>">
                Editar Registro
            </a>
        <?php endif; ?>
        <?php if (can($app, ($module['permission_prefix'] ?? $module['slug']) . '.delete')): ?>
            <?php
            $childRefs = [];
            foreach ($childRelations as $cr) {
                $cCount = count($cr['items'] ?? []);
                if ($cCount > 0) {
                    $childRefs[] = "{$cCount} no módulo '{$cr['module']['name']}'";
                }
            }
            $hasChildRefs = !empty($childRefs);
            $childRefsText = implode(', ', $childRefs);
            ?>
            <?php if ($hasChildRefs): ?>
                <?php
                $showMsg = "Não é possível excluir este registro pois ele possui vínculos ativos ({$childRefsText}).\n\nRemova ou desvincule os registros listados abaixo antes de excluí-lo.";
                ?>
                <button type="button" 
                        class="btn btn-secondary btn-sm" 
                        style="color: #64748b; border-color: #cbd5e1; cursor: not-allowed;" 
                        data-alert="<?= e($showMsg) ?>"
                        onclick="alert(this.getAttribute('data-alert'));" 
                        title="Protegido por integridade referencial: <?= e($childRefsText) ?>">
                    🔒 Excluir
                </button>
            <?php else: ?>
                <form method="post" action="<?= url($app, '/app/' . $module['slug'] . '/' . $item['id'] . '/delete') ?>" style="display:inline; background:transparent; border:0; padding:0; box-shadow:none; margin:0;" onsubmit="return confirm('Deseja realmente excluir este registro de <?= e($module['entity']) ?>?');">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Excluir</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
        <a class="btn btn-secondary btn-sm" href="<?= url($app, '/app/' . $module['slug']) ?>">
            Voltar
        </a>
    </div>
</div>

<div class="card" style="max-width: 800px;">
    <table style="width: 100%;">
        <tbody>
            <tr>
                <th style="width: 220px; background: transparent; border-bottom: 1px solid var(--border-color);">Identificador</th>
                <td style="border-bottom: 1px solid var(--border-color);"><code><?= e($item['id'] ?? '') ?></code></td>
            </tr>
            <?php foreach ($module['fields'] as $key => $f): ?>
                <?php
                $type = $f['type'] ?? 'string';
                $label = $f['label'] ?? ucfirst($key);
                $val = (string) ($item[$key] ?? '');
                ?>
                <tr>
                    <th style="background: transparent; border-bottom: 1px solid var(--border-color);"><?= e($label) ?></th>
                    <td style="border-bottom: 1px solid var(--border-color);">
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
                            ?>
                            <?php if ($val !== '' && $relData !== null): ?>
                                <a href="<?= url($app, '/app/' . $targetSlug . '/' . $val) ?>" style="font-weight: 700; color: var(--primary); text-decoration: none;">
                                    <?= e($relData) ?>
                                </a>
                                <code style="margin-left: 0.35rem;"><?= e($val) ?></code>
                            <?php elseif ($val !== ''): ?>
                                <code><?= e($val) ?></code>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        <?php elseif ($type === 'text'): ?>
                            <div style="white-space: pre-wrap;"><?= e($val ?: '-') ?></div>
                        <?php else: ?>
                            <strong><?= e($val ?: '-') ?></strong>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <th style="background: transparent; border-bottom: 1px solid var(--border-color);">Criado em</th>
                <td style="border-bottom: 1px solid var(--border-color);"><?= !empty($item['created_at']) ? date('d/m/Y H:i:s', strtotime($item['created_at'])) : '-' ?></td>
            </tr>
            <tr>
                <th style="background: transparent;">Última atualização</th>
                <td><?= !empty($item['updated_at']) ? date('d/m/Y H:i:s', strtotime($item['updated_at'])) : '-' ?></td>
            </tr>
        </tbody>
    </table>
</div>

<?php if (!empty($childRelations)): ?>
    <div style="margin-top: 2rem; max-width: 960px;">
        <h2 style="font-size: 1.35rem; margin-bottom: 1.25rem;">Registros Vinculados</h2>
        
        <?php foreach ($childRelations as $child): ?>
            <?php 
            $cMod = $child['module']; 
            $cItems = $child['items'];
            $cFields = $child['display_fields'];
            ?>
            <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-size: 1.25rem;"><?= e($cMod['icon'] ?? '📁') ?></span>
                        <h3 style="font-size: 1.1rem; margin: 0; font-weight: 700; color: var(--text-main);">
                            <?= e($cMod['name']) ?>
                        </h3>
                        <span class="badge badge-gray" style="font-size: 0.75rem;"><?= count($cItems) ?></span>
                    </div>
                    <?php if (can($app, ($cMod['permission_prefix'] ?? $cMod['slug']) . '.create')): ?>
                        <a class="btn btn-primary btn-sm" href="<?= url($app, $child['create_url']) ?>">
                            + Novo(a) <?= e($cMod['entity']) ?>
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (empty($cItems)): ?>
                    <p class="muted" style="font-size: 0.875rem; margin-bottom: 0;">
                        Nenhum registro de <strong><?= e($cMod['name']) ?></strong> vinculado a este(a) <?= e($module['entity']) ?> até o momento.
                    </p>
                <?php else: ?>
                    <div class="table-container" style="margin-bottom: 0; box-shadow: none;">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 90px;">ID</th>
                                    <?php foreach ($cFields as $ckey => $cf): ?>
                                        <th><?= e($cf['label'] ?? ucfirst($ckey)) ?></th>
                                    <?php endforeach; ?>
                                    <th style="text-align: right; min-width: 130px;">Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cItems as $ci): ?>
                                    <tr>
                                        <td><code><?= e($ci['id'] ?? '') ?></code></td>
                                        <?php foreach ($cFields as $ckey => $cf): ?>
                                            <?php 
                                            $cval = (string) ($ci[$ckey] ?? ''); 
                                            $ctype = $cf['type'] ?? 'string';
                                            ?>
                                            <td>
                                                <?php if ($ctype === 'boolean'): ?>
                                                    <span class="badge <?= ($cval === '1' || $cval === 'true') ? 'badge-success' : 'badge-danger' ?>">
                                                        <?= ($cval === '1' || $cval === 'true') ? 'Sim' : 'Não' ?>
                                                    </span>
                                                <?php elseif ($ctype === 'select'): ?>
                                                    <span class="badge badge-gray"><?= e($cval ?: '-') ?></span>
                                                <?php else: ?>
                                                    <?= e($cval ?: '-') ?>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                        <td style="text-align: right; white-space: nowrap;">
                                            <a class="btn btn-secondary btn-sm" href="<?= url($app, '/app/' . $cMod['slug'] . '/' . $ci['id']) ?>">
                                                Ver
                                            </a>
                                            <?php if (can($app, ($cMod['permission_prefix'] ?? $cMod['slug']) . '.edit')): ?>
                                                <a class="btn btn-secondary btn-sm" href="<?= url($app, '/app/' . $cMod['slug'] . '/' . $ci['id'] . '/edit') ?>">
                                                    Editar
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
