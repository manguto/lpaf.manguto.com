<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;

final class PasswordResetService
{
    private AuditService $audit;

    public function __construct(private Application $app, ?AuditService $audit = null)
    {
        $this->audit = $audit ?? new AuditService($this->app->storage);
    }

    /**
     * Cria e registra um token seguro de recuperação para o usuário informado.
     *
     * @return array{
     *     user: array,
     *     token: string,
     *     url: string,
     *     expires_at: string
     * }|null Retorna os dados do token gerado ou null se o usuário não for encontrado/estiver inativo.
     */
    public function createToken(string $username, string $ip = ''): ?array
    {
        $user = $this->app->users->findByUsername($username);

        if (!$user || ($user['active'] ?? '1') !== '1') {
            $this->audit->log(
                'password_reset_failed',
                null,
                "username=" . substr($username, 0, 80) . "; ip={$ip}; reason=user_not_found_or_inactive"
            );
            return null;
        }

        // Invalida tokens anteriores não utilizados deste usuário
        $this->app->passwordResets->invalidateUserTokens($user['id']);

        // Gera token criptograficamente seguro (64 caracteres hex)
        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);

        // Validade de 60 minutos
        $expiresAt = date('Y-m-d H:i:s', time() + 3600);
        $createdAt = date('Y-m-d H:i:s');

        $this->app->passwordResets->insert([
            'id' => $this->app->passwordResets->nextId(),
            'user_id' => $user['id'],
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt,
            'used_at' => '',
            'created_at' => $createdAt,
            'ip' => $ip,
        ]);

        $resetUrl = $this->app->url('/reset-password?token=' . urlencode($plainToken));

        // Registro de Auditoria
        $this->audit->log('password_reset_requested', $user['id'], "ip={$ip}");

        // Log simulado de envio de e-mail (para dev local XAMPP e auditoria de mensageria)
        $this->logSimulatedMail($user, $resetUrl, $ip, $expiresAt);

        return [
            'user' => $user,
            'token' => $plainToken,
            'url' => $resetUrl,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Valida um token de recuperação.
     *
     * @return array{reset: array, user: array}|null
     */
    public function validateToken(string $plainToken): ?array
    {
        $plainToken = trim($plainToken);
        if ($plainToken === '') {
            return null;
        }

        $tokenHash = hash('sha256', $plainToken);
        $record = $this->app->passwordResets->findValidByTokenHash($tokenHash);

        if (!$record) {
            return null;
        }

        $user = $this->app->users->find($record['user_id']);
        if (!$user || ($user['active'] ?? '1') !== '1') {
            return null;
        }

        return [
            'reset' => $record,
            'user' => $user,
        ];
    }

    /**
     * Redefine a senha do usuário utilizando o token fornecido e valida contra a política de senhas.
     *
     * @return array{success: bool, error: ?string}
     */
    public function resetPassword(string $plainToken, string $newPassword): array
    {
        $validated = $this->validateToken($plainToken);

        if (!$validated) {
            return [
                'success' => false,
                'error' => 'O link de recuperação informado é inválido, expirou ou já foi utilizado.',
            ];
        }

        // Validação contra a Política de Senhas ativa
        $policyService = new PasswordPolicyService($this->app);
        $policyError = $policyService->validate($newPassword);
        if ($policyError !== null) {
            return [
                'success' => false,
                'error' => $policyError,
            ];
        }

        $user = $validated['user'];
        $resetRecord = $validated['reset'];

        // Atualização da senha do usuário
        $this->app->users->update($user['id'], [
            'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
            'updated_at' => date('c'),
        ]);

        // Marca o token como consumido
        $this->app->passwordResets->markUsed($resetRecord['id']);

        // Invalida quaisquer outros tokens remanescentes do usuário
        $this->app->passwordResets->invalidateUserTokens($user['id']);

        // Registro de auditoria
        $this->audit->log('password_reset_completed', $user['id'], "token_id={$resetRecord['id']}");

        return [
            'success' => true,
            'error' => null,
        ];
    }

    /**
     * Registra o e-mail de recuperação simulado em storage/logs/mail.log.
     */
    private function logSimulatedMail(array $user, string $resetUrl, string $ip, string $expiresAt): void
    {
        $logsDir = $this->app->config->get('storage_logs');
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0775, true);
        }

        $mailLogFile = $logsDir . '/mail.log';
        $timestamp = date('Y-m-d H:i:s');
        $username = $user['username'] ?? 'desconhecido';
        $name = $user['name'] ?? 'Usuário';

        $entry = sprintf(
            "[%s] RECOVERY EMAIL | To: @%s (%s) | Reset Link: %s | Expiration: %s | IP: %s\n",
            $timestamp,
            $username,
            $name,
            $resetUrl,
            $expiresAt,
            $ip
        );

        @file_put_contents($mailLogFile, $entry, FILE_APPEND | LOCK_EX);
    }
}
