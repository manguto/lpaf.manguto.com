<?php

declare(strict_types=1);

namespace App\Repositories;

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
        foreach ($this->all() as $row) if ($row['name'] === $name) return $row;
        return null;
    }
}
