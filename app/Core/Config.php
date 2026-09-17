<?php

declare(strict_types=1);

namespace App\Core;

final class Config
{
    private array $values;
    public function __construct(string $root)
    {
        $this->values = ['root' => $root, 'app_name' => 'PHP Admin Framework', 'app_env' => 'local', 'app_debug' => false, 'app_url' => '', 'app_setup_key' => '', 'storage_data' => $root . '/storage/data', 'storage_logs' => $root . '/storage/logs'];
        $path = $root . '/.env';
        if (!is_file($path)) return;
        $map = ['APP_NAME' => 'app_name', 'APP_ENV' => 'app_env', 'APP_DEBUG' => 'app_debug', 'APP_URL' => 'app_url', 'APP_SETUP_KEY' => 'app_setup_key'];
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \\t\\n\\r\\0\\x0B\\\"'");
            if (isset($map[$key])) $this->values[$map[$key]] = $key === 'APP_DEBUG' ? filter_var($value, FILTER_VALIDATE_BOOLEAN) : $value;
        }
    }
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }
}
