<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\CsvStorage;

final class GenericRepository extends CsvRepository
{
    public function __construct(
        CsvStorage $storage,
        private string $fileName,
        private array $customHeaders,
        private string $prefix = 'rec'
    ) {
        parent::__construct($storage);
        $this->ensureFile();
    }

    protected function file(): string
    {
        return $this->fileName;
    }

    protected function headers(): array
    {
        return array_values(array_unique(array_merge(['id', 'created_at', 'updated_at'], $this->customHeaders)));
    }

    private function ensureFile(): void
    {
        if (!$this->storage->exists($this->file())) {
            $this->storage->write($this->file(), $this->headers(), []);
        }
    }

    public function nextId(): string
    {
        $max = 0;
        $cleanPrefix = preg_replace('/[^a-z0-9]/i', '', strtolower($this->prefix)) ?: 'rec';
        foreach ($this->all() as $row) {
            $id = (string) ($row['id'] ?? '');
            if (preg_match('/^' . preg_quote($cleanPrefix, '/') . '_(\d+)$/', $id, $matches)) {
                $num = (int) $matches[1];
                if ($num > $max) {
                    $max = $num;
                }
            }
        }
        return sprintf('%s_%03d', $cleanPrefix, $max + 1);
    }

    public function findBy(string $field, mixed $value): ?array
    {
        foreach ($this->all() as $row) {
            if (($row[$field] ?? null) === $value) {
                return $row;
            }
        }
        return null;
    }

    public function count(): int
    {
        return count($this->all());
    }
}
