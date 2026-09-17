<a href="<?= url($app, '/app/' . $module['slug']) ?>" class="back-link">&larr; Voltar para <?= e($module['name']) ?></a>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1><?= e($module['entity']) ?>: <code><?= e($item['id'] ?? '') ?></code></h1>
        <p class="muted">Detalhes do registro cadastrado no módulo <?= e($module['name']) ?>.</p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <?php if (can($app, ($module['permission_prefix'] ?? $module['slug']) . '.edit')): ?>
            <a class="btn btn-primary btn-sm" href="<?= url($app, '/app/' . $module['slug'] . '/' . $item['id'] . '/edit') ?>">
                Editar Registro
            </a>
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
