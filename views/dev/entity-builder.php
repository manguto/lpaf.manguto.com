<?php
$isEdit = !empty($isEdit);
$module = $module ?? [];
$fields = $module['fields'] ?? [];
?>
<style>
/* Expansão confortável da área de trabalho no Entity Builder */
main {
    max-width: 1240px !important;
}

#entity-builder-form {
    background: transparent;
    border: none;
    padding: 0;
    box-shadow: none;
}

.field-row {
    transition: background-color 0.15s ease, border-color 0.15s ease;
}

.field-row.drag-over {
    border-top: 3px solid var(--primary) !important;
    background-color: var(--primary-light) !important;
}

.field-row.dragging {
    opacity: 0.45;
}

.drag-handle {
    cursor: grab;
    user-select: none;
    display: inline-flex;
    align-items: center;
    color: var(--text-muted);
    font-size: 0.95rem;
    padding: 0 1px;
}

.drag-handle:active {
    cursor: grabbing;
}

.btn-order {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 12px;
    padding: 0;
    font-size: 7px;
    line-height: 1;
    background: #cbd5e1;
    color: #0f172a;
    border: 1px solid #94a3b8;
    border-radius: 2px;
    cursor: pointer;
    font-weight: bold;
    transition: var(--transition);
}

.btn-order:hover {
    background: #94a3b8;
    color: #000000;
}

.btn-order:disabled {
    opacity: 0.25 !important;
    cursor: not-allowed !important;
    pointer-events: none;
}

#fields-table {
    table-layout: auto;
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    border: 1.5px solid #cbd5e1;
    border-radius: var(--radius-sm);
    overflow: hidden;
}

#fields-table th {
    background-color: #e2e8f0;
    color: #0f172a;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    padding: 0.6rem 0.45rem;
    white-space: nowrap;
    border-bottom: 2px solid #94a3b8;
}

#fields-table td {
    padding: 0.45rem 0.45rem;
    border-bottom: 1px solid #cbd5e1;
    background-color: #ffffff;
}

#fields-table tbody tr:nth-child(even) td {
    background-color: #f8fafc;
}

#fields-table tbody tr:hover td {
    background-color: #f1f5f9;
}

#fields-table input[type="text"],
#fields-table select {
    margin-top: 0;
    padding: 0.35rem 0.55rem;
    font-size: 0.875rem;
    font-weight: 600;
    height: 34px;
    border: 1.5px solid #94a3b8;
    border-radius: var(--radius-sm);
    background-color: #ffffff;
    color: #0f172a;
}

#fields-table input[type="text"]:focus,
#fields-table select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.2);
    outline: none;
}

#fields-table input[type="checkbox"] {
    margin: 0 auto;
    display: block;
    width: 18px;
    height: 18px;
    accent-color: var(--primary);
    cursor: pointer;
}

.row-order-number {
    font-weight: 800;
    font-size: 0.85rem;
    color: #0f172a;
    min-width: 22px;
}

.cell-muted-dash {
    display: block;
    text-align: center;
    color: #94a3b8;
    font-weight: bold;
    font-size: 0.95rem;
    user-select: none;
}
</style>

<a href="<?= url($app, '/dev/modules') ?>" class="back-link">&larr; Voltar para Módulos</a>

<div style="margin-bottom: 2rem;">
    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
        <h1><?= $isEdit ? 'Editar Entidade: ' . e($module['name'] ?? '') : 'Entity Builder' ?></h1>
        <span class="badge <?= $isEdit ? 'badge-warning' : 'badge-purple' ?>"><?= $isEdit ? 'Modo Edição' : 'Dev-End Studio' ?></span>
    </div>
    <p class="muted">
        <?= $isEdit 
            ? 'Modifique os metadados e os campos da entidade. Os dados já cadastrados em <code>storage/data/' . e($module['slug'] ?? '') . '.csv</code> serão preservados e uma salvaguarda de segurança será gerada automaticamente.' 
            : 'Defina uma nova entidade administrativa. O sistema gerará automaticamente o módulo, rotas, persistência CSV, formulários e permissões com salvaguarda de segurança.' ?>
    </p>
</div>

<form method="post" action="<?= $isEdit ? url($app, '/dev/modules/' . ($module['slug'] ?? '') . '/edit') : url($app, '/dev/entity-builder') ?>" id="entity-builder-form" onsubmit="reindexFields()">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <!-- Informações Gerais -->
    <div class="card" style="margin-bottom: 2rem;">
        <h2 style="font-size: 1.2rem; margin-bottom: 1.25rem;">1. Informações da Entidade</h2>

        <div class="grid-2" style="gap: 1.25rem; margin-bottom: 1rem;">
            <div>
                <label for="name">Nome do Módulo (Plural): <span style="color: var(--danger);">*</span></label>
                <input type="text" id="name" name="name" value="<?= e($module['name'] ?? '') ?>" placeholder="Ex: Clientes, Projetos, Veículos" required oninput="autoGenerateSlug(this.value)">
                <span class="muted" style="font-size: 0.75rem;">Nome de exibição nas listagens e menus.</span>
            </div>

            <div>
                <label for="entity">Nome da Entidade (Singular): <span style="color: var(--danger);">*</span></label>
                <input type="text" id="entity" name="entity" value="<?= e($module['entity'] ?? '') ?>" placeholder="Ex: Cliente, Projeto, Veículo" required oninput="autoGeneratePrefix(this.value)">
                <span class="muted" style="font-size: 0.75rem;">Utilizado nos botões de cadastro (+ Novo Cliente).</span>
            </div>
        </div>

        <div class="grid-3" style="gap: 1.25rem; margin-bottom: 1rem;">
            <div>
                <label for="slug">Slug da URL: <span style="color: var(--danger);">*</span> <?= $isEdit ? '<small class="badge badge-gray" style="font-size: 0.7rem; font-weight: normal; margin-left: 0.35rem;">🔒 Fixo</small>' : '' ?></label>
                <input type="text" id="slug" name="slug" value="<?= e($module['slug'] ?? '') ?>" placeholder="Ex: clientes" required pattern="[a-z0-9_-]{3,30}" <?= $isEdit ? 'readonly' : '' ?>>
                <span class="muted" style="font-size: 0.75rem;">Rota de acesso: <code>/app/<?= e($module['slug'] ?? '{slug}') ?></code>.</span>
            </div>

            <div>
                <label for="prefix">Prefixo de ID: <span style="color: var(--danger);">*</span> <?= $isEdit ? '<small class="badge badge-gray" style="font-size: 0.7rem; font-weight: normal; margin-left: 0.35rem;">🔒 Fixo</small>' : '' ?></label>
                <input type="text" id="prefix" name="prefix" value="<?= e($module['prefix'] ?? '') ?>" placeholder="Ex: cli" required maxlength="6" pattern="[a-z0-9]{2,6}" <?= $isEdit ? 'readonly' : '' ?>>
                <span class="muted" style="font-size: 0.75rem;">Identificadores gerados: <code><?= e($module['prefix'] ?? 'cli') ?>_001</code>.</span>
            </div>

            <div style="position: relative;">
                <label for="icon">Ícone / Emoji:</label>
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <div id="emoji-preview-btn" 
                         onclick="toggleEmojiPicker()" 
                         title="Clique para abrir a lista de emojis"
                         style="width: 44px; height: 38px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; background: var(--card-bg, #ffffff); border: 1.5px solid #94a3b8; border-radius: var(--radius-sm); cursor: pointer; user-select: none; transition: transform 0.1s, border-color 0.15s;">
                        <span id="emoji-preview-char"><?= e($module['icon'] ?? '📁') ?></span>
                    </div>
                    <input type="text" id="icon" name="icon" value="<?= e($module['icon'] ?? '📁') ?>" maxlength="4" 
                           style="width: 70px; text-align: center; font-size: 1.2rem; margin-top: 0; height: 38px;" 
                           oninput="updateEmojiPreview(this.value)">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleEmojiPicker()" style="height: 38px; white-space: nowrap; font-size: 0.82rem; padding: 0 0.75rem;">
                        Escolher ▾
                    </button>
                </div>

                <!-- Chips de Acesso Rápido -->
                <div style="display: flex; gap: 0.25rem; flex-wrap: wrap; margin-top: 0.4rem; align-items: center;">
                    <span class="muted" style="font-size: 0.72rem; margin-right: 0.15rem;">Sugestões:</span>
                    <?php 
                    $quickEmojis = ['📁', '👥', '💼', '💻', '📦', '📊', '⚙️', '📝', '🛒', '💰', '🔧', '🔒'];
                    foreach ($quickEmojis as $qEmo): ?>
                        <button type="button" 
                                onclick="selectEmoji('<?= $qEmo ?>')" 
                                title="Selecionar <?= $qEmo ?>"
                                style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 4px; padding: 1px 5px; font-size: 0.95rem; cursor: pointer; line-height: 1.2; transition: all 0.12s;"
                                onmouseover="this.style.background='#e2e8f0'; this.style.borderColor='#94a3b8';"
                                onmouseout="this.style.background='#f1f5f9'; this.style.borderColor='#cbd5e1';">
                            <?= $qEmo ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Popover com Catálogo Completo de Emojis -->
                <div id="emoji-picker-dropdown" 
                     style="display: none; position: absolute; top: calc(100% + 4px); right: 0; z-index: 1000; width: 330px; background: #ffffff; border: 1.5px solid #94a3b8; border-radius: var(--radius-sm, 6px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.25), 0 8px 10px -6px rgba(0,0,0,0.15); padding: 0.75rem;">
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                        <strong style="font-size: 0.85rem; color: #0f172a;">Biblioteca de Ícones / Emojis</strong>
                        <button type="button" onclick="closeEmojiPicker()" style="background: none; border: none; font-size: 1.1rem; cursor: pointer; color: #64748b; line-height: 1; padding: 0 0.25rem;">&times;</button>
                    </div>

                    <input type="text" id="emoji-search-input" placeholder="Pesquisar emoji (ex: pasta, cliente)..." 
                           oninput="filterEmojis(this.value)"
                           style="width: 100%; font-size: 0.8rem; padding: 0.35rem 0.55rem; height: 32px; margin-top: 0; margin-bottom: 0.6rem; border: 1px solid #cbd5e1;">

                    <div id="emoji-list-scroll" style="max-height: 240px; overflow-y: auto; padding-right: 2px;">
                        <!-- Renderizado via JavaScript -->
                    </div>
                </div>
            </div>
        </div>

        <div>
            <label for="description">Descrição da Funcionalidade:</label>
            <input type="text" id="description" name="description" value="<?= e($module['description'] ?? '') ?>" placeholder="Ex: Controle e cadastro de clientes corporativos e contatos.">
        </div>
    </div>

    <!-- Construtor de Campos -->
    <div class="card" style="margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.5rem;">
            <div>
                <h2 style="font-size: 1.2rem; margin-bottom: 0.25rem;">2. Estrutura de Campos e Ordenação</h2>
                <p class="muted" style="font-size: 0.85rem;">
                    Os campos <code>id</code>, <code>created_at</code> e <code>updated_at</code> são automáticos. 
                    Utilize os botões <strong>▲</strong> e <strong>▼</strong> ou arraste as linhas para definir a ordem dos campos.
                </p>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="addFieldRow()">
                + Adicionar Campo
            </button>
        </div>

        <div class="table-container" style="margin-bottom: 1rem;">
            <table id="fields-table">
                <thead>
                    <tr>
                        <th style="text-align: center; width: 68px;">Ordem</th>
                        <th style="min-width: 130px;">Campo (ID)</th>
                        <th style="min-width: 140px;">Rótulo (Label)</th>
                        <th style="min-width: 140px;">Tipo</th>
                        <th style="min-width: 180px;">Opções / Relação</th>
                        <th style="text-align: center; width: 55px;" title="Campo Obrigatório">Obrig.</th>
                        <th style="text-align: center; width: 50px;" title="Valor Único">Único</th>
                        <th style="text-align: center; width: 50px;" title="Visível na Listagem">Lista</th>
                        <th style="text-align: center; width: 45px;">Ação</th>
                    </tr>
                </thead>
                <tbody id="fields-tbody">
                    <?php if ($isEdit && !empty($fields)): ?>
                        <?php $idx = 0; foreach ($fields as $fname => $f): ?>
                            <?php 
                            $fType = $f['type'] ?? 'string';
                            $isSelect = ($fType === 'select');
                            $isRelation = ($fType === 'relation');
                            $isManyToMany = ($fType === 'many_to_many');
                            $optionsVal = isset($f['options']) && is_array($f['options']) ? implode(', ', $f['options']) : '';
                            $relTarget = $f['target'] ?? '';
                            $pivotFile = $f['pivot_file'] ?? '';
                            ?>
                            <tr class="field-row" data-index="<?= $idx ?>">
                                <td style="text-align: center; white-space: nowrap; width: 68px;">
                                    <div style="display: inline-flex; align-items: center; justify-content: center; gap: 3px;">
                                        <span class="drag-handle" title="Arraste para reordenar">⋮⋮</span>
                                        <div style="display: inline-flex; flex-direction: column; gap: 1px;">
                                            <button type="button" class="btn-order btn-move-up" onclick="moveFieldUp(this)" title="Mover campo para cima">▲</button>
                                            <button type="button" class="btn-order btn-move-down" onclick="moveFieldDown(this)" title="Mover campo para baixo">▼</button>
                                        </div>
                                        <span class="row-order-number" style="font-weight: 700; font-size: 0.78rem; color: var(--text-muted); min-width: 20px;">#<?= $idx + 1 ?></span>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" name="fields[<?= $idx ?>][name]" value="<?= e($fname) ?>" placeholder="ex: nome" required pattern="[a-z0-9_]{2,30}" style="font-family: monospace;">
                                </td>
                                <td>
                                    <input type="text" name="fields[<?= $idx ?>][label]" value="<?= e($f['label'] ?? ucfirst($fname)) ?>" placeholder="ex: Nome Completo" required>
                                </td>
                                <td>
                                    <select name="fields[<?= $idx ?>][type]" onchange="handleTypeChange(this)">
                                        <option value="string" <?= $fType === 'string' ? 'selected' : '' ?>>Texto Curto</option>
                                        <option value="text" <?= $fType === 'text' ? 'selected' : '' ?>>Texto Longo</option>
                                        <option value="number" <?= $fType === 'number' ? 'selected' : '' ?>>Número</option>
                                        <option value="date" <?= $fType === 'date' ? 'selected' : '' ?>>Data</option>
                                        <option value="select" <?= $isSelect ? 'selected' : '' ?>>Seleção</option>
                                        <option value="relation" <?= $isRelation ? 'selected' : '' ?>>Relação 1:N</option>
                                        <option value="many_to_many" <?= $isManyToMany ? 'selected' : '' ?>>Relação N:N</option>
                                        <option value="boolean" <?= $fType === 'boolean' ? 'selected' : '' ?>>Sim / Não</option>
                                    </select>
                                </td>
                                <td>
                                    <div class="col-options-select" style="display: <?= $isSelect ? 'block' : 'none' ?>;">
                                        <input type="text" name="fields[<?= $idx ?>][options]" value="<?= e($optionsVal) ?>" placeholder="Opção 1, Opção 2" <?= $isSelect ? 'required' : 'disabled' ?>>
                                    </div>
                                    <div class="col-options-relation" style="display: <?= $isRelation ? 'block' : 'none' ?>;">
                                        <select name="fields[<?= $idx ?>][relation_target]" <?= $isRelation ? 'required' : 'disabled' ?> style="margin-bottom: 0.35rem;">
                                            <option value="">Vincular a...</option>
                                            <?php foreach (($allModules ?? []) as $modSlug => $mod): ?>
                                                <?php if ($modSlug !== ($module['slug'] ?? '')): ?>
                                                    <option value="<?= e($modSlug) ?>" <?= $relTarget === $modSlug ? 'selected' : '' ?>>
                                                        <?= e($mod['name']) ?> (<?= e($mod['entity']) ?>)
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                        <select name="fields[<?= $idx ?>][relation_on_delete]" title="Ação ao Excluir o Registro Pai" style="font-size: 0.8rem; padding: 0.25rem 0.4rem;" <?= $isRelation ? '' : 'disabled' ?>>
                                            <option value="restrict" <?= ($f['on_delete'] ?? 'restrict') === 'restrict' ? 'selected' : '' ?>>🔒 Bloquear (Restrict)</option>
                                            <option value="set_null" <?= ($f['on_delete'] ?? '') === 'set_null' ? 'selected' : '' ?>>⚪ Desvincular (Set Null)</option>
                                            <option value="cascade" <?= ($f['on_delete'] ?? '') === 'cascade' ? 'selected' : '' ?>>💥 Em Cascata (Cascade)</option>
                                        </select>
                                    </div>
                                    <div class="col-options-many-to-many" style="display: <?= $isManyToMany ? 'block' : 'none' ?>;">
                                        <select name="fields[<?= $idx ?>][relation_target]" <?= $isManyToMany ? 'required' : 'disabled' ?>>
                                            <option value="">Vincular a...</option>
                                            <?php foreach (($allModules ?? []) as $modSlug => $mod): ?>
                                                <?php if ($modSlug !== ($module['slug'] ?? '')): ?>
                                                    <option value="<?= e($modSlug) ?>" <?= $relTarget === $modSlug ? 'selected' : '' ?>>
                                                        <?= e($mod['name']) ?> (<?= e($mod['entity']) ?>)
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (!empty($pivotFile)): ?>
                                            <input type="hidden" name="fields[<?= $idx ?>][pivot_file]" value="<?= e($pivotFile) ?>" <?= $isManyToMany ? '' : 'disabled' ?>>
                                        <?php endif; ?>
                                    </div>
                                    <span class="cell-muted-dash" style="display: <?= (!$isSelect && !$isRelation && !$isManyToMany) ? 'block' : 'none' ?>;">—</span>
                                </td>
                                <td style="text-align: center;">
                                    <input type="checkbox" name="fields[<?= $idx ?>][required]" value="1" <?= !empty($f['required']) ? 'checked' : '' ?>>
                                </td>
                                <td style="text-align: center;">
                                    <input type="checkbox" name="fields[<?= $idx ?>][unique]" value="1" <?= !empty($f['unique']) ? 'checked' : '' ?>>
                                </td>
                                <td style="text-align: center;">
                                    <input type="checkbox" name="fields[<?= $idx ?>][list]" value="1" <?= (!isset($f['list']) || $f['list'] === true) ? 'checked' : '' ?>>
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeFieldRow(this)" title="Remover Campo" style="padding: 0.2rem 0.45rem; font-size: 0.9rem; line-height: 1;">&times;</button>
                                </td>
                            </tr>
                        <?php $idx++; endforeach; ?>
                    <?php else: ?>
                        <tr class="field-row" data-index="0">
                            <td style="text-align: center; white-space: nowrap; width: 68px;">
                                <div style="display: inline-flex; align-items: center; justify-content: center; gap: 3px;">
                                    <span class="drag-handle" title="Arraste para reordenar">⋮⋮</span>
                                    <div style="display: inline-flex; flex-direction: column; gap: 1px;">
                                        <button type="button" class="btn-order btn-move-up" onclick="moveFieldUp(this)" title="Mover campo para cima">▲</button>
                                        <button type="button" class="btn-order btn-move-down" onclick="moveFieldDown(this)" title="Mover campo para baixo">▼</button>
                                    </div>
                                    <span class="row-order-number" style="font-weight: 700; font-size: 0.78rem; color: var(--text-muted); min-width: 20px;">#1</span>
                                </div>
                            </td>
                            <td>
                                <input type="text" name="fields[0][name]" value="nome" placeholder="ex: nome" required pattern="[a-z0-9_]{2,30}" style="font-family: monospace;">
                            </td>
                            <td>
                                <input type="text" name="fields[0][label]" value="Nome" placeholder="ex: Nome Completo" required>
                            </td>
                            <td>
                                <select name="fields[0][type]" onchange="handleTypeChange(this)">
                                    <option value="string" selected>Texto Curto</option>
                                    <option value="text">Texto Longo</option>
                                    <option value="number">Número</option>
                                    <option value="date">Data</option>
                                    <option value="select">Seleção</option>
                                    <option value="relation">Relação 1:N</option>
                                    <option value="many_to_many">Relação N:N</option>
                                    <option value="boolean">Sim / Não</option>
                                </select>
                            </td>
                            <td>
                                <div class="col-options-select" style="display: none;">
                                    <input type="text" name="fields[0][options]" placeholder="Opção 1, Opção 2" disabled>
                                </div>
                                <div class="col-options-relation" style="display: none;">
                                    <select name="fields[0][relation_target]" style="margin-bottom: 0.35rem;" disabled>
                                        <option value="">Vincular a...</option>
                                        <?php foreach (($allModules ?? []) as $modSlug => $mod): ?>
                                            <option value="<?= e($modSlug) ?>">
                                                <?= e($mod['name']) ?> (<?= e($mod['entity']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="fields[0][relation_on_delete]" title="Ação ao Excluir o Registro Pai" style="font-size: 0.8rem; padding: 0.25rem 0.4rem;" disabled>
                                        <option value="restrict" selected>🔒 Bloquear (Restrict)</option>
                                        <option value="set_null">⚪ Desvincular (Set Null)</option>
                                        <option value="cascade">💥 Em Cascata (Cascade)</option>
                                    </select>
                                </div>
                                <div class="col-options-many-to-many" style="display: none;">
                                    <select name="fields[0][relation_target]" disabled>
                                        <option value="">Vincular a...</option>
                                        <?php foreach (($allModules ?? []) as $modSlug => $mod): ?>
                                            <option value="<?= e($modSlug) ?>">
                                                <?= e($mod['name']) ?> (<?= e($mod['entity']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <span class="cell-muted-dash">—</span>
                            </td>
                            <td style="text-align: center;">
                                <input type="checkbox" name="fields[0][required]" value="1" checked>
                            </td>
                            <td style="text-align: center;">
                                <input type="checkbox" name="fields[0][unique]" value="1">
                            </td>
                            <td style="text-align: center;">
                                <input type="checkbox" name="fields[0][list]" value="1" checked>
                            </td>
                            <td style="text-align: center;">
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeFieldRow(this)" title="Remover Campo" style="padding: 0.2rem 0.45rem; font-size: 0.9rem; line-height: 1;">&times;</button>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <button type="button" class="btn btn-secondary btn-sm" onclick="addFieldRow()">
            + Adicionar Outro Campo
        </button>
    </div>

    <!-- Ações Finais -->
    <div style="display: flex; gap: 1rem; align-items: center;">
        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem; font-size: 1rem;">
            <?= $isEdit ? 'Salvar Alterações da Entidade' : 'Criar e Ativar Entidade' ?>
        </button>
        <a href="<?= url($app, '/dev/modules') ?>" class="btn btn-secondary">
            Cancelar
        </a>
    </div>
</form>

<script>
let fieldCount = <?= $isEdit ? (count($fields) ?: 1) : 1 ?>;
const isEditMode = <?= $isEdit ? 'true' : 'false' ?>;
const availableModules = <?= json_encode(array_values(array_map(function($m) {
    return [
        'slug' => $m['slug'],
        'name' => $m['name'],
        'entity' => $m['entity'] ?? $m['name'],
    ];
}, $allModules ?? []))) ?>;
const currentModuleSlug = '<?= e($module['slug'] ?? '') ?>';

function autoGenerateSlug(value) {
    if (isEditMode) return;
    const slugInput = document.getElementById('slug');
    if (!slugInput.dataset.manual) {
        const clean = value.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
        slugInput.value = clean;
    }
}

function autoGeneratePrefix(value) {
    if (isEditMode) return;
    const prefixInput = document.getElementById('prefix');
    if (!prefixInput.dataset.manual) {
        const clean = value.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]/g, '');
        prefixInput.value = clean.substring(0, 3) || 'rec';
    }
}

document.getElementById('slug').addEventListener('input', function() {
    this.dataset.manual = 'true';
});

document.getElementById('prefix').addEventListener('input', function() {
    this.dataset.manual = 'true';
});

function handleTypeChange(selectElement) {
    const row = selectElement.closest('tr');
    const selectBox = row.querySelector('.col-options-select');
    const relationBox = row.querySelector('.col-options-relation');
    const m2mBox = row.querySelector('.col-options-many-to-many');
    const dash = row.querySelector('.cell-muted-dash');

    const optionsInputs = selectBox ? selectBox.querySelectorAll('input') : [];
    const relationInputs = relationBox ? relationBox.querySelectorAll('select, input') : [];
    const m2mInputs = m2mBox ? m2mBox.querySelectorAll('select, input') : [];

    function toggleBox(box, inputs, show, requireFirst) {
        if (!box) return;
        box.style.display = show ? 'block' : 'none';
        inputs.forEach((inp, i) => {
            inp.disabled = !show;
            if (i === 0) inp.required = (show && requireFirst);
        });
        if (show && inputs.length > 0) {
            inputs[0].focus();
        }
    }

    if (dash) dash.style.display = 'none';

    if (selectElement.value === 'select') {
        toggleBox(selectBox, optionsInputs, true, true);
        toggleBox(relationBox, relationInputs, false, false);
        toggleBox(m2mBox, m2mInputs, false, false);
    } else if (selectElement.value === 'relation') {
        toggleBox(selectBox, optionsInputs, false, false);
        toggleBox(relationBox, relationInputs, true, true);
        toggleBox(m2mBox, m2mInputs, false, false);
    } else if (selectElement.value === 'many_to_many') {
        toggleBox(selectBox, optionsInputs, false, false);
        toggleBox(relationBox, relationInputs, false, false);
        toggleBox(m2mBox, m2mInputs, true, true);
    } else {
        toggleBox(selectBox, optionsInputs, false, false);
        toggleBox(relationBox, relationInputs, false, false);
        toggleBox(m2mBox, m2mInputs, false, false);
        if (dash) dash.style.display = 'block';
    }
}

function moveFieldUp(btn) {
    const row = btn.closest('tr');
    const prev = row.previousElementSibling;
    if (prev) {
        row.parentNode.insertBefore(row, prev);
        reindexFields();
    }
}

function moveFieldDown(btn) {
    const row = btn.closest('tr');
    const next = row.nextElementSibling;
    if (next) {
        row.parentNode.insertBefore(next, row);
        reindexFields();
    }
}

function reindexFields() {
    const tbody = document.getElementById('fields-tbody');
    const rows = tbody.querySelectorAll('tr.field-row');
    rows.forEach((tr, index) => {
        tr.dataset.index = index;
        
        const orderSpan = tr.querySelector('.row-order-number');
        if (orderSpan) {
            orderSpan.textContent = '#' + (index + 1);
        }
        
        const btnUp = tr.querySelector('.btn-move-up');
        const btnDown = tr.querySelector('.btn-move-down');
        if (btnUp) btnUp.disabled = (index === 0);
        if (btnDown) btnDown.disabled = (index === rows.length - 1);
        
        tr.querySelectorAll('input, select').forEach(input => {
            if (input.name) {
                input.name = input.name.replace(/fields\[\d+\]/, 'fields[' + index + ']');
            }
        });
    });
}

let draggedRow = null;

function setupDragAndDrop(tr) {
    tr.draggable = true;
    tr.addEventListener('dragstart', function(e) {
        draggedRow = tr;
        tr.classList.add('dragging');
        e.dataTransfer.effectAllowed = 'move';
    });
    tr.addEventListener('dragend', function() {
        draggedRow = null;
        tr.classList.remove('dragging');
        document.querySelectorAll('#fields-tbody tr.field-row').forEach(r => r.classList.remove('drag-over'));
        reindexFields();
    });
    tr.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        if (draggedRow && draggedRow !== tr) {
            tr.classList.add('drag-over');
        }
    });
    tr.addEventListener('dragleave', function() {
        tr.classList.remove('drag-over');
    });
    tr.addEventListener('drop', function(e) {
        e.preventDefault();
        tr.classList.remove('drag-over');
        if (draggedRow && draggedRow !== tr) {
            const tbody = tr.parentNode;
            const rows = Array.from(tbody.querySelectorAll('tr.field-row'));
            const fromIndex = rows.indexOf(draggedRow);
            const toIndex = rows.indexOf(tr);
            if (fromIndex < toIndex) {
                tbody.insertBefore(draggedRow, tr.nextSibling);
            } else {
                tbody.insertBefore(draggedRow, tr);
            }
            reindexFields();
        }
    });
}

function addFieldRow() {
    const tbody = document.getElementById('fields-tbody');
    const idx = fieldCount++;
    const tr = document.createElement('tr');
    tr.className = 'field-row';
    tr.dataset.index = idx;

    let relationOptionsHtml = '<option value="">Vincular a...</option>';
    availableModules.forEach(mod => {
        if (mod.slug !== currentModuleSlug) {
            relationOptionsHtml += `<option value="${mod.slug}">${mod.name} (${mod.entity})</option>`;
        }
    });

    tr.innerHTML = `
        <td style="text-align: center; white-space: nowrap; width: 68px;">
            <div style="display: inline-flex; align-items: center; justify-content: center; gap: 3px;">
                <span class="drag-handle" title="Arraste para reordenar">⋮⋮</span>
                <div style="display: inline-flex; flex-direction: column; gap: 1px;">
                    <button type="button" class="btn-order btn-move-up" onclick="moveFieldUp(this)" title="Mover campo para cima">▲</button>
                    <button type="button" class="btn-order btn-move-down" onclick="moveFieldDown(this)" title="Mover campo para baixo">▼</button>
                </div>
                <span class="row-order-number" style="font-weight: 700; font-size: 0.78rem; color: var(--text-muted); min-width: 20px;">#${idx + 1}</span>
            </div>
        </td>
        <td>
            <input type="text" name="fields[${idx}][name]" placeholder="ex: campo_${idx}" required pattern="[a-z0-9_]{2,30}" style="font-family: monospace;">
        </td>
        <td>
            <input type="text" name="fields[${idx}][label]" placeholder="Rótulo legível" required>
        </td>
        <td>
            <select name="fields[${idx}][type]" onchange="handleTypeChange(this)">
                <option value="string" selected>Texto Curto</option>
                <option value="text">Texto Longo</option>
                <option value="number">Número</option>
                <option value="date">Data</option>
                <option value="select">Seleção</option>
                <option value="relation">Relação 1:N</option>
                <option value="many_to_many">Relação N:N</option>
                <option value="boolean">Sim / Não</option>
            </select>
        </td>
        <td>
            <div class="col-options-select" style="display: none;">
                <input type="text" name="fields[${idx}][options]" placeholder="Opção 1, Opção 2" disabled>
            </div>
            <div class="col-options-relation" style="display: none;">
                <select name="fields[${idx}][relation_target]" style="margin-bottom: 0.35rem;" disabled>
                    ${relationOptionsHtml}
                </select>
                <select name="fields[${idx}][relation_on_delete]" title="Ação ao Excluir o Registro Pai" style="font-size: 0.8rem; padding: 0.25rem 0.4rem;" disabled>
                    <option value="restrict" selected>🔒 Bloquear (Restrict)</option>
                    <option value="set_null">⚪ Desvincular (Set Null)</option>
                    <option value="cascade">💥 Em Cascata (Cascade)</option>
                </select>
            </div>
            <div class="col-options-many-to-many" style="display: none;">
                <select name="fields[${idx}][relation_target]" disabled>
                    <option value="">Vincular a...</option>
                    ${relationOptionsHtml.replace('<option value="">Vincular a...</option>', '')}
                </select>
            </div>
            <span class="cell-muted-dash">—</span>
        </td>
        <td style="text-align: center;">
            <input type="checkbox" name="fields[${idx}][required]" value="1">
        </td>
        <td style="text-align: center;">
            <input type="checkbox" name="fields[${idx}][unique]" value="1">
        </td>
        <td style="text-align: center;">
            <input type="checkbox" name="fields[${idx}][list]" value="1" checked>
        </td>
        <td style="text-align: center;">
            <button type="button" class="btn btn-danger btn-sm" onclick="removeFieldRow(this)" title="Remover Campo" style="padding: 0.2rem 0.45rem; font-size: 0.9rem; line-height: 1;">&times;</button>
        </td>
    `;
    tbody.appendChild(tr);
    setupDragAndDrop(tr);
    reindexFields();

    const nameInput = tr.querySelector('input[type="text"]');
    if (nameInput) nameInput.focus();
}

function removeFieldRow(btn) {
    const tbody = document.getElementById('fields-tbody');
    if (tbody.querySelectorAll('tr.field-row').length <= 1) {
        alert('A entidade deve possuir pelo menos um campo customizado.');
        return;
    }
    btn.closest('tr').remove();
    reindexFields();
}

document.querySelectorAll('#fields-tbody tr.field-row').forEach(tr => setupDragAndDrop(tr));
reindexFields();

// --- Catálogo e Seletor de Emojis / Ícones ---
const emojiCatalog = [
    {
        category: 'Gestão & Negócios',
        items: [
            { char: '📁', name: 'Pasta de Arquivos', tags: 'pasta arquivo pasta doc' },
            { char: '💼', name: 'Maleta / Negócios', tags: 'maleta trabalho empresa negocio' },
            { char: '📊', name: 'Gráfico de Barras', tags: 'relatorio estatistica dados analytics' },
            { char: '📈', name: 'Gráfico Ascendente', tags: 'crescimento evolucao lucro meta' },
            { char: '📋', name: 'Prancheta / Tarefas', tags: 'tarefas checklist lista afazeres' },
            { char: '📝', name: 'Anotações / Registro', tags: 'nota redacao rascunho formulario' },
            { char: '📅', name: 'Calendário / Agenda', tags: 'agenda prazo evento compromisso' },
            { char: '🏷️', name: 'Etiqueta / Tag', tags: 'categoria classificacao rotulo' },
            { char: '📌', name: 'Alfinete / Destaque', tags: 'marcador pino fixado' },
            { char: '🎯', name: 'Alvo / Objetivos', tags: 'meta kpi meta foco' },
            { char: '🏢', name: 'Edifício / Empresa', tags: 'organizacao predio matriz filial' },
            { char: '🏭', name: 'Indústria / Fábrica', tags: 'producao manufatura estoque planta' }
        ]
    },
    {
        category: 'Pessoas, Clientes & Contatos',
        items: [
            { char: '👥', name: 'Grupo de Usuários', tags: 'usuarios equipe time membros' },
            { char: '👤', name: 'Perfil / Indivíduo', tags: 'cliente usuario contato pessoa' },
            { char: '👔', name: 'Executivo / Gerente', tags: 'colaborador lider funcionario' },
            { char: '🧑‍💼', name: 'Atendimento / Operador', tags: 'atendente funcionario agente' },
            { char: '🤝', name: 'Parceria / Acordo', tags: 'contrato negociacao aperto de mao' },
            { char: '📞', name: 'Telefone / Chamadas', tags: 'ligacao suporte fone call' },
            { char: '✉️', name: 'E-mail / Correio', tags: 'mensagem correio contato caixa postal' },
            { char: '💬', name: 'Chat / Mensagens', tags: 'conversa comunicacao whatsapp' },
            { char: '🎓', name: 'Treinamento / Alunos', tags: 'educacao curso capacitacao escola' },
            { char: '🏥', name: 'Saúde / Pacientes', tags: 'clinica medico hospital atendimento' }
        ]
    },
    {
        category: 'Vendas, Financeiro & Operações',
        items: [
            { char: '💰', name: 'Saco de Moedas', tags: 'dinheiro financeiro capital caixa' },
            { char: '💳', name: 'Cartão de Crédito', tags: 'pagamento cartao cobranca fatura' },
            { char: '💵', name: 'Cédula / Dinheiro', tags: 'grana especie fluxo de caixa' },
            { char: '🧾', name: 'Recibo / Nota Fiscal', tags: 'fatura cupom comprovante fiscal' },
            { char: '🛒', name: 'Carrinho de Compras', tags: 'pedido ecommerce compras venda' },
            { char: '🛍️', name: 'Sacola de Compras', tags: 'loja varejo sacola' },
            { char: '📦', name: 'Pacote / Mercadoria', tags: 'estoque caixa produto entrega' },
            { char: '🚚', name: 'Caminhão / Frete', tags: 'entrega logistica despacho transporte' },
            { char: '⚖️', name: 'Balança / Jurídico', tags: 'advocacia lei contratos justica' },
            { char: '🪙', name: 'Moeda', tags: 'valor taxa cambio preco' }
        ]
    },
    {
        category: 'TI, Sistemas & Ferramentas',
        items: [
            { char: '💻', name: 'Notebook / Computador', tags: 'ti sistema computacao desenvolvimento' },
            { char: '🖥️', name: 'Monitor / Servidor', tags: 'computador terminal servidor tela' },
            { char: '📱', name: 'Celular / Mobile', tags: 'smartphone aplicativo aparelho' },
            { char: '🗄️', name: 'Armário / Banco de Dados', tags: 'banco de dados servidor storage arquivo' },
            { char: '🌐', name: 'Rede / Internet', tags: 'web portal online dominio' },
            { char: '⚙️', name: 'Engrenagens / Config', tags: 'configuracoes sistema parametros ajustes' },
            { char: '🔧', name: 'Ferramenta / Manutenção', tags: 'conserto suporte tecnico servico' },
            { char: '🔒', name: 'Cadeado / Segurança', tags: 'privacidade permissao acesso autenticacao' },
            { char: '🔑', name: 'Chave de Acesso', tags: 'token senha login credencial' },
            { char: '🖨️', name: 'Impressora', tags: 'documento impressao papel' },
            { char: '💡', name: 'Ideia / Lâmpada', tags: 'inovacao sugestao melhoria' },
            { char: '⭐', name: 'Estrela / Avaliação', tags: 'favorito destaque nota ranking' },
            { char: '🔔', name: 'Notificação / Sino', tags: 'alerta aviso lembrete' },
            { char: '🚩', name: 'Bandeira / Status', tags: 'marcador prioridade status bandeira' }
        ]
    }
];

function renderEmojiList(query = '') {
    const container = document.getElementById('emoji-list-scroll');
    if (!container) return;

    const q = query.trim().toLowerCase();
    let html = '';
    let totalFound = 0;

    emojiCatalog.forEach(cat => {
        const matches = cat.items.filter(item => {
            if (!q) return true;
            return item.char.includes(q) || 
                   item.name.toLowerCase().includes(q) || 
                   item.tags.toLowerCase().includes(q);
        });

        if (matches.length > 0) {
            totalFound += matches.length;
            html += `<div style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin: 0.5rem 0 0.3rem 0; letter-spacing: 0.04em;">${cat.category}</div>`;
            html += `<div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 4px; margin-bottom: 0.4rem;">`;
            matches.forEach(item => {
                const escapedChar = item.char.replace(/'/g, "\\'");
                html += `
                    <button type="button" 
                            onclick="selectEmoji('${escapedChar}')" 
                            title="${item.name}" 
                            style="font-size: 1.35rem; padding: 4px 0; height: 38px; border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.12s;"
                            onmouseover="this.style.background='#e2e8f0'; this.style.borderColor='#94a3b8'; this.style.transform='scale(1.15)';"
                            onmouseout="this.style.background='#f8fafc'; this.style.borderColor='#e2e8f0'; this.style.transform='scale(1)';">
                        ${item.char}
                    </button>
                `;
            });
            html += `</div>`;
        }
    });

    if (totalFound === 0) {
        html = `<div style="text-align: center; padding: 1.5rem 0.5rem; color: #64748b; font-size: 0.82rem;">Nenhum emoji encontrado para "<strong>${escapeHtml(query)}</strong>".</div>`;
    }

    container.innerHTML = html;
}

function escapeHtml(str) {
    return str.replace(/[&<>"']/g, m => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[m]));
}

function filterEmojis(val) {
    renderEmojiList(val);
}

function selectEmoji(char) {
    const input = document.getElementById('icon');
    if (input) {
        input.value = char;
        updateEmojiPreview(char);
    }
    closeEmojiPicker();
}

function updateEmojiPreview(val) {
    const preview = document.getElementById('emoji-preview-char');
    if (preview) {
        preview.textContent = val.trim() || '📁';
    }
}

function toggleEmojiPicker() {
    const dropdown = document.getElementById('emoji-picker-dropdown');
    if (!dropdown) return;
    if (dropdown.style.display === 'none' || !dropdown.style.display) {
        dropdown.style.display = 'block';
        const search = document.getElementById('emoji-search-input');
        if (search) {
            search.value = '';
            renderEmojiList('');
            setTimeout(() => search.focus(), 50);
        }
    } else {
        dropdown.style.display = 'none';
    }
}

function closeEmojiPicker() {
    const dropdown = document.getElementById('emoji-picker-dropdown');
    if (dropdown) dropdown.style.display = 'none';
}

document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('emoji-picker-dropdown');
    if (!dropdown || dropdown.style.display === 'none') return;
    
    const emojiWrapper = dropdown.parentElement;
    if (emojiWrapper && !emojiWrapper.contains(event.target)) {
        closeEmojiPicker();
    }
});

// Inicialização da lista de emojis
renderEmojiList();
</script>
