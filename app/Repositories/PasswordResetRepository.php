<?php

declare(strict_types=1);

namespace App\Repositories;

final class PasswordResetRepository extends CsvRepository
{
    protected function file(): string
    {
        return 'password_resets.csv';
    }

    protected function headers(): array
    {
        return ['id', 'user_id', 'token_hash', 'expires_at', 'used_at', 'created_at', 'ip'];
    }

    public function findValidByTokenHash(string $tokenHash): ?array
    {
        $now = time();
        foreach ($this->all() as $row) {
            if (
                hash_equals($row['token_hash'] ?? '', $tokenHash) &&
                empty($row['used_at']) &&
                strtotime($row['expires_at'] ?? '1970-01-01') >= $now
            ) {
                return $row;
            }
        }
        return null;
    }

    public function markUsed(string $id): void
    {
        $this->update($id, [
            'used_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function invalidateUserTokens(string $userId): void
    {
        $rows = $this->all();
        $changed = false;
        $nowStr = date('Y-m-d H:i:s');
        foreach ($rows as &$row) {
            if (($row['user_id'] ?? '') === $userId && empty($row['used_at'])) {
                $row['used_at'] = $nowStr;
                $changed = true;
            }
        }
        unset($row);

        if ($changed) {
            $this->storage->write($this->file(), $this->headers(), $rows);
        }
    }

    public function nextId(): string
    {
        return sprintf('rst_%04d', count($this->all()) + 1);
    }
}
