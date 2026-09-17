<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\CsvStorage;

final class AuditService
{
    public function __construct(private CsvStorage $storage) {}
    public function log(string $action, ?string $userId = null, string $details = ''): void
    {
        $rows = $this->storage->read('audit_log.csv', ['id', 'created_at', 'user_id', 'action', 'details']);
        $rows[] = ['id' => 'audit_' . str_pad((string) (count($rows) + 1), 4, '0', STR_PAD_LEFT), 'created_at' => date('c'), 'user_id' => $userId ?? ($_SESSION['user_id'] ?? ''), 'action' => $action, 'details' => $details];
        $this->storage->write('audit_log.csv', ['id', 'created_at', 'user_id', 'action', 'details'], $rows);
    }
}
