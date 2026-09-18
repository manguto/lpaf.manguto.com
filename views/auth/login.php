<div class="form-auth">
    <div style="text-align: center; margin-bottom: 2rem;">
        <span class="brand-badge" style="font-size: 0.8rem; padding: 0.25rem 0.6rem;">LPAF</span>
        <h1 style="font-size: 1.6rem; margin-top: 0.75rem; margin-bottom: 0.35rem;">Acesse sua Conta</h1>
        <p class="muted" style="font-size: 0.9rem;">Informe suas credenciais para entrar na aplicação.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($installed)): ?>
        <div class="alert alert-success">
            <span>Instalação concluída com sucesso! Faça seu primeiro login.</span>
        </div>
    <?php endif; ?>

    <?php if (!empty($reset)): ?>
        <div class="alert alert-success">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            <span>Sua senha foi redefinida com sucesso! Digite suas novas credenciais para entrar.</span>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= url($app, '/login') ?>" style="border: 0; padding: 0; box-shadow: none;">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <label for="username">Login</label>
        <input type="text" id="username" name="username" placeholder="Seu nome de usuário" autocomplete="username" required autofocus>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; margin-bottom: 0.25rem;">
            <label for="password" style="margin: 0;">Senha</label>
            <a href="<?= url($app, '/forgot-password') ?>" style="font-size: 0.825rem; color: var(--primary); text-decoration: none; font-weight: 600;">Esqueci minha senha</a>
        </div>
        <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; padding: 0.75rem;">
            Entrar no Sistema &rarr;
        </button>
    </form>
</div>