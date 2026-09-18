<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;

final class PasswordPolicyService
{
    public function __construct(private Application $app) {}

    /**
     * Retorna a configuração atual da política de senhas.
     *
     * @return array{
     *     enabled: bool,
     *     min_length: int,
     *     require_uppercase: bool,
     *     require_lowercase: bool,
     *     require_numbers: bool,
     *     require_symbols: bool
     * }
     */
    public function getPolicy(): array
    {
        return [
            'enabled' => $this->app->setting('password_policy_enabled', '0') === '1',
            'min_length' => max(1, min(128, (int) $this->app->setting('password_min_length', '8'))),
            'require_uppercase' => $this->app->setting('password_require_uppercase', '0') === '1',
            'require_lowercase' => $this->app->setting('password_require_lowercase', '0') === '1',
            'require_numbers' => $this->app->setting('password_require_numbers', '0') === '1',
            'require_symbols' => $this->app->setting('password_require_symbols', '0') === '1',
        ];
    }

    /**
     * Valida uma senha contra a política ativa.
     * Retorna null se válida, ou uma mensagem de erro detalhada em português se inválida.
     */
    public function validate(string $password): ?string
    {
        if ($password === '') {
            return 'A senha não pode ficar em branco.';
        }

        $policy = $this->getPolicy();

        // Se a política estiver desativada (Modo Desenvolvimento Livre), aceita qualquer senha
        if (!$policy['enabled']) {
            return null;
        }

        if (strlen($password) < $policy['min_length']) {
            return "A senha deve conter no mínimo {$policy['min_length']} caracteres.";
        }

        if ($policy['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            return 'A senha deve conter pelo menos uma letra maiúscula (A-Z).';
        }

        if ($policy['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            return 'A senha deve conter pelo menos uma letra minúscula (a-z).';
        }

        if ($policy['require_numbers'] && !preg_match('/[0-9]/', $password)) {
            return 'A senha deve conter pelo menos um número (0-9).';
        }

        if ($policy['require_symbols'] && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            return 'A senha deve conter pelo menos um caractere especial (!@#$%...).';
        }

        return null;
    }

    /**
     * Atualiza as configurações da política de senhas e registra auditoria.
     */
    public function updatePolicy(array $data, ?string $userId = null): void
    {
        $enabled = !empty($data['enabled']) ? '1' : '0';
        $minLength = (string) max(1, min(128, (int) ($data['min_length'] ?? 8)));
        $reqUpper = !empty($data['require_uppercase']) ? '1' : '0';
        $reqLower = !empty($data['require_lowercase']) ? '1' : '0';
        $reqNumbers = !empty($data['require_numbers']) ? '1' : '0';
        $reqSymbols = !empty($data['require_symbols']) ? '1' : '0';

        $this->app->settings->setMultiple([
            'password_policy_enabled' => $enabled,
            'password_min_length' => $minLength,
            'password_require_uppercase' => $reqUpper,
            'password_require_lowercase' => $reqLower,
            'password_require_numbers' => $reqNumbers,
            'password_require_symbols' => $reqSymbols,
        ]);

        $audit = new AuditService($this->app->storage);
        $audit->log(
            'password_policy_updated',
            $userId ?? 'usr_001',
            "enabled={$enabled}; min_length={$minLength}; upper={$reqUpper}; lower={$reqLower}; num={$reqNumbers}; sym={$reqSymbols}"
        );
    }

    /**
     * Retorna resumo textual das regras ativas para exibição em formulários.
     *
     * @return string[]
     */
    public function getRulesSummary(): array
    {
        $policy = $this->getPolicy();
        if (!$policy['enabled']) {
            return ['Modo Livre (Sem restrições ativas de complexidade)'];
        }

        $rules = ["No mínimo {$policy['min_length']} caracteres"];
        if ($policy['require_uppercase']) {
            $rules[] = 'Pelo menos uma letra maiúscula (A-Z)';
        }
        if ($policy['require_lowercase']) {
            $rules[] = 'Pelo menos uma letra minúscula (a-z)';
        }
        if ($policy['require_numbers']) {
            $rules[] = 'Pelo menos um número (0-9)';
        }
        if ($policy['require_symbols']) {
            $rules[] = 'Pelo menos um caractere especial (!@#$...)';
        }

        return $rules;
    }
}
