<?php

declare(strict_types=1);

namespace App\Core;

final class Preflight
{
    /**
     * Executa checagem de pré-requisitos antes do require do autoloader.
     */
    public static function check(string $root): void
    {
        $hasAutoload = is_file($root . '/vendor/autoload.php');
        $phpVersionOk = PHP_VERSION_ID >= 80200;
        
        $requiredExtensions = ['session', 'json', 'mbstring', 'filter'];
        $missingExtensions = array_values(array_filter(
            $requiredExtensions,
            fn(string $ext) => !extension_loaded($ext)
        ));

        $storageDirs = [
            $root . '/storage',
            $root . '/storage/data',
            $root . '/storage/logs',
            $root . '/storage/backups',
            $root . '/storage/tmp',
        ];
        $storageErrors = [];
        foreach ($storageDirs as $dir) {
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            if (!is_dir($dir) || !is_writable($dir)) {
                $storageErrors[] = str_replace([$root . '/', $root . '\\'], '', $dir);
            }
        }

        // Se tudo estiver OK, prossegue com o fluxo normal da aplicação
        if ($hasAutoload && $phpVersionOk && empty($missingExtensions) && empty($storageErrors)) {
            return;
        }

        $composerInfo = self::detectComposer();
        $isCli = (PHP_SAPI === 'cli');

        if ($isCli) {
            self::renderCli($root, $hasAutoload, $phpVersionOk, $missingExtensions, $storageErrors, $composerInfo);
            exit(1);
        }

        $actionMessage = null;
        $actionSuccess = false;

        // Suporte a ações executadas pela interface web em ambiente local
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'install_composer' && $composerInfo['available']) {
                $result = self::runComposerInstall($root, $composerInfo['command']);
                $actionMessage = $result['output'];
                $actionSuccess = $result['success'];
                if ($actionSuccess) {
                    header('Location: ' . ($_SERVER['REQUEST_URI'] ?? '/'));
                    exit;
                }
            } elseif ($action === 'generate_fallback_autoloader') {
                $result = self::generateFallbackAutoloader($root);
                $actionMessage = $result['output'];
                $actionSuccess = $result['success'];
                if ($actionSuccess) {
                    header('Location: ' . ($_SERVER['REQUEST_URI'] ?? '/'));
                    exit;
                }
            }
        }

        http_response_code(503);
        header('Retry-After: 5');
        self::renderWeb($root, $hasAutoload, $phpVersionOk, $missingExtensions, $storageErrors, $composerInfo, $actionMessage, $actionSuccess);
        exit(1);
    }

    /**
     * Detecta se o comando composer está disponível no ambiente.
     */
    private static function detectComposer(): array
    {
        if (!function_exists('exec')) {
            return ['available' => false, 'version' => null, 'command' => null];
        }

        $commands = ['composer', 'composer.phar'];
        foreach ($commands as $cmd) {
            $output = [];
            $returnVar = 1;
            @exec($cmd . ' --version 2>&1', $output, $returnVar);
            if ($returnVar === 0 && !empty($output)) {
                $firstLine = implode(' ', $output);
                if (preg_match('/Composer\s+(?:version\s+)?([0-9\.]+)/i', $firstLine, $matches)) {
                    return [
                        'available' => true,
                        'version' => $matches[1],
                        'command' => $cmd,
                        'full_output' => $firstLine,
                    ];
                }
                return [
                    'available' => true,
                    'version' => 'detectado',
                    'command' => $cmd,
                    'full_output' => $firstLine,
                ];
            }
        }

        return ['available' => false, 'version' => null, 'command' => null];
    }

    /**
     * Executa composer install via processo PHP.
     */
    private static function runComposerInstall(string $root, string $command): array
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $cmd = $command . ' install --no-interaction';
        $process = @proc_open($cmd, $descriptors, $pipes, $root);
        if (!is_resource($process)) {
            return [
                'success' => false,
                'output' => 'Não foi possível iniciar o processo de instalação do Composer.',
            ];
        }

        fclose($pipes[0]);
        $stdout = (string) stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = (string) stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        $output = trim($stdout . "\n" . $stderr);

        $hasAutoload = is_file($root . '/vendor/autoload.php');

        return [
            'success' => ($exitCode === 0 || $hasAutoload),
            'output' => $output !== '' ? $output : "Processo encerrado com código {$exitCode}.",
        ];
    }

    /**
     * Gera autoloader PSR-4 leve de emergência para a namespace App\ caso Composer não esteja disponível.
     */
    private static function generateFallbackAutoloader(string $root): array
    {
        $vendorDir = $root . '/vendor';
        if (!is_dir($vendorDir) && !@mkdir($vendorDir, 0775, true)) {
            return ['success' => false, 'output' => "Não foi possível criar o diretório 'vendor'."];
        }

        $code = <<<'PHP'
<?php
// Autoloader PSR-4 gerado pelo Preflight do LPAF (Namespace App\ -> app/)
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (is_file($file)) {
        require $file;
    }
});
PHP;

        $written = @file_put_contents($vendorDir . '/autoload.php', $code);
        if ($written !== false) {
            return ['success' => true, 'output' => 'Autoloader de emergência gerado com sucesso em vendor/autoload.php.'];
        }

        return ['success' => false, 'output' => 'Falha ao gravar arquivo vendor/autoload.php.'];
    }

    /**
     * Exibe diagnóstico formatado no terminal CLI.
     */
    private static function renderCli(
        string $root,
        bool $hasAutoload,
        bool $phpVersionOk,
        array $missingExtensions,
        array $storageErrors,
        array $composerInfo
    ): void {
        echo "\n" . str_repeat('=', 78) . "\n";
        echo "  LPAF - PHP Admin Framework | Verificação Prévia de Ambiente\n";
        echo str_repeat('=', 78) . "\n\n";

        if (!$hasAutoload) {
            echo " [NECESSIDADE IDENTIFICADA]\n";
            echo "  O autoloader do Composer não foi encontrado:\n";
            echo "  -> " . $root . "/vendor/autoload.php\n\n";

            echo " [POR QUE ISSO ACONTECE?]\n";
            echo "  Ao clonar um repositório do Git, a pasta 'vendor/' não é incluída no versionamento\n";
            echo "  por convenção do .gitignore. É necessário inicializar as dependências.\n\n";

            echo " [O QUE PRECISA SER FEITO?]\n";
            echo "  1. Acesse o diretório do projeto no seu terminal:\n";
            echo "     cd " . escapeshellarg($root) . "\n\n";
            echo "  2. Execute a instalação das dependências:\n";
            echo "     composer install\n\n";
            echo "  3. Em seguida, acesse ou execute a aplicação novamente.\n\n";
        }

        if (!$phpVersionOk) {
            echo " [VERSÃO DO PHP INCOMPATÍVEL]\n";
            echo "  Versão atual: PHP " . PHP_VERSION . " | Requisito: PHP >= 8.2.0\n\n";
        }

        if (!empty($missingExtensions)) {
            echo " [EXTENSÕES PHP AUSENTES]\n";
            echo "  Instale/ative as extensões: " . implode(', ', $missingExtensions) . "\n\n";
        }

        if (!empty($storageErrors)) {
            echo " [PERMISSÕES DE GRAVAÇÃO EM STORAGE]\n";
            echo "  Certifique-se de que os seguintes caminhos tenham permissão de escrita:\n";
            foreach ($storageErrors as $errDir) {
                echo "  - {$errDir}\n";
            }
            echo "\n";
        }

        echo str_repeat('-', 78) . "\n";
        echo " Diagnósticos:\n";
        echo "  * Autoload Composer: " . ($hasAutoload ? "[OK]" : "[AUSENTE]") . "\n";
        echo "  * Versão do PHP: " . PHP_VERSION . " " . ($phpVersionOk ? "[OK]" : "[INCOMPATÍVEL]") . "\n";
        echo "  * Extensões Essenciais: " . (empty($missingExtensions) ? "[OK]" : "[FALTAM: " . implode(', ', $missingExtensions) . "]") . "\n";
        echo "  * Pastas de Armazenamento: " . (empty($storageErrors) ? "[OK]" : "[VERIFICAR PERMISSÕES]") . "\n";
        echo "  * Composer no Sistema: " . ($composerInfo['available'] ? "Disponível (v" . ($composerInfo['version'] ?? 'detectada') . ") [OK]" : "Não encontrado no PATH") . "\n";
        echo str_repeat('=', 78) . "\n\n";
    }

    /**
     * Renderiza tela web com design moderno e amigável.
     */
    private static function renderWeb(
        string $root,
        bool $hasAutoload,
        bool $phpVersionOk,
        array $missingExtensions,
        array $storageErrors,
        array $composerInfo,
        ?string $actionMessage,
        bool $actionSuccess
    ): void {
        $phpVersion = PHP_VERSION;
        $composerAvailable = $composerInfo['available'];
        $composerVersion = $composerInfo['version'] ?? '';
        $rootEscaped = htmlspecialchars($root, ENT_QUOTES, 'UTF-8');
        $hasEnv = is_file($root . '/.env');
        ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configuração Prévia Necessária - LPAF</title>
    <style>
        :root {
            --bg: #0b0f19;
            --card-bg: #131b2e;
            --card-border: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --text-dim: #64748b;
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --success: #10b981;
            --success-bg: rgba(16, 185, 129, 0.12);
            --danger: #ef4444;
            --danger-bg: rgba(239, 68, 68, 0.12);
            --warning: #f59e0b;
            --warning-bg: rgba(245, 158, 11, 0.12);
            --code-bg: #090d16;
            --radius-lg: 16px;
            --radius-md: 10px;
            --radius-sm: 6px;
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
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            line-height: 1.5;
        }

        .container {
            max-width: 780px;
            width: 100%;
        }

        .header-brand {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            padding: 0 0.25rem;
        }

        .brand-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #e2e8f0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .brand-logo {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            color: #fff;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 800;
        }

        .badge-status {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            background: var(--warning-bg);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .main-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-lg);
            padding: 2.25rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
        }

        .alert-hero {
            display: flex;
            align-items: flex-start;
            gap: 1.25rem;
            padding-bottom: 1.75rem;
            border-bottom: 1px solid var(--card-border);
            margin-bottom: 1.75rem;
        }

        .alert-icon {
            font-size: 2.5rem;
            line-height: 1;
            flex-shrink: 0;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.25);
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
        }

        .alert-content h1 {
            font-size: 1.45rem;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0.4rem;
        }

        .alert-content p {
            color: var(--text-muted);
            font-size: 0.95rem;
        }

        .section-title {
            font-size: 1.05rem;
            font-weight: 600;
            color: #e2e8f0;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .instruction-box {
            background: var(--code-bg);
            border: 1px solid #1e293b;
            border-radius: var(--radius-md);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .instruction-step {
            margin-bottom: 1rem;
        }

        .instruction-step:last-child {
            margin-bottom: 0;
        }

        .step-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 0.4rem;
            display: block;
        }

        .code-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #000000;
            border: 1px solid #273549;
            border-radius: var(--radius-sm);
            padding: 0.6rem 0.9rem;
            font-family: Consolas, Monaco, "Courier New", monospace;
            font-size: 0.95rem;
            color: #38bdf8;
            word-break: break-all;
        }

        .btn-copy {
            background: #1e293b;
            border: 1px solid #334155;
            color: #f1f5f9;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.35rem 0.75rem;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.2s;
            margin-left: 0.75rem;
            flex-shrink: 0;
        }

        .btn-copy:hover {
            background: #334155;
            color: #fff;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
            margin-bottom: 1.75rem;
        }

        .action-card {
            background: rgba(30, 41, 59, 0.45);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-md);
            padding: 1.15rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .action-card-header {
            margin-bottom: 0.75rem;
        }

        .action-card-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #f1f5f9;
            margin-bottom: 0.35rem;
        }

        .action-card-desc {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.65rem 1.1rem;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: var(--radius-md);
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            width: 100%;
        }

        .btn-primary {
            background: var(--primary);
            color: #ffffff;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .btn-secondary {
            background: #1e293b;
            color: #e2e8f0;
            border-color: #334155;
        }

        .btn-secondary:hover {
            background: #334155;
            color: #fff;
        }

        .btn-success {
            background: var(--success);
            color: #ffffff;
        }

        .btn-success:hover {
            background: #059669;
        }

        .checklist-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .checklist-table tr {
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .checklist-table tr:last-child {
            border-bottom: none;
        }

        .checklist-table td {
            padding: 0.65rem 0.25rem;
        }

        .check-status {
            width: 32px;
            text-align: center;
            font-weight: 700;
        }

        .check-status.ok { color: var(--success); }
        .check-status.warn { color: var(--warning); }
        .check-status.err { color: var(--danger); }

        .check-name {
            font-weight: 600;
            color: #f1f5f9;
        }

        .check-detail {
            color: var(--text-muted);
            font-size: 0.82rem;
            margin-top: 0.15rem;
        }

        .check-badge {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.2rem 0.55rem;
            border-radius: 9999px;
            margin-left: 0.5rem;
        }

        .check-badge.ok {
            background: var(--success-bg);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .check-badge.err {
            background: var(--danger-bg);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .check-badge.warn {
            background: var(--warning-bg);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .feedback-alert {
            margin-bottom: 1.5rem;
            padding: 1rem 1.25rem;
            border-radius: var(--radius-md);
            font-size: 0.9rem;
            white-space: pre-wrap;
            font-family: Consolas, monospace;
            background: #181111;
            border: 1px solid #7f1d1d;
            color: #fca5a5;
        }

        .footer-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.75rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--card-border);
            font-size: 0.85rem;
            color: var(--text-dim);
        }

        .footer-bar a {
            color: var(--primary);
            text-decoration: none;
        }

        .footer-bar a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header-brand">
            <div class="brand-title">
                <span class="brand-logo">L</span>
                <span>LPAF &bull; PHP Admin Framework</span>
            </div>
            <span class="badge-status">Diagnóstico de Inicialização</span>
        </div>

        <div class="main-card">
            <?php if ($actionMessage): ?>
                <div class="feedback-alert">
                    <?= htmlspecialchars($actionMessage, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <div class="alert-hero">
                <div class="alert-icon">📦</div>
                <div class="alert-content">
                    <h1>Dependências do Composer Ausentes</h1>
                    <p>
                        O arquivo <strong>vendor/autoload.php</strong> não foi encontrado na raiz da aplicação.
                        Ao clonar projetos do Git, as dependências da pasta <code>vendor/</code> não são versionadas
                        por padrão e precisam ser geradas uma única vez antes do primeiro acesso.
                    </p>
                </div>
            </div>

            <div class="section-title">
                <span>📋 Como Resolver</span>
            </div>

            <div class="instruction-box">
                <div class="instruction-step">
                    <span class="step-label">Passo 1: Abra o terminal no diretório do projeto</span>
                    <div class="code-row">
                        <span id="cmd-cd">cd <?= $rootEscaped ?></span>
                        <button type="button" class="btn-copy" onclick="copyText('cmd-cd', this)">Copiar</button>
                    </div>
                </div>

                <div class="instruction-step" style="margin-top: 0.85rem;">
                    <span class="step-label">Passo 2: Execute o comando de instalação do Composer</span>
                    <div class="code-row">
                        <span id="cmd-install">composer install</span>
                        <button type="button" class="btn-copy" onclick="copyText('cmd-install', this)">Copiar</button>
                    </div>
                </div>
            </div>

            <div class="action-grid">
                <?php if ($composerAvailable): ?>
                    <div class="action-card">
                        <div class="action-card-header">
                            <div class="action-card-title">🚀 Instalação Automática</div>
                            <div class="action-card-desc">
                                Composer <?= htmlspecialchars($composerVersion, ENT_QUOTES, 'UTF-8') ?> detectado no seu sistema. Você pode executar o comando diretamente pelo navegador.
                            </div>
                        </div>
                        <form method="post">
                            <input type="hidden" name="action" value="install_composer">
                            <button type="submit" class="btn btn-primary" onclick="this.disabled=true;this.innerHTML='Instalando...';this.form.submit();">
                                Executar 'composer install' Agora
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="action-card">
                    <div class="action-card-header">
                        <div class="action-card-title">⚡ Autoloader Leve (Sem Composer)</div>
                        <div class="action-card-desc">
                            Como este framework utiliza apenas autoloading PSR-4 nativo e persistência em CSV, você pode gerar um autoloader imediato sem instalar pacotes adicionais.
                        </div>
                    </div>
                    <form method="post">
                        <input type="hidden" name="action" value="generate_fallback_autoloader">
                        <button type="submit" class="btn btn-secondary">
                            Gerar Autoloader Básico
                        </button>
                    </form>
                </div>
            </div>

            <div class="section-title" style="margin-top: 1.5rem;">
                <span>🔍 Verificação de Requisitos do Sistema</span>
            </div>

            <table class="checklist-table">
                <tbody>
                    <tr>
                        <td class="check-status <?= $hasAutoload ? 'ok' : 'err' ?>"><?= $hasAutoload ? '✓' : '✗' ?></td>
                        <td>
                            <div class="check-name">
                                Autoloader do Composer (vendor/autoload.php)
                                <span class="check-badge <?= $hasAutoload ? 'ok' : 'err' ?>"><?= $hasAutoload ? 'Presente' : 'Ausente' ?></span>
                            </div>
                            <div class="check-detail">Responsável pelo carregamento das classes do projeto (PSR-4).</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="check-status <?= $phpVersionOk ? 'ok' : 'err' ?>"><?= $phpVersionOk ? '✓' : '✗' ?></td>
                        <td>
                            <div class="check-name">
                                Versão do PHP: <?= htmlspecialchars($phpVersion, ENT_QUOTES, 'UTF-8') ?>
                                <span class="check-badge <?= $phpVersionOk ? 'ok' : 'err' ?>"><?= $phpVersionOk ? 'Compatível (>= 8.2)' : 'Requer >= 8.2' ?></span>
                            </div>
                            <div class="check-detail">O framework requer recursos modernos do PHP 8.2 ou superior.</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="check-status <?= empty($missingExtensions) ? 'ok' : 'err' ?>"><?= empty($missingExtensions) ? '✓' : '✗' ?></td>
                        <td>
                            <div class="check-name">
                                Extensões PHP Essenciais
                                <span class="check-badge <?= empty($missingExtensions) ? 'ok' : 'err' ?>">
                                    <?= empty($missingExtensions) ? 'Todas Ativas' : 'Ausentes: ' . htmlspecialchars(implode(', ', $missingExtensions), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <div class="check-detail">session, json, mbstring e filter para segurança e manipulação de dados.</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="check-status <?= empty($storageErrors) ? 'ok' : 'warn' ?>"><?= empty($storageErrors) ? '✓' : '!' ?></td>
                        <td>
                            <div class="check-name">
                                Permissões em storage/ (data, logs, backups, tmp)
                                <span class="check-badge <?= empty($storageErrors) ? 'ok' : 'warn' ?>"><?= empty($storageErrors) ? 'Gravável' : 'Ajustar Permissões' ?></span>
                            </div>
                            <div class="check-detail">Onde os arquivos CSV e logs do sistema são armazenados.</div>
                        </td>
                    </tr>
                    <tr>
                        <td class="check-status <?= $hasEnv ? 'ok' : 'warn' ?>"><?= $hasEnv ? '✓' : 'ℹ' ?></td>
                        <td>
                            <div class="check-name">
                                Arquivo de Configuração (.env)
                                <span class="check-badge <?= $hasEnv ? 'ok' : 'warn' ?>"><?= $hasEnv ? 'Configurado' : 'Usando Padrões (.env.example)' ?></span>
                            </div>
                            <div class="check-detail">Valores padrão integrados para ambiente de desenvolvimento.</div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <div class="footer-bar">
                <div>
                    Precisa de ajuda com o Composer? <a href="https://getcomposer.org/download/" target="_blank" rel="noopener">Baixar Composer</a>
                </div>
                <div>
                    <button type="button" class="btn btn-secondary" style="width: auto; padding: 0.45rem 1rem;" onclick="location.reload()">
                        🔄 Verificar Novamente
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyText(elementId, btnElement) {
            const el = document.getElementById(elementId);
            if (!el) return;
            const text = el.innerText || el.textContent;
            navigator.clipboard.writeText(text).then(() => {
                const originalText = btnElement.innerText;
                btnElement.innerText = 'Copiado! ✓';
                btnElement.style.background = '#10b981';
                btnElement.style.color = '#ffffff';
                setTimeout(() => {
                    btnElement.innerText = originalText;
                    btnElement.style.background = '';
                    btnElement.style.color = '';
                }, 2000);
            }).catch(() => {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                btnElement.innerText = 'Copiado! ✓';
                setTimeout(() => { btnElement.innerText = 'Copiar'; }, 2000);
            });
        }
    </script>
</body>
</html>
        <?php
    }
}
