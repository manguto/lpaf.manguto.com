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

    public function index(Request $request, array $params): void
    {
        $module = $this->resolveModule($request, $params);
        $repo = $this->app->modules->repository($module['slug']);
        $items = $repo ? $repo->all() : [];

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

        $this->view('crud/index', [
            'module' => $module,
            'items' => $items,
            'query' => $query,
        ]);
    }

    public function create(Request $request, array $params): void
    {
        $module = $this->resolveModule($request, $params);

        $this->view('crud/form', [
            'module' => $module,
            'item' => null,
            'isEdit' => false,
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

        $this->view('crud/show', [
            'module' => $module,
            'item' => $item,
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

        $this->view('crud/form', [
            'module' => $module,
            'item' => $item,
            'isEdit' => true,
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
