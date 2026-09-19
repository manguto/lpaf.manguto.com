<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Application;

final class PermissionRepository extends CsvRepository
{
    protected function file(): string
    {
        return 'permissions.csv';
    }

    protected function headers(): array
    {
        return ['id', 'name', 'description'];
    }

    public function findByName(string $name): ?array
    {
        foreach ($this->all() as $row) {
            if ($row['name'] === $name) {
                return $row;
            }
        }
        return null;
    }

    /**
     * Resolve a rota interna da aplicação associada a uma permissão para verificação prática.
     */
    public function resolveRoute(string $permissionId, ?Application $app = null): ?string
    {
        $id = trim($permissionId);

        $map = [
            '*' => '/app',
            'dashboard.view' => '/app',
            'profile.edit' => '/profile',
            'users.view' => '/admin/users',
            'users.create' => '/admin/users/create',
            'users.edit' => '/admin/users',
            'users.manage' => '/admin/users',
            'roles.view' => '/admin/roles',
            'roles.create' => '/admin/roles/create',
            'roles.edit' => '/admin/roles',
            'roles.manage' => '/admin/roles',
            'settings.manage' => '/admin',
            'audit.view' => '/dev/logs',
            'dev.access' => '/dev',
            'dev.logs' => '/dev/logs',
            'dev.settings' => '/dev/password-policy',
        ];

        if (isset($map[$id])) {
            return $map[$id];
        }

        // Módulos dinâmicos (ex: clientes.view, produtos.create, tags.edit)
        if (str_contains($id, '.')) {
            [$prefix, $action] = explode('.', $id, 2);
            return $action === 'create' ? "/app/{$prefix}/create" : "/app/{$prefix}";
        }

        return null;
    }

    /**
     * Retorna a descrição legível e amigável da tela/ação associada à permissão.
     */
    public function friendlyDescription(string $permissionId, string $defaultDesc = '', ?Application $app = null): string
    {
        $id = trim($permissionId);

        $friendly = [
            '*' => 'Acesso irrestrito a todas as funcionalidades do sistema',
            'dashboard.view' => 'Painel inicial e visualização de módulos',
            'profile.edit' => 'Edição de perfil pessoal e troca de senha',
            'users.view' => 'Listagem e consulta de usuários cadastrados',
            'users.create' => 'Formulário de cadastro de novo usuário',
            'users.edit' => 'Edição de cadastro, status e senha de usuários',
            'users.manage' => 'Gerenciamento completo do módulo de usuários',
            'roles.view' => 'Listagem e consulta de perfis de acesso (RBAC)',
            'roles.create' => 'Formulário para criação de novos perfis de acesso',
            'roles.edit' => 'Edição e concessão de permissões de perfis',
            'roles.manage' => 'Gerenciamento completo de perfis de acesso',
            'settings.manage' => 'Painel de administração e configurações do sistema',
            'audit.view' => 'Visualização e filtros da trilha de auditoria',
            'dev.access' => 'Acesso ao painel Dev-End e ferramentas de desenvolvimento',
            'dev.logs' => 'Inspeção e visualização de arquivos de logs e auditoria',
            'dev.settings' => 'Painel de governança da política de senhas',
        ];

        if (isset($friendly[$id])) {
            return $friendly[$id];
        }

        if (str_contains($id, '.')) {
            [$prefix, $action] = explode('.', $id, 2);
            $entityName = ucfirst($prefix);
            if ($app && ($module = $app->modules->get($prefix))) {
                $entityName = $module['entity'] ?? $module['name'] ?? ucfirst($prefix);
            }
            return match ($action) {
                'view' => "Listagem e consulta de registros de {$entityName}",
                'create' => "Formulário de cadastro de {$entityName}",
                'edit' => "Edição de dados existentes de {$entityName}",
                'delete' => "Exclusão de registros de {$entityName}",
                default => $defaultDesc !== '' && !str_starts_with($defaultDesc, 'Permissão ') ? $defaultDesc : "Ação {$action} em {$entityName}",
            };
        }

        return ($defaultDesc !== '' && !str_starts_with($defaultDesc, 'Permissão ')) ? $defaultDesc : $id;
    }
}
