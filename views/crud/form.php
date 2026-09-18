<a href="<?= url($app, '/app/' . $module['slug']) ?>" class="back-link">&larr; Voltar para <?= e($module['name']) ?></a>

<h1><?= $isEdit ? 'Editar' : 'Novo(a)' ?> <?= e($module['entity']) ?></h1>
<p class="muted" style="margin-bottom: 2rem;">Preencha os dados abaixo para <?= $isEdit ? 'atualizar o' : 'cadastrar um novo' ?> registro.</p>

<form method="post" action="<?= $isEdit ? url($app, '/app/' . $module['slug'] . '/' . $item['id']) : url($app, '/app/' . $module['slug']) ?>" style="max-width: 720px;">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

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
                    ?>
                    <select id="field_<?= e($key) ?>" name="<?= e($key) ?>" <?= $required ? 'required' : '' ?>>
                        <option value="">Selecione um(a) <?= e($relInfo['target_entity']) ?>...</option>
                        <?php foreach ($relItems as $relOpt): ?>
                            <option value="<?= e($relOpt['id']) ?>" <?= $val === (string) $relOpt['id'] ? 'selected' : '' ?>>
                                <?= e($relOpt['label']) ?> (<?= e($relOpt['id']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($relItems)): ?>
                        <span class="muted" style="font-size: 0.775rem; display: block; margin-top: 0.35rem;">
                            ⚠️ Nenhum(a) <strong><?= e($relInfo['target_entity']) ?></strong> cadastrado(a) ainda. 
                            <a href="<?= url($app, '/app/' . $relInfo['target'] . '/create') ?>" target="_blank" style="color: var(--primary); font-weight: 600;">Cadastrar agora &rarr;</a>
                        </span>
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
function filterM2MList(input, containerId) {
    const q = input.value.toLowerCase().trim();
    const container = document.getElementById(containerId);
    if (!container) return;
    const cards = container.querySelectorAll('.m2m-item-card');
    cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        card.style.display = text.includes(q) ? 'flex' : 'none';
    });
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
