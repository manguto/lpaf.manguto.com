<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;

final class AuthService
{
    public function __construct(private Application $app, private AuditService $audit) {}
    public function login(string $username, string $password): bool
    {
        $user = $this->app->users->findByUsername($username);
        if (!$user || $user['active'] !== '1' || !password_verify($password, $user['password_hash'])) {
            $this->audit->log('login_failed', null, 'username=' . substr($username, 0, 80));
            return false;
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION['user_id'] = $user['id'];
        $this->audit->log('login', $user['id']);
        return true;
    }
    public function logout(): void
    {
        $id = $_SESSION['user_id'] ?? null;
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
        }
        session_destroy();
        if ($id) $this->audit->log('logout', (string) $id);
    }
}
