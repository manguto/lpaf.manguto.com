<?php

declare(strict_types=1);

namespace App\Repositories;

final class RoleRepository extends CsvRepository
{
    protected function file(): string
    {
        return 'roles.csv';
    }
    protected function headers(): array
    {
        return ['id', 'name', 'description', 'protected', 'created_at', 'updated_at'];
    }

    public function forUser(string $userId): array
    {
        $roleIds = [];
        foreach ($this->storage->read('user_roles.csv', ['user_id', 'role_id']) as $row) {
            if (($row['user_id'] ?? '') === $userId) {
                $roleIds[] = $row['role_id'];
            }
        }

        $roles = [];
        foreach ($this->all() as $role) {
            if (in_array($role['id'], $roleIds, true)) {
                $roles[] = $role;
            }
        }

        return $roles;
    }
}

