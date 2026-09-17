<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\GenericRepository;

final class ModuleManager
{
    private array $modules = [];
    private array $repositories = [];

    public function __construct(private Application $app)
    {
        $this->discover();
    }

    private function discover(): void
    {
        $modulesDir = $this->app->config->get('root') . '/modules';
        if (!is_dir($modulesDir)) {
            return;
        }

        $entries = scandir($modulesDir) ?: [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..' || !is_dir($modulesDir . '/' . $entry)) {
                continue;
            }

            $moduleFile = $modulesDir . '/' . $entry . '/module.php';
            if (is_file($moduleFile)) {
                $definition = require $moduleFile;
                if (is_array($definition) && !empty($definition['slug'])) {
                    $slug = (string) $definition['slug'];
                    $definition['name'] = $definition['name'] ?? ucfirst($slug);
                    $definition['entity'] = $definition['entity'] ?? ucfirst($slug);
                    $definition['icon'] = $definition['icon'] ?? '📁';
                    $definition['description'] = $definition['description'] ?? '';
                    $definition['storage'] = $definition['storage'] ?? ($slug . '.csv');
                    $definition['prefix'] = $definition['prefix'] ?? substr(preg_replace('/[^a-z0-9]/i', '', $slug), 0, 3);
                    $definition['permission_prefix'] = $definition['permission_prefix'] ?? $slug;
                    $definition['fields'] = (array) ($definition['fields'] ?? []);
                    $definition['path'] = $modulesDir . '/' . $entry;

                    $this->modules[$slug] = $definition;
                }
            }
        }
    }

    public function all(): array
    {
        return $this->modules;
    }

    public function get(string $slug): ?array
    {
        return $this->modules[$slug] ?? null;
    }

    public function repository(string $slug): ?GenericRepository
    {
        if (isset($this->repositories[$slug])) {
            return $this->repositories[$slug];
        }

        $module = $this->get($slug);
        if (!$module) {
            return null;
        }

        $fieldNames = array_keys($module['fields']);
        $repo = new GenericRepository(
            $this->app->storage,
            $module['storage'],
            $fieldNames,
            $module['prefix']
        );

        $this->repositories[$slug] = $repo;
        return $repo;
    }

    public function registerRoutes(Router $router): void
    {
        foreach ($this->modules as $slug => $module) {
            $perm = $module['permission_prefix'];

            $router->get('/app/' . $slug, [\App\Controllers\GenericCrudController::class, 'index'])
                ->middleware(\App\Middleware\AuthMiddleware::class)
                ->permission($perm . '.view');

            $router->get('/app/' . $slug . '/create', [\App\Controllers\GenericCrudController::class, 'create'])
                ->middleware(\App\Middleware\AuthMiddleware::class)
                ->permission($perm . '.create');

            $router->post('/app/' . $slug, [\App\Controllers\GenericCrudController::class, 'store'])
                ->middleware(\App\Middleware\AuthMiddleware::class)
                ->middleware(\App\Middleware\CsrfMiddleware::class)
                ->permission($perm . '.create');

            $router->get('/app/' . $slug . '/{id}', [\App\Controllers\GenericCrudController::class, 'show'])
                ->middleware(\App\Middleware\AuthMiddleware::class)
                ->permission($perm . '.view');

            $router->get('/app/' . $slug . '/{id}/edit', [\App\Controllers\GenericCrudController::class, 'edit'])
                ->middleware(\App\Middleware\AuthMiddleware::class)
                ->permission($perm . '.edit');

            $router->post('/app/' . $slug . '/{id}', [\App\Controllers\GenericCrudController::class, 'update'])
                ->middleware(\App\Middleware\AuthMiddleware::class)
                ->middleware(\App\Middleware\CsrfMiddleware::class)
                ->permission($perm . '.edit');

            $router->post('/app/' . $slug . '/{id}/delete', [\App\Controllers\GenericCrudController::class, 'delete'])
                ->middleware(\App\Middleware\AuthMiddleware::class)
                ->middleware(\App\Middleware\CsrfMiddleware::class)
                ->permission($perm . '.delete');
        }
    }

    public function ensurePermissions(): void
    {
        if (!$this->app->storage->exists('permissions.csv')) {
            return;
        }

        $existing = $this->app->permissions->all();
        $existingIds = array_column($existing, 'id');
        $newPermissions = [];

        foreach ($this->modules as $slug => $module) {
            $perm = $module['permission_prefix'];
            $entity = $module['entity'];

            $modulePerms = [
                $perm . '.view' => "Visualizar registros de {$entity}",
                $perm . '.create' => "Criar novos registros de {$entity}",
                $perm . '.edit' => "Editar registros de {$entity}",
                $perm . '.delete' => "Excluir registros de {$entity}",
            ];

            foreach ($modulePerms as $id => $desc) {
                if (!in_array($id, $existingIds, true)) {
                    $newPermissions[] = [
                        'id' => $id,
                        'name' => $id,
                        'description' => $desc,
                    ];
                    $existingIds[] = $id;
                }
            }
        }

        if (!empty($newPermissions)) {
            $all = array_merge($existing, $newPermissions);
            $this->app->storage->write('permissions.csv', ['id', 'name', 'description'], $all);
        }

        if ($this->app->storage->exists('role_permissions.csv')) {
            $rolePerms = $this->app->storage->read('role_permissions.csv', ['role_id', 'permission_id']);
            $adminPerms = [];
            foreach ($rolePerms as $rp) {
                if (($rp['role_id'] ?? '') === 'role_admin') {
                    $adminPerms[] = $rp['permission_id'] ?? '';
                }
            }
            $added = false;
            foreach ($this->modules as $slug => $module) {
                $perm = $module['permission_prefix'];
                foreach ([$perm . '.view', $perm . '.create', $perm . '.edit', $perm . '.delete'] as $pId) {
                    if (!in_array($pId, $adminPerms, true)) {
                        $rolePerms[] = ['role_id' => 'role_admin', 'permission_id' => $pId];
                        $adminPerms[] = $pId;
                        $added = true;
                    }
                }
            }
            if ($added) {
                $this->app->storage->write('role_permissions.csv', ['role_id', 'permission_id'], $rolePerms);
            }
        }
    }
}
