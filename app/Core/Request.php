<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public function method(): string
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $spoofed = strtoupper((string) ($_POST['_method'] ?? ''));
        return $method === 'POST' && in_array($spoofed, ['PUT', 'PATCH', 'DELETE'], true) ? $spoofed : $method;
    }
    public function path(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = $this->basePath();
        if ($base !== '' && $base !== '/' && str_starts_with($path, $base)) $path = substr($path, strlen($base));
        return '/' . trim($path, '/');
    }
    public function basePath(): string
    {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $directory = rtrim(str_replace('\\', '/', dirname($script)), '/');
        if (basename($directory) === 'public') $directory = dirname($directory);
        return $directory === '/' || $directory === '.' ? '' : $directory;
    }
    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }
}
