<?php

declare(strict_types=1);

namespace App\Repositories;

final class UserRepository extends CsvRepository
{
    protected function file(): string
    {
        return 'users.csv';
    }
    protected function headers(): array
    {
        return ['id', 'name', 'username', 'password_hash', 'active', 'created_at', 'updated_at'];
    }
    public function findByUsername(string $username): ?array
    {
        foreach ($this->all() as $row) if (strtolower($row['username']) === strtolower($username)) return $row;
        return null;
    }
    public function nextId(): string
    {
        return sprintf('usr_%03d', count($this->all()) + 1);
    }
}
