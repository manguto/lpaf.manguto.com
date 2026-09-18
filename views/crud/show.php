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
            $restrictRefs = [];
            $cascadeRefs = [];
            $setNullRefs = [];
            foreach ($childRelations as $cr) {
                $cCount = count($cr['items'] ?? []);
                if ($cCount > 0) {
                    $policy = $cr['on_delete'] ?? 'restrict';
                    $mName = $cr['module']['name'] ?? 'Módulo';
                    if ($policy === 'restrict') {
                        $restrictRefs[] = "{$cCount} em {$mName}";
                    } elseif ($policy === 'cascade') {
                        $cascadeRefs[] = "{$cCount} em {$mName}";
                    } elseif ($policy === 'set_null') {
                        $setNullRefs[] = "{$cCount} em {$mName}";
                    }
                }
            }
            $hasRestrict = !empty($restrictRefs);
            $hasCascade = !empty($cascadeRefs);
            $hasSetNull = !empty($setNullRefs);
            ?>
            <?php if ($hasRestrict): ?>
                <?php
                $restrictText = implode(', ', $restrictRefs);
                $showMsg = "Não é possível excluir este(a) {$module['entity']} pois possui vínculos protegidos (restrict): {$restrictText}.\n\nRemova ou desvincule esses registros antes de excluí-lo.";
                ?>
                <button type="button" 
                        class="btn btn-secondary btn-sm" 
                        style="color: #64748b; border-color: #cbd5e1; cursor: not-allowed;" 
                        data-alert="<?= e($showMsg) ?>"
                        onclick="alert(this.getAttribute('data-alert'));" 
                        title="Protegido por integridade referencial: <?= e($restrictText) ?>">
                    🔒 Excluir
                </button>
            <?php else: ?>
                <?php
                if ($hasCascade && $hasSetNull) {
                    $confirmMsg = "ATENÇÃO: Ao excluir este registro de {$module['entity']}:\n- " . implode(', ', $cascadeRefs) . " serão EXCLUÍDOS permanentemente (em cascata).\n- " . implode(', ', $setNullRefs) . " serão desvinculados (set null).\n\nDeseja realmente continuar?";
                } elseif ($hasCascade) {
                    $confirmMsg = "ATENÇÃO: Este registro de {$module['entity']} possui dependentes que serão EXCLUÍDOS permanentemente em cascata (" . implode(', ', $cascadeRefs) . ").\n\nDeseja realmente continuar?";
                } elseif ($hasSetNull) {
                    $confirmMsg = "Aviso: Este registro de {$module['entity']} possui vínculos que serão desvinculados (" . implode(', ', $setNullRefs) . ").\n\nDeseja continuar com a exclusão?";
                } else {
                    $confirmMsg = "Deseja realmente excluir este registro de {$module['entity']}?";
                }
                ?>
                <form method="post" 
                      action="<?= url($app, '/app/' . $module['slug'] . '/' . $item['id'] . '/delete') ?>" 
                      style="display:inline; background:transparent; border:0; padding:0; box-shadow:none; margin:0;"
                      data-confirm="<?= e($confirmMsg) ?>"
                      onsubmit="return confirm(this.getAttribute('data-confirm'));">
                    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                    <button type="submit" 
                            class="btn btn-danger btn-sm"
                            title="<?= $hasCascade ? 'Exclusão com remoção de dependentes em cascata' : 'Excluir registro' ?>">
                        <?= $hasCascade ? '💥 Excluir' : 'Excluir' ?>
                    </button>
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
                        <?php elseif ($type === 'many_to_many'): ?>
                            <?php
                            $m2mData = $manyToMany[$key] ?? null;
                            $selectedIds = $m2mData['selected'] ?? [];
                            $targetSlug = $m2mData['target'] ?? '';
                            $itemMap = $m2mData['map'] ?? [];
                            ?>
                            <?php if (!empty($selectedIds)): ?>
                                <div style="display: flex; flex-wrap: wrap; gap: 0.4rem; align-items: center;">
                                    <?php foreach ($selectedIds as $sId): ?>
                                        <a href="<?= url($app, '/app/' . $targetSlug . '/' . $sId) ?>" 
                                           class="badge badge-primary" 
                                           style="text-decoration: none; padding: 0.3rem 0.6rem; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 0.35rem;"
                                           title="Ver detalhes de <?= e($itemMap[$sId] ?? $sId) ?>">
                                            <span>🔗 <?= e($itemMap[$sId] ?? $sId) ?></span>
                                            <code style="font-size: 0.72rem; opacity: 0.85; background: rgba(0,0,0,0.2); padding: 0.1rem 0.3rem; border-radius: 3px;"><?= e($sId) ?></code>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="muted">— Nenhum vínculo associado —</span>
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

<?php if (!empty($childRelations) || !empty($reverseManyToMany)): ?>
    <div style="margin-top: 2rem; max-width: 960px;">
        <h2 style="font-size: 1.35rem; margin-bottom: 1.25rem;">Registros Vinculados (Visão 360°)</h2>
        
        <?php foreach ($childRelations as $child): ?>
            <?php 
            $cMod = $child['module']; 
            $cItems = $child['items'];
            $cFields = $child['display_fields'];
            ?>
            <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                        <span style="font-size: 1.25rem;"><?= e($cMod['icon'] ?? '📁') ?></span>
                        <h3 style="font-size: 1.1rem; margin: 0; font-weight: 700; color: var(--text-main);">
                            <?= e($cMod['name']) ?>
                        </h3>
                        <span class="badge badge-gray" style="font-size: 0.75rem;"><?= count($cItems) ?></span>
                        <?php
                        $cPolicy = $child['on_delete'] ?? 'restrict';
                        if ($cPolicy === 'cascade'): ?>
                            <span class="badge badge-danger" style="font-size: 0.7rem; padding: 0.15rem 0.4rem;" title="Ao excluir este registro pai, todos os registros dependentes vinculados serão excluídos em cascata">💥 Cascade</span>
                        <?php elseif ($cPolicy === 'set_null'): ?>
                            <span class="badge badge-gray" style="font-size: 0.7rem; padding: 0.15rem 0.4rem; color: #475569;" title="Ao excluir este registro pai, estes registros serão desvinculados">⚪ Set Null</span>
                        <?php else: ?>
                            <span class="badge badge-gray" style="font-size: 0.7rem; padding: 0.15rem 0.4rem;" title="Ao excluir este registro pai, a exclusão será bloqueada enquanto houver registros vinculados">🔒 Restrict</span>
                        <?php endif; ?>
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

        <?php if (!empty($reverseManyToMany)): ?>
            <?php foreach ($reverseManyToMany as $rev): ?>
                <?php 
                $rMod = $rev['module']; 
                $rItems = $rev['items'];
                $rFields = $rev['display_fields'];
                ?>
                <div class="card" style="margin-bottom: 1.5rem; padding: 1.25rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
                        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                            <span style="font-size: 1.25rem;"><?= e($rMod['icon'] ?? '📁') ?></span>
                            <h3 style="font-size: 1.1rem; margin: 0; font-weight: 700; color: var(--text-main);">
                                <?= e($rMod['name']) ?>
                            </h3>
                            <span class="badge badge-gray" style="font-size: 0.75rem;"><?= count($rItems) ?></span>
                            <span class="badge badge-primary" style="font-size: 0.7rem; padding: 0.15rem 0.4rem;" title="Relacionamento N:N via tabela pivô">🔗 N:N Tabela Pivô</span>
                            <span class="muted" style="font-size: 0.8rem;">(campo: <code><?= e($rev['field_label']) ?></code>)</span>
                        </div>
                        <?php if (can($app, ($rMod['permission_prefix'] ?? $rMod['slug']) . '.create')): ?>
                            <a class="btn btn-secondary btn-sm" href="<?= url($app, '/app/' . $rMod['slug'] . '/create') ?>">
                                + Novo(a) <?= e($rMod['entity']) ?>
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($rItems)): ?>
                        <p class="muted" style="font-size: 0.875rem; margin-bottom: 0;">
                            Nenhum registro de <strong><?= e($rMod['name']) ?></strong> está associado a este(a) <?= e($module['entity']) ?> no momento.
                        </p>
                    <?php else: ?>
                        <div class="table-container" style="margin-bottom: 0; box-shadow: none;">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width: 90px;">ID</th>
                                        <?php foreach ($rFields as $rkey => $rf): ?>
                                            <th><?= e($rf['label'] ?? ucfirst($rkey)) ?></th>
                                        <?php endforeach; ?>
                                        <th style="text-align: right; min-width: 130px;">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rItems as $ri): ?>
                                        <tr>
                                            <td><code><?= e($ri['id'] ?? '') ?></code></td>
                                            <?php foreach ($rFields as $rkey => $rf): ?>
                                                <?php 
                                                $rval = (string) ($ri[$rkey] ?? ''); 
                                                $rtype = $rf['type'] ?? 'string';
                                                ?>
                                                <td>
                                                    <?php if ($rtype === 'boolean'): ?>
                                                        <span class="badge <?= ($rval === '1' || $rval === 'true') ? 'badge-success' : 'badge-danger' ?>">
                                                            <?= ($rval === '1' || $rval === 'true') ? 'Sim' : 'Não' ?>
                                                        </span>
                                                    <?php elseif ($rtype === 'select'): ?>
                                                        <span class="badge badge-gray"><?= e($rval ?: '-') ?></span>
                                                    <?php else: ?>
                                                        <?= e($rval ?: '-') ?>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                            <td style="text-align: right; white-space: nowrap;">
                                                <a class="btn btn-secondary btn-sm" href="<?= url($app, '/app/' . $rMod['slug'] . '/' . $ri['id']) ?>">
                                                    Ver
                                                </a>
                                                <?php if (can($app, ($rMod['permission_prefix'] ?? $rMod['slug']) . '.edit')): ?>
                                                    <a class="btn btn-secondary btn-sm" href="<?= url($app, '/app/' . $rMod['slug'] . '/' . $ri['id'] . '/edit') ?>">
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
        <?php endif; ?>
    </div>
<?php endif; ?>
