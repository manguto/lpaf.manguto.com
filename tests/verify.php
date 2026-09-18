<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/Preflight.php';
\App\Core\Preflight::check(dirname(__DIR__));

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

// Teste de Reordenação de Campos (novo campo como 2º item)
$reorderedFields = [
    'codigo' => $builderConfig['fields']['codigo'],
    'orcamento' => $builderConfig['fields']['orcamento'],
    'titulo' => $builderConfig['fields']['titulo'],
    'prioridade' => $builderConfig['fields']['prioridade'],
    'ativo' => $builderConfig['fields']['ativo'],
];
$builderConfig['fields'] = $reorderedFields;
file_put_contents($builderDir . '/module.php', "<?php\nreturn " . var_export($builderConfig, true) . ";\n");

// Reordena cabeçalho do CSV preservando os registros existentes
$reorderedHeaders = array_merge(['id', 'created_at', 'updated_at'], array_keys($reorderedFields));
$existingRowsReordered = $app->storage->read('projetos.csv');
$app->storage->write('projetos.csv', $reorderedHeaders, $existingRowsReordered);

$moduleManagerReordered = new \App\Core\ModuleManager($app);
$reorderedMod = $moduleManagerReordered->get('projetos');
$fieldKeys = array_keys($reorderedMod['fields']);
if ($fieldKeys[1] !== 'orcamento') {
    throw new RuntimeException('Ordem dos campos não foi aplicada corretamente. Esperado orcamento na 2ª posição.');
}

// Verifica se os dados continuam intactos após reordenação
$prjRepoReordered = $moduleManagerReordered->repository('projetos');
$prjAfterReorder = $prjRepoReordered->find('prj_002');
if (!$prjAfterReorder || $prjAfterReorder['orcamento'] !== '50000' || $prjAfterReorder['titulo'] !== 'Data Center Cloud') {
    throw new RuntimeException('Dados corrompidos após reordenação dos campos.');
}

// Limpa módulo de teste
unlink($builderDir . '/module.php');
rmdir($builderDir);

// =========================================================================
// Teste de Relacionamento entre Entidades (Chave Estrangeira 1:N e Integridade Referencial)
// =========================================================================
$clientesDir = $app->config->get('root') . '/modules/clientes_test';
if (!is_dir($clientesDir)) mkdir($clientesDir, 0775, true);
$contratosDir = $app->config->get('root') . '/modules/contratos_test';
if (!is_dir($contratosDir)) mkdir($contratosDir, 0775, true);

// Entidade Pai: Clientes
$clientesConfig = [
    'name' => 'Clientes Teste',
    'entity' => 'Cliente Teste',
    'slug' => 'clientes_test',
    'icon' => '🏢',
    'prefix' => 'cli',
    'storage' => 'clientes_test.csv',
    'fields' => [
        'nome' => ['type' => 'string', 'label' => 'Nome da Empresa', 'required' => true],
    ],
];
file_put_contents($clientesDir . '/module.php', "<?php\nreturn " . var_export($clientesConfig, true) . ";\n");

// Entidade Filha: Contratos (com campo relation para clientes_test)
$contratosConfig = [
    'name' => 'Contratos Teste',
    'entity' => 'Contrato Teste',
    'slug' => 'contratos_test',
    'icon' => '📄',
    'prefix' => 'cnt',
    'storage' => 'contratos_test.csv',
    'fields' => [
        'numero' => ['type' => 'string', 'label' => 'Número', 'required' => true],
        'cliente_id' => [
            'type' => 'relation',
            'label' => 'Cliente',
            'target' => 'clientes_test',
            'display' => 'nome',
            'required' => true,
        ],
    ],
];
file_put_contents($contratosDir . '/module.php', "<?php\nreturn " . var_export($contratosConfig, true) . ";\n");

$app->modules->reload();
$cliRepo = $app->modules->repository('clientes_test');
$cntRepo = $app->modules->repository('contratos_test');

// Insere registro no Pai
$cli1 = $cliRepo->insert([
    'id' => $cliRepo->nextId(),
    'nome' => 'Manguto Corp',
    'created_at' => date('c'),
    'updated_at' => date('c'),
]);
if ($cli1['id'] !== 'cli_001') throw new RuntimeException('ID do cliente pai incorreto.');

// Insere registro no Filho vinculado ao Pai
$cnt1 = $cntRepo->insert([
    'id' => $cntRepo->nextId(),
    'numero' => 'CTR-2026-001',
    'cliente_id' => $cli1['id'],
    'created_at' => date('c'),
    'updated_at' => date('c'),
]);
if ($cnt1['id'] !== 'cnt_001' || $cnt1['cliente_id'] !== 'cli_001') {
    throw new RuntimeException('Vínculo de chave estrangeira no filho incorreto.');
}

// Testa resolução reversa de vínculos (visão 360° do pai)
$crudCtrl = new \App\Controllers\GenericCrudController($app);
$refMethod = new ReflectionMethod($crudCtrl, 'resolveReverseRelations');
$refMethod->setAccessible(true);
$reverseData = $refMethod->invoke($crudCtrl, $clientesConfig, 'cli_001');

if (empty($reverseData) || count($reverseData[0]['items']) !== 1) {
    throw new RuntimeException('Falha na resolução reversa de vínculos de entidades.');
}
if ($reverseData[0]['items'][0]['numero'] !== 'CTR-2026-001') {
    throw new RuntimeException('Dados do registro filho na sub-listagem reversa incorretos.');
}

// Testa cálculo de reverseReferenceCounts (UX proativa de exclusão)
$refCountMethod = new ReflectionMethod($crudCtrl, 'resolveReverseReferenceCounts');
$refCountMethod->setAccessible(true);
$refCounts = $refCountMethod->invoke($crudCtrl, $clientesConfig, [$cli1]);
if (($refCounts['cli_001']['restrict']['Contratos Teste'] ?? 0) !== 1 || empty($refCounts['cli_001']['has_restrict'])) {
    throw new RuntimeException('Cálculo de contagem de vínculos reversos (restrict) incorreto.');
}

// Testa filtro por relação na listagem do filho (Demanda 2)
$cntRows = $cntRepo->all();
$filteredByCli1 = array_values(array_filter($cntRows, fn($r) => ($r['cliente_id'] ?? '') === 'cli_001'));
$filteredByCli999 = array_values(array_filter($cntRows, fn($r) => ($r['cliente_id'] ?? '') === 'cli_999'));
if (count($filteredByCli1) !== 1 || $filteredByCli1[0]['id'] !== 'cnt_001') {
    throw new RuntimeException('Falha no filtro por entidade relacionada.');
}
if (count($filteredByCli999) !== 0) {
    throw new RuntimeException('Filtro por relação inexistente deveria retornar lista vazia.');
}

// Testa integridade referencial: on_delete = restrict (bloqueio de exclusão)
$checkMethod = new ReflectionMethod($crudCtrl, 'checkDeletionRestrictions');
$checkMethod->setAccessible(true);
$restrictErrors = [];
$checkMethod->invokeArgs($crudCtrl, ['clientes_test', 'cli_001', &$restrictErrors]);
if (empty($restrictErrors)) {
    throw new RuntimeException('Falha na detecção de integridade referencial: exclusão do pai deveria ser bloqueada por restrict.');
}

// Testa política on_delete = set_null (Demanda 3)
$contratosConfig['fields']['cliente_id']['on_delete'] = 'set_null';
$contratosConfig['fields']['cliente_id']['required'] = false;
file_put_contents($contratosDir . '/module.php', "<?php\nreturn " . var_export($contratosConfig, true) . ";\n");
$app->modules->reload();

$setNullErrors = [];
$checkMethod->invokeArgs($crudCtrl, ['clientes_test', 'cli_001', &$setNullErrors]);
if (!empty($setNullErrors)) {
    throw new RuntimeException('checkDeletionRestrictions não deveria bloquear exclusão com política set_null.');
}

$execMethod = new ReflectionMethod($crudCtrl, 'executeRelationPolicies');
$execMethod->setAccessible(true);
$unlinkedCount = 0;
$cascadeCount = 0;
$execMethod->invokeArgs($crudCtrl, ['clientes_test', 'cli_001', &$unlinkedCount, &$cascadeCount]);
if ($unlinkedCount !== 1 || $cascadeCount !== 0) {
    throw new RuntimeException('Falha na execução da política set_null (contagem de desvinculados incorreta).');
}
$cntAfterSetNull = $cntRepo->find('cnt_001');
if (!$cntAfterSetNull || $cntAfterSetNull['cliente_id'] !== '') {
    throw new RuntimeException('Falha ao aplicar set_null: campo cliente_id deveria estar vazio no filho.');
}

// Testa política on_delete = cascade (Demanda 3)
// Vincula novamente o contrato ao cliente
$cntRepo->update('cnt_001', ['cliente_id' => 'cli_001']);
$contratosConfig['fields']['cliente_id']['on_delete'] = 'cascade';
file_put_contents($contratosDir . '/module.php', "<?php\nreturn " . var_export($contratosConfig, true) . ";\n");
$app->modules->reload();

$cascadeErrors = [];
$checkMethod->invokeArgs($crudCtrl, ['clientes_test', 'cli_001', &$cascadeErrors]);
if (!empty($cascadeErrors)) {
    throw new RuntimeException('checkDeletionRestrictions não deveria bloquear exclusão com política cascade pura.');
}

$unlinkedCount = 0;
$cascadeCount = 0;
$execMethod->invokeArgs($crudCtrl, ['clientes_test', 'cli_001', &$unlinkedCount, &$cascadeCount]);
if ($unlinkedCount !== 0 || $cascadeCount !== 1) {
    throw new RuntimeException('Falha na execução da política cascade (contagem de exclusões em cascata incorreta).');
}
$cntAfterCascade = $cntRepo->find('cnt_001');
if ($cntAfterCascade !== null) {
    throw new RuntimeException('Falha ao aplicar cascade: registro filho deveria ter sido removido.');
}

// Agora exclui o pai livremente
$cliRepo->delete('cli_001');
if ($cliRepo->find('cli_001') !== null) {
    throw new RuntimeException('Falha ao remover pai após exclusão em cascata do filho.');
}

// Limpeza dos módulos de teste de relacionamento 1:N
unlink($clientesDir . '/module.php');
rmdir($clientesDir);
unlink($contratosDir . '/module.php');
rmdir($contratosDir);

// =============================================================================
// TESTE DE RELACIONAMENTOS N:N COM TABELAS PIVÔ DECLARATIVAS (Item 19)
// =============================================================================
$projetosTestDir = $root . '/modules/projetos_test';
$equipamentosTestDir = $root . '/modules/equipamentos_test';
mkdir($projetosTestDir, 0775, true);
mkdir($equipamentosTestDir, 0775, true);

$equipamentosConfig = [
    'name' => 'Equipamentos Teste',
    'entity' => 'Equipamento Teste',
    'slug' => 'equipamentos_test',
    'prefix' => 'eqt',
    'storage' => 'equipamentos_test.csv',
    'permission_prefix' => 'equipamentos_test',
    'fields' => [
        'nome' => [
            'label' => 'Nome do Equipamento',
            'type' => 'string',
            'required' => true,
            'list' => true,
        ],
    ],
];
file_put_contents($equipamentosTestDir . '/module.php', "<?php\nreturn " . var_export($equipamentosConfig, true) . ";\n");

$projetosConfig = [
    'name' => 'Projetos Teste',
    'entity' => 'Projeto Teste',
    'slug' => 'projetos_test',
    'prefix' => 'prt',
    'storage' => 'projetos_test.csv',
    'permission_prefix' => 'projetos_test',
    'fields' => [
        'nome' => [
            'label' => 'Nome do Projeto',
            'type' => 'string',
            'required' => true,
            'list' => true,
        ],
        'equipamentos' => [
            'label' => 'Equipamentos Alocados',
            'type' => 'many_to_many',
            'target' => 'equipamentos_test',
            'display' => 'nome',
            'pivot_file' => 'projeto_equipamentos_test.csv',
            'parent_key' => 'projeto_id',
            'target_key' => 'equipamento_id',
            'required' => false,
            'list' => true,
        ],
    ],
];
file_put_contents($projetosTestDir . '/module.php', "<?php\nreturn " . var_export($projetosConfig, true) . ";\n");

$app->modules->reload();

// Cria repositórios e registros de teste
$eqpRepo = $app->modules->repository('equipamentos_test');
$eqp1 = $eqpRepo->insert(['id' => $eqpRepo->nextId(), 'nome' => 'Servidor Cloud', 'created_at' => date('c'), 'updated_at' => date('c')]);
$eqp2 = $eqpRepo->insert(['id' => $eqpRepo->nextId(), 'nome' => 'Switch Core 10G', 'created_at' => date('c'), 'updated_at' => date('c')]);
$eqp3 = $eqpRepo->insert(['id' => $eqpRepo->nextId(), 'nome' => 'Firewall UTM', 'created_at' => date('c'), 'updated_at' => date('c')]);

$prtRepo = $app->modules->repository('projetos_test');
$prt1 = $prtRepo->insert(['id' => $prtRepo->nextId(), 'nome' => 'Infraestrutura Central', 'created_at' => date('c'), 'updated_at' => date('c')]);
$prt2 = $prtRepo->insert(['id' => $prtRepo->nextId(), 'nome' => 'Segurança de Borda', 'created_at' => date('c'), 'updated_at' => date('c')]);

// 1. Testa sincronização N:N (syncManyToMany)
$syncMethod = new ReflectionMethod($crudCtrl, 'syncManyToMany');
$syncMethod->setAccessible(true);

// Simula request selecionando eqp1 e eqp2 para prt1
$_POST_BACKUP = $_POST;
$_POST = ['equipamentos' => [$eqp1['id'], $eqp2['id']]];
$reqSync1 = new Request();
$syncMethod->invokeArgs($crudCtrl, [$projetosConfig, $prt1['id'], $reqSync1]);

// Simula request selecionando eqp2 e eqp3 para prt2
$_POST = ['equipamentos' => [$eqp2['id'], $eqp3['id']]];
$reqSync2 = new Request();
$syncMethod->invokeArgs($crudCtrl, [$projetosConfig, $prt2['id'], $reqSync2]);
$_POST = $_POST_BACKUP;

// Verifica conteúdo persistido no arquivo pivô CSV
if (!$app->storage->exists('projeto_equipamentos_test.csv')) {
    throw new RuntimeException('Arquivo pivô projeto_equipamentos_test.csv não foi criado.');
}
$pivotRows = $app->storage->read('projeto_equipamentos_test.csv');
if (count($pivotRows) !== 4) {
    throw new RuntimeException('Contagem incorreta de linhas na tabela pivô (esperado 4, obtido ' . count($pivotRows) . ').');
}

// 2. Testa resolução N:N direta (resolveManyToMany)
$resolveM2mMethod = new ReflectionMethod($crudCtrl, 'resolveManyToMany');
$resolveM2mMethod->setAccessible(true);
$resolvedPrt1 = $resolveM2mMethod->invokeArgs($crudCtrl, [$projetosConfig, $prt1['id']]);

if (!isset($resolvedPrt1['equipamentos'])) {
    throw new RuntimeException('Campo equipamentos não resolvido por resolveManyToMany.');
}
if ($resolvedPrt1['equipamentos']['selected'] !== [$eqp1['id'], $eqp2['id']]) {
    throw new RuntimeException('IDs selecionados incorretos em resolveManyToMany para prt1.');
}

// 3. Testa resolução reversa N:N (resolveReverseManyToMany)
$resolveRevM2mMethod = new ReflectionMethod($crudCtrl, 'resolveReverseManyToMany');
$resolveRevM2mMethod->setAccessible(true);
$resolvedRevEqp2 = $resolveRevM2mMethod->invokeArgs($crudCtrl, [$equipamentosConfig, $eqp2['id']]);

if (empty($resolvedRevEqp2)) {
    throw new RuntimeException('resolveReverseManyToMany não retornou referências reversas para eqp2.');
}
$revItems = $resolvedRevEqp2[0]['items'] ?? [];
$revIds = array_column($revItems, 'id');
if (count($revIds) !== 2 || !in_array($prt1['id'], $revIds, true) || !in_array($prt2['id'], $revIds, true)) {
    throw new RuntimeException('resolveReverseManyToMany não identificou ambos os projetos vinculados ao eqp2.');
}

// 4. Testa atualização N:N (desvinculando eqp1 e vinculando eqp3 ao prt1)
$_POST = ['equipamentos' => [$eqp2['id'], $eqp3['id']]];
$reqUpdate1 = new Request();
$syncMethod->invokeArgs($crudCtrl, [$projetosConfig, $prt1['id'], $reqUpdate1]);
$_POST = $_POST_BACKUP;

$resolvedPrt1Updated = $resolveM2mMethod->invokeArgs($crudCtrl, [$projetosConfig, $prt1['id']]);
if ($resolvedPrt1Updated['equipamentos']['selected'] !== [$eqp2['id'], $eqp3['id']]) {
    throw new RuntimeException('Falha na atualização de vínculos N:N via syncManyToMany.');
}

// 5. Testa limpeza de registros pivô ao excluir entidade (cleanupManyToManyOnDelete)
$cleanupMethod = new ReflectionMethod($crudCtrl, 'cleanupManyToManyOnDelete');
$cleanupMethod->setAccessible(true);

// Exclui prt1 e executa cleanupManyToManyOnDelete
$prtRepo->delete($prt1['id']);
$cleanupMethod->invokeArgs($crudCtrl, ['projetos_test', $prt1['id']]);

$pivotRowsAfterPrtDelete = $app->storage->read('projeto_equipamentos_test.csv');
// Devem sobrar apenas os 2 vínculos de prt2 (eqp2 e eqp3)
if (count($pivotRowsAfterPrtDelete) !== 2) {
    throw new RuntimeException('Falha no cleanupManyToManyOnDelete ao excluir pai: esperado 2 linhas na tabela pivô, obtido ' . count($pivotRowsAfterPrtDelete));
}
foreach ($pivotRowsAfterPrtDelete as $pRow) {
    if (($pRow['projeto_id'] ?? '') === $prt1['id']) {
        throw new RuntimeException('Linha órfã do projeto excluído encontrada na tabela pivô.');
    }
}

// Exclui eqp3 (alvo referenciado) e executa cleanupManyToManyOnDelete
$eqpRepo->delete($eqp3['id']);
$cleanupMethod->invokeArgs($crudCtrl, ['equipamentos_test', $eqp3['id']]);

$pivotRowsAfterEqpDelete = $app->storage->read('projeto_equipamentos_test.csv');
// Deve sobrar apenas 1 vínculo: prt2 -> eqp2
if (count($pivotRowsAfterEqpDelete) !== 1) {
    throw new RuntimeException('Falha no cleanupManyToManyOnDelete ao excluir alvo referenciado: esperado 1 linha, obtido ' . count($pivotRowsAfterEqpDelete));
}
if (($pivotRowsAfterEqpDelete[0]['equipamento_id'] ?? '') !== $eqp2['id']) {
    throw new RuntimeException('Vínculo incorreto remanescente na tabela pivô após exclusão do equipamento.');
}

// Limpeza dos módulos temporários de teste N:N
unlink($projetosTestDir . '/module.php');
rmdir($projetosTestDir);
unlink($equipamentosTestDir . '/module.php');
rmdir($equipamentosTestDir);

// 6. Teste da renderização do componente de Busca Assistida / Autocomplete (1:N)
$testModule = [
    'name' => 'Tarefas de Teste',
    'entity' => 'Tarefa',
    'slug' => 'tarefas_test',
    'fields' => [
        'titulo' => ['label' => 'Título', 'type' => 'string', 'required' => true],
        'projeto_id' => ['label' => 'Projeto', 'type' => 'relation', 'target' => 'projetos', 'required' => true],
    ],
];
$testRelations = [
    'projeto_id' => [
        'target' => 'projetos',
        'target_entity' => 'Projeto',
        'items' => [
            ['id' => 'pro_001', 'label' => 'Portal de Telemetria'],
            ['id' => 'pro_002', 'label' => 'App Mobile'],
        ],
    ],
];
$testManyToMany = [];
$isEdit = false;
$csrf = 'test_token';
$item = ['projeto_id' => 'pro_001'];
$module = $testModule;
$relations = $testRelations;
$manyToMany = $testManyToMany;

ob_start();
include dirname(__DIR__) . '/views/crud/form.php';
$formHtml = ob_get_clean();

if (!str_contains($formHtml, 'autocomplete-container') || !str_contains($formHtml, 'autocomplete_projeto_id')) {
    throw new RuntimeException('Componente de Busca Assistida (autocomplete) 1:N não foi renderizado no form.php.');
}
if (!str_contains($formHtml, 'data-id="pro_001"') || !str_contains($formHtml, 'Portal de Telemetria')) {
    throw new RuntimeException('Opções do autocomplete 1:N não foram renderizadas corretamente no form.php.');
}
// 7. Teste de detecção de IP do cliente (Request::ip)
$req = new Request();
if ($req->ip() !== '127.0.0.1') {
    throw new RuntimeException('IP padrão esperado 127.0.0.1, recebido: ' . $req->ip());
}
$_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.195, 70.41.3.18';
if ($req->ip() !== '203.0.113.195') {
    throw new RuntimeException('Falha no parse de X-Forwarded-For no Request::ip(), recebido: ' . $req->ip());
}
unset($_SERVER['HTTP_X_FORWARDED_FOR']);

// 8. Teste do RateLimiter (Proteção de Força Bruta)
$rateLimiter = $app->rateLimiter;
$testKey = 'test_login_' . bin2hex(random_bytes(4));

if ($rateLimiter->tooManyAttempts($testKey, 5)) {
    throw new RuntimeException('RateLimiter não deveria bloquear chave nova.');
}
if ($rateLimiter->retriesLeft($testKey, 5) !== 5) {
    throw new RuntimeException('RateLimiter deveria indicar 5 tentativas restantes.');
}

// Executa 4 tentativas
for ($i = 1; $i <= 4; $i++) {
    $attempts = $rateLimiter->hit($testKey, 300, 5);
    if ($attempts !== $i) {
        throw new RuntimeException("RateLimiter hit retornou {$attempts}, esperado {$i}.");
    }
}
if ($rateLimiter->tooManyAttempts($testKey, 5)) {
    throw new RuntimeException('RateLimiter não deveria bloquear na 4ª tentativa.');
}
if ($rateLimiter->retriesLeft($testKey, 5) !== 1) {
    throw new RuntimeException('RateLimiter deveria indicar 1 tentativa restante após 4 hits.');
}

// 5ª tentativa: bloqueio
$attempts = $rateLimiter->hit($testKey, 300, 5);
if ($attempts !== 5) {
    throw new RuntimeException("RateLimiter hit 5 falhou: esperado 5, obtido {$attempts}.");
}
if (!$rateLimiter->tooManyAttempts($testKey, 5)) {
    throw new RuntimeException('RateLimiter deveria bloquear após 5 tentativas.');
}
$availableIn = $rateLimiter->availableIn($testKey);
if ($availableIn <= 0 || $availableIn > 300) {
    throw new RuntimeException("Tempo restante inválido no RateLimiter: {$availableIn}s.");
}

// Limpeza após sucesso (clear)
$rateLimiter->clear($testKey);
if ($rateLimiter->tooManyAttempts($testKey, 5)) {
    throw new RuntimeException('RateLimiter clear não limpou o bloqueio.');
}
if ($rateLimiter->retriesLeft($testKey, 5) !== 5) {
    throw new RuntimeException('RateLimiter retriesLeft deveria ser 5 após clear.');
}

// 9. Teste de conformidade de arquivos de segurança e licença
$projectRoot = dirname(__DIR__);
if (!is_file($projectRoot . '/LICENSE')) {
    throw new RuntimeException('Arquivo LICENSE não encontrado na raiz.');
}
$licenseContent = (string) file_get_contents($projectRoot . '/LICENSE');
if (!str_contains($licenseContent, 'MIT License')) {
    throw new RuntimeException('Arquivo LICENSE não contém a declaração do MIT License.');
}

if (!is_file($projectRoot . '/storage/.htaccess')) {
    throw new RuntimeException('Arquivo storage/.htaccess de proteção não encontrado.');
}
$storageHtaccess = (string) file_get_contents($projectRoot . '/storage/.htaccess');
if (!str_contains($storageHtaccess, 'Require all denied')) {
    throw new RuntimeException('storage/.htaccess não contém regra "Require all denied".');
}

$rootHtaccess = (string) file_get_contents($projectRoot . '/.htaccess');
if (!str_contains($rootHtaccess, 'storage') || !str_contains($rootHtaccess, 'composer')) {
    throw new RuntimeException('Root .htaccess não possui bloqueios de segurança essenciais.');
}

echo "Verificação OK: setup, CSV, hash de senha, RBAC, Backups, Auditoria, Perfil, Motor de Módulos, Entity Builder (criação, edição e reordenação de campos), Relacionamentos 1:N (restrict, set_null, cascade), Relacionamentos N:N com Tabelas Pivô Declarativas, Busca Assistida / Autocomplete (Item 20), Rate Limiting (Força Bruta), Proteção .htaccess e Licença MIT.\n";



