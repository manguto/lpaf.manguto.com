<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\MiddlewareInterface;

final class Router
{
    private array $routes = [];
    public function __construct(private Request $request) {}
    public function get(string $path, array $handler): Route
    {
        return $this->add('GET', $path, $handler);
    }
    public function post(string $path, array $handler): Route
    {
        return $this->add('POST', $path, $handler);
    }
    public function put(string $path, array $handler): Route
    {
        return $this->add('PUT', $path, $handler);
    }
    public function patch(string $path, array $handler): Route
    {
        return $this->add('PATCH', $path, $handler);
    }
    public function delete(string $path, array $handler): Route
    {
        return $this->add('DELETE', $path, $handler);
    }
    public function dispatch(Application $app): mixed
    {
        foreach ($this->routes as $route) {
            $parameters = $route->match($this->request->method(), $this->request->path());
            if ($parameters === null) continue;
            foreach ($route->middleware as $middleware) $middleware->handle($this->request, $app);
            $controller = new $route->handler[0]($app);
            return $controller->{$route->handler[1]}($this->request, $parameters);
        }
        Response::error(404, 'Página não encontrada.');
    }
    private function add(string $method, string $path, array $handler): Route
    {
        $route = new Route($method, $path, $handler);
        $this->routes[] = $route;
        return $route;
    }
}

final class Route
{
    public array $middleware = [];
    public function __construct(public string $method, public string $path, public array $handler) {}
    public function middleware(MiddlewareInterface|string $middleware): self
    {
        $this->middleware[] = is_string($middleware) ? new $middleware() : $middleware;
        return $this;
    }
    public function permission(string $permission): self
    {
        $this->middleware[] = new \App\Middleware\PermissionMiddleware($permission);
        return $this;
    }
    public function match(string $method, string $path): ?array
    {
        if ($method !== $this->method) return null;
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $this->path);
        if (!preg_match('#^' . rtrim((string) $pattern, '/') . '/?$#', $path, $matches)) return null;
        return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    }
}
