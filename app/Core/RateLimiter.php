<?php

declare(strict_types=1);

namespace App\Core;

final class RateLimiter
{
    private string $filePath;

    public function __construct(Config $config)
    {
        $dir = (string) $config->get('storage_tmp', $config->get('root') . '/storage/tmp');
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $this->filePath = $dir . '/rate_limits.csv';
    }

    public function tooManyAttempts(string $key, int $maxAttempts = 5): bool
    {
        $record = $this->get($key);
        if (!$record) {
            return false;
        }

        $now = time();
        if ($record['locked_until'] > $now) {
            return true;
        }

        if ($record['locked_until'] > 0 && $record['locked_until'] <= $now) {
            $this->clear($key);
            return false;
        }

        return $record['attempts'] >= $maxAttempts;
    }

    public function hit(string $key, int $decaySeconds = 300, int $maxAttempts = 5): int
    {
        $now = time();
        $records = $this->readAll();
        $record = $records[$key] ?? [
            'key' => $key,
            'attempts' => 0,
            'first_attempt_at' => $now,
            'locked_until' => 0,
        ];

        if ($record['locked_until'] <= 0 && ($now - $record['first_attempt_at']) > $decaySeconds) {
            $record['attempts'] = 0;
            $record['first_attempt_at'] = $now;
        }

        $record['attempts']++;

        if ($record['attempts'] >= $maxAttempts) {
            $record['locked_until'] = $now + $decaySeconds;
        }

        $records[$key] = $record;
        $this->writeAll($records);

        return $record['attempts'];
    }

    public function retriesLeft(string $key, int $maxAttempts = 5): int
    {
        $record = $this->get($key);
        if (!$record) {
            return $maxAttempts;
        }

        return max(0, $maxAttempts - $record['attempts']);
    }

    public function availableIn(string $key): int
    {
        $record = $this->get($key);
        if (!$record || $record['locked_until'] <= time()) {
            return 0;
        }

        return max(0, $record['locked_until'] - time());
    }

    public function clear(string $key): void
    {
        $records = $this->readAll();
        if (isset($records[$key])) {
            unset($records[$key]);
            $this->writeAll($records);
        }
    }

    private function get(string $key): ?array
    {
        $records = $this->readAll();
        return $records[$key] ?? null;
    }

    /**
     * @return array<string, array{key: string, attempts: int, first_attempt_at: int, locked_until: int}>
     */
    private function readAll(): array
    {
        if (!is_file($this->filePath)) {
            return [];
        }

        $handle = @fopen($this->filePath, 'rb');
        if (!$handle) {
            return [];
        }

        flock($handle, LOCK_SH);
        fgetcsv($handle, 0, ';');
        $records = [];
        $now = time();

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            if (count($row) >= 4) {
                $k = trim((string) $row[0]);
                $attempts = (int) $row[1];
                $firstAt = (int) $row[2];
                $lockedUntil = (int) $row[3];

                if (($now - $firstAt) < 86400) {
                    $records[$k] = [
                        'key' => $k,
                        'attempts' => $attempts,
                        'first_attempt_at' => $firstAt,
                        'locked_until' => $lockedUntil,
                    ];
                }
            }
        }

        flock($handle, LOCK_UN);
        fclose($handle);

        return $records;
    }

    private function writeAll(array $records): void
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $tmp = $this->filePath . '.' . bin2hex(random_bytes(4)) . '.tmp';
        $handle = @fopen($tmp, 'wb');
        if (!$handle) {
            return;
        }

        flock($handle, LOCK_EX);
        fputcsv($handle, ['key', 'attempts', 'first_attempt_at', 'locked_until'], ';');
        foreach ($records as $row) {
            fputcsv($handle, [
                $row['key'],
                (string) $row['attempts'],
                (string) $row['first_attempt_at'],
                (string) $row['locked_until'],
            ], ';');
        }
        fflush($handle);
        flock($handle, LOCK_UN);
        fclose($handle);

        @rename($tmp, $this->filePath);
    }
}
