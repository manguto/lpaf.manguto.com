<a href="<?= url($app, '/dev/modules') ?>" class="back-link">&larr; Voltar para Módulos</a>

<div style="margin-bottom: 2rem;">
    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
        <h1>Entity Builder</h1>
        <span class="badge badge-purple">Dev-End Studio</span>
    </div>
    <p class="muted">Defina uma nova entidade administrativa. O sistema gerará automaticamente o módulo, rotas, persistência CSV, formulários e permissões com salvaguarda de segurança.</p>
</div>

<form method="post" action="<?= url($app, '/dev/entity-builder') ?>" id="entity-builder-form">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <!-- Informações Gerais -->
    <div class="card" style="margin-bottom: 2rem;">
        <h2 style="font-size: 1.2rem; margin-bottom: 1.25rem;">1. Informações da Entidade</h2>

        <div class="grid-2" style="gap: 1.25rem; margin-bottom: 1rem;">
            <div>
                <label for="name">Nome do Módulo (Plural): <span style="color: var(--danger);">*</span></label>
                <input type="text" id="name" name="name" placeholder="Ex: Clientes, Projetos, Veículos" required oninput="autoGenerateSlug(this.value)">
                <span class="muted" style="font-size: 0.75rem;">Nome de exibição nas listagens e menus.</span>
            </div>

            <div>
                <label for="entity">Nome da Entidade (Singular): <span style="color: var(--danger);">*</span></label>
                <input type="text" id="entity" name="entity" placeholder="Ex: Cliente, Projeto, Veículo" required oninput="autoGeneratePrefix(this.value)">
                <span class="muted" style="font-size: 0.75rem;">Utilizado nos botões de cadastro (+ Novo Cliente).</span>
            </div>
        </div>

        <div class="grid-3" style="gap: 1.25rem; margin-bottom: 1rem;">
            <div>
                <label for="slug">Slug da URL: <span style="color: var(--danger);">*</span></label>
                <input type="text" id="slug" name="slug" placeholder="Ex: clientes" required pattern="[a-z0-9_-]{3,30}">
                <span class="muted" style="font-size: 0.75rem;">Rota de acesso: <code>/app/{slug}</code>.</span>
            </div>

            <div>
                <label for="prefix">Prefixo de ID: <span style="color: var(--danger);">*</span></label>
                <input type="text" id="prefix" name="prefix" placeholder="Ex: cli" required maxlength="6" pattern="[a-z0-9]{2,6}">
                <span class="muted" style="font-size: 0.75rem;">Identificadores gerados: <code>cli_001</code>.</span>
            </div>

            <div>
                <label for="icon">Ícone / Emoji:</label>
                <input type="text" id="icon" name="icon" value="📁" maxlength="4" style="text-align: center; font-size: 1.2rem;">
                <span class="muted" style="font-size: 0.75rem;">Emoji visual no dashboard.</span>
            </div>
        </div>

        <div>
            <label for="description">Descrição da Funcionalidade:</label>
            <input type="text" id="description" name="description" placeholder="Ex: Controle e cadastro de clientes corporativos e contatos.">
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
                    <!-- Linha 1 padrão -->
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
            Criar e Ativar Entidade
        </button>
        <a href="<?= url($app, '/dev/modules') ?>" class="btn btn-secondary">
            Cancelar
        </a>
    </div>
</form>

<script>
let fieldCount = 1;

function autoGenerateSlug(value) {
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
