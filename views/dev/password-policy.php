<a href="<?= url($app, '/dev') ?>" class="back-link">&larr; Voltar para o Dev-End</a>

<?php $flashMessage = \App\Core\Session::flash('message'); ?>
<?php if (!empty($flashMessage)): ?>
    <div class="alert alert-success" style="margin-bottom: 1.5rem;">
        <span><?= e($flashMessage) ?></span>
    </div>
<?php endif; ?>

<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="display: flex; align-items: center; gap: 0.75rem;">
            <h1>Política e Governança de Senhas</h1>
            <?php if (!empty($policy['enabled'])): ?>
                <span class="badge badge-success" style="font-size: 0.8rem; font-weight: 700;">Ativa (Restrita)</span>
            <?php else: ?>
                <span class="badge badge-purple" style="font-size: 0.8rem; font-weight: 700;">Modo Dev Livre (Desativada)</span>
            <?php endif; ?>
        </div>
        <p class="muted">Habilite, calibre ou desative parâmetros de complexidade para criação e alteração de credenciais.</p>
    </div>

    <!-- Botões de Predefinição Rápida -->
    <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
        <button type="button" class="btn btn-secondary btn-sm" onclick="applyPreset('free')" style="border-color: #cbd5e1; font-weight: 600;">
            ⚡ Preset: Modo Dev Livre
        </button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="applyPreset('balanced')" style="border-color: #3b82f6; color: #2563eb; font-weight: 600;">
            🛡️ Preset: Padrão Seguro
        </button>
        <button type="button" class="btn btn-secondary btn-sm" onclick="applyPreset('strict')" style="border-color: #7c3aed; color: #7c3aed; font-weight: 600;">
            🔒 Preset: Alta Segurança
        </button>
    </div>
</div>

<form method="post" action="<?= url($app, '/dev/password-policy') ?>" style="padding: 0; background: transparent; border: 0; box-shadow: none;">
    <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

    <div class="grid-2" style="gap: 1.5rem; align-items: flex-start;">
        <!-- Coluna 1: Formulário de Configurações -->
        <div>
            <!-- Card 1: Toggle Principal -->
            <div class="card" style="margin-bottom: 1.25rem; border: 2px solid <?= !empty($policy['enabled']) ? '#3b82f6' : '#cbd5e1' ?>;">
                <label style="margin: 0; display: flex; align-items: flex-start; gap: 0.85rem; cursor: pointer;">
                    <input type="checkbox" id="enabled" name="enabled" value="1" <?= !empty($policy['enabled']) ? 'checked' : '' ?> onchange="togglePolicyControls(this.checked)" style="width: 22px; height: 22px; margin-top: 0.15rem; cursor: pointer;">
                    <div>
                        <strong style="font-size: 1.05rem; color: #0f172a; display: block; margin-bottom: 0.25rem;">
                            Exigir Política de Complexidade de Senhas
                        </strong>
                        <span class="muted" style="font-size: 0.875rem; line-height: 1.45; display: block;">
                            Quando <strong>desmarcado</strong>, o sistema opera no modo livre: qualquer senha simples (ex: <code>123</code>, <code>dev</code>) é aceita sem restrições. Ideal para fase de desenvolvimento e testes rápidos.
                        </span>
                    </div>
                </label>
            </div>

            <!-- Card 2: Parâmetros Detalhados -->
            <div class="card" id="parameters-card" style="margin-bottom: 1.25rem; opacity: <?= !empty($policy['enabled']) ? '1' : '0.5' ?>; pointer-events: <?= !empty($policy['enabled']) ? 'auto' : 'none' ?>; transition: opacity 0.2s ease;">
                <h2 style="font-size: 1.15rem; margin-bottom: 0.35rem;">Parâmetros da Política</h2>
                <p class="muted" style="font-size: 0.85rem; margin-bottom: 1.25rem;">Defina os critérios obrigatórios quando a política estiver ativa.</p>

                <!-- Tamanho Mínimo -->
                <div style="margin-bottom: 1.25rem;">
                    <label for="min_length" style="font-weight: 600;">Tamanho Mínimo de Caracteres:</label>
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0.35rem;">
                        <input type="number" id="min_length" name="min_length" value="<?= (int) $policy['min_length'] ?>" min="1" max="64" style="max-width: 120px; font-size: 1.1rem; font-weight: 700; text-align: center;" oninput="updateSimulator()">
                        <span class="muted" style="font-size: 0.85rem;">caracteres (recomendado para produção: 8 ou mais)</span>
                    </div>
                </div>

                <!-- Checkboxes de Complexidade -->
                <label style="font-weight: 600; margin-bottom: 0.5rem;">Critérios de Caracteres:</label>
                <div class="checkbox-group" style="margin-top: 0.5rem;">
                    <label>
                        <input type="checkbox" id="require_uppercase" name="require_uppercase" value="1" <?= !empty($policy['require_uppercase']) ? 'checked' : '' ?> onchange="updateSimulator()">
                        <span><strong>Letras Maiúsculas:</strong> Exigir pelo menos uma letra maiúscula (A-Z)</span>
                    </label>

                    <label>
                        <input type="checkbox" id="require_lowercase" name="require_lowercase" value="1" <?= !empty($policy['require_lowercase']) ? 'checked' : '' ?> onchange="updateSimulator()">
                        <span><strong>Letras Minúsculas:</strong> Exigir pelo menos uma letra minúscula (a-z)</span>
                    </label>

                    <label>
                        <input type="checkbox" id="require_numbers" name="require_numbers" value="1" <?= !empty($policy['require_numbers']) ? 'checked' : '' ?> onchange="updateSimulator()">
                        <span><strong>Números:</strong> Exigir pelo menos um dígito numérico (0-9)</span>
                    </label>

                    <label>
                        <input type="checkbox" id="require_symbols" name="require_symbols" value="1" <?= !empty($policy['require_symbols']) ? 'checked' : '' ?> onchange="updateSimulator()">
                        <span><strong>Símbolos:</strong> Exigir pelo menos um caractere especial (!@#$%...)</span>
                    </label>
                </div>
            </div>

            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem; font-size: 0.95rem;">
                    Salvar Política de Senhas
                </button>
                <a href="<?= url($app, '/dev') ?>" class="btn btn-secondary">Cancelar</a>
            </div>
        </div>

        <!-- Coluna 2: Simulador e Resumo em Tempo Real -->
        <div>
            <!-- Resumo Atual -->
            <div class="card" style="margin-bottom: 1.25rem;">
                <h2 style="font-size: 1.15rem; margin-bottom: 0.35rem;">Regras Vigentes na Aplicação</h2>
                <p class="muted" style="font-size: 0.85rem; margin-bottom: 0.75rem;">Aplicadas em <code>/setup</code>, <code>/profile</code> e <code>/admin/users</code>.</p>
                <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.9rem; color: #334155; line-height: 1.6;">
                    <?php foreach ($rules as $rule): ?>
                        <li><strong><?= e($rule) ?></strong></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Simulador / Testador Interativo -->
            <div class="card" style="border: 1.5px solid #cbd5e1; background: #f8fafc;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                    <h2 style="font-size: 1.15rem; margin-bottom: 0;">🧪 Testador de Senha em Tempo Real</h2>
                    <span id="simulator-status-badge" class="badge badge-gray" style="font-size: 0.75rem;">Aguardando digitação</span>
                </div>
                <p class="muted" style="font-size: 0.85rem; margin-bottom: 1rem;">Digite uma senha hipotética abaixo para verificar se ela passaria nos critérios configurados ao lado.</p>

                <input type="text" id="simulator-input" placeholder="Digite uma senha de teste..." oninput="updateSimulator()" style="margin-bottom: 1rem; font-family: monospace; font-size: 1.05rem; padding: 0.65rem 0.85rem; background: #ffffff;">

                <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.85rem;" id="simulator-checklist">
                    <div id="sim-len" style="display: flex; align-items: center; gap: 0.5rem; color: #64748b;">
                        <span class="icon">⚪</span> <span>Tamanho mínimo</span>
                    </div>
                    <div id="sim-upper" style="display: flex; align-items: center; gap: 0.5rem; color: #64748b;">
                        <span class="icon">⚪</span> <span>Letra maiúscula (A-Z)</span>
                    </div>
                    <div id="sim-lower" style="display: flex; align-items: center; gap: 0.5rem; color: #64748b;">
                        <span class="icon">⚪</span> <span>Letra minúscula (a-z)</span>
                    </div>
                    <div id="sim-num" style="display: flex; align-items: center; gap: 0.5rem; color: #64748b;">
                        <span class="icon">⚪</span> <span>Número (0-9)</span>
                    </div>
                    <div id="sim-sym" style="display: flex; align-items: center; gap: 0.5rem; color: #64748b;">
                        <span class="icon">⚪</span> <span>Caractere especial (!@#$...)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function togglePolicyControls(enabled) {
    const card = document.getElementById('parameters-card');
    if (enabled) {
        card.style.opacity = '1';
        card.style.pointerEvents = 'auto';
    } else {
        card.style.opacity = '0.5';
        card.style.pointerEvents = 'none';
    }
    updateSimulator();
}

function applyPreset(type) {
    const enabled = document.getElementById('enabled');
    const minLength = document.getElementById('min_length');
    const reqUpper = document.getElementById('require_uppercase');
    const reqLower = document.getElementById('require_lowercase');
    const reqNum = document.getElementById('require_numbers');
    const reqSym = document.getElementById('require_symbols');

    if (type === 'free') {
        enabled.checked = false;
        minLength.value = 1;
        reqUpper.checked = false;
        reqLower.checked = false;
        reqNum.checked = false;
        reqSym.checked = false;
    } else if (type === 'balanced') {
        enabled.checked = true;
        minLength.value = 8;
        reqUpper.checked = true;
        reqLower.checked = true;
        reqNum.checked = true;
        reqSym.checked = false;
    } else if (type === 'strict') {
        enabled.checked = true;
        minLength.value = 12;
        reqUpper.checked = true;
        reqLower.checked = true;
        reqNum.checked = true;
        reqSym.checked = true;
    }

    togglePolicyControls(enabled.checked);
}

function updateSimulator() {
    const enabled = document.getElementById('enabled').checked;
    const minLength = parseInt(document.getElementById('min_length').value, 10) || 1;
    const reqUpper = document.getElementById('require_uppercase').checked;
    const reqLower = document.getElementById('require_lowercase').checked;
    const reqNum = document.getElementById('require_numbers').checked;
    const reqSym = document.getElementById('require_symbols').checked;

    const val = document.getElementById('simulator-input').value;
    const badge = document.getElementById('simulator-status-badge');

    const lenOk = val.length >= minLength;
    const upperOk = /[A-Z]/.test(val);
    const lowerOk = /[a-z]/.test(val);
    const numOk = /[0-9]/.test(val);
    const symOk = /[^a-zA-Z0-9]/.test(val);

    function updateItem(id, required, ok, label) {
        const el = document.getElementById(id);
        const icon = el.querySelector('.icon');
        const text = el.querySelector('span:last-child');
        text.textContent = label;

        if (!enabled) {
            el.style.color = '#94a3b8';
            icon.textContent = '⚪';
            return true;
        }

        if (!required) {
            el.style.color = '#94a3b8';
            icon.textContent = '➖ (Opcional)';
            return true;
        }

        if (ok) {
            el.style.color = '#15803d';
            icon.textContent = '✅';
            return true;
        } else {
            el.style.color = '#b91c1c';
            icon.textContent = '❌';
            return false;
        }
    }

    const resLen = updateItem('sim-len', true, lenOk, 'Mínimo de ' + minLength + ' caracteres (' + val.length + '/' + minLength + ')');
    const resUpper = updateItem('sim-upper', reqUpper, upperOk, 'Letra maiúscula (A-Z)');
    const resLower = updateItem('sim-lower', reqLower, lowerOk, 'Letra minúscula (a-z)');
    const resNum = updateItem('sim-num', reqNum, numOk, 'Número (0-9)');
    const resSym = updateItem('sim-sym', reqSym, symOk, 'Caractere especial (!@#$...)');

    if (!val) {
        badge.className = 'badge badge-gray';
        badge.textContent = 'Aguardando digitação';
        return;
    }

    if (!enabled) {
        badge.className = 'badge badge-success';
        badge.textContent = 'Válida (Modo Livre Ativo)';
        return;
    }

    const allOk = resLen && resUpper && resLower && resNum && resSym;
    if (allOk) {
        badge.className = 'badge badge-success';
        badge.textContent = 'Senha Forte & Aprovada';
    } else {
        badge.className = 'badge badge-danger';
        badge.textContent = 'Critérios Pendentes';
    }
}

document.addEventListener('DOMContentLoaded', updateSimulator);
</script>
