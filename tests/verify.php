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

// Teste do Motor de Módulos e CRUD Declarativo
$modulesDir = $root . '/modules/equipamentos';
mkdir($modulesDir, 0775, true);
copy(dirname(__DIR__) . '/modules/equipamentos/module.php', $modulesDir . '/module.php');

$moduleManager = new \App\Core\ModuleManager($app);
$modules = $moduleManager->all();
if (!isset($modules['equipamentos'])) throw new RuntimeException('Módulo equipamentos não descoberto pelo ModuleManager.');

$moduleManager->ensurePermissions();
$permissions = $app->permissions->all();
$permIds = array_column($permissions, 'id');
if (!in_array('equipamentos.view', $permIds, true)) throw new RuntimeException('Permissão equipamentos.view não registrada automaticamente.');
if (!in_array('equipamentos.create', $permIds, true)) throw new RuntimeException('Permissão equipamentos.create não registrada automaticamente.');

$repo = $moduleManager->repository('equipamentos');
if (!$repo) throw new RuntimeException('GenericRepository para equipamentos não foi instanciado.');

$newEqp = $repo->insert([
    'id' => $repo->nextId(),
    'patrimonio' => 'PAT-001',
    'nome' => 'Notebook Dell Latitude',
    'categoria' => 'Notebook',
    'fabricante' => 'Dell',
    'modelo' => '5420',
    'ativo' => '1',
    'observacoes' => 'Equipamento de TI para testes',
    'created_at' => date('c'),
    'updated_at' => date('c'),
]);

if ($newEqp['id'] !== 'eqp_001') throw new RuntimeException('ID do equipamento não gerou prefixo esperado eqp_001, obtido: ' . $newEqp['id']);
if ($repo->count() !== 1) throw new RuntimeException('Contagem de equipamentos inválida.');

$foundEqp = $repo->findBy('patrimonio', 'PAT-001');
if (!$foundEqp || $foundEqp['nome'] !== 'Notebook Dell Latitude') throw new RuntimeException('Falha no findBy do GenericRepository.');

$repo->update('eqp_001', ['nome' => 'Notebook Dell Atualizado']);
if ($repo->find('eqp_001')['nome'] !== 'Notebook Dell Atualizado') throw new RuntimeException('Falha no update do GenericRepository.');

$deleted = $repo->delete('eqp_001');
if (!$deleted || $repo->count() !== 0) throw new RuntimeException('Falha no delete do GenericRepository.');

// Teste do Entity Builder (Criação de Entidade)
$builderSlug = 'projetos';
$builderDir = $root . '/modules/' . $builderSlug;
mkdir($builderDir, 0775, true);

$builderConfig = [
    'name' => 'Projetos',
    'entity' => 'Projeto',
    'slug' => $builderSlug,
    'icon' => '🚀',
    'description' => 'Módulo de projetos criado via Entity Builder',
    'prefix' => 'prj',
    'storage' => 'projetos.csv',
    'permission_prefix' => $builderSlug,
    'fields' => [
        'codigo' => ['label' => 'Código', 'type' => 'string', 'required' => true, 'unique' => true, 'list' => true],
        'titulo' => ['label' => 'Título do Projeto', 'type' => 'string', 'required' => true, 'list' => true],
        'prioridade' => ['label' => 'Prioridade', 'type' => 'select', 'options' => ['Alta', 'Média', 'Baixa'], 'list' => true],
        'ativo' => ['label' => 'Ativo', 'type' => 'boolean', 'default' => true, 'list' => true],
    ],
];
file_put_contents($builderDir . '/module.php', "<?php\nreturn " . var_export($builderConfig, true) . ";\n");

// Gera salvaguarda de segurança pré-criação
$backupService->create('pre_entity_create_' . $builderSlug, 'usr_001');

// Inicializa CSV e atualiza permissões
$builderHeaders = ['id', 'created_at', 'updated_at', 'codigo', 'titulo', 'prioridade', 'ativo'];
$app->storage->write('projetos.csv', $builderHeaders, []);
$moduleManager = new \App\Core\ModuleManager($app);
$moduleManager->ensurePermissions();

$allMods = $moduleManager->all();
if (!isset($allMods['projetos'])) throw new RuntimeException('Módulo projetos criado pelo Entity Builder não foi reconhecido.');

$prjRepo = $moduleManager->repository('projetos');
$prj = $prjRepo->insert([
    'id' => $prjRepo->nextId(),
    'codigo' => 'PRJ-2026',
    'titulo' => 'Expansão da Infraestrutura',
    'prioridade' => 'Alta',
    'ativo' => '1',
    'created_at' => date('c'),
    'updated_at' => date('c'),
]);
if ($prj['id'] !== 'prj_001') throw new RuntimeException('Prefixo de ID gerado pelo Entity Builder inválido: ' . $prj['id']);
if ($prjRepo->count() !== 1) throw new RuntimeException('Contagem da entidade projetos inválida.');

$auditService->log('module_created', 'usr_001', 'slug=projetos');
$modLogs = $auditService->all('module_created');
if (empty($modLogs)) throw new RuntimeException('Auditoria module_created não foi registrada.');

// Teste de Edição de Entidade (Entity Builder - Edição e Expansão de Schema)
$backupService->create('pre_entity_edit_' . $builderSlug, 'usr_001');

$builderConfig['fields']['orcamento'] = [
    'label' => 'Orçamento Previsto',
    'type' => 'number',
    'required' => false,
    'unique' => false,
    'list' => true,
];
file_put_contents($builderDir . '/module.php', "<?php\nreturn " . var_export($builderConfig, true) . ";\n");

// Expansão do CSV preservando os registros existentes
$updatedHeaders = array_merge(['id', 'created_at', 'updated_at'], array_keys($builderConfig['fields']));
$existingRows = $app->storage->read('projetos.csv');
$app->storage->write('projetos.csv', $updatedHeaders, $existingRows);

$moduleManagerUpdated = new \App\Core\ModuleManager($app);
$prjRepoUpdated = $moduleManagerUpdated->repository('projetos');

// Verifica se os dados existentes continuam íntegros
$existingPrj = $prjRepoUpdated->find('prj_001');
if (!$existingPrj || $existingPrj['codigo'] !== 'PRJ-2026' || $existingPrj['titulo'] !== 'Expansão da Infraestrutura') {
    throw new RuntimeException('Dados da entidade projetos corrompidos ou perdidos durante a edição do schema.');
}
if (!array_key_exists('orcamento', $existingPrj)) {
    throw new RuntimeException('Nova coluna orcamento não está presente no registro existente.');
}

// Insere novo registro com o novo campo
$prj2 = $prjRepoUpdated->insert([
    'id' => $prjRepoUpdated->nextId(),
    'codigo' => 'PRJ-2027',
    'titulo' => 'Data Center Cloud',
    'prioridade' => 'Alta',
    'ativo' => '1',
    'orcamento' => '50000',
    'created_at' => date('c'),
    'updated_at' => date('c'),
]);
if ($prj2['id'] !== 'prj_002') throw new RuntimeException('Segundo ID da entidade projetos inválido.');
if ($prjRepoUpdated->count() !== 2) throw new RuntimeException('Contagem após inserção pós-edição de schema inválida.');

$auditService->log('module_updated', 'usr_001', 'slug=projetos');
$modUpdateLogs = $auditService->all('module_updated');
if (empty($modUpdateLogs)) throw new RuntimeException('Auditoria module_updated não foi registrada.');

// Limpa módulo de teste
unlink($builderDir . '/module.php');
rmdir($builderDir);

echo "Verificação OK: setup, CSV, hash de senha, RBAC, Backups, Auditoria, Perfil, Motor de Módulos e Entity Builder (criação e edição).\n";



