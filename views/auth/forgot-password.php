<div class="form-auth">
    <div style="text-align: center; margin-bottom: 2rem;">
        <span class="brand-badge" style="font-size: 0.8rem; padding: 0.25rem 0.6rem;">LPAF</span>
        <h1 style="font-size: 1.6rem; margin-top: 0.75rem; margin-bottom: 0.35rem;">Recuperação de Senha</h1>
        <p class="muted" style="font-size: 0.9rem;">Informe seu nome de usuário para redefinir seu acesso.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success" style="margin-bottom: 1.5rem;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
            <div>
                <strong>Solicitação processada com sucesso!</strong>
                <p style="margin-top: 0.35rem; font-size: 0.875rem; color: inherit;">
                    Se a conta informada estiver ativa em nosso sistema, as instruções e o token seguro com validade de 60 minutos foram emitidos.
                </p>
            </div>
        </div>

        <?php if (!empty($devLink)): ?>
            <div style="background-color: var(--primary-light); border: 1.5px solid var(--border-color); border-radius: var(--radius-sm); padding: 1.25rem; margin-bottom: 1.5rem;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                    <span class="badge badge-info" style="font-size: 0.75rem;">Ambiente Local (XAMPP)</span>
                    <span style="font-size: 0.75rem; color: var(--text-muted);">Simulação de E-mail</span>
                </div>
                <p style="font-size: 0.85rem; color: var(--text-main); margin-bottom: 1rem; line-height: 1.4;">
                    Como você está em ambiente local, a notificação foi gravada em <code>storage/logs/mail.log</code>. Você pode prosseguir clicando diretamente no atalho abaixo:
                </p>
                <a href="<?= e($devLink) ?>" class="btn btn-primary" style="width: 100%; text-decoration: none; padding: 0.75rem;">
                    Redefinir Senha Agora &rarr;
                </a>
            </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 1.25rem;">
            <a href="<?= url($app, '/login') ?>" style="font-size: 0.875rem; color: var(--primary); text-decoration: none; font-weight: 600;">
                &larr; Voltar para a Tela de Login
            </a>
        </div>
    <?php else: ?>
        <form method="post" action="<?= url($app, '/forgot-password') ?>" style="border: 0; padding: 0; box-shadow: none;">
            <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
            
            <label for="username">Login ou Nome de Usuário</label>
            <input type="text" id="username" name="username" value="<?= e($username) ?>" placeholder="Ex: admin" autocomplete="username" required autofocus>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem; padding: 0.75rem;">
                Enviar Link de Recuperação &rarr;
            </button>

            <div style="text-align: center; margin-top: 1.25rem;">
                <a href="<?= url($app, '/login') ?>" style="font-size: 0.875rem; color: var(--text-muted); text-decoration: none; font-weight: 600;">
                    &larr; Lembra da sua senha? Entrar
                </a>
            </div>
        </form>
    <?php endif; ?>
</div>
