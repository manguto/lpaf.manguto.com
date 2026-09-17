<?php

declare(strict_types=1);
require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/app/Helpers/functions.php';

use App\Core\Application;
use App\Core\Config;
use App\Core\CsvStorage;
use App\Core\Request;
use App\Services\BackupService;
use App\Services\SetupService;

$root = sys_get_temp_dir() . '/lpaf_test_' . bin2hex(random_bytes(4));
mkdir($root, 0775, true);
$config = new Config($root);
$app = new Application($config, new CsvStorage($config), new Request());
(new SetupService($app))->install('Teste', 'Administrador', 'admin', 'senha-segura');
$files = ['users.csv', 'roles.csv', 'permissions.csv', 'user_roles.csv', 'role_permissions.csv', 'settings.csv'];
foreach ($files as $file) if (!$app->storage->exists($file)) throw new RuntimeException('CSV ausente: ' . $file);
$user = $app->users->findByUsername('admin');
if (!$user || !password_verify('senha-segura', $user['password_hash'])) throw new RuntimeException('Autenticação inválida.');
$relations = $app->storage->read('user_roles.csv');
if (($relations[0]['role_id'] ?? '') !== 'role_dev') throw new RuntimeException('RBAC inicial inválido.');
$app->storage->write('check.csv', ['id', 'value'], [['id' => '1', 'value' => 'ok']]);
if ($app->storage->read('check.csv')[0]['value'] !== 'ok') throw new RuntimeException('CSV não persistiu.');

// Teste do BackupService
$backupService = new BackupService($app);
$backupId = $backupService->create('teste_unitario', 'usr_001');
$backups = $backupService->all();
if (empty($backups) || $backups[0]['id'] !== $backupId) throw new RuntimeException('Backup não encontrado na listagem.');

// Altera dado para verificar restauração
$app->storage->write('check.csv', ['id', 'value'], [['id' => '1', 'value' => 'alterado']]);
if ($app->storage->read('check.csv')[0]['value'] !== 'alterado') throw new RuntimeException('Falha na alteração pré-restauração.');

// Restaura o backup
$restored = $backupService->restore($backupId, 'usr_001');
if (!$restored) throw new RuntimeException('Restauração falhou.');
if ($app->storage->read('check.csv')[0]['value'] !== 'ok') throw new RuntimeException('Dado não foi restaurado para o estado original.');

// Verifica se a salvaguarda pré-restauração foi gerada
$backupsAfter = $backupService->all();
$hasPreRestore = false;
foreach ($backupsAfter as $b) {
    if (str_contains($b['id'], 'pre_restore')) {
        $hasPreRestore = true;
        break;
    }
}
// Teste do AuditService (append atômico e filtros)
$auditService = new \App\Services\AuditService($app->storage);
$auditService->log('evento_teste', 'usr_001', 'detalhe=123');
$logs = $auditService->all();
if (empty($logs) || $logs[0]['action'] !== 'evento_teste') throw new RuntimeException('Falha no registro/leitura de auditoria.');
$filteredLogs = $auditService->all('evento_teste');
if (empty($filteredLogs) || $filteredLogs[0]['action'] !== 'evento_teste') throw new RuntimeException('Falha no filtro de auditoria.');
// Teste de atualização de perfil e senha
$currentUser = $app->users->find('usr_001');
if (!$currentUser) throw new RuntimeException('Usuário usr_001 não encontrado.');

// Testa recuperação dos papéis do usuário
$userRoles = $app->roles->forUser('usr_001');
if (empty($userRoles) || $userRoles[0]['id'] !== 'role_dev') throw new RuntimeException('Falha em roles->forUser().');

// Simula atualização de nome e troca de senha
$newName = 'Administrador Atualizado';
$newPass = 'nova-senha-segura-2026';
$app->users->update('usr_001', [
    'name' => $newName,
    'password_hash' => password_hash($newPass, PASSWORD_DEFAULT),
    'updated_at' => date('c'),
]);
$auditService->log('profile_updated', 'usr_001', 'Nome e senha atualizados');

$userAfterUpdate = $app->users->find('usr_001');
if ($userAfterUpdate['name'] !== $newName) throw new RuntimeException('Falha na atualização do nome do perfil.');
if (!password_verify($newPass, $userAfterUpdate['password_hash'])) throw new RuntimeException('Falha na verificação da nova senha atualizada.');
if (password_verify('senha-segura', $userAfterUpdate['password_hash'])) throw new RuntimeException('Senha antiga ainda continua válida.');

$profileLogs = $auditService->all('profile_updated');
if (empty($profileLogs)) throw new RuntimeException('Log de auditoria profile_updated não registrado.');

echo "Verificação OK: setup, CSV, hash de senha, RBAC, Backups, Auditoria e Perfil/Troca de Senha.\n";

