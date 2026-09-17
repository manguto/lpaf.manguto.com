<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    private static string $basePath = '';

    public static function setBasePath(string $basePath): void
    {
        self::$basePath = rtrim($basePath, '/');
    }

    public static function redirect(string $path): never
    {
        $location = str_starts_with($path, '/') ? self::$basePath . $path : $path;
        header('Location: ' . ($location !== '' ? $location : '/'));
        exit;
    }
    public static function error(int $status, string $message): never
    {
        http_response_code($status);
        $errorView = dirname(__DIR__, 2) . '/views/errors/error.php';
        if (is_file($errorView)) {
            $basePath = self::$basePath;
            include $errorView;
            exit;
        }
        echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        exit;
    }
}
