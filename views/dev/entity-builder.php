<?php
$isEdit = !empty($isEdit);
$module = $module ?? [];
$fields = $module['fields'] ?? [];
?>
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

<form method="post" action="<?= $isEdit ? url($app, '/dev/modules/' . ($module['slug'] ?? '') . '/edit') : url($app, '/dev/entity-builder') ?>" id="entity-builder-form">
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
                <label for="slug">Slug da URL: <span style="color: var(--danger);">*</span></label>
                <input type="text" id="slug" name="slug" value="<?= e($module['slug'] ?? '') ?>" placeholder="Ex: clientes" required pattern="[a-z0-9_-]{3,30}" <?= $isEdit ? 'readonly style="background-color: var(--card-bg-alt, #1e293b); cursor: not-allowed;"' : '' ?>>
                <span class="muted" style="font-size: 0.75rem;">Rota de acesso: <code>/app/<?= e($module['slug'] ?? '{slug}') ?></code>.</span>
            </div>

            <div>
                <label for="prefix">Prefixo de ID: <span style="color: var(--danger);">*</span></label>
                <input type="text" id="prefix" name="prefix" value="<?= e($module['prefix'] ?? '') ?>" placeholder="Ex: cli" required maxlength="6" pattern="[a-z0-9]{2,6}" <?= $isEdit ? 'readonly style="background-color: var(--card-bg-alt, #1e293b); cursor: not-allowed;"' : '' ?>>
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
                <h2 style="font-size: 1.2rem; margin-bottom: 0.25rem;">2. Estrutura de Campos</h2>
                <p class="muted" style="font-size: 0.85rem;">Os campos <code>id</code>, <code>created_at</code> e <code>updated_at</code> são gerenciados automaticamente pela plataforma.</p>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="addFieldRow()">
                + Adicionar Campo
            </button>
        </div>

        <div class="table-container" style="margin-bottom: 1rem;">
            <table id="fields-table">
                <thead>
                    <tr>
                        <th style="min-width: 150px;">Nome do Campo (snake_case)</th>
                        <th style="min-width: 170px;">Rótulo (Label)</th>
                        <th style="min-width: 140px;">Tipo de Dado</th>
                        <th style="min-width: 180px;">Opções (para Select)</th>
                        <th style="text-align: center; width: 80px;">Obrigatório</th>
                        <th style="text-align: center; width: 70px;">Único</th>
                        <th style="text-align: center; width: 70px;">Na Lista</th>
                        <th style="text-align: center; width: 60px;">Ação</th>
                    </tr>
                </thead>
                <tbody id="fields-tbody">
                    <?php if ($isEdit && !empty($fields)): ?>
                        <?php $idx = 0; foreach ($fields as $fname => $f): ?>
                            <tr data-index="<?= $idx ?>">
                                <td>
                                    <input type="text" name="fields[<?= $idx ?>][name]" value="<?= e($fname) ?>" placeholder="ex: nome" required pattern="[a-z0-9_]{2,30}" style="font-family: monospace;">
                                </td>
                                <td>
                                    <input type="text" name="fields[<?= $idx ?>][label]" value="<?= e($f['label'] ?? ucfirst($fname)) ?>" placeholder="ex: Nome Completo" required>
                                </td>
                                <td>
                                    <select name="fields[<?= $idx ?>][type]" onchange="handleTypeChange(this)">
                                        <option value="string" <?= ($f['type'] ?? 'string') === 'string' ? 'selected' : '' ?>>Texto Curto</option>
                                        <option value="text" <?= ($f['type'] ?? '') === 'text' ? 'selected' : '' ?>>Texto Longo</option>
                                        <option value="number" <?= ($f['type'] ?? '') === 'number' ? 'selected' : '' ?>>Número</option>
                                        <option value="date" <?= ($f['type'] ?? '') === 'date' ? 'selected' : '' ?>>Data</option>
                                        <option value="select" <?= ($f['type'] ?? '') === 'select' ? 'selected' : '' ?>>Seleção (Select)</option>
                                        <option value="boolean" <?= ($f['type'] ?? '') === 'boolean' ? 'selected' : '' ?>>Sim / Não (Boolean)</option>
                                    </select>
                                </td>
                                <td>
                                    <?php
                                    $optionsVal = isset($f['options']) && is_array($f['options']) ? implode(', ', $f['options']) : '';
                                    ?>
                                    <input type="text" name="fields[<?= $idx ?>][options]" value="<?= e($optionsVal) ?>" placeholder="Opção 1, Opção 2" style="display: <?= ($f['type'] ?? '') === 'select' ? 'block' : 'none' ?>;">
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
                                    <button type="button" class="btn btn-danger btn-sm" onclick="removeFieldRow(this)" title="Remover Campo" style="padding: 0.25rem 0.5rem;">&times;</button>
                                </td>
                            </tr>
                        <?php $idx++; endforeach; ?>
                    <?php else: ?>
                        <tr data-index="0">
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
                                    <option value="select">Seleção (Select)</option>
                                    <option value="boolean">Sim / Não (Boolean)</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="fields[0][options]" placeholder="Opção 1, Opção 2" style="display: none;">
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
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeFieldRow(this)" title="Remover Campo" style="padding: 0.25rem 0.5rem;">&times;</button>
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
    const optionsInput = row.querySelector('input[name*="[options]"]');
    if (selectElement.value === 'select') {
        optionsInput.style.display = 'block';
        optionsInput.required = true;
    } else {
        optionsInput.style.display = 'none';
        optionsInput.required = false;
    }
}

function addFieldRow() {
    const tbody = document.getElementById('fields-tbody');
    const idx = fieldCount++;
    const tr = document.createElement('tr');
    tr.dataset.index = idx;
    tr.innerHTML = `
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
                <option value="select">Seleção (Select)</option>
                <option value="boolean">Sim / Não (Boolean)</option>
            </select>
        </td>
        <td>
            <input type="text" name="fields[${idx}][options]" placeholder="Opção 1, Opção 2" style="display: none;">
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
            <button type="button" class="btn btn-danger btn-sm" onclick="removeFieldRow(this)" title="Remover Campo" style="padding: 0.25rem 0.5rem;">&times;</button>
        </td>
    `;
    tbody.appendChild(tr);
}

function removeFieldRow(btn) {
    const tbody = document.getElementById('fields-tbody');
    if (tbody.querySelectorAll('tr').length <= 1) {
        alert('A entidade deve possuir pelo menos um campo customizado.');
        return;
    }
    btn.closest('tr').remove();
}
</script>
