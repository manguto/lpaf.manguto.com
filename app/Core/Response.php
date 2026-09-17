<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }
    public static function error(int $status, string $message): never
    {
        http_response_code($status);
        echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        exit;
    }
}
