<div class="form-auth">
    <div style="text-align: center; margin-bottom: 2rem;">
        <span class="brand-badge" style="font-size: 0.8rem; padding: 0.25rem 0.6rem;">LPAF</span>
        <?php if (!empty($invalid)): ?>
            <h1 style="font-size: 1.6rem; margin-top: 0.75rem; margin-bottom: 0.35rem; color: var(--danger-text);">Link Indisponível</h1>
            <p class="muted" style="font-size: 0.9rem;">O token de recuperação não pôde ser validado.</p>
        <?php else: ?>
            <h1 style="font-size: 1.6rem; margin-top: 0.75rem; margin-bottom: 0.35rem;">Redefinir Senha</h1>
            <p class="muted" style="font-size: 0.9rem;">
                Definindo novo acesso para <strong><?= e($user['name'] ?? '') ?></strong> (@<?= e($user['username'] ?? '') ?>).
            </p>
        <?php endif; ?>
    </div>

    <?php if (!empty($invalid)): ?>
        <div class="alert alert-error" style="margin-bottom: 1.5rem;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <div>
                <strong>Token inválido ou expirado</strong>
                <p style="margin-top: 0.35rem; font-size: 0.875rem; color: inherit;">
                    <?= e($error ?? 'O link de recuperação acessado não é válido, expirou após 60 minutos ou já foi utilizado para alterar a senha.') ?>
                </p>
            </div>
        </div>

        <a href="<?= url($app, '/forgot-password') ?>" class="btn btn-primary" style="width: 100%; text-decoration: none; padding: 0.75rem;">
            Solicitar Novo Link de Recuperação &rarr;
        </a>

        <div style="text-align: center; margin-top: 1.25rem;">
            <a href="<?= url($app, '/login') ?>" style="font-size: 0.875rem; color: var(--text-muted); text-decoration: none; font-weight: 600;">
                &larr; Voltar para o Login
            </a>
        </div>
    <?php else: ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                <span><?= e($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($rules)): ?>
            <div style="background-color: var(--bg-muted); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 0.85rem 1rem; margin-bottom: 1.25rem; font-size: 0.825rem;">
                <strong style="display: block; margin-bottom: 0.35rem; color: var(--text-main);">Requisitos da Política de Senhas:</strong>
                <ul style="margin: 0; padding-left: 1.25rem; color: var(--text-muted); line-height: 1.5;">
                    <?php foreach ($rules as $rule): ?>
                        <li><?= e($rule) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= url($app, '/reset-password') ?>" style="border: 0; padding: 0; box-shadow: none;">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            <input type="hidden" name="token" value="<?= e($token) ?>">

            <label for="password">Nova Senha</label>
            <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="new-password" required autofocus>

            <label for="password_confirmation">Confirmar Nova Senha</label>
            <input type="password" id="password_confirmation" name="password_confirmation" placeholder="••••••••" autocomplete="new-password" required>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; padding: 0.75rem;">
                Salvar Nova Senha &rarr;
            </button>

            <div style="text-align: center; margin-top: 1.25rem;">
                <a href="<?= url($app, '/login') ?>" style="font-size: 0.875rem; color: var(--text-muted); text-decoration: none; font-weight: 600;">
                    &larr; Cancelar e voltar para o login
                </a>
            </div>
        </form>
    <?php endif; ?>
</div>
