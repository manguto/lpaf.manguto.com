<?php

$status = $status ?? http_response_code() ?: 500;
$message = $message ?? 'Ocorreu um erro ao processar sua solicitação.';
$basePath = $basePath ?? '';

$statusTitles = [
    400 => 'Requisição Inválida',
    401 => 'Não Autenticado',
    403 => 'Acesso Negado',
    404 => 'Página Não Encontrada',
    405 => 'Método Não Permitido',
    500 => 'Erro Interno do Servidor',
];

$title = $statusTitles[$status] ?? 'Erro';

$statusDescriptions = [
    403 => 'Você não possui as permissões necessárias para acessar este recurso ou área administrativa.',
    404 => 'O endereço solicitado não foi encontrado no servidor.',
    500 => 'Ocorreu uma falha inesperada no processamento interno da solicitação.',
];

$description = $statusDescriptions[$status] ?? 'Verifique o endereço ou consulte o administrador do sistema.';
$appUrl = $basePath !== '' ? $basePath : '/';
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        :root {
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: #334155;
            --danger: #ef4444;
            --danger-bg: rgba(239, 68, 68, 0.15);
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --btn-sec-bg: #334155;
            --btn-sec-hover: #475569;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .error-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 16px;
            max-width: 520px;
            width: 100%;
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
            animation: fadeIn .3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: var(--danger-bg);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: .4rem .9rem;
            border-radius: 9999px;
            font-size: .875rem;
            font-weight: 700;
            letter-spacing: .05em;
            margin-bottom: 1.25rem;
            text-transform: uppercase;
        }

        .status-code {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.04em;
            background: linear-gradient(135deg, #f87171, #ef4444);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: .75rem;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: .75rem;
            color: var(--text-main);
        }

        .message {
            font-size: 1.05rem;
            color: #e2e8f0;
            font-weight: 500;
            margin-bottom: .5rem;
        }

        .description {
            font-size: .925rem;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 2rem;
        }

        .actions {
            display: flex;
            gap: .75rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .7rem 1.4rem;
            border-radius: 8px;
            font-size: .925rem;
            font-weight: 600;
            text-decoration: none;
            transition: all .15s ease;
            cursor: pointer;
            border: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .btn-secondary {
            background: var(--btn-sec-bg);
            color: #f1f5f9;
        }

        .btn-secondary:hover {
            background: var(--btn-sec-hover);
        }

        .footer-note {
            margin-top: 2rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border);
            font-size: .8rem;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="status-badge">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>
        </div>

        <div class="status-code"><?= htmlspecialchars((string) $status, ENT_QUOTES, 'UTF-8') ?></div>

        <h1><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="description"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>

        <div class="actions">
            <button class="btn btn-secondary" onclick="window.history.length > 1 ? window.history.back() : window.location.href='<?= htmlspecialchars($appUrl, ENT_QUOTES, 'UTF-8') ?>'">
                &larr; Voltar
            </button>
            <a class="btn btn-primary" href="<?= htmlspecialchars($appUrl . (empty($_SESSION['user_id']) ? '/login' : '/app'), ENT_QUOTES, 'UTF-8') ?>">
                Ir para o Início
            </a>
        </div>

        <div class="footer-note">
            PHP Admin Framework &bull; LPAF
        </div>
    </div>
</body>
</html>
