<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\CsvStorage;

final class AuditService
{
    private array $headers = ['id', 'created_at', 'user_id', 'action', 'details'];

    public function __construct(private CsvStorage $storage) {}

    public function log(string $action, ?string $userId = null, string $details = ''): void
    {
        $path = $this->storage->path('audit_log.csv');
        $isNew = !is_file($path) || filesize($path) === 0;

        $handle = fopen($path, 'ab');
        if ($handle === false || !flock($handle, LOCK_EX)) {
            return;
        }

        if ($isNew) {
            fputcsv($handle, $this->headers, ';');
        }

        $id = 'aud_' . date('YmdHis') . '_' . bin2hex(random_bytes(2));
        $row = [
            $id,
            date('c'),
            $userId ?? ($_SESSION['user_id'] ?? ''),
            $action,
            $details,
        ];

        fputcsv($handle, $row, ';');
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    public function all(?string $filterAction = null, int $limit = 200): array
    {
        $rows = $this->storage->read('audit_log.csv', $this->headers);
        if ($filterAction !== null && $filterAction !== '') {
            $rows = array_filter($rows, fn($r) => ($r['action'] ?? '') === $filterAction);
        }
        $reversed = array_reverse(array_values($rows));
        return array_slice($reversed, 0, $limit);
    }

    public function actions(): array
    {
        $rows = $this->storage->read('audit_log.csv', $this->headers);
        $actions = [];
        foreach ($rows as $row) {
            $act = trim($row['action'] ?? '');
            if ($act !== '' && !in_array($act, $actions, true)) {
                $actions[] = $act;
            }
        }
        sort($actions);
        return $actions;
    }
}
