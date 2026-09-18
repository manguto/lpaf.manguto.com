<a href="<?= url($app, '/app/' . $module['slug']) ?>" class="back-link">&larr; Voltar para <?= e($module['name']) ?></a>

<h1><?= $isEdit ? 'Editar' : 'Novo(a)' ?> <?= e($module['entity']) ?></h1>
<p class="muted" style="margin-bottom: 2rem;">Preencha os dados abaixo para <?= $isEdit ? 'atualizar o' : 'cadastrar um novo' ?> registro.</p>

<form method="post" action="<?= $isEdit ? url($app, '/app/' . $module['slug'] . '/' . $item['id']) : url($app, '/app/' . $module['slug']) ?>" style="max-width: 720px;">
    <input type="hidden" name="_csrf" value="<?= e($csrf ?? '') ?>">

    <?php if ($isEdit && !empty($item['id'])): ?>
        <div style="margin-bottom: 1rem;">
            <label>Identificador (ID):</label>
            <input type="text" value="<?= e($item['id']) ?>" readonly style="background-color: var(--bg-muted); color: var(--text-muted); cursor: not-allowed;">
        </div>
    <?php endif; ?>

    <?php foreach ($module['fields'] as $key => $f): ?>
        <?php
        $type = $f['type'] ?? 'string';
        $label = $f['label'] ?? ucfirst($key);
        $val = $item ? ($item[$key] ?? '') : (isset($f['default']) ? (string) $f['default'] : '');
        $required = !empty($f['required']);
        ?>

        <div style="margin-bottom: 1.25rem;">
            <?php if ($type === 'boolean'): ?>
                <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; margin-top: 1rem;">
                    <input type="checkbox" name="<?= e($key) ?>" value="1" <?= ($val === '1' || $val === 'true' || (!$item && !empty($f['default']))) ? 'checked' : '' ?>>
                    <span style="font-weight: 600; color: var(--text-main);"><?= e($label) ?></span>
                </label>
            <?php else: ?>
                <label for="field_<?= e($key) ?>">
                    <?= e($label) ?><?= $required ? ' <span style="color: var(--danger);">*</span>' : '' ?>:
                </label>

                <?php if ($type === 'select'): ?>
                    <select id="field_<?= e($key) ?>" name="<?= e($key) ?>" <?= $required ? 'required' : '' ?>>
                        <option value="">Selecione...</option>
                        <?php foreach (($f['options'] ?? []) as $opt): ?>
                            <option value="<?= e($opt) ?>" <?= $val === (string) $opt ? 'selected' : '' ?>>
                                <?= e($opt) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($type === 'relation'): ?>
                    <?php 
                    $relInfo = $relations[$key] ?? ['items' => [], 'target' => '', 'target_entity' => 'Registro']; 
                    $relItems = $relInfo['items'] ?? [];
                    $selectedLabel = '';
                    foreach ($relItems as $relOpt) {
                        if ($val === (string) $relOpt['id']) {
                            $selectedLabel = $relOpt['label'];
                            break;
                        }
                    }
                    ?>
                    <?php if (empty($relItems)): ?>
                        <input type="hidden" name="<?= e($key) ?>" id="field_<?= e($key) ?>" value="">
                        <input type="text" class="autocomplete-input" value="" placeholder="Nenhum(a) <?= e($relInfo['target_entity']) ?> cadastrado(a)" disabled>
                        <span class="muted" style="font-size: 0.775rem; display: block; margin-top: 0.35rem;">
                            ⚠️ Nenhum(a) <strong><?= e($relInfo['target_entity']) ?></strong> cadastrado(a) ainda. 
                            <a href="<?= url($app, '/app/' . $relInfo['target'] . '/create') ?>" target="_blank" style="color: var(--primary); font-weight: 600;">Cadastrar agora &rarr;</a>
                        </span>
                    <?php else: ?>
                        <div class="autocomplete-container" id="autocomplete_<?= e($key) ?>">
                            <input type="hidden" name="<?= e($key) ?>" id="field_<?= e($key) ?>" value="<?= e($val) ?>">
                            
                            <div class="autocomplete-input-wrapper">
                                <input type="text" 
                                       id="autocomplete_input_<?= e($key) ?>" 
                                       class="autocomplete-input" 
                                       placeholder="Buscar e selecionar <?= e($relInfo['target_entity']) ?>..." 
                                       value="<?= e($selectedLabel) ?>" 
                                       autocomplete="off"
                                       <?= $required ? 'required' : '' ?>
                                       onfocus="openAutocomplete('<?= e($key) ?>')"
                                       oninput="filterAutocomplete('<?= e($key) ?>', this.value)"
                                       onkeydown="navigateAutocomplete('<?= e($key) ?>', event)">
                                
                                <div class="autocomplete-actions">
                                    <button type="button" 
                                            id="autocomplete_clear_<?= e($key) ?>" 
                                            class="autocomplete-clear-btn" 
                                            title="Limpar seleção" 
                                            style="<?= $val !== '' ? 'display: block;' : 'display: none;' ?>" 
                                            onclick="clearAutocomplete('<?= e($key) ?>')">
                                        &times;
                                    </button>
                                    <span class="autocomplete-arrow" onclick="toggleAutocomplete('<?= e($key) ?>')">▾</span>
                                </div>
                            </div>

                            <div class="autocomplete-dropdown" id="autocomplete_dropdown_<?= e($key) ?>">
                                <div class="autocomplete-options" id="autocomplete_options_<?= e($key) ?>">
                                    <?php foreach ($relItems as $relOpt): ?>
                                        <?php $isSelected = ($val === (string) $relOpt['id']); ?>
                                        <div class="autocomplete-option <?= $isSelected ? 'is-selected' : '' ?>" 
                                             data-id="<?= e($relOpt['id']) ?>" 
                                             data-label="<?= e($relOpt['label']) ?>"
                                             onclick="selectAutocompleteOption('<?= e($key) ?>', '<?= e($relOpt['id']) ?>', this.getAttribute('data-label'))">
                                            <span class="autocomplete-text"><?= e($relOpt['label']) ?></span>
                                            <code class="autocomplete-badge"><?= e($relOpt['id']) ?></code>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="autocomplete-empty" id="autocomplete_empty_<?= e($key) ?>" style="display: none;">
                                    Nenhum registro encontrado.
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php elseif ($type === 'many_to_many'): ?>
                    <?php
                    $m2mConfig = $manyToMany[$key] ?? ['items' => [], 'selected' => [], 'target' => '', 'target_name' => 'Itens', 'target_entity' => 'Registro'];
                    $m2mItems = $m2mConfig['items'] ?? [];
                    $selectedIds = $m2mConfig['selected'] ?? [];
                    ?>
                    <div class="m2m-container" style="border: 1.5px solid var(--border-color); border-radius: var(--radius-md); padding: 0.85rem; background-color: var(--bg-card); box-shadow: var(--shadow-sm); margin-top: 0.35rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.65rem; flex-wrap: wrap; gap: 0.5rem;">
                            <input type="text" 
                                   placeholder="Filtrar <?= e($m2mConfig['target_name']) ?>..." 
                                   oninput="filterM2MList(this, 'm2m_list_<?= e($key) ?>')" 
                                   style="margin: 0; padding: 0.35rem 0.6rem; font-size: 0.85rem; max-width: 240px; height: 32px;">
                            
                            <div style="display: flex; gap: 0.4rem;">
                                <button type="button" class="btn btn-secondary btn-sm" style="padding: 0.2rem 0.55rem; font-size: 0.75rem;" onclick="toggleAllM2M('m2m_list_<?= e($key) ?>', true)">
                                    Marcar Todos
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" style="padding: 0.2rem 0.55rem; font-size: 0.75rem;" onclick="toggleAllM2M('m2m_list_<?= e($key) ?>', false)">
                                    Desmarcar Todos
                                </button>
                            </div>
                        </div>

                        <?php if (empty($m2mItems)): ?>
                            <p class="muted" style="font-size: 0.85rem; margin-bottom: 0;">
                                ⚠️ Nenhum(a) <strong><?= e($m2mConfig['target_entity']) ?></strong> disponível para vincular.
                                <a href="<?= url($app, '/app/' . $m2mConfig['target'] . '/create') ?>" target="_blank" style="color: var(--primary); font-weight: 600;">Cadastrar <?= e($m2mConfig['target_entity']) ?> &rarr;</a>
                            </p>
                        <?php else: ?>
                            <div id="m2m_list_<?= e($key) ?>" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 0.5rem; max-height: 220px; overflow-y: auto; padding: 0.25rem;">
                                <?php foreach ($m2mItems as $mItem): ?>
                                    <?php $isChecked = in_array((string) $mItem['id'], $selectedIds, true); ?>
                                    <label class="m2m-item-card" style="display: flex; align-items: center; gap: 0.6rem; padding: 0.45rem 0.65rem; border: 1.5px solid var(--border-color); border-radius: var(--radius-sm); cursor: pointer; background: var(--bg-page); transition: var(--transition);">
                                        <input type="checkbox" name="<?= e($key) ?>[]" value="<?= e($mItem['id']) ?>" <?= $isChecked ? 'checked' : '' ?> style="margin: 0; width: 16px; height: 16px; accent-color: var(--primary);">
                                        <span style="font-size: 0.85rem; font-weight: 600; color: var(--text-main); flex: 1;"><?= e($mItem['label']) ?></span>
                                        <code style="font-size: 0.72rem; color: var(--text-muted);"><?= e($mItem['id']) ?></code>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php elseif ($type === 'text'): ?>
                    <textarea id="field_<?= e($key) ?>" name="<?= e($key) ?>" rows="4" <?= $required ? 'required' : '' ?>><?= e($val) ?></textarea>
                <?php elseif ($type === 'date'): ?>
                    <input type="date" id="field_<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($val) ?>" <?= $required ? 'required' : '' ?>>
                <?php elseif ($type === 'number'): ?>
                    <input type="number" step="any" id="field_<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($val) ?>" <?= $required ? 'required' : '' ?>>
                <?php else: ?>
                    <input type="text" id="field_<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($val) ?>" <?= $required ? 'required' : '' ?>>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($f['help'])): ?>
                <span class="muted" style="font-size: 0.775rem; display: block; margin-top: 0.25rem;">
                    <?= e($f['help']) ?>
                </span>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div style="display: flex; gap: 0.75rem; align-items: center; border-top: 1px solid var(--border-color); padding-top: 1.25rem; margin-top: 1.5rem;">
        <button type="submit" class="btn btn-primary">
            <?= $isEdit ? 'Atualizar Registro' : 'Salvar Registro' ?>
        </button>
        <a href="<?= url($app, '/app/' . $module['slug']) ?>" class="btn btn-secondary">Cancelar</a>
    </div>
</form>

<script>
// --- Busca Assistida / Autocomplete (1:N) ---
function escapeHtml(str) {
    return str.replace(/[&<>"']/g, m => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[m]));
}

function openAutocomplete(key) {
    document.querySelectorAll('.autocomplete-container.is-open').forEach(c => {
        if (c.id !== 'autocomplete_' + key) c.classList.remove('is-open');
    });
    const container = document.getElementById('autocomplete_' + key);
    if (container) {
        container.classList.add('is-open');
        const options = container.querySelectorAll('.autocomplete-option');
        options.forEach(opt => opt.style.display = 'flex');
        const emptyState = document.getElementById('autocomplete_empty_' + key);
        if (emptyState) emptyState.style.display = 'none';
    }
}

function toggleAutocomplete(key) {
    const container = document.getElementById('autocomplete_' + key);
    const input = document.getElementById('autocomplete_input_' + key);
    if (!container) return;
    if (container.classList.contains('is-open')) {
        container.classList.remove('is-open');
    } else {
        openAutocomplete(key);
        if (input) input.focus();
    }
}

function filterAutocomplete(key, query) {
    const q = query.toLowerCase().trim();
    const container = document.getElementById('autocomplete_' + key);
    if (!container) return;
    container.classList.add('is-open');

    const optionsContainer = document.getElementById('autocomplete_options_' + key);
    const emptyState = document.getElementById('autocomplete_empty_' + key);
    const options = optionsContainer.querySelectorAll('.autocomplete-option');
    let visibleCount = 0;

    options.forEach(opt => {
        const label = (opt.getAttribute('data-label') || '').toLowerCase();
        const id = (opt.getAttribute('data-id') || '').toLowerCase();
        const match = label.includes(q) || id.includes(q);
        opt.style.display = match ? 'flex' : 'none';
        opt.classList.remove('is-focused');
        if (match) visibleCount++;
    });

    if (emptyState) {
        if (visibleCount === 0) {
            emptyState.style.display = 'block';
            emptyState.innerHTML = `Nenhum registro encontrado para "<strong>${escapeHtml(query)}</strong>".`;
        } else {
            emptyState.style.display = 'none';
        }
    }

    const hidden = document.getElementById('field_' + key);
    const clearBtn = document.getElementById('autocomplete_clear_' + key);
    if (q === '') {
        if (hidden) hidden.value = '';
        if (clearBtn) clearBtn.style.display = 'none';
    }
}

function selectAutocompleteOption(key, id, label) {
    const hidden = document.getElementById('field_' + key);
    const input = document.getElementById('autocomplete_input_' + key);
    const clearBtn = document.getElementById('autocomplete_clear_' + key);
    const container = document.getElementById('autocomplete_' + key);

    if (hidden) hidden.value = id;
    if (input) {
        input.value = label;
        input.setCustomValidity('');
    }
    if (clearBtn) clearBtn.style.display = 'block';

    const options = container.querySelectorAll('.autocomplete-option');
    options.forEach(opt => {
        const isThis = opt.getAttribute('data-id') === id;
        opt.classList.toggle('is-selected', isThis);
        opt.style.display = 'flex';
    });

    container.classList.remove('is-open');
}

function clearAutocomplete(key) {
    const hidden = document.getElementById('field_' + key);
    const input = document.getElementById('autocomplete_input_' + key);
    const clearBtn = document.getElementById('autocomplete_clear_' + key);
    const container = document.getElementById('autocomplete_' + key);

    if (hidden) hidden.value = '';
    if (input) {
        input.value = '';
        input.focus();
    }
    if (clearBtn) clearBtn.style.display = 'none';

    const options = container.querySelectorAll('.autocomplete-option');
    options.forEach(opt => {
        opt.classList.remove('is-selected');
        opt.style.display = 'flex';
    });

    const emptyState = document.getElementById('autocomplete_empty_' + key);
    if (emptyState) emptyState.style.display = 'none';

    container.classList.add('is-open');
}

function navigateAutocomplete(key, event) {
    const container = document.getElementById('autocomplete_' + key);
    if (!container) return;

    if (event.key === 'Escape') {
        container.classList.remove('is-open');
        return;
    }

    if (!container.classList.contains('is-open') && (event.key === 'ArrowDown' || event.key === 'ArrowUp')) {
        openAutocomplete(key);
        return;
    }

    const options = Array.from(container.querySelectorAll('.autocomplete-option')).filter(o => o.style.display !== 'none');
    if (options.length === 0) return;

    let focusedIdx = options.findIndex(o => o.classList.contains('is-focused'));

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        if (focusedIdx >= 0) options[focusedIdx].classList.remove('is-focused');
        focusedIdx = (focusedIdx + 1) % options.length;
        options[focusedIdx].classList.add('is-focused');
        options[focusedIdx].scrollIntoView({ block: 'nearest' });
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        if (focusedIdx >= 0) options[focusedIdx].classList.remove('is-focused');
        focusedIdx = (focusedIdx - 1 + options.length) % options.length;
        options[focusedIdx].classList.add('is-focused');
        options[focusedIdx].scrollIntoView({ block: 'nearest' });
    } else if (event.key === 'Enter') {
        if (focusedIdx >= 0) {
            event.preventDefault();
            options[focusedIdx].click();
        }
    }
}

// Fechar ao clicar fora e garantir integridade de rótulo
document.addEventListener('click', function(e) {
    document.querySelectorAll('.autocomplete-container.is-open').forEach(container => {
        if (!container.contains(e.target)) {
            container.classList.remove('is-open');
            const key = container.id.replace('autocomplete_', '');
            const hidden = document.getElementById('field_' + key);
            const input = document.getElementById('autocomplete_input_' + key);
            if (hidden && input) {
                if (hidden.value === '') {
                    input.value = '';
                } else {
                    const selectedOpt = container.querySelector('.autocomplete-option[data-id="' + hidden.value + '"]');
                    if (selectedOpt) {
                        input.value = selectedOpt.getAttribute('data-label') || '';
                    }
                }
            }
        }
    });
});

// Validação prévia de campos obrigatórios no submit
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const autocompletes = form.querySelectorAll('.autocomplete-container');
        autocompletes.forEach(container => {
            const key = container.id.replace('autocomplete_', '');
            const hidden = document.getElementById('field_' + key);
            const input = document.getElementById('autocomplete_input_' + key);
            if (input && input.hasAttribute('required') && (!hidden || hidden.value.trim() === '')) {
                input.value = '';
            }
        });
    });
});

// --- Filtro de Relacionamentos N:N ---
function filterM2MList(input, containerId) {
    const q = input.value.toLowerCase().trim();
    const container = document.getElementById(containerId);
    if (!container) return;
    const cards = container.querySelectorAll('.m2m-item-card');
    let visibleCount = 0;
    cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        const match = text.includes(q);
        card.style.display = match ? 'flex' : 'none';
        if (match) visibleCount++;
    });

    let emptyMsg = container.querySelector('.m2m-filter-empty');
    if (!emptyMsg) {
        emptyMsg = document.createElement('div');
        emptyMsg.className = 'm2m-filter-empty muted';
        emptyMsg.style.cssText = 'grid-column: 1 / -1; text-align: center; padding: 1rem; font-size: 0.85rem;';
        container.appendChild(emptyMsg);
    }
    if (visibleCount === 0 && cards.length > 0) {
        emptyMsg.style.display = 'block';
        emptyMsg.innerHTML = `Nenhum registro encontrado para "<strong>${escapeHtml(input.value)}</strong>".`;
    } else {
        emptyMsg.style.display = 'none';
    }
}

function toggleAllM2M(containerId, check) {
    const container = document.getElementById(containerId);
    if (!container) return;
    const cards = container.querySelectorAll('.m2m-item-card');
    cards.forEach(card => {
        if (card.style.display !== 'none') {
            const cb = card.querySelector('input[type="checkbox"]');
            if (cb) cb.checked = check;
        }
    });
}
</script>
