<div style="margin-bottom: 2rem;">
    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
        <h1>Dev-End Console</h1>
        <span class="badge badge-purple" style="font-size: 0.8rem;">Área do Desenvolvedor</span>
    </div>
    <p class="muted">Ambiente estrutural para gerenciamento técnico, governança de dados e inspeção da plataforma.</p>
</div>

<div class="grid-3">
    <a href="<?= url($app, '/dev/diagnostics') ?>" class="card card-interactive">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <span class="badge badge-info" style="margin-bottom: 0.5rem;">Infraestrutura</span>
                <h2>Diagnósticos</h2>
                <p class="muted" style="font-size: 0.875rem; margin-top: 0.35rem;">
                    Verifique os parâmetros do PHP, integridade física dos arquivos CSV e caminhos de armazenamento.
                </p>
            </div>
            <span style="font-size: 1.5rem; color: var(--primary);">&rarr;</span>
        </div>
    </a>

    <a href="<?= url($app, '/dev/backups') ?>" class="card card-interactive">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <span class="badge badge-success" style="margin-bottom: 0.5rem;">Segurança de Dados</span>
                <h2>Backups & Rollback</h2>
                <p class="muted" style="font-size: 0.875rem; margin-top: 0.35rem;">
                    Gere snapshots pontuais dos dados, baixe pacotes compactados (.zip) e faça restaurações com salvaguarda automática.
                </p>
            </div>
            <span style="font-size: 1.5rem; color: var(--success);">&rarr;</span>
        </div>
    </a>

    <a href="<?= url($app, '/dev/logs') ?>" class="card card-interactive">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <span class="badge badge-purple" style="margin-bottom: 0.5rem;">Governança</span>
                <h2>Logs de Auditoria</h2>
                <p class="muted" style="font-size: 0.875rem; margin-top: 0.35rem;">
                    Inspecione todas as ações administrativas, tentativas de login, alterações de usuários e eventos do sistema.
                </p>
            </div>
            <span style="font-size: 1.5rem; color: var(--purple);">&rarr;</span>
        </div>
    </a>
</div>