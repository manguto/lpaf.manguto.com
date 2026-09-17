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
}
