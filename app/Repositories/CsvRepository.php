<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\CsvStorage;

abstract class CsvRepository
{
    public function __construct(protected CsvStorage $storage) {}
    abstract protected function file(): string;
    abstract protected function headers(): array;
    public function all(): array
    {
        return $this->storage->read($this->file(), $this->headers());
    }
    public function find(string $id): ?array
    {
        foreach ($this->all() as $row) if (($row['id'] ?? '') === $id) return $row;
        return null;
    }
    public function insert(array $data): array
    {
        $rows = $this->all();
        $rows[] = array_merge(array_fill_keys($this->headers(), ''), $data);
        $this->storage->write($this->file(), $this->headers(), $rows);
        return $rows[array_key_last($rows)];
    }
    public function update(string $id, array $data): ?array
    {
        $rows = $this->all();
        $found = null;
        foreach ($rows as &$row) if (($row['id'] ?? '') === $id) {
            $row = array_merge($row, $data);
            $found = $row;
        }
        unset($row);
        if ($found) $this->storage->write($this->file(), $this->headers(), $rows);
        return $found;
    }
    public function delete(string $id): bool
    {
        $rows = $this->all();
        $filtered = array_values(array_filter($rows, fn($row) => ($row['id'] ?? '') !== $id));
        if (count($filtered) === count($rows)) {
            return false;
        }
        $this->storage->write($this->file(), $this->headers(), $filtered);
        return true;
    }
}
