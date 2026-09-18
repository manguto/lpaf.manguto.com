<div class="card" style="max-width: 580px; margin: 2rem auto; padding: 2.5rem; border-radius: var(--radius-lg);">
    <div style="text-align: center; margin-bottom: 2rem;">
        <span class="badge badge-purple" style="padding: 0.3rem 0.75rem; font-size: 0.8rem;">Assistente de Inicialização</span>
        <h1 style="font-size: 1.75rem; margin-top: 0.75rem; margin-bottom: 0.35rem;">Configuração Inicial</h1>
        <p class="muted" style="font-size: 0.9rem;">Configure os dados básicos da aplicação e crie o primeiro usuário Desenvolvedor.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <span><?= e($error) ?></span>
        </div>
    <?php endif; ?>

    <form method="post" action="<?= url($app, '/setup') ?>" style="border: 0; padding: 0; box-shadow: none;">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">

        <label for="app_name">Nome da Aplicação</label>
        <input type="text" id="app_name" name="app_name" value="<?= e($app->config->get('app_name', 'PHP Admin Framework')) ?>" required>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 0.5rem;">
            <div>
                <label for="name">Seu Nome Completo</label>
                <input type="text" id="name" name="name" placeholder="Ex: Fulano da Silva" required>
            </div>
            <div>
                <label for="username">Login (Nome de Usuário)</label>
                <input type="text" id="username" name="username" pattern="[a-zA-Z0-9._-]{3,30}" placeholder="Ex: dev" title="O login deve conter entre 3 e 30 caracteres (letras, números, '.', '_' ou '-'), sem @" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 0.5rem;">
            <div>
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" placeholder="Digite sua senha" required>
            </div>
            <div>
                <label for="password_confirmation">Confirmar Senha</label>
                <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Repita a senha" required>
            </div>
        </div>

        <?php if ($app->config->get('app_setup_key')): ?>
            <label for="setup_key" style="margin-top: 1rem;">Chave de Instalação (APP_SETUP_KEY)</label>
            <input type="password" id="setup_key" name="setup_key" placeholder="Chave de segurança exigida" required>
        <?php endif; ?>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1.75rem; padding: 0.8rem; font-size: 0.95rem;">
            Concluir Instalação & Conectar &rarr;
        </button>
    </form>
</div>