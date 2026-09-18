<?php

declare(strict_types=1);

namespace App\Core;

final class CsvStorage
{
    public function __construct(private Config $config)
    {
        foreach ([$config->get('storage_data'), $config->get('storage_logs'), $config->get('root') . '/storage/backups', $config->get('root') . '/storage/tmp'] as $dir) if (!is_dir($dir)) mkdir($dir, 0775, true);
    }
    public function read(string $file, array $headers = []): array
    {
        $path = $this->path($file);
        if (!is_file($path)) return [];
        $handle = fopen($path, 'rb');
        if ($handle === false) return [];
        flock($handle, LOCK_SH);
        $columns = fgetcsv($handle, 0, ';') ?: $headers;
        $rows = [];
        while (($row = fgetcsv($handle, 0, ';')) !== false) if ($row !== [null] && $row !== []) $rows[] = array_combine($columns, array_pad(array_slice($row, 0, count($columns)), count($columns), '')) ?: [];
        flock($handle, LOCK_UN);
        fclose($handle);
        return $rows;
    }
    public function write(string $file, array $headers, array $rows): void
    {
        $path = $this->path($file);
        $temp = $path . '.tmp';
        $handle = fopen($temp, 'wb');
        if ($handle === false || !flock($handle, LOCK_EX)) throw new \RuntimeException('Não foi possível gravar o CSV.');
        fputcsv($handle, $headers, ';');
        foreach ($rows as $row) fputcsv($handle, array_map(fn($header) => $row[$header] ?? '', $headers), ';');
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
        if (!rename($temp, $path)) throw new \RuntimeException('Não foi possível substituir o CSV.');
    }
    public function exists(string $file): bool
    {
        return is_file($this->path($file));
    }
    public function delete(string $file): bool
    {
        $path = $this->path($file);
        return is_file($path) ? unlink($path) : false;
    }
    public function path(string $file): string
    {
        return str_contains($file, 'audit_log') ? $this->config->get('storage_logs') . '/' . basename($file) : $this->config->get('storage_data') . '/' . basename($file);
    }
}
