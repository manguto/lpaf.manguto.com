<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\PermissionRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;

final class Application
{
    public UserRepository $users;
    public RoleRepository $roles;
    public PermissionRepository $permissions;
    public ModuleManager $modules;
    public RateLimiter $rateLimiter;
    public function __construct(public Config $config, public CsvStorage $storage, public Request $request)
    {
        $this->users = new UserRepository($storage);
        $this->roles = new RoleRepository($storage);
        $this->permissions = new PermissionRepository($storage);
        $this->modules = new ModuleManager($this);
        $this->rateLimiter = new RateLimiter($config);
    }
    public function installed(): bool
    {
        return $this->storage->exists('settings.csv') && $this->setting('installed') === '1';
    }
    public function url(string $path = '/'): string
    {
        $path = '/' . ltrim($path, '/');
        return $this->request->basePath() . $path;
    }
    public function setting(string $key, mixed $default = null): mixed
    {
        foreach ($this->storage->read('settings.csv', ['key', 'value']) as $row) if ($row['key'] === $key) return $row['value'];
        return $default;
    }
}
