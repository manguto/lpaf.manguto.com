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
