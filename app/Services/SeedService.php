<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Application;

final class SeedService
{
    public function __construct(private Application $app) {}

    /**
     * Verifica se os módulos de modelo/demonstração estão instalados atualmente.
     */
    public function isDemoInstalled(string $preset = 'ecommerce'): bool
    {
        $modulesRoot = $this->app->config->get('root') . '/modules';
        return is_file($modulesRoot . '/clientes/module.php') || is_file($modulesRoot . '/produtos/module.php');
    }

    /**
     * Copia os módulos de template/preset para o diretório de módulos da aplicação.
     *
     * @return string[] Lista de slugs dos módulos instalados
     */
    public function installPresetModules(string $preset = 'ecommerce'): array
    {
        $presetModulesDir = $this->app->config->get('root') . '/templates/presets/' . $preset . '/modules';
        if (!is_dir($presetModulesDir)) {
            $fallback = dirname(__DIR__, 2) . '/templates/presets/' . $preset . '/modules';
            if (is_dir($fallback)) {
                $presetModulesDir = $fallback;
            } else {
                return [];
            }
        }
        $modulesRoot = $this->app->config->get('root') . '/modules';

        $installed = [];
        $entries = scandir($presetModulesDir) ?: [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $srcDir = $presetModulesDir . '/' . $entry;
            if (!is_dir($srcDir)) {
                continue;
            }

            $dstDir = $modulesRoot . '/' . $entry;
            if (!is_dir($dstDir)) {
                mkdir($dstDir, 0775, true);
            }

            $files = scandir($srcDir) ?: [];
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $srcFile = $srcDir . '/' . $file;
                $dstFile = $dstDir . '/' . $file;
                if (is_file($srcFile)) {
                    copy($srcFile, $dstFile);
                }
            }

            $installed[] = $entry;
        }

        return $installed;
    }

    /**
     * Remove com salvaguarda de backup todos os módulos e registros da demonstração, restaurando a base limpa (Clean Slate).
     *
     * @return array{modules_removed: string[], csvs_removed: string[], backup_id: string}
     */
    public function clearDemo(?string $userId = null, string $preset = 'ecommerce'): array
    {
        $storage = $this->app->storage;

        // 1. Snapshot automático de salvaguarda antes de limpar
        $backupService = new BackupService($this->app);
        $backupId = $backupService->create('pre_clear_demo_' . $preset, $userId ?? 'usr_001');

        // 2. Módulos do preset a remover
        $demoModules = ['clientes', 'produtos', 'tags', 'pedidos', 'avaliacoes'];
        $modulesRoot = $this->app->config->get('root') . '/modules';
        $removedModules = [];

        foreach ($demoModules as $mod) {
            $modDir = $modulesRoot . '/' . $mod;
            if (is_dir($modDir)) {
                $files = glob($modDir . '/*') ?: [];
                foreach ($files as $file) {
                    if (is_file($file)) {
                        @unlink($file);
                    }
                }
                @rmdir($modDir);
                $removedModules[] = $mod;
            }
        }

        // 3. Arquivos CSV demonstrativos a remover
        $demoCsvs = ['clientes.csv', 'produtos.csv', 'tags.csv', 'produto_tags.csv', 'pedidos.csv', 'avaliacoes.csv'];
        $removedCsvs = [];
        foreach ($demoCsvs as $csv) {
            if ($storage->exists($csv)) {
                $storage->delete($csv);
                $removedCsvs[] = $csv;
            }
        }

        // 4. Limpeza de permissões relacionadas aos módulos de demonstração
        $demoPermPrefixes = ['clientes', 'produtos', 'tags', 'pedidos', 'avaliacoes'];
        if ($storage->exists('permissions.csv')) {
            $allPerms = $storage->read('permissions.csv', ['id', 'name', 'description']);
            $filteredPerms = array_values(array_filter($allPerms, function ($p) use ($demoPermPrefixes) {
                $id = $p['id'] ?? '';
                foreach ($demoPermPrefixes as $prefix) {
                    if (str_starts_with($id, $prefix . '.')) {
                        return false;
                    }
                }
                return true;
            }));
            $storage->write('permissions.csv', ['id', 'name', 'description'], $filteredPerms);
        }

        if ($storage->exists('role_permissions.csv')) {
            $allRolePerms = $storage->read('role_permissions.csv', ['role_id', 'permission_id']);
            $filteredRolePerms = array_values(array_filter($allRolePerms, function ($rp) use ($demoPermPrefixes) {
                $id = $rp['permission_id'] ?? '';
                foreach ($demoPermPrefixes as $prefix) {
                    if (str_starts_with($id, $prefix . '.')) {
                        return false;
                    }
                }
                return true;
            }));
            $storage->write('role_permissions.csv', ['role_id', 'permission_id'], $filteredRolePerms);
        }

        // 5. Auditoria e Recarga do Gerenciador de Módulos
        $auditService = new AuditService($storage);
        $auditService->log('demo_cleared', $userId ?? 'usr_001', "preset={$preset}; modulos=" . implode(',', $removedModules));

        $this->app->modules->reload();

        return [
            'modules_removed' => $removedModules,
            'csvs_removed' => $removedCsvs,
            'backup_id' => $backupId,
        ];
    }

    /**
     * Popula a base de dados com o domínio demonstrativo intuitivo de Catálogo & Vendas,
     * garantindo a instalação prévia dos módulos de modelo a partir dos templates.
     *
     * @return array{modules: int, clientes: int, produtos: int, tags: int, produto_tags: int, pedidos: int, avaliacoes: int, backup_id: string}
     */
    public function run(?string $userId = null, string $preset = 'ecommerce'): array
    {
        // Garante que os módulos do preset de demonstração estejam presentes
        $installedModules = $this->installPresetModules($preset);
        $this->app->modules->reload();
        $this->app->modules->ensurePermissions();

        $storage = $this->app->storage;
        $now = date('c');
        $yesterday = date('c', strtotime('-1 day'));
        $lastWeek = date('c', strtotime('-7 days'));
        $lastMonth = date('c', strtotime('-30 days'));

        // 1. USUÁRIOS E RBAC
        $existingDev = null;
        if ($storage->exists('users.csv')) {
            foreach ($storage->read('users.csv') as $u) {
                if (($u['username'] ?? '') === 'dev') {
                    $existingDev = $u;
                    break;
                }
            }
        }

        $devHash = $existingDev['password_hash'] ?? password_hash('dev123456', PASSWORD_DEFAULT);
        $adminHash = password_hash('admin123456', PASSWORD_DEFAULT);
        $userHash = password_hash('user123456', PASSWORD_DEFAULT);

        $devName = 'Fulano da Silva (Desenvolvedor)';
        if ($existingDev && !empty($existingDev['name']) && !in_array($existingDev['name'], ['Desenvolvedor', 'dev', 'Desenvolvedor Master'], true)) {
            $devName = $existingDev['name'];
        }

        $users = [
            [
                'id' => 'usr_001',
                'name' => $devName,
                'username' => 'dev',
                'password_hash' => $devHash,
                'active' => '1',
                'created_at' => $existingDev['created_at'] ?? $lastMonth,
                'updated_at' => $now,
            ],
            [
                'id' => 'usr_002',
                'name' => 'Ciclana de Oliveira (Administradora)',
                'username' => 'admin',
                'password_hash' => $adminHash,
                'active' => '1',
                'created_at' => $lastMonth,
                'updated_at' => $now,
            ],
            [
                'id' => 'usr_003',
                'name' => 'Beltrano Pereira (Operador)',
                'username' => 'operador',
                'password_hash' => $userHash,
                'active' => '1',
                'created_at' => $lastWeek,
                'updated_at' => $now,
            ],
            [
                'id' => 'usr_004',
                'name' => 'Dulce Santos (Analista)',
                'username' => 'analista',
                'password_hash' => $userHash,
                'active' => '1',
                'created_at' => $lastWeek,
                'updated_at' => $now,
            ],
            [
                'id' => 'usr_005',
                'name' => 'Juvêncio Santos (Inativo)',
                'username' => 'inativo',
                'password_hash' => $userHash,
                'active' => '0',
                'created_at' => $lastMonth,
                'updated_at' => $lastWeek,
            ],
        ];
        $storage->write('users.csv', ['id', 'name', 'username', 'password_hash', 'active', 'created_at', 'updated_at'], $users);

        $roles = [
            ['id' => 'role_dev', 'name' => 'Desenvolvedor', 'description' => 'Acesso irrestrito a configurações, motor de módulos, logs e backups.'],
            ['id' => 'role_admin', 'name' => 'Administrador', 'description' => 'Gestão de usuários, perfis e operações completas em todos os módulos.'],
            ['id' => 'role_user', 'name' => 'Usuário Padrão', 'description' => 'Acesso operacional aos módulos permitidos de catálogo e pedidos.'],
        ];
        $storage->write('roles.csv', ['id', 'name', 'description'], $roles);

        $permissions = [
            ['id' => '*', 'name' => 'Acesso Integral (*)'],
            ['id' => 'dashboard.view', 'name' => 'Visualizar Painel Principal'],
            ['id' => 'profile.edit', 'name' => 'Editar Perfil Pessoal'],
            ['id' => 'users.manage', 'name' => 'Gerenciar Usuários'],
            ['id' => 'roles.manage', 'name' => 'Gerenciar Papéis'],
            ['id' => 'dev.access', 'name' => 'Acessar Área Dev-End'],
            ['id' => 'dev.diagnostics', 'name' => 'Acessar Diagnósticos Técnicos'],
            ['id' => 'dev.backups', 'name' => 'Acessar e Gerenciar Backups'],
            ['id' => 'dev.logs', 'name' => 'Visualizar Logs de Auditoria'],
            ['id' => 'dev.modules', 'name' => 'Gerenciar Módulos e Entidades'],
            ['id' => 'clientes.view', 'name' => 'Visualizar Clientes'],
            ['id' => 'clientes.create', 'name' => 'Cadastrar Clientes'],
            ['id' => 'clientes.edit', 'name' => 'Editar Clientes'],
            ['id' => 'clientes.delete', 'name' => 'Excluir Clientes'],
            ['id' => 'produtos.view', 'name' => 'Visualizar Produtos'],
            ['id' => 'produtos.create', 'name' => 'Cadastrar Produtos'],
            ['id' => 'produtos.edit', 'name' => 'Editar Produtos'],
            ['id' => 'produtos.delete', 'name' => 'Excluir Produtos'],
            ['id' => 'tags.view', 'name' => 'Visualizar Etiquetas'],
            ['id' => 'tags.create', 'name' => 'Cadastrar Etiquetas'],
            ['id' => 'tags.edit', 'name' => 'Editar Etiquetas'],
            ['id' => 'tags.delete', 'name' => 'Excluir Etiquetas'],
            ['id' => 'pedidos.view', 'name' => 'Visualizar Pedidos'],
            ['id' => 'pedidos.create', 'name' => 'Cadastrar Pedidos'],
            ['id' => 'pedidos.edit', 'name' => 'Editar Pedidos'],
            ['id' => 'pedidos.delete', 'name' => 'Excluir Pedidos'],
            ['id' => 'avaliacoes.view', 'name' => 'Visualizar Avaliações'],
            ['id' => 'avaliacoes.create', 'name' => 'Cadastrar Avaliações'],
            ['id' => 'avaliacoes.edit', 'name' => 'Editar Avaliações'],
            ['id' => 'avaliacoes.delete', 'name' => 'Excluir Avaliações'],
        ];
        $storage->write('permissions.csv', ['id', 'name'], $permissions);

        $userRoles = [
            ['user_id' => 'usr_001', 'role_id' => 'role_dev'],
            ['user_id' => 'usr_002', 'role_id' => 'role_admin'],
            ['user_id' => 'usr_003', 'role_id' => 'role_user'],
            ['user_id' => 'usr_004', 'role_id' => 'role_user'],
            ['user_id' => 'usr_005', 'role_id' => 'role_user'],
        ];
        $storage->write('user_roles.csv', ['user_id', 'role_id'], $userRoles);

        $rolePermissions = [
            // Desenvolvedor tem acesso total
            ['role_id' => 'role_dev', 'permission_id' => '*'],

            // Administrador tem gestão total dos dados
            ['role_id' => 'role_admin', 'permission_id' => 'dashboard.view'],
            ['role_id' => 'role_admin', 'permission_id' => 'profile.edit'],
            ['role_id' => 'role_admin', 'permission_id' => 'users.manage'],
            ['role_id' => 'role_admin', 'permission_id' => 'roles.manage'],
            ['role_id' => 'role_admin', 'permission_id' => 'clientes.view'],
            ['role_id' => 'role_admin', 'permission_id' => 'clientes.create'],
            ['role_id' => 'role_admin', 'permission_id' => 'clientes.edit'],
            ['role_id' => 'role_admin', 'permission_id' => 'clientes.delete'],
            ['role_id' => 'role_admin', 'permission_id' => 'produtos.view'],
            ['role_id' => 'role_admin', 'permission_id' => 'produtos.create'],
            ['role_id' => 'role_admin', 'permission_id' => 'produtos.edit'],
            ['role_id' => 'role_admin', 'permission_id' => 'produtos.delete'],
            ['role_id' => 'role_admin', 'permission_id' => 'tags.view'],
            ['role_id' => 'role_admin', 'permission_id' => 'tags.create'],
            ['role_id' => 'role_admin', 'permission_id' => 'tags.edit'],
            ['role_id' => 'role_admin', 'permission_id' => 'tags.delete'],
            ['role_id' => 'role_admin', 'permission_id' => 'pedidos.view'],
            ['role_id' => 'role_admin', 'permission_id' => 'pedidos.create'],
            ['role_id' => 'role_admin', 'permission_id' => 'pedidos.edit'],
            ['role_id' => 'role_admin', 'permission_id' => 'pedidos.delete'],
            ['role_id' => 'role_admin', 'permission_id' => 'avaliacoes.view'],
            ['role_id' => 'role_admin', 'permission_id' => 'avaliacoes.create'],
            ['role_id' => 'role_admin', 'permission_id' => 'avaliacoes.edit'],
            ['role_id' => 'role_admin', 'permission_id' => 'avaliacoes.delete'],

            // Usuário Comum tem dashboard, perfil e módulos operacionais
            ['role_id' => 'role_user', 'permission_id' => 'dashboard.view'],
            ['role_id' => 'role_user', 'permission_id' => 'profile.edit'],
            ['role_id' => 'role_user', 'permission_id' => 'clientes.view'],
            ['role_id' => 'role_user', 'permission_id' => 'produtos.view'],
            ['role_id' => 'role_user', 'permission_id' => 'tags.view'],
            ['role_id' => 'role_user', 'permission_id' => 'pedidos.view'],
            ['role_id' => 'role_user', 'permission_id' => 'pedidos.create'],
            ['role_id' => 'role_user', 'permission_id' => 'avaliacoes.view'],
            ['role_id' => 'role_user', 'permission_id' => 'avaliacoes.create'],
        ];
        $storage->write('role_permissions.csv', ['role_id', 'permission_id'], $rolePermissions);

        $settings = [
            ['key' => 'installed', 'value' => '1'],
            ['key' => 'app_name', 'value' => 'LPAF - Catálogo & Gestão Comercial'],
            ['key' => 'app_theme', 'value' => 'dark'],
        ];
        $storage->write('settings.csv', ['key', 'value'], $settings);

        // 2. CLIENTES
        $clientes = [
            [
                'id' => 'cli_001',
                'nome' => 'Fulano de Tal',
                'email' => 'fulano@exemplo.com',
                'telefone' => '(11) 98888-0001',
                'cidade' => 'São Paulo / SP',
                'status' => 'Ativo',
                'observacoes' => 'Cliente ilustrativo para demonstração de compras frequentes.',
                'created_at' => $lastMonth,
                'updated_at' => $now,
            ],
            [
                'id' => 'cli_002',
                'nome' => 'Ciclano de Oliveira',
                'email' => 'ciclano@exemplo.com',
                'telefone' => '(21) 98888-0002',
                'cidade' => 'Rio de Janeiro / RJ',
                'status' => 'Ativo',
                'observacoes' => 'Cliente ilustrativo para demonstração de compras corporativas.',
                'created_at' => $lastMonth,
                'updated_at' => $now,
            ],
            [
                'id' => 'cli_003',
                'nome' => 'Beltrana Pereira',
                'email' => 'beltrana@exemplo.com',
                'telefone' => '(31) 98888-0003',
                'cidade' => 'Belo Horizonte / MG',
                'status' => 'Ativo',
                'observacoes' => 'Cliente ilustrativa para testes de múltiplos pedidos e avaliações.',
                'created_at' => $lastWeek,
                'updated_at' => $now,
            ],
            [
                'id' => 'cli_004',
                'nome' => 'Empresa Exemplo Ltda',
                'email' => 'contato@empresaexemplo.com',
                'telefone' => '(41) 98888-0004',
                'cidade' => 'Curitiba / PR',
                'status' => 'Potencial (Lead)',
                'observacoes' => 'Cadastro corporativo modelo aguardando confirmação.',
                'created_at' => $yesterday,
                'updated_at' => $now,
            ],
            [
                'id' => 'cli_005',
                'nome' => 'Cliente Inativo Ilustrativo',
                'email' => 'inativo@exemplo.com',
                'telefone' => '(51) 98888-0005',
                'cidade' => 'Porto Alegre / RS',
                'status' => 'Inativo',
                'observacoes' => 'Cadastro inativo para validação de filtros de status.',
                'created_at' => $lastMonth,
                'updated_at' => $lastWeek,
            ],
            [
                'id' => 'cli_006',
                'nome' => 'Cliente Teste (Sem Pedidos)',
                'email' => 'sem.pedidos@exemplo.com',
                'telefone' => '(71) 98888-0006',
                'cidade' => 'Salvador / BA',
                'status' => 'Ativo',
                'observacoes' => 'Registro isolado sem vínculos para testar exclusão livre sem restrição de chave estrangeira.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        $storage->write('clientes.csv', ['id', 'nome', 'email', 'telefone', 'cidade', 'status', 'observacoes', 'created_at', 'updated_at'], $clientes);

        // 3. ETIQUETAS / TAGS
        $tags = [
            [
                'id' => 'tag_001',
                'nome' => 'Lançamento',
                'cor' => 'Azul (Destaque)',
                'descricao' => 'Produtos adicionados recentemente ao catálogo oficial.',
                'created_at' => $lastMonth,
                'updated_at' => $now,
            ],
            [
                'id' => 'tag_002',
                'nome' => 'Mais Vendido',
                'cor' => 'Verde (Sucesso / Frete Grátis)',
                'descricao' => 'Itens campeões de procura e preferência dos clientes.',
                'created_at' => $lastMonth,
                'updated_at' => $now,
            ],
            [
                'id' => 'tag_003',
                'nome' => 'Super Oferta',
                'cor' => 'Vermelho (Super Oferta)',
                'descricao' => 'Preço promocional por tempo limitado com desconto agressivo.',
                'created_at' => $lastWeek,
                'updated_at' => $now,
            ],
            [
                'id' => 'tag_004',
                'nome' => 'Frete Grátis',
                'cor' => 'Verde (Sucesso / Frete Grátis)',
                'descricao' => 'Envio sem custo adicional para todo o território nacional.',
                'created_at' => $lastMonth,
                'updated_at' => $now,
            ],
            [
                'id' => 'tag_005',
                'nome' => 'Edição Limitada',
                'cor' => 'Roxo (Exclusivo / Premium)',
                'descricao' => 'Lote exclusivo com número restrito de unidades fabricadas.',
                'created_at' => $lastWeek,
                'updated_at' => $now,
            ],
        ];
        $storage->write('tags.csv', ['id', 'nome', 'cor', 'descricao', 'created_at', 'updated_at'], $tags);

        // 4. PRODUTOS
        $produtos = [
            [
                'id' => 'prd_001',
                'nome' => 'Smartphone Galaxy Ultra 5G',
                'categoria' => 'Eletrônicos & Smartphones',
                'preco' => '3499.00',
                'estoque' => '45',
                'ativo' => '1',
                'descricao' => 'Tela AMOLED de 6.7 pol, 256GB de armazenamento, câmera tripla de 108MP e bateria de 5000mAh.',
                'created_at' => $lastMonth,
                'updated_at' => $now,
            ],
            [
                'id' => 'prd_002',
                'nome' => 'Notebook Pro 14 Pol M-Series 16GB',
                'categoria' => 'Informática & Escritório',
                'preco' => '5899.00',
                'estoque' => '18',
                'ativo' => '1',
                'descricao' => 'Processador de 10 núcleos, SSD NVMe de 512GB, teclado retroiluminado e bateria com autonomia de 18 horas.',
                'created_at' => $lastMonth,
                'updated_at' => $now,
            ],
            [
                'id' => 'prd_003',
                'nome' => 'Fone de Ouvido Bluetooth Over-Ear ANC',
                'categoria' => 'Acessórios & Wearables',
                'preco' => '649.00',
                'estoque' => '80',
                'ativo' => '1',
                'descricao' => 'Cancelamento de ruído ativo inteligente, microfone com IA para chamadas e almofadas com espuma viscoelástica.',
                'created_at' => $lastWeek,
                'updated_at' => $now,
            ],
            [
                'id' => 'prd_004',
                'nome' => 'Livro: Arquitetura de Software Prática & Limpa',
                'categoria' => 'Livros & Cursos',
                'preco' => '89.90',
                'estoque' => '120',
                'ativo' => '1',
                'descricao' => 'Guia definitivo de boas práticas, desacoplamento, domínio orientado a objetos e desenvolvimento ágil.',
                'created_at' => $lastMonth,
                'updated_at' => $now,
            ],
            [
                'id' => 'prd_005',
                'nome' => 'Cadeira Ergonômica Presidente Mesh',
                'categoria' => 'Casa & Conforto',
                'preco' => '1290.00',
                'estoque' => '14',
                'ativo' => '1',
                'descricao' => 'Apoio lombar 3D ajustável, braços articulados, rodízios em PU anti-risco e mecanismo sincronizado relax.',
                'created_at' => $lastWeek,
                'updated_at' => $now,
            ],
            [
                'id' => 'prd_006',
                'nome' => 'Teclado Mecânico Compacto Wireless RGB',
                'categoria' => 'Informática & Escritório',
                'preco' => '420.00',
                'estoque' => '35',
                'ativo' => '1',
                'descricao' => 'Switches lineares silenciosos, conexão tri-mode (2.4GHz, Bluetooth e USB-C) e teclas PBT de alta durabilidade.',
                'created_at' => $yesterday,
                'updated_at' => $now,
            ],
            [
                'id' => 'prd_007',
                'nome' => 'Monitor Gamer 27 Pol IPS 165Hz QHD',
                'categoria' => 'Informática & Escritório',
                'preco' => '1799.00',
                'estoque' => '22',
                'ativo' => '1',
                'descricao' => 'Resolução 2560x1440, tempo de resposta de 1ms, suporte a HDR10 e tecnologia FreeSync Premium.',
                'created_at' => $yesterday,
                'updated_at' => $now,
            ],
            [
                'id' => 'prd_008',
                'nome' => 'Carregador Sem Fio por Indução 3 em 1',
                'categoria' => 'Acessórios & Wearables',
                'preco' => '229.00',
                'estoque' => '0',
                'ativo' => '0',
                'descricao' => 'Base rápida magnética compatível com smartphone, fones e smartwatch simultaneamente.',
                'created_at' => $lastMonth,
                'updated_at' => $lastWeek,
            ],
        ];
        $storage->write('produtos.csv', ['id', 'nome', 'categoria', 'preco', 'estoque', 'ativo', 'descricao', 'created_at', 'updated_at'], $produtos);

        // 5. TABELA PIVÔ N:N (PRODUTOS <-> ETIQUETAS)
        $produtoTags = [
            ['id' => 'ptg_001', 'created_at' => $lastMonth, 'produto_id' => 'prd_001', 'tag_id' => 'tag_001'],
            ['id' => 'ptg_002', 'created_at' => $lastMonth, 'produto_id' => 'prd_001', 'tag_id' => 'tag_002'],
            ['id' => 'ptg_003', 'created_at' => $lastMonth, 'produto_id' => 'prd_001', 'tag_id' => 'tag_004'],
            ['id' => 'ptg_004', 'created_at' => $lastMonth, 'produto_id' => 'prd_002', 'tag_id' => 'tag_002'],
            ['id' => 'ptg_005', 'created_at' => $lastMonth, 'produto_id' => 'prd_002', 'tag_id' => 'tag_004'],
            ['id' => 'ptg_006', 'created_at' => $lastWeek,  'produto_id' => 'prd_003', 'tag_id' => 'tag_003'],
            ['id' => 'ptg_007', 'created_at' => $lastWeek,  'produto_id' => 'prd_003', 'tag_id' => 'tag_004'],
            ['id' => 'ptg_008', 'created_at' => $lastMonth, 'produto_id' => 'prd_004', 'tag_id' => 'tag_002'],
            ['id' => 'ptg_009', 'created_at' => $lastWeek,  'produto_id' => 'prd_005', 'tag_id' => 'tag_005'],
            ['id' => 'ptg_010', 'created_at' => $lastWeek,  'produto_id' => 'prd_005', 'tag_id' => 'tag_004'],
            ['id' => 'ptg_011', 'created_at' => $yesterday, 'produto_id' => 'prd_006', 'tag_id' => 'tag_001'],
            ['id' => 'ptg_012', 'created_at' => $yesterday, 'produto_id' => 'prd_007', 'tag_id' => 'tag_003'],
        ];
        $storage->write('produto_tags.csv', ['id', 'created_at', 'produto_id', 'tag_id'], $produtoTags);

        // 6. PEDIDOS (1:N RESTRICT)
        $pedidos = [
            [
                'id' => 'ped_001',
                'numero' => 'PED-2026-001',
                'cliente_id' => 'cli_001',
                'produto_id' => 'prd_001',
                'quantidade' => '1',
                'valor_total' => '3499.00',
                'status' => 'Entregue',
                'data_pedido' => date('Y-m-d', strtotime('-15 days')),
                'observacoes' => 'Entregue com sucesso com nota fiscal acompanhada.',
                'created_at' => date('c', strtotime('-15 days')),
                'updated_at' => date('c', strtotime('-12 days')),
            ],
            [
                'id' => 'ped_002',
                'numero' => 'PED-2026-002',
                'cliente_id' => 'cli_002',
                'produto_id' => 'prd_002',
                'quantidade' => '1',
                'valor_total' => '5899.00',
                'status' => 'Em Transporte',
                'data_pedido' => date('Y-m-d', strtotime('-5 days')),
                'observacoes' => 'Despachado via transportadora expressa com código rastreado.',
                'created_at' => date('c', strtotime('-5 days')),
                'updated_at' => date('c', strtotime('-2 days')),
            ],
            [
                'id' => 'ped_003',
                'numero' => 'PED-2026-003',
                'cliente_id' => 'cli_003',
                'produto_id' => 'prd_003',
                'quantidade' => '2',
                'valor_total' => '1298.00',
                'status' => 'Aprovado',
                'data_pedido' => date('Y-m-d', strtotime('-3 days')),
                'observacoes' => 'Pagamento aprovado via PIX, aguardando separação no estoque.',
                'created_at' => date('c', strtotime('-3 days')),
                'updated_at' => date('c', strtotime('-3 days')),
            ],
            [
                'id' => 'ped_004',
                'numero' => 'PED-2026-004',
                'cliente_id' => 'cli_001',
                'produto_id' => 'prd_004',
                'quantidade' => '1',
                'valor_total' => '89.90',
                'status' => 'Entregue',
                'data_pedido' => date('Y-m-d', strtotime('-8 days')),
                'observacoes' => 'Entrega rápida realizada sem intercorrências.',
                'created_at' => date('c', strtotime('-8 days')),
                'updated_at' => date('c', strtotime('-6 days')),
            ],
            [
                'id' => 'ped_005',
                'numero' => 'PED-2026-005',
                'cliente_id' => 'cli_004',
                'produto_id' => 'prd_005',
                'quantidade' => '1',
                'valor_total' => '1290.00',
                'status' => 'Pendente',
                'data_pedido' => date('Y-m-d', strtotime('-1 day')),
                'observacoes' => 'Aguardando confirmação bancária do boleto.',
                'created_at' => $yesterday,
                'updated_at' => $yesterday,
            ],
            [
                'id' => 'ped_006',
                'numero' => 'PED-2026-006',
                'cliente_id' => 'cli_002',
                'produto_id' => 'prd_006',
                'quantidade' => '1',
                'valor_total' => '420.00',
                'status' => 'Aprovado',
                'data_pedido' => date('Y-m-d'),
                'observacoes' => 'Item separado na expedição para envio no próximo lote.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        $storage->write('pedidos.csv', ['id', 'numero', 'cliente_id', 'produto_id', 'quantidade', 'valor_total', 'status', 'data_pedido', 'observacoes', 'created_at', 'updated_at'], $pedidos);

        // 7. AVALIAÇÕES (1:N CASCADE)
        $avaliacoes = [
            [
                'id' => 'avl_001',
                'produto_id' => 'prd_001',
                'cliente_id' => 'cli_001',
                'nota' => '⭐⭐⭐⭐⭐ 5 Estrelas (Excelente)',
                'titulo' => 'Simplesmente espetacular!',
                'comentario' => 'Câmera incrível, fotos nítidas até à noite. A tela tem cores muito vivas e a bateria dura mais de um dia e meio com uso intenso.',
                'data' => date('Y-m-d', strtotime('-10 days')),
                'created_at' => date('c', strtotime('-10 days')),
                'updated_at' => date('c', strtotime('-10 days')),
            ],
            [
                'id' => 'avl_002',
                'produto_id' => 'prd_002',
                'cliente_id' => 'cli_002',
                'nota' => '⭐⭐⭐⭐⭐ 5 Estrelas (Excelente)',
                'titulo' => 'Máquina perfeita para desenvolvedores',
                'comentario' => 'Compila projetos pesados em segundos sem esquentar nem fazer barulho. O teclado é extremamente confortável para longas sessões.',
                'data' => date('Y-m-d', strtotime('-4 days')),
                'created_at' => date('c', strtotime('-4 days')),
                'updated_at' => date('c', strtotime('-4 days')),
            ],
            [
                'id' => 'avl_003',
                'produto_id' => 'prd_003',
                'cliente_id' => 'cli_003',
                'nota' => '⭐⭐⭐⭐ 4 Estrelas (Muito Bom)',
                'titulo' => 'Cancelamento de ruído muito eficiente',
                'comentario' => 'Isola perfeitamente o som do escritório aberto. O acabamento é de muita qualidade. Só achei o estojo de transporte um pouco volumoso.',
                'data' => date('Y-m-d', strtotime('-2 days')),
                'created_at' => date('c', strtotime('-2 days')),
                'updated_at' => date('c', strtotime('-2 days')),
            ],
            [
                'id' => 'avl_004',
                'produto_id' => 'prd_004',
                'cliente_id' => 'cli_001',
                'nota' => '⭐⭐⭐⭐⭐ 5 Estrelas (Excelente)',
                'titulo' => 'Leitura obrigatória para todo programador',
                'comentario' => 'Explicações muito práticas e diretas ao ponto. Mudou a forma como projeto as camadas de serviço e repositório nos meus projetos.',
                'data' => date('Y-m-d', strtotime('-6 days')),
                'created_at' => date('c', strtotime('-6 days')),
                'updated_at' => date('c', strtotime('-6 days')),
            ],
            [
                'id' => 'avl_005',
                'produto_id' => 'prd_005',
                'cliente_id' => 'cli_004',
                'nota' => '⭐⭐⭐⭐ 4 Estrelas (Muito Bom)',
                'titulo' => 'Ergonomia nota 10',
                'comentario' => 'Acabaram minhas dores nas costas depois de trocar de cadeira. O tecido em mesh respirável é excelente para dias mais quentes.',
                'data' => date('Y-m-d'),
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        $storage->write('avaliacoes.csv', ['id', 'produto_id', 'cliente_id', 'nota', 'titulo', 'comentario', 'data', 'created_at', 'updated_at'], $avaliacoes);

        // 8. AUDITORIA E BACKUP
        $auditService = new AuditService($storage);
        $auditService->log('database_seeded', $userId ?? 'usr_001', 'Carga de dados de demonstração (Catálogo & Vendas)');

        $backupService = new BackupService($this->app);
        $backupId = $backupService->create('carga_demonstracao', $userId ?? 'usr_001');

        return [
            'modules' => count($installedModules),
            'clientes' => count($clientes),
            'produtos' => count($produtos),
            'tags' => count($tags),
            'produto_tags' => count($produtoTags),
            'pedidos' => count($pedidos),
            'avaliacoes' => count($avaliacoes),
            'backup_id' => $backupId,
        ];
    }
}
