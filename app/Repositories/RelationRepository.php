<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\CsvStorage;

final class RelationRepository
{
    public function __construct(private CsvStorage $storage, private string $file, private array $headers) {}
    public function all(): array
    {
        return $this->storage->read($this->file, $this->headers);
    }
    public function replace(array $rows): void
    {
        $this->storage->write($this->file, $this->headers, $rows);
    }
    public function add(array $row): void
    {
        $rows = $this->all();
        foreach ($rows as $existing) if ($existing === $row) return;
        $rows[] = $row;
        $this->replace($rows);
    }

    public function findBy(string $key, string $value): array
    {
        return array_values(array_filter($this->all(), fn(array $r) => ($r[$key] ?? '') === $value));
    }

    public function sync(string $parentKey, string $parentId, string $targetKey, array $targetIds): void
    {
        $current = $this->all();
        $kept = array_values(array_filter($current, fn(array $r) => ($r[$parentKey] ?? '') !== $parentId));
        $targetIds = array_values(array_unique(array_filter($targetIds, fn($id) => $id !== null && $id !== '')));
        foreach ($targetIds as $tid) {
            $kept[] = [
                $parentKey => (string) $parentId,
                $targetKey => (string) $tid,
            ];
        }
        $this->replace($kept);
    }

    public function deleteBy(string $key, string $value): int
    {
        $all = $this->all();
        $kept = array_values(array_filter($all, fn(array $r) => ($r[$key] ?? '') !== $value));
        $deleted = count($all) - count($kept);
        if ($deleted > 0) {
            $this->replace($kept);
        }
        return $deleted;
    }
}
