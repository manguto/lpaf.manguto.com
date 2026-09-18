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

            <div>
                <label for="icon">Ícone / Emoji:</label>
                <input type="text" id="icon" name="icon" value="<?= e($module['icon'] ?? '📁') ?>" maxlength="4" style="text-align: center; font-size: 1.2rem;">
                <span class="muted" style="font-size: 0.75rem;">Emoji visual no dashboard.</span>
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
                                        <select name="fields[<?= $idx ?>][relation_target]" <?= $isManyToMany ? 'required' : 'disabled' ?> style="margin-bottom: 0.35rem;">
                                            <option value="">Vincular a...</option>
                                            <?php foreach (($allModules ?? []) as $modSlug => $mod): ?>
                                                <?php if ($modSlug !== ($module['slug'] ?? '')): ?>
                                                    <option value="<?= e($modSlug) ?>" <?= $relTarget === $modSlug ? 'selected' : '' ?>>
                                                        <?= e($mod['name']) ?> (<?= e($mod['entity']) ?>)
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="text" name="fields[<?= $idx ?>][pivot_file]" value="<?= e($pivotFile) ?>" placeholder="Arquivo pivô (ex: <?= e($module['slug'] ?? 'modulo') ?>_itens.csv)" style="font-size: 0.8rem; padding: 0.25rem 0.4rem;" title="Opcional: nome do arquivo CSV pivô" <?= $isManyToMany ? '' : 'disabled' ?>>
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
                                    <select name="fields[0][relation_target]" style="margin-bottom: 0.35rem;" disabled>
                                        <option value="">Vincular a...</option>
                                        <?php foreach (($allModules ?? []) as $modSlug => $mod): ?>
                                            <option value="<?= e($modSlug) ?>">
                                                <?= e($mod['name']) ?> (<?= e($mod['entity']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="fields[0][pivot_file]" placeholder="Arquivo pivô (ex: pivot.csv)" style="font-size: 0.8rem; padding: 0.25rem 0.4rem;" title="Opcional: nome do arquivo CSV pivô" disabled>
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
                <select name="fields[${idx}][relation_target]" style="margin-bottom: 0.35rem;" disabled>
                    <option value="">Vincular a...</option>
                    ${relationOptionsHtml.replace('<option value="">Vincular a...</option>', '')}
                </select>
                <input type="text" name="fields[${idx}][pivot_file]" placeholder="Arquivo pivô (ex: pivot.csv)" style="font-size: 0.8rem; padding: 0.25rem 0.4rem;" title="Opcional: nome do arquivo CSV pivô" disabled>
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
</script>
