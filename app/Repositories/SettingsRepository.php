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
    public function get(string $key, mixed $default = null): mixed
    {
        foreach ($this->all() as $row) {
            if (($row['key'] ?? '') === $key) {
                return $row['value'];
            }
        }
        return $default;
    }
    public function set(string $key, string $value): void
    {
        $rows = $this->all();
        $found = false;
        foreach ($rows as &$row) {
            if (($row['key'] ?? '') === $key) {
                $row['value'] = $value;
                $found = true;
                break;
            }
        }
        unset($row);
        if (!$found) {
            $rows[] = ['key' => $key, 'value' => $value];
        }
        $this->write($rows);
    }
    public function setMultiple(array $pairs): void
    {
        $rows = $this->all();
        $map = [];
        foreach ($rows as $row) {
            if (isset($row['key'])) {
                $map[$row['key']] = $row['value'] ?? '';
            }
        }
        foreach ($pairs as $k => $v) {
            $map[(string) $k] = (string) $v;
        }
        $newRows = [];
        foreach ($map as $k => $v) {
            $newRows[] = ['key' => $k, 'value' => $v];
        }
        $this->write($newRows);
    }
}
