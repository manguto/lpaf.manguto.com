<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\CsvStorage;

final class SettingsRepository
{
    public function __construct(private CsvStorage $storage) {}
    public function all(): array
    {
        return $this->storage->read('settings.csv', ['key', 'value']);
    }
    public function write(array $rows): void
    {
        $this->storage->write('settings.csv', ['key', 'value'], $rows);
    }
}
