<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuditService;

final class GenericCrudController extends Controller
{
    private function resolveModule(Request $request, array $params): array
    {
        $slug = $params['module'] ?? null;
        if (!$slug) {
            $segments = explode('/', trim($request->path(), '/'));
            if (($segments[0] ?? '') === 'app' && isset($segments[1])) {
                $slug = $segments[1];
            }
        }

        $module = $slug ? $this->app->modules->get($slug) : null;
        if (!$module) {
            Response::error(404, 'Módulo não encontrado.');
        }

        return $module;
    }

    private function resolveRelations(array $module): array
    {
        $relations = [];
        foreach ($module['fields'] as $key => $f) {
            if (($f['type'] ?? '') === 'relation') {
                $targetSlug = $f['target'] ?? '';
                $targetModule = $this->app->modules->get($targetSlug);
                $targetRepo = $this->app->modules->repository($targetSlug);
                $rows = $targetRepo ? $targetRepo->all() : [];
                $displayField = $f['display'] ?? '';

                $items = [];
                foreach ($rows as $row) {
                    $label = '';
                    if ($displayField !== '' && isset($row[$displayField])) {
                        $label = (string) $row[$displayField];
                    } else {
                        $label = (string) ($row['nome'] ?? $row['name'] ?? $row['title'] ?? $row['titulo'] ?? $row['descricao'] ?? $row['id']);
                    }
                    $items[] = [
                        'id' => (string) ($row['id'] ?? ''),
                        'label' => $label,
                    ];
                }

                $relations[$key] = [
                    'target' => $targetSlug,
                    'target_entity' => $targetModule['entity'] ?? ucfirst($targetSlug),
                    'items' => $items,
                    'map' => array_column($items, 'label', 'id'),
                ];
            }
        }
        return $relations;
    }

    private function resolveReverseRelations(array $module, string $parentId): array
    {
        $parentSlug = $module['slug'];
        $allModules = $this->app->modules->all();
        $reverse = [];

        foreach ($allModules as $childSlug => $childModule) {
            foreach (($childModule['fields'] ?? []) as $fKey => $fConfig) {
                if (($fConfig['type'] ?? '') === 'relation' && ($fConfig['target'] ?? '') === $parentSlug) {
                    $childRepo = $this->app->modules->repository($childSlug);
                    $childRows = $childRepo ? $childRepo->all() : [];
                    $matching = array_values(array_filter($childRows, fn($r) => ($r[$fKey] ?? '') === $parentId));

                    $displayFields = [];
                    foreach (($childModule['fields'] ?? []) as $k => $cfg) {
                        if ($k !== $fKey && (!isset($cfg['list']) || $cfg['list'] === true)) {
                            $displayFields[$k] = $cfg;
                        }
                    }

                    $reverse[] = [
                        'module' => $childModule,
                        'field_key' => $fKey,
                        'field_label' => $fConfig['label'] ?? ucfirst($fKey),
                        'items' => $matching,
                        'display_fields' => $displayFields,
                        'create_url' => "/app/{$childSlug}/create?{$fKey}=" . urlencode($parentId),
                    ];
                }
            }
        }

        return $reverse;
    }

    private function resolveReverseReferenceCounts(array $module, array $items): array
    {
        $parentSlug = $module['slug'];
        $allModules = $this->app->modules->all();
        $itemIds = array_column($items, 'id');
        $counts = array_fill_keys($itemIds, []);

        foreach ($allModules as $childSlug => $childModule) {
            foreach (($childModule['fields'] ?? []) as $fKey => $fConfig) {
                if (($fConfig['type'] ?? '') === 'relation' && ($fConfig['target'] ?? '') === $parentSlug) {
                    $childRepo = $this->app->modules->repository($childSlug);
                    $childRows = $childRepo ? $childRepo->all() : [];
                    foreach ($childRows as $cRow) {
                        $val = (string) ($cRow[$fKey] ?? '');
                        if ($val !== '' && isset($counts[$val])) {
                            $counts[$val][$childModule['name']] = ($counts[$val][$childModule['name']] ?? 0) + 1;
                        }
                    }
                }
            }
        }

        return $counts;
    }

    public function index(Request $request, array $params): void
    {
        $module = $this->resolveModule($request, $params);
        $repo = $this->app->modules->repository($module['slug']);
        $items = $repo ? $repo->all() : [];
        $relations = $this->resolveRelations($module);

        $activeFilters = [];
        foreach (($module['fields'] ?? []) as $fKey => $fConfig) {
            if (($fConfig['type'] ?? '') === 'relation') {
                $filterVal = trim((string) $request->input($fKey, ''));
                if ($filterVal !== '') {
                    $activeFilters[$fKey] = $filterVal;
                    $items = array_values(array_filter($items, fn($item) => ($item[$fKey] ?? '') === $filterVal));
                }
            }
        }

        $query = trim((string) $request->input('q', ''));
        if ($query !== '') {
            $queryLower = mb_strtolower($query);
            $items = array_filter($items, function ($item) use ($queryLower) {
                foreach ($item as $val) {
                    if (str_contains(mb_strtolower((string) $val), $queryLower)) {
                        return true;
                    }
                }
                return false;
            });
            $items = array_values($items);
        }

        $reverseCounts = $this->resolveReverseReferenceCounts($module, $items);

        $this->view('crud/index', [
            'module' => $module,
            'items' => $items,
            'query' => $query,
            'relationMaps' => $relations,
            'reverseCounts' => $reverseCounts,
            'activeFilters' => $activeFilters,
        ]);
    }

    public function create(Request $request, array $params): void
    {
        $module = $this->resolveModule($request, $params);
        $relations = $this->resolveRelations($module);

        $prefill = [];
        foreach ($module['fields'] as $key => $f) {
            $val = $request->input($key);
            if ($val !== null && $val !== '') {
                $prefill[$key] = (string) $val;
            }
        }

        $this->view('crud/form', [
            'module' => $module,
            'item' => !empty($prefill) ? $prefill : null,
            'isEdit' => false,
            'relations' => $relations,
        ]);
    }

    public function store(Request $request, array $params): void
    {
        $module = $this->resolveModule($request, $params);
        $repo = $this->app->modules->repository($module['slug']);
        $slug = $module['slug'];

        $data = [];
        foreach ($module['fields'] as $field => $config) {
            $val = $request->input($field);
            $type = $config['type'] ?? 'string';
            $label = $config['label'] ?? ucfirst($field);

            if ($type === 'boolean') {
                $val = $val ? '1' : '0';
            } else {
                $val = is_string($val) ? trim($val) : $val;
            }

            if (!empty($config['required']) && ($val === null || $val === '')) {
                Session::flash('error', "O campo '{$label}' é obrigatório.");
                Response::redirect("/app/{$slug}/create");
            }

            if ($type === 'relation' && $val !== null && $val !== '') {
                $targetSlug = $config['target'] ?? '';
                $targetRepo = $this->app->modules->repository($targetSlug);
                if (!$targetRepo || !$targetRepo->find((string) $val)) {
                    Session::flash('error', "O valor selecionado para o campo '{$label}' não existe no módulo de destino.");
                    Response::redirect("/app/{$slug}/create");
                }
            }

            if (!empty($config['unique']) && $val !== null && $val !== '') {
                if ($repo->findBy($field, $val)) {
                    Session::flash('error', "O valor informado para '{$label}' já está em uso.");
                    Response::redirect("/app/{$slug}/create");
                }
            }

            $data[$field] = (string) ($val ?? '');
        }

        $now = date('c');
        $id = $repo->nextId();
        $data['id'] = $id;
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        $repo->insert($data);

        (new AuditService($this->app->storage))->log(
            $slug . '_created',
            $this->user()['id'] ?? null,
            "ID: {$id}"
        );

        Session::flash('message', "{$module['entity']} cadastrado com sucesso.");
        Response::redirect("/app/{$slug}");
    }

    public function show(Request $request, array $params): void
    {
        $module = $this->resolveModule($request, $params);
        $repo = $this->app->modules->repository($module['slug']);
        $id = $params['id'] ?? '';
        $item = $repo ? $repo->find($id) : null;

        if (!$item) {
            Response::error(404, "Registro de {$module['entity']} não encontrado.");
        }

        $relations = $this->resolveRelations($module);
        $childRelations = $this->resolveReverseRelations($module, (string) ($item['id'] ?? ''));

        $this->view('crud/show', [
            'module' => $module,
            'item' => $item,
            'relationMaps' => $relations,
            'childRelations' => $childRelations,
        ]);
    }

    public function edit(Request $request, array $params): void
    {
        $module = $this->resolveModule($request, $params);
        $repo = $this->app->modules->repository($module['slug']);
        $id = $params['id'] ?? '';
        $item = $repo ? $repo->find($id) : null;

        if (!$item) {
            Response::error(404, "Registro de {$module['entity']} não encontrado.");
        }

        $relations = $this->resolveRelations($module);

        $this->view('crud/form', [
            'module' => $module,
            'item' => $item,
            'isEdit' => true,
            'relations' => $relations,
        ]);
    }

    public function update(Request $request, array $params): void
    {
        $module = $this->resolveModule($request, $params);
        $repo = $this->app->modules->repository($module['slug']);
        $slug = $module['slug'];
        $id = $params['id'] ?? '';
        $existing = $repo ? $repo->find($id) : null;

        if (!$existing) {
            Response::error(404, "Registro de {$module['entity']} não encontrado.");
        }

        $data = [];
        foreach ($module['fields'] as $field => $config) {
            $val = $request->input($field);
            $type = $config['type'] ?? 'string';
            $label = $config['label'] ?? ucfirst($field);

            if ($type === 'boolean') {
                $val = $val ? '1' : '0';
            } else {
                $val = is_string($val) ? trim($val) : $val;
            }

            if (!empty($config['required']) && ($val === null || $val === '')) {
                Session::flash('error', "O campo '{$label}' é obrigatório.");
                Response::redirect("/app/{$slug}/{$id}/edit");
            }

            if ($type === 'relation' && $val !== null && $val !== '') {
                $targetSlug = $config['target'] ?? '';
                $targetRepo = $this->app->modules->repository($targetSlug);
                if (!$targetRepo || !$targetRepo->find((string) $val)) {
                    Session::flash('error', "O valor selecionado para o campo '{$label}' não existe no módulo de destino.");
                    Response::redirect("/app/{$slug}/{$id}/edit");
                }
            }

            if (!empty($config['unique']) && $val !== null && $val !== '') {
                $match = $repo->findBy($field, $val);
                if ($match && ($match['id'] ?? '') !== $id) {
                    Session::flash('error', "O valor informado para '{$label}' já está em uso por outro registro.");
                    Response::redirect("/app/{$slug}/{$id}/edit");
                }
            }

            $data[$field] = (string) ($val ?? '');
        }

        $data['updated_at'] = date('c');
        $repo->update($id, $data);

        (new AuditService($this->app->storage))->log(
            $slug . '_updated',
            $this->user()['id'] ?? null,
            "ID: {$id}"
        );

        Session::flash('message', "{$module['entity']} atualizado com sucesso.");
        Response::redirect("/app/{$slug}");
    }

    public function delete(Request $request, array $params): void
    {
        $module = $this->resolveModule($request, $params);
        $repo = $this->app->modules->repository($module['slug']);
        $slug = $module['slug'];
        $id = $params['id'] ?? '';

        // Proteção de Integridade Referencial (Impede exclusão se houver vínculos ativos)
        $allModules = $this->app->modules->all();
        foreach ($allModules as $otherSlug => $otherModule) {
            foreach (($otherModule['fields'] ?? []) as $fKey => $fConfig) {
                if (($fConfig['type'] ?? '') === 'relation' && ($fConfig['target'] ?? '') === $slug) {
                    $otherRepo = $this->app->modules->repository($otherSlug);
                    if ($otherRepo) {
                        $referencing = array_filter($otherRepo->all(), fn($r) => ($r[$fKey] ?? '') === (string) $id);
                        if (!empty($referencing)) {
                            $count = count($referencing);
                            Session::flash('error', "Não é possível excluir este registro pois ele possui {$count} vínculo(s) no módulo '{$otherModule['name']}'.");
                            Response::redirect("/app/{$slug}");
                            return;
                        }
                    }
                }
            }
        }

        if ($repo) {
            $repo->delete($id);
            (new AuditService($this->app->storage))->log(
                $slug . '_deleted',
                $this->user()['id'] ?? null,
                "ID: {$id}"
            );
        }

        Session::flash('message', "{$module['entity']} excluído com sucesso.");
        Response::redirect("/app/{$slug}");
    }
}
