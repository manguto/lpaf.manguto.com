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
                        'on_delete' => $fConfig['on_delete'] ?? 'restrict',
                        'items' => $matching,
                        'display_fields' => $displayFields,
                        'create_url' => "/app/{$childSlug}/create?{$fKey}=" . urlencode($parentId),
                    ];
                }
            }
        }

        return $reverse;
    }

    private function resolveManyToMany(array $module, ?string $recordId = null): array
    {
        $m2m = [];
        foreach ($module['fields'] as $key => $f) {
            if (($f['type'] ?? '') === 'many_to_many') {
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
                        $label = (string) ($row['nome'] ?? $row['name'] ?? $row['title'] ?? $row['titulo'] ?? $row['razao_social'] ?? $row['id']);
                    }
                    $items[] = [
                        'id' => (string) ($row['id'] ?? ''),
                        'label' => $label,
                    ];
                }

                $pivotFile = $f['pivot_file'] ?? ($module['slug'] . '_' . $targetSlug . '.csv');
                $parentKey = $f['parent_key'] ?? (rtrim($module['slug'], 's') . '_id');
                $targetKey = $f['target_key'] ?? (rtrim($targetSlug, 's') . '_id');

                $selected = [];
                if ($recordId !== null && $this->app->storage->exists($pivotFile)) {
                    $pivotRows = $this->app->storage->read($pivotFile);
                    foreach ($pivotRows as $pRow) {
                        if (($pRow[$parentKey] ?? '') === $recordId) {
                            $val = (string) ($pRow[$targetKey] ?? '');
                            if ($val !== '') {
                                $selected[] = $val;
                            }
                        }
                    }
                }

                $m2m[$key] = [
                    'target' => $targetSlug,
                    'target_entity' => $targetModule['entity'] ?? ucfirst($targetSlug),
                    'target_name' => $targetModule['name'] ?? ucfirst($targetSlug),
                    'pivot_file' => $pivotFile,
                    'parent_key' => $parentKey,
                    'target_key' => $targetKey,
                    'items' => $items,
                    'map' => array_column($items, 'label', 'id'),
                    'selected' => $selected,
                ];
            }
        }
        return $m2m;
    }

    private function resolveReverseManyToMany(array $module, string $targetId): array
    {
        $currentSlug = $module['slug'];
        $allModules = $this->app->modules->all();
        $reverse = [];

        foreach ($allModules as $parentSlug => $parentModule) {
            foreach (($parentModule['fields'] ?? []) as $fKey => $fConfig) {
                if (($fConfig['type'] ?? '') === 'many_to_many' && ($fConfig['target'] ?? '') === $currentSlug) {
                    $pivotFile = $fConfig['pivot_file'] ?? ($parentSlug . '_' . $currentSlug . '.csv');
                    $parentKey = $fConfig['parent_key'] ?? (rtrim($parentSlug, 's') . '_id');
                    $targetKey = $fConfig['target_key'] ?? (rtrim($currentSlug, 's') . '_id');

                    if (!$this->app->storage->exists($pivotFile)) {
                        continue;
                    }

                    $pivotRows = $this->app->storage->read($pivotFile);
                    $matchedParentIds = [];
                    foreach ($pivotRows as $pRow) {
                        if (($pRow[$targetKey] ?? '') === $targetId) {
                            $pId = (string) ($pRow[$parentKey] ?? '');
                            if ($pId !== '') {
                                $matchedParentIds[] = $pId;
                            }
                        }
                    }

                    $parentRepo = $this->app->modules->repository($parentSlug);
                    $parentRows = $parentRepo ? $parentRepo->all() : [];
                    $matchingItems = array_values(array_filter(
                        $parentRows,
                        fn($r) => in_array((string) ($r['id'] ?? ''), $matchedParentIds, true)
                    ));

                    $displayFields = [];
                    foreach (($parentModule['fields'] ?? []) as $k => $cfg) {
                        if (($cfg['type'] ?? '') !== 'many_to_many' && (!isset($cfg['list']) || $cfg['list'] === true)) {
                            $displayFields[$k] = $cfg;
                        }
                    }

                    $reverse[] = [
                        'module' => $parentModule,
                        'field_key' => $fKey,
                        'field_label' => $fConfig['label'] ?? ucfirst($fKey),
                        'items' => $matchingItems,
                        'display_fields' => $displayFields,
                    ];
                }
            }
        }
        return $reverse;
    }

    private function syncManyToMany(array $module, string $parentId, Request $request): void
    {
        foreach ($module['fields'] as $field => $config) {
            if (($config['type'] ?? '') === 'many_to_many') {
                $rawInput = $request->input($field);
                $selectedIds = is_array($rawInput) ? $rawInput : ($rawInput !== null && $rawInput !== '' ? [(string) $rawInput] : []);
                $selectedIds = array_values(array_unique(array_filter(
                    array_map('trim', array_map('strval', $selectedIds)),
                    fn($id) => $id !== ''
                )));

                $targetSlug = $config['target'] ?? '';
                $targetRepo = $this->app->modules->repository($targetSlug);
                $validIds = [];
                if ($targetRepo) {
                    foreach ($selectedIds as $tgtId) {
                        if ($targetRepo->find($tgtId)) {
                            $validIds[] = $tgtId;
                        }
                    }
                }

                $pivotFile = $config['pivot_file'] ?? ($module['slug'] . '_' . $targetSlug . '.csv');
                $parentKey = $config['parent_key'] ?? (rtrim($module['slug'], 's') . '_id');
                $targetKey = $config['target_key'] ?? (rtrim($targetSlug, 's') . '_id');
                $pivotHeaders = [$parentKey, $targetKey];

                $existingRows = $this->app->storage->exists($pivotFile) ? $this->app->storage->read($pivotFile) : [];
                $remainingRows = array_values(array_filter(
                    $existingRows,
                    fn($r) => ($r[$parentKey] ?? '') !== $parentId
                ));

                foreach ($validIds as $tgtId) {
                    $remainingRows[] = [
                        $parentKey => $parentId,
                        $targetKey => $tgtId,
                    ];
                }

                $this->app->storage->write($pivotFile, $pivotHeaders, $remainingRows);
            }
        }
    }

    private function cleanupManyToManyOnDelete(string $slug, string $id): int
    {
        $removedCount = 0;
        $allModules = $this->app->modules->all();

        // 1. Limpa tabelas pivot onde esta entidade é o parent
        $currentModule = $allModules[$slug] ?? null;
        if ($currentModule) {
            foreach (($currentModule['fields'] ?? []) as $fKey => $fConfig) {
                if (($fConfig['type'] ?? '') === 'many_to_many') {
                    $targetSlug = $fConfig['target'] ?? '';
                    $pivotFile = $fConfig['pivot_file'] ?? ($slug . '_' . $targetSlug . '.csv');
                    $parentKey = $fConfig['parent_key'] ?? (rtrim($slug, 's') . '_id');
                    $targetKey = $fConfig['target_key'] ?? (rtrim($targetSlug, 's') . '_id');

                    if ($this->app->storage->exists($pivotFile)) {
                        $rows = $this->app->storage->read($pivotFile);
                        $initialCount = count($rows);
                        $kept = array_values(array_filter($rows, fn($r) => ($r[$parentKey] ?? '') !== $id));
                        if (count($kept) !== $initialCount) {
                            $removedCount += ($initialCount - count($kept));
                            $this->app->storage->write($pivotFile, [$parentKey, $targetKey], $kept);
                        }
                    }
                }
            }
        }

        // 2. Limpa tabelas pivot onde esta entidade é o target
        foreach ($allModules as $parentSlug => $parentModule) {
            foreach (($parentModule['fields'] ?? []) as $fKey => $fConfig) {
                if (($fConfig['type'] ?? '') === 'many_to_many' && ($fConfig['target'] ?? '') === $slug) {
                    $pivotFile = $fConfig['pivot_file'] ?? ($parentSlug . '_' . $slug . '.csv');
                    $parentKey = $fConfig['parent_key'] ?? (rtrim($parentSlug, 's') . '_id');
                    $targetKey = $fConfig['target_key'] ?? (rtrim($slug, 's') . '_id');

                    if ($this->app->storage->exists($pivotFile)) {
                        $rows = $this->app->storage->read($pivotFile);
                        $initialCount = count($rows);
                        $kept = array_values(array_filter($rows, fn($r) => ($r[$targetKey] ?? '') !== $id));
                        if (count($kept) !== $initialCount) {
                            $removedCount += ($initialCount - count($kept));
                            $this->app->storage->write($pivotFile, [$parentKey, $targetKey], $kept);
                        }
                    }
                }
            }
        }

        return $removedCount;
    }

    private function resolveReverseReferenceCounts(array $module, array $items): array
    {
        $parentSlug = $module['slug'];
        $allModules = $this->app->modules->all();
        $itemIds = array_column($items, 'id');
        
        $counts = [];
        foreach ($itemIds as $id) {
            $counts[$id] = [
                'has_restrict' => false,
                'has_cascade' => false,
                'has_set_null' => false,
                'total_restrict' => 0,
                'total_cascade' => 0,
                'total_set_null' => 0,
                'restrict' => [],
                'cascade' => [],
                'set_null' => [],
                'restrict_text' => '',
                'cascade_text' => '',
                'set_null_text' => '',
            ];
        }

        foreach ($allModules as $childSlug => $childModule) {
            foreach (($childModule['fields'] ?? []) as $fKey => $fConfig) {
                if (($fConfig['type'] ?? '') === 'relation' && ($fConfig['target'] ?? '') === $parentSlug) {
                    $policy = $fConfig['on_delete'] ?? 'restrict';
                    if (!in_array($policy, ['restrict', 'set_null', 'cascade'], true)) {
                        $policy = 'restrict';
                    }
                    $childRepo = $this->app->modules->repository($childSlug);
                    $childRows = $childRepo ? $childRepo->all() : [];
                    foreach ($childRows as $cRow) {
                        $val = (string) ($cRow[$fKey] ?? '');
                        if ($val !== '' && isset($counts[$val])) {
                            $counts[$val][$policy][$childModule['name']] = ($counts[$val][$policy][$childModule['name']] ?? 0) + 1;
                            $counts[$val]['total_' . $policy]++;
                            $counts[$val]['has_' . $policy] = true;
                        }
                    }
                }
            }
        }

        foreach ($counts as $id => &$data) {
            $rParts = [];
            foreach ($data['restrict'] as $mName => $c) {
                $rParts[] = "{$c} em {$mName}";
            }
            $data['restrict_text'] = implode(', ', $rParts);

            $cParts = [];
            foreach ($data['cascade'] as $mName => $c) {
                $cParts[] = "{$c} em {$mName}";
            }
            $data['cascade_text'] = implode(', ', $cParts);

            $sParts = [];
            foreach ($data['set_null'] as $mName => $c) {
                $sParts[] = "{$c} em {$mName}";
            }
            $data['set_null_text'] = implode(', ', $sParts);
        }
        unset($data);

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

        $manyToManyMaps = [];
        foreach (($module['fields'] ?? []) as $fKey => $fConfig) {
            if (($fConfig['type'] ?? '') === 'many_to_many') {
                $targetSlug = $fConfig['target'] ?? '';
                $targetRepo = $this->app->modules->repository($targetSlug);
                $displayField = $fConfig['display'] ?? '';
                $targetItems = [];
                if ($targetRepo) {
                    foreach ($targetRepo->all() as $tRow) {
                        $tId = (string) ($tRow['id'] ?? '');
                        $label = '';
                        if ($displayField !== '' && isset($tRow[$displayField])) {
                            $label = (string) $tRow[$displayField];
                        } else {
                            $label = (string) ($tRow['nome'] ?? $tRow['name'] ?? $tRow['title'] ?? $tRow['titulo'] ?? $tRow['razao_social'] ?? $tId);
                        }
                        $targetItems[$tId] = ['id' => $tId, 'label' => $label];
                    }
                }

                $pivotFile = $fConfig['pivot_file'] ?? ($module['slug'] . '_' . $targetSlug . '.csv');
                $parentKey = $fConfig['parent_key'] ?? (rtrim($module['slug'], 's') . '_id');
                $targetKey = $fConfig['target_key'] ?? (rtrim($targetSlug, 's') . '_id');

                $recordMap = [];
                if ($this->app->storage->exists($pivotFile)) {
                    $pRows = $this->app->storage->read($pivotFile);
                    foreach ($pRows as $pRow) {
                        $pId = (string) ($pRow[$parentKey] ?? '');
                        $tId = (string) ($pRow[$targetKey] ?? '');
                        if ($pId !== '' && $tId !== '' && isset($targetItems[$tId])) {
                            $recordMap[$pId][] = $targetItems[$tId];
                        }
                    }
                }
                $manyToManyMaps[$fKey] = $recordMap;
            }
        }

        $this->view('crud/index', [
            'module' => $module,
            'items' => $items,
            'query' => $query,
            'relationMaps' => $relations,
            'manyToManyMaps' => $manyToManyMaps,
            'reverseCounts' => $reverseCounts,
            'activeFilters' => $activeFilters,
        ]);
    }

    public function create(Request $request, array $params): void
    {
        $module = $this->resolveModule($request, $params);
        $relations = $this->resolveRelations($module);
        $manyToMany = $this->resolveManyToMany($module);

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
            'manyToMany' => $manyToMany,
        ]);
    }

    public function store(Request $request, array $params): void
    {
        $module = $this->resolveModule($request, $params);
        $repo = $this->app->modules->repository($module['slug']);
        $slug = $module['slug'];

        $data = [];
        foreach ($module['fields'] as $field => $config) {
            $type = $config['type'] ?? 'string';
            $label = $config['label'] ?? ucfirst($field);

            if ($type === 'many_to_many') {
                $rawInput = $request->input($field);
                $selected = is_array($rawInput) ? $rawInput : ($rawInput !== null && $rawInput !== '' ? [(string) $rawInput] : []);
                $selected = array_filter(array_map('strval', $selected), fn($s) => trim($s) !== '');
                if (!empty($config['required']) && empty($selected)) {
                    Session::flash('error', "O campo '{$label}' é obrigatório.");
                    Response::redirect("/app/{$slug}/create");
                }
                continue;
            }

            $val = $request->input($field);

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
        $this->syncManyToMany($module, $id, $request);

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
        $manyToMany = $this->resolveManyToMany($module, (string) ($item['id'] ?? ''));
        $childRelations = $this->resolveReverseRelations($module, (string) ($item['id'] ?? ''));
        $reverseManyToMany = $this->resolveReverseManyToMany($module, (string) ($item['id'] ?? ''));

        $this->view('crud/show', [
            'module' => $module,
            'item' => $item,
            'relationMaps' => $relations,
            'manyToMany' => $manyToMany,
            'childRelations' => $childRelations,
            'reverseManyToMany' => $reverseManyToMany,
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
        $manyToMany = $this->resolveManyToMany($module, (string) ($item['id'] ?? ''));

        $this->view('crud/form', [
            'module' => $module,
            'item' => $item,
            'isEdit' => true,
            'relations' => $relations,
            'manyToMany' => $manyToMany,
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
            $type = $config['type'] ?? 'string';
            $label = $config['label'] ?? ucfirst($field);

            if ($type === 'many_to_many') {
                $rawInput = $request->input($field);
                $selected = is_array($rawInput) ? $rawInput : ($rawInput !== null && $rawInput !== '' ? [(string) $rawInput] : []);
                $selected = array_filter(array_map('strval', $selected), fn($s) => trim($s) !== '');
                if (!empty($config['required']) && empty($selected)) {
                    Session::flash('error', "O campo '{$label}' é obrigatório.");
                    Response::redirect("/app/{$slug}/{$id}/edit");
                }
                continue;
            }

            $val = $request->input($field);

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
        $this->syncManyToMany($module, $id, $request);

        (new AuditService($this->app->storage))->log(
            $slug . '_updated',
            $this->user()['id'] ?? null,
            "ID: {$id}"
        );

        Session::flash('message', "{$module['entity']} atualizado com sucesso.");
        Response::redirect("/app/{$slug}");
    }

    private function checkDeletionRestrictions(string $parentSlug, string $parentId, array &$errors, array &$visited = []): void
    {
        $visitKey = "{$parentSlug}:{$parentId}";
        if (isset($visited[$visitKey])) {
            return;
        }
        $visited[$visitKey] = true;

        $allModules = $this->app->modules->all();
        foreach ($allModules as $childSlug => $childModule) {
            foreach (($childModule['fields'] ?? []) as $fKey => $fConfig) {
                if (($fConfig['type'] ?? '') === 'relation' && ($fConfig['target'] ?? '') === $parentSlug) {
                    $policy = $fConfig['on_delete'] ?? 'restrict';
                    $childRepo = $this->app->modules->repository($childSlug);
                    if (!$childRepo) continue;

                    $referencing = array_filter($childRepo->all(), fn($r) => ($r[$fKey] ?? '') === $parentId);
                    $count = count($referencing);
                    if ($count === 0) continue;

                    if ($policy === 'restrict') {
                        $errors[] = "{$count} vínculo(s) no módulo '{$childModule['name']}'";
                    } elseif ($policy === 'cascade') {
                        foreach ($referencing as $childRow) {
                            $childId = (string) ($childRow['id'] ?? '');
                            if ($childId !== '') {
                                $this->checkDeletionRestrictions($childSlug, $childId, $errors, $visited);
                            }
                        }
                    }
                }
            }
        }
    }

    private function executeRelationPolicies(string $parentSlug, string $parentId, int &$unlinkedCount, int &$cascadeCount, array &$visited = []): void
    {
        $visitKey = "{$parentSlug}:{$parentId}";
        if (isset($visited[$visitKey])) {
            return;
        }
        $visited[$visitKey] = true;

        $allModules = $this->app->modules->all();
        foreach ($allModules as $childSlug => $childModule) {
            foreach (($childModule['fields'] ?? []) as $fKey => $fConfig) {
                if (($fConfig['type'] ?? '') === 'relation' && ($fConfig['target'] ?? '') === $parentSlug) {
                    $policy = $fConfig['on_delete'] ?? 'restrict';
                    $childRepo = $this->app->modules->repository($childSlug);
                    if (!$childRepo) continue;

                    $referencing = array_filter($childRepo->all(), fn($r) => ($r[$fKey] ?? '') === $parentId);
                    if (empty($referencing)) continue;

                    if ($policy === 'set_null') {
                        foreach ($referencing as $childRow) {
                            $childId = (string) ($childRow['id'] ?? '');
                            if ($childId !== '') {
                                $childRepo->update($childId, [$fKey => '', 'updated_at' => date('c')]);
                                $unlinkedCount++;
                                (new AuditService($this->app->storage))->log(
                                    $childSlug . '_unlinked',
                                    $this->user()['id'] ?? null,
                                    "ID: {$childId}, desvinculado de {$parentSlug}: {$parentId} (campo: {$fKey})"
                                );
                            }
                        }
                    } elseif ($policy === 'cascade') {
                        foreach ($referencing as $childRow) {
                            $childId = (string) ($childRow['id'] ?? '');
                            if ($childId !== '') {
                                $this->executeRelationPolicies($childSlug, $childId, $unlinkedCount, $cascadeCount, $visited);
                                $childRepo->delete($childId);
                                $cascadeCount++;
                                (new AuditService($this->app->storage))->log(
                                    $childSlug . '_cascade_deleted',
                                    $this->user()['id'] ?? null,
                                    "ID: {$childId}, cascade de {$parentSlug}: {$parentId}"
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    public function delete(Request $request, array $params): void
    {
        $module = $this->resolveModule($request, $params);
        $repo = $this->app->modules->repository($module['slug']);
        $slug = $module['slug'];
        $id = (string) ($params['id'] ?? '');

        if (!$repo || !$repo->find($id)) {
            Session::flash('error', "Registro de {$module['entity']} não encontrado.");
            Response::redirect("/app/{$slug}");
            return;
        }

        // 1. Verificação prévia de integridade referencial (bloqueia se houver restrict)
        $restrictErrors = [];
        $this->checkDeletionRestrictions($slug, $id, $restrictErrors);

        if (!empty($restrictErrors)) {
            $errorMsg = "Não é possível excluir este registro pois possui vínculos protegidos (restrict): " . implode('; ', $restrictErrors) . ".";
            Session::flash('error', $errorMsg);
            Response::redirect("/app/{$slug}");
            return;
        }

        // 2. Executar políticas relacionais ativas (set_null e cascade)
        $unlinkedCount = 0;
        $cascadeCount = 0;
        $this->executeRelationPolicies($slug, $id, $unlinkedCount, $cascadeCount);

        // 3. Limpar associações em tabelas pivot N:N
        $detachedPivotCount = $this->cleanupManyToManyOnDelete($slug, $id);

        // 4. Excluir o registro principal
        $repo->delete($id);
        (new AuditService($this->app->storage))->log(
            $slug . '_deleted',
            $this->user()['id'] ?? null,
            "ID: {$id}"
        );

        // 5. Feedback detalhado para o usuário
        $details = [];
        if ($cascadeCount > 0) {
            $details[] = "{$cascadeCount} registro(s) dependente(s) excluído(s) em cascata";
        }
        if ($unlinkedCount > 0) {
            $details[] = "{$unlinkedCount} registro(s) desvinculado(s)";
        }
        if ($detachedPivotCount > 0) {
            $details[] = "{$detachedPivotCount} vínculo(s) N:N desassociado(s)";
        }

        $successMsg = "{$module['entity']} excluído com sucesso.";
        if (!empty($details)) {
            $successMsg .= " (" . implode(', ', $details) . ")";
        }

        Session::flash('message', $successMsg);
        Response::redirect("/app/{$slug}");
    }
}
