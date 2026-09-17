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
}
