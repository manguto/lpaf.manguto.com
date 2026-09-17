<div class="card" style="padding: 3rem 2.5rem; text-align: center; max-width: 860px; margin: 2rem auto; border-radius: var(--radius-lg);">
    <div style="display: inline-block; margin-bottom: 1.25rem;">
        <span class="badge badge-purple" style="font-size: 0.825rem; padding: 0.35rem 0.85rem;">
            Micro-Framework Administrativo
        </span>
    </div>
    
    <h1 style="font-size: 2.25rem; margin-bottom: 1rem; color: #0f172a;">
        <?= e($app->setting('app_name', 'PHP Admin Framework')) ?>
    </h1>
    
    <p style="font-size: 1.15rem; color: #475569; max-width: 620px; margin: 0 auto 2.25rem; line-height: 1.6;">
        Base reutilizável e leve para pequenos sistemas internos em PHP 8.2+. Alta produtividade com autenticação por sessão, controle de acesso RBAC e persistência em CSV.
    </p>
    
    <div style="margin-bottom: 3rem;">
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a class="btn btn-primary" style="font-size: 1rem; padding: 0.75rem 2rem;" href="<?= url($app, '/login') ?>">
                Acessar o Painel &rarr;
            </a>
        <?php else: ?>
            <a class="btn btn-primary" style="font-size: 1rem; padding: 0.75rem 2rem;" href="<?= url($app, '/app') ?>">
                Abrir Aplicação &rarr;
            </a>
        <?php endif; ?>
    </div>
    
    <div class="grid-3" style="text-align: left; margin-top: 1.5rem; padding-top: 2rem; border-top: 1px solid var(--border-color);">
        <div style="padding: 0.75rem;">
            <h3 style="color: var(--primary); font-size: 1.05rem;">⚡ Leve & Autocontido</h3>
            <p class="muted" style="font-size: 0.875rem;">Executa sem banco de dados complexo. Persistência CSV direta com travas atômicas.</p>
        </div>
        <div style="padding: 0.75rem;">
            <h3 style="color: var(--success); font-size: 1.05rem;">🔒 Segurança & RBAC</h3>
            <p class="muted" style="font-size: 0.875rem;">Proteção CSRF nativa, cookies seguros, hashes modernos e controle granular de permissões.</p>
        </div>
        <div style="padding: 0.75rem;">
            <h3 style="color: var(--purple); font-size: 1.05rem;">🛠 Dev-End Integrado</h3>
            <p class="muted" style="font-size: 0.875rem;">Ambiente reservado com backups dinâmicos, diagnósticos e trilha de auditoria em tempo real.</p>
        </div>
    </div>
</div>