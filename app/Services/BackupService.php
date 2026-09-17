<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;
use App\Core\Response;

final class BackupService
{
    private string $backupsDir;
    private string $dataDir;
    private AuditService $audit;

    public function __construct(private Application $app, ?AuditService $audit = null)
    {
        $this->backupsDir = $this->app->config->get('root') . '/storage/backups';
        $this->dataDir = (string) $this->app->config->get('storage_data');
        $this->audit = $audit ?? new AuditService($this->app->storage);

        if (!is_dir($this->backupsDir)) {
            mkdir($this->backupsDir, 0775, true);
        }
    }

    public function all(): array
    {
        if (!is_dir($this->backupsDir)) {
            return [];
        }

        $items = scandir($this->backupsDir, SCANDIR_SORT_DESCENDING) ?: [];
        $backups = [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..' || !is_dir($this->backupsDir . '/' . $item)) {
                continue;
            }

            $path = $this->backupsDir . '/' . $item;
            $metaPath = $path . '/meta.json';
            $meta = is_file($metaPath) ? json_decode((string) file_get_contents($metaPath), true) : [];

            $files = glob($path . '/*.csv') ?: [];
            $totalSize = 0;
            foreach ($files as $file) {
                $totalSize += filesize($file);
            }

            $createdAt = $meta['created_at'] ?? date('c', filemtime($path));
            $label = $meta['label'] ?? (str_contains($item, 'pre_restore') ? 'Auto (Salvaguarda pré-restauração)' : 'Manual');

            $backups[] = [
                'id' => $item,
                'path' => $path,
                'label' => $label,
                'files_count' => count($files),
                'total_bytes' => $totalSize,
                'formatted_size' => $this->formatBytes($totalSize),
                'created_at' => $createdAt,
                'created_by' => $meta['created_by'] ?? null,
            ];
        }

        return $backups;
    }

    public function create(?string $label = null, ?string $userId = null): string
    {
        $safeLabel = $label !== null && $label !== ''
            ? '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower($label))
            : '';

        $backupId = date('Y-m-d_H-i-s') . $safeLabel;
        $destDir = $this->backupsDir . '/' . $backupId;

        $counter = 1;
        while (is_dir($destDir)) {
            $backupId = date('Y-m-d_H-i-s') . $safeLabel . '_' . $counter;
            $destDir = $this->backupsDir . '/' . $backupId;
            $counter++;
        }

        if (!mkdir($destDir, 0775, true) && !is_dir($destDir)) {
            throw new \RuntimeException('Não foi possível criar o diretório de backup: ' . $backupId);
        }

        $sourceFiles = glob($this->dataDir . '/*.csv') ?: [];
        $copiedFiles = [];
        $totalSize = 0;

        foreach ($sourceFiles as $sourceFile) {
            $fileName = basename($sourceFile);
            $targetFile = $destDir . '/' . $fileName;

            if (!copy($sourceFile, $targetFile)) {
                throw new \RuntimeException('Falha ao copiar arquivo para o backup: ' . $fileName);
            }

            $size = filesize($targetFile);
            $totalSize += $size;
            $copiedFiles[] = [
                'name' => $fileName,
                'size' => $size,
            ];
        }

        $meta = [
            'id' => $backupId,
            'label' => $label,
            'created_at' => date('c'),
            'created_by' => $userId ?? ($_SESSION['user_id'] ?? null),
            'files' => $copiedFiles,
            'total_size' => $totalSize,
        ];

        file_put_contents($destDir . '/meta.json', json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->audit->log('backup_created', $userId, 'backup=' . $backupId);

        return $backupId;
    }

    public function restore(string $backupId, ?string $userId = null): bool
    {
        $safeId = basename($backupId);
        $sourceDir = $this->backupsDir . '/' . $safeId;

        if (!is_dir($sourceDir)) {
            return false;
        }

        $backupFiles = glob($sourceDir . '/*.csv') ?: [];
        if (empty($backupFiles)) {
            return false;
        }

        // Salvaguarda automática antes de sobrescrever os dados atuais
        $this->create('pre_restore', $userId);

        foreach ($backupFiles as $backupFile) {
            $fileName = basename($backupFile);
            $destination = $this->dataDir . '/' . $fileName;
            $tempDest = $destination . '.tmp';

            if (!copy($backupFile, $tempDest)) {
                throw new \RuntimeException('Falha ao preparar restauração para: ' . $fileName);
            }

            if (!rename($tempDest, $destination)) {
                throw new \RuntimeException('Falha ao substituir arquivo na restauração: ' . $fileName);
            }
        }

        $this->audit->log('backup_restored', $userId, 'backup=' . $safeId);

        return true;
    }

    public function download(string $backupId): never
    {
        $safeId = basename($backupId);
        $targetFolder = $this->backupsDir . '/' . $safeId;

        if (!is_dir($targetFolder)) {
            Response::error(404, 'Backup não encontrado.');
        }

        if (!class_exists(\ZipArchive::class)) {
            Response::error(500, 'Extensão ZipArchive não disponível no servidor.');
        }

        $tmpZip = tempnam(sys_get_temp_dir(), 'bkp_') . '.zip';
        $zip = new \ZipArchive();

        if ($zip->open($tmpZip, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            Response::error(500, 'Não foi possível gerar arquivo ZIP do backup.');
        }

        $files = glob($targetFolder . '/*') ?: [];
        foreach ($files as $file) {
            if (is_file($file)) {
                $zip->addFile($file, basename($file));
            }
        }

        $zip->close();

        if (!is_file($tmpZip)) {
            Response::error(500, 'Falha ao acessar pacote ZIP gerado.');
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="backup_' . $safeId . '.zip"');
        header('Content-Length: ' . (string) filesize($tmpZip));
        header('Pragma: no-cache');
        header('Expires: 0');

        readfile($tmpZip);
        @unlink($tmpZip);
        exit;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return round($bytes / 1048576, 2) . ' MB';
    }
}
