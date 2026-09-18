<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuditService;
use App\Services\BackupService;
use App\Services\PasswordPolicyService;
use App\Services\SeedService;

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

    public function logs(Request $request): void
    {
        $audit = new AuditService($this->app->storage);
        $filterAction = trim((string) $request->input('action', ''));
        $logs = $audit->all($filterAction !== '' ? $filterAction : null);

        $users = [];
        foreach ($this->app->users->all() as $u) {
            $users[$u['id']] = $u;
        }

        $this->view('dev/logs', [
            'logs' => $logs,
            'actions' => $audit->actions(),
            'filterAction' => $filterAction,
            'users' => $users,
        ]);
    }

    public function modules(Request $request): void
    {
        $modules = $this->app->modules->all();
        $stats = [];
        foreach ($modules as $slug => $mod) {
            $repo = $this->app->modules->repository($slug);
            $stats[$slug] = [
                'count' => $repo ? $repo->count() : 0,
                'storage_exists' => $this->app->storage->exists($mod['storage']),
            ];
        }

        $seedService = new SeedService($this->app);
        $isDemoInstalled = $seedService->isDemoInstalled('ecommerce');

        $this->view('dev/modules', [
            'modules' => $modules,
            'stats' => $stats,
            'isDemoInstalled' => $isDemoInstalled,
        ]);
    }

    public function seedDatabase(Request $request): void
    {
        $userId = $this->user()['id'] ?? 'usr_001';
        $seedService = new SeedService($this->app);
        $stats = $seedService->run($userId);
        Session::flash('message', "Módulos de modelo e dados de demonstração carregados com sucesso! ({$stats['modules']} módulos ativados: {$stats['clientes']} clientes, {$stats['produtos']} produtos, {$stats['tags']} etiquetas, {$stats['pedidos']} pedidos e {$stats['avaliacoes']} avaliações).");
        Response::redirect('/dev/modules');
    }

    public function clearDemo(Request $request): void
    {
        $userId = $this->user()['id'] ?? 'usr_001';
        $seedService = new SeedService($this->app);
        $stats = $seedService->clearDemo($userId);
        $modsCount = count($stats['modules_removed']);
        $csvsCount = count($stats['csvs_removed']);
        Session::flash('message', "Demonstração removida com sucesso! ({$modsCount} módulos e {$csvsCount} arquivos CSV removidos). A base voltou ao estado limpo (Clean Slate).");
        Response::redirect('/dev/modules');
    }

    public function passwordPolicy(Request $request): void
    {
        $service = new PasswordPolicyService($this->app);
        $this->view('dev/password-policy', [
            'policy' => $service->getPolicy(),
            'rules' => $service->getRulesSummary(),
        ]);
    }

    public function updatePasswordPolicy(Request $request): void
    {
        $service = new PasswordPolicyService($this->app);
        $userId = $this->user()['id'] ?? null;
        $service->updatePolicy([
            'enabled' => $request->input('enabled'),
            'min_length' => $request->input('min_length'),
            'require_uppercase' => $request->input('require_uppercase'),
            'require_lowercase' => $request->input('require_lowercase'),
            'require_numbers' => $request->input('require_numbers'),
            'require_symbols' => $request->input('require_symbols'),
        ], $userId);

        Session::flash('message', 'Configurações de política de senhas atualizadas com sucesso.');
        Response::redirect('/dev/password-policy');
    }

    public function entityBuilder(): void
    {
        $this->view('dev/entity-builder', [
            'module' => null,
            'isEdit' => false,
            'allModules' => $this->app->modules->all(),
        ]);
    }

    public function editEntity(Request $request, array $params): void
    {
        $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($params['slug'] ?? '')));
        $modules = $this->app->modules->all();
        $module = $modules[$slug] ?? null;

        if (!$module) {
            Session::flash('error', "Módulo '{$slug}' não encontrado.");
            Response::redirect('/dev/modules');
        }

        $this->view('dev/entity-builder', [
            'module' => $module,
            'isEdit' => true,
            'allModules' => $modules,
        ]);
    }

    public function updateEntity(Request $request, array $params): void
    {
        $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($params['slug'] ?? '')));
        $modulesDir = $this->app->config->get('root') . '/modules';
        $targetModuleDir = $modulesDir . '/' . $slug;

        if (!is_dir($targetModuleDir)) {
            Session::flash('error', "Módulo '{$slug}' não encontrado.");
            Response::redirect('/dev/modules');
        }

        $name = trim((string) $request->input('name'));
        $entity = trim((string) $request->input('entity'));
        $icon = trim((string) $request->input('icon')) ?: '📁';
        $description = trim((string) $request->input('description'));

        if ($name === '' || $entity === '') {
            Session::flash('error', 'Nome do módulo e nome da entidade são obrigatórios.');
            Response::redirect('/dev/modules/' . $slug . '/edit');
        }

        $moduleFile = $targetModuleDir . '/module.php';
        $currentConfig = file_exists($moduleFile) ? require $moduleFile : [];

        $prefix = $currentConfig['prefix'] ?? substr($slug, 0, 3);
        $storage = $currentConfig['storage'] ?? ($slug . '.csv');

        $rawFields = (array) $request->input('fields', []);
        $fields = [];
        $allowedTypes = ['string', 'text', 'number', 'date', 'select', 'boolean', 'relation', 'many_to_many'];

        if (!empty($rawFields)) {
            foreach ($rawFields as $fieldData) {
                if (!is_array($fieldData)) continue;
                $fName = preg_replace('/[^a-z0-9_]/', '', strtolower(trim((string) ($fieldData['name'] ?? ''))));
                if ($fName === '' || $fName === 'id' || $fName === 'created_at' || $fName === 'updated_at') continue;

                $fLabel = trim((string) ($fieldData['label'] ?? '')) ?: ucfirst($fName);
                $fType = in_array($fieldData['type'] ?? '', $allowedTypes, true) ? $fieldData['type'] : 'string';
                $fRequired = !empty($fieldData['required']);
                $fUnique = !empty($fieldData['unique']);
                $fList = !empty($fieldData['list']);
                $fHelp = trim((string) ($fieldData['help'] ?? ''));

                $fieldConfig = [
                    'label' => $fLabel,
                    'type' => $fType,
                    'required' => $fRequired,
                    'unique' => $fUnique,
                    'list' => $fList,
                ];

                if ($fType === 'select') {
                    $rawOptions = trim((string) ($fieldData['options'] ?? ''));
                    if ($rawOptions !== '') {
                        $options = array_values(array_filter(array_map('trim', explode(',', $rawOptions))));
                        $fieldConfig['options'] = $options ?: ['Opção 1', 'Opção 2'];
                    } else {
                        $fieldConfig['options'] = $currentConfig['fields'][$fName]['options'] ?? ['Opção 1', 'Opção 2'];
                    }
                }

                if ($fType === 'relation') {
                    $targetModule = trim((string) ($fieldData['relation_target'] ?? ''));
                    if ($targetModule !== '') {
                        $fieldConfig['target'] = $targetModule;
                    } elseif (isset($currentConfig['fields'][$fName]['target'])) {
                        $fieldConfig['target'] = $currentConfig['fields'][$fName]['target'];
                    }
                    $displayField = trim((string) ($fieldData['relation_display'] ?? ''));
                    if ($displayField !== '') {
                        $fieldConfig['display'] = $displayField;
                    } elseif (isset($currentConfig['fields'][$fName]['display'])) {
                        $fieldConfig['display'] = $currentConfig['fields'][$fName]['display'];
                    }
                    $onDelete = trim((string) ($fieldData['relation_on_delete'] ?? ''));
                    if (in_array($onDelete, ['restrict', 'set_null', 'cascade'], true)) {
                        $fieldConfig['on_delete'] = $onDelete;
                    } elseif (isset($currentConfig['fields'][$fName]['on_delete'])) {
                        $fieldConfig['on_delete'] = $currentConfig['fields'][$fName]['on_delete'];
                    } else {
                        $fieldConfig['on_delete'] = 'restrict';
                    }
                }

                if ($fType === 'many_to_many') {
                    $targetModule = trim((string) ($fieldData['relation_target'] ?? ''));
                    if ($targetModule !== '') {
                        $fieldConfig['target'] = $targetModule;
                    } elseif (isset($currentConfig['fields'][$fName]['target'])) {
                        $fieldConfig['target'] = $currentConfig['fields'][$fName]['target'];
                    }
                    $displayField = trim((string) ($fieldData['relation_display'] ?? ''));
                    if ($displayField !== '') {
                        $fieldConfig['display'] = $displayField;
                    } elseif (isset($currentConfig['fields'][$fName]['display'])) {
                        $fieldConfig['display'] = $currentConfig['fields'][$fName]['display'];
                    }
                    $pivotFile = trim((string) ($fieldData['pivot_file'] ?? ''));
                    if ($pivotFile !== '') {
                        $fieldConfig['pivot_file'] = $pivotFile;
                    } elseif (isset($currentConfig['fields'][$fName]['pivot_file'])) {
                        $fieldConfig['pivot_file'] = $currentConfig['fields'][$fName]['pivot_file'];
                    } else {
                        $fieldConfig['pivot_file'] = $slug . '_' . ($fieldConfig['target'] ?? 'pivot') . '.csv';
                    }
                    $parentKey = trim((string) ($fieldData['parent_key'] ?? ''));
                    $fieldConfig['parent_key'] = $parentKey !== '' ? $parentKey : (rtrim($slug, 's') . '_id');
                    $targetKey = trim((string) ($fieldData['target_key'] ?? ''));
                    $fieldConfig['target_key'] = $targetKey !== '' ? $targetKey : (rtrim($fieldConfig['target'] ?? 'target', 's') . '_id');
                }

                if ($fType === 'boolean') {
                    $fieldConfig['default'] = true;
                }

                if ($fHelp !== '') {
                    $fieldConfig['help'] = $fHelp;
                }

                $fields[$fName] = $fieldConfig;
            }
        }

        if (empty($fields)) {
            $fields = $currentConfig['fields'] ?? [];
        }

        (new BackupService($this->app))->create('pre_entity_edit_' . $slug, $this->user()['id'] ?? null);

        $moduleConfig = [
            'name' => $name,
            'entity' => $entity,
            'slug' => $slug,
            'icon' => $icon,
            'description' => $description,
            'prefix' => $prefix,
            'storage' => $storage,
            'permission_prefix' => $slug,
            'fields' => $fields,
        ];

        $code = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($moduleConfig, true) . ";\n";
        file_put_contents($targetModuleDir . '/module.php', $code);

        // Preservação de dados e expansão de colunas no arquivo CSV (campos many_to_many residem na tabela pivot)
        $scalarFieldNames = array_keys(array_filter($fields, fn($f) => ($f['type'] ?? '') !== 'many_to_many'));
        $fieldHeaders = array_merge(['id', 'created_at', 'updated_at'], $scalarFieldNames);
        $csvFile = $slug . '.csv';
        if ($this->app->storage->exists($csvFile)) {
            $existingRows = $this->app->storage->read($csvFile);
            $this->app->storage->write($csvFile, $fieldHeaders, $existingRows);
        } else {
            $this->app->storage->write($csvFile, $fieldHeaders, []);
        }

        // Garante existência das tabelas pivot para campos many_to_many
        foreach ($fields as $fcfg) {
            if (($fcfg['type'] ?? '') === 'many_to_many' && !empty($fcfg['pivot_file'])) {
                $pFile = $fcfg['pivot_file'];
                if (!$this->app->storage->exists($pFile)) {
                    $pHeaders = [
                        $fcfg['parent_key'] ?? (rtrim($slug, 's') . '_id'),
                        $fcfg['target_key'] ?? (rtrim($fcfg['target'] ?? 'target', 's') . '_id'),
                    ];
                    $this->app->storage->write($pFile, $pHeaders, []);
                }
            }
        }

        $this->app->modules->ensurePermissions();

        (new AuditService($this->app->storage))->log(
            'module_updated',
            $this->user()['id'] ?? null,
            "slug={$slug}; entity={$entity}"
        );

        Session::flash('message', "Módulo '{$name}' atualizado com sucesso!");
        Response::redirect('/dev/modules');
    }

    public function storeEntity(Request $request): void
    {
        $name = trim((string) $request->input('name'));
        $entity = trim((string) $request->input('entity'));
        $rawSlug = trim((string) $request->input('slug'));
        $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower($rawSlug));
        $icon = trim((string) $request->input('icon')) ?: '📁';
        $description = trim((string) $request->input('description'));
        $prefix = preg_replace('/[^a-z0-9]/', '', strtolower((string) $request->input('prefix'))) ?: substr($slug, 0, 3);

        if ($name === '' || $entity === '' || $slug === '') {
            Session::flash('error', 'Nome do módulo, nome da entidade e slug são obrigatórios.');
            Response::redirect('/dev/entity-builder');
        }

        if (strlen($slug) < 3 || strlen($slug) > 30) {
            Session::flash('error', 'O slug deve conter entre 3 e 30 caracteres alfanuméricos.');
            Response::redirect('/dev/entity-builder');
        }

        $reserved = ['admin', 'app', 'dev', 'login', 'setup', 'profile', 'logout', 'assets', 'api', 'install', 'system'];
        if (in_array($slug, $reserved, true)) {
            Session::flash('error', "O slug '{$slug}' é uma palavra reservada da plataforma e não pode ser utilizado.");
            Response::redirect('/dev/entity-builder');
        }

        $modulesDir = $this->app->config->get('root') . '/modules';
        $targetModuleDir = $modulesDir . '/' . $slug;
        if (is_dir($targetModuleDir)) {
            Session::flash('error', "Já existe um módulo registrado com o slug '{$slug}'. Operação cancelada para evitar sobrescrita.");
            Response::redirect('/dev/entity-builder');
        }

        // Processa os campos enviados
        $rawFields = (array) $request->input('fields', []);
        $fields = [];
        $allowedTypes = ['string', 'text', 'number', 'date', 'select', 'boolean', 'relation', 'many_to_many'];

        foreach ($rawFields as $fieldData) {
            if (!is_array($fieldData)) continue;
            $fName = preg_replace('/[^a-z0-9_]/', '', strtolower(trim((string) ($fieldData['name'] ?? ''))));
            if ($fName === '' || $fName === 'id' || $fName === 'created_at' || $fName === 'updated_at') continue;

            $fLabel = trim((string) ($fieldData['label'] ?? '')) ?: ucfirst($fName);
            $fType = in_array($fieldData['type'] ?? '', $allowedTypes, true) ? $fieldData['type'] : 'string';
            $fRequired = !empty($fieldData['required']);
            $fUnique = !empty($fieldData['unique']);
            $fList = !empty($fieldData['list']);
            $fHelp = trim((string) ($fieldData['help'] ?? ''));

            $fieldConfig = [
                'label' => $fLabel,
                'type' => $fType,
                'required' => $fRequired,
                'unique' => $fUnique,
                'list' => $fList,
            ];

            if ($fType === 'select') {
                $rawOptions = trim((string) ($fieldData['options'] ?? ''));
                $options = array_values(array_filter(array_map('trim', explode(',', $rawOptions))));
                $fieldConfig['options'] = $options ?: ['Opção 1', 'Opção 2'];
            }

            if ($fType === 'relation') {
                $targetModule = trim((string) ($fieldData['relation_target'] ?? ''));
                if ($targetModule !== '') {
                    $fieldConfig['target'] = $targetModule;
                }
                $displayField = trim((string) ($fieldData['relation_display'] ?? ''));
                if ($displayField !== '') {
                    $fieldConfig['display'] = $displayField;
                }
                $onDelete = trim((string) ($fieldData['relation_on_delete'] ?? ''));
                $fieldConfig['on_delete'] = in_array($onDelete, ['restrict', 'set_null', 'cascade'], true) ? $onDelete : 'restrict';
            }

            if ($fType === 'many_to_many') {
                $targetModule = trim((string) ($fieldData['relation_target'] ?? ''));
                if ($targetModule !== '') {
                    $fieldConfig['target'] = $targetModule;
                }
                $displayField = trim((string) ($fieldData['relation_display'] ?? ''));
                if ($displayField !== '') {
                    $fieldConfig['display'] = $displayField;
                }
                $pivotFile = trim((string) ($fieldData['pivot_file'] ?? ''));
                $fieldConfig['pivot_file'] = $pivotFile !== '' ? $pivotFile : ($slug . '_' . ($fieldConfig['target'] ?? 'pivot') . '.csv');
                $parentKey = trim((string) ($fieldData['parent_key'] ?? ''));
                $fieldConfig['parent_key'] = $parentKey !== '' ? $parentKey : (rtrim($slug, 's') . '_id');
                $targetKey = trim((string) ($fieldData['target_key'] ?? ''));
                $fieldConfig['target_key'] = $targetKey !== '' ? $targetKey : (rtrim($fieldConfig['target'] ?? 'target', 's') . '_id');
            }

            if ($fType === 'boolean') {
                $fieldConfig['default'] = true;
            }

            if ($fHelp !== '') {
                $fieldConfig['help'] = $fHelp;
            }

            $fields[$fName] = $fieldConfig;
        }

        if (empty($fields)) {
            Session::flash('error', 'Defina pelo menos um campo válido para a entidade.');
            Response::redirect('/dev/entity-builder');
        }

        // Salvaguarda pré-criação conforme Seção 29 do README
        (new BackupService($this->app))->create('pre_entity_create_' . $slug, $this->user()['id'] ?? null);

        // Criação física do diretório do módulo
        if (!mkdir($targetModuleDir, 0775, true) && !is_dir($targetModuleDir)) {
            Session::flash('error', 'Não foi possível criar o diretório do módulo.');
            Response::redirect('/dev/entity-builder');
        }

        // Geração do arquivo module.php
        $moduleConfig = [
            'name' => $name,
            'entity' => $entity,
            'slug' => $slug,
            'icon' => $icon,
            'description' => $description,
            'prefix' => $prefix,
            'storage' => $slug . '.csv',
            'permission_prefix' => $slug,
            'fields' => $fields,
        ];

        $code = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($moduleConfig, true) . ";\n";
        file_put_contents($targetModuleDir . '/module.php', $code);

        // Inicializa arquivo CSV no storage/data se não existir (campos many_to_many residem na pivot)
        $scalarFieldNames = array_keys(array_filter($fields, fn($f) => ($f['type'] ?? '') !== 'many_to_many'));
        $fieldHeaders = array_merge(['id', 'created_at', 'updated_at'], $scalarFieldNames);
        if (!$this->app->storage->exists($slug . '.csv')) {
            $this->app->storage->write($slug . '.csv', $fieldHeaders, []);
        }

        // Garante existência das tabelas pivot para campos many_to_many
        foreach ($fields as $fcfg) {
            if (($fcfg['type'] ?? '') === 'many_to_many' && !empty($fcfg['pivot_file'])) {
                $pFile = $fcfg['pivot_file'];
                if (!$this->app->storage->exists($pFile)) {
                    $pHeaders = [
                        $fcfg['parent_key'] ?? (rtrim($slug, 's') . '_id'),
                        $fcfg['target_key'] ?? (rtrim($fcfg['target'] ?? 'target', 's') . '_id'),
                    ];
                    $this->app->storage->write($pFile, $pHeaders, []);
                }
            }
        }

        // Sincroniza permissões no RBAC
        $this->app->modules->ensurePermissions();

        // Registra evento de auditoria
        (new AuditService($this->app->storage))->log(
            'module_created',
            $this->user()['id'] ?? null,
            "slug={$slug}; entity={$entity}"
        );

        Session::flash('message', "Módulo '{$name}' criado e ativado com sucesso!");
        Response::redirect('/dev/modules');
    }

    public function deleteModule(Request $request, array $params): void
    {
        $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower((string) ($params['slug'] ?? '')));
        $modulesDir = $this->app->config->get('root') . '/modules';
        $targetModuleDir = $modulesDir . '/' . $slug;

        if (!is_dir($targetModuleDir)) {
            Session::flash('error', "Módulo '{$slug}' não encontrado.");
            Response::redirect('/dev/modules');
        }

        // Salvaguarda pré-exclusão
        (new BackupService($this->app))->create('pre_entity_delete_' . $slug, $this->user()['id'] ?? null);

        // Exclui arquivos do diretório do módulo
        $files = glob($targetModuleDir . '/*') ?: [];
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($targetModuleDir);

        (new AuditService($this->app->storage))->log(
            'module_deleted',
            $this->user()['id'] ?? null,
            "slug={$slug}"
        );

        Session::flash('message', "Módulo '{$slug}' removido com sucesso. Um backup de salvaguarda foi gerado automaticamente.");
        Response::redirect('/dev/modules');
    }
}
