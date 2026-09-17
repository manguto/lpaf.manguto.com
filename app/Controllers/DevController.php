<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\BackupService;

final class DevController extends Controller
{
    public function index(Request $request): void
    {
        $this->view('dev/index');
    }

    public function diagnostics(Request $request): void
    {
        $files = ['users.csv', 'roles.csv', 'permissions.csv', 'user_roles.csv', 'role_permissions.csv', 'settings.csv'];
        $status = [];
        foreach ($files as $file) $status[$file] = ['exists' => $this->app->storage->exists($file), 'rows' => count($this->app->storage->read($file))];
        $this->view('dev/diagnostics', ['status' => $status]);
    }

    public function backups(Request $request): void
    {
        $service = new BackupService($this->app);
        $this->view('dev/backups', ['backups' => $service->all()]);
    }

    public function createBackup(Request $request): void
    {
        $service = new BackupService($this->app);
        $label = trim((string) $request->input('label', ''));
        $userId = $this->user()['id'] ?? null;
        $service->create($label !== '' ? $label : null, $userId);
        Session::flash('message', 'Backup criado com sucesso.');
        Response::redirect('/dev/backups');
    }

    public function restoreBackup(Request $request, array $params): void
    {
        $service = new BackupService($this->app);
        $backupId = (string) ($params['id'] ?? '');
        $userId = $this->user()['id'] ?? null;

        if ($service->restore($backupId, $userId)) {
            Session::flash('message', 'Backup restaurado com sucesso. Um snapshot de salvaguarda foi gerado automaticamente.');
        } else {
            Session::flash('error', 'Não foi possível restaurar o backup informado.');
        }

        Response::redirect('/dev/backups');
    }

    public function downloadBackup(Request $request, array $params): void
    {
        $service = new BackupService($this->app);
        $backupId = (string) ($params['id'] ?? '');
        $service->download($backupId);
    }
}
