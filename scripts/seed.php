<?php

declare(strict_types=1);

/**
 * Seeder do LPAF - População de Banco de Dados (CSV) para Demonstração e Testes
 * 
 * Executa a carga de dados realistas e conectados para o domínio intuitivo de E-commerce / Vendas:
 * - Usuários e RBAC (Desenvolvedor, Administrador, Usuário comum, Inativo)
 * - Clientes (compradores em múltiplos status)
 * - Etiquetas / Tags (marcadores promocionais transversais)
 * - Produtos (catálogo de produtos e controle de estoque)
 * - Tabela Pivô N:N Produtos <-> Etiquetas (produto_tags.csv)
 * - Pedidos (vendas com integridade referencial 1:N restritiva para Clientes e Produtos)
 * - Avaliações (depoimentos com estrelas e exclusão em cascata)
 * - Logs de Auditoria Realistas
 * - Snapshot de Backup de Demonstração
 * 
 * Uso: php scripts/seed.php
 */

require dirname(__DIR__) . '/app/Core/Preflight.php';
\App\Core\Preflight::check(dirname(__DIR__));

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/app/Helpers/functions.php';

use App\Core\Config;
use App\Core\CsvStorage;
use App\Services\BackupService;

$root = dirname(__DIR__);
$config = new Config($root);
$storage = new CsvStorage($config);

echo "\n" . str_repeat('=', 78) . "\n";
echo "  LPAF - PHP Admin Framework | Carga de Dados de Teste & Demonstração\n";
echo str_repeat('=', 78) . "\n\n";

$now = date('c');
$yesterday = date('c', strtotime('-1 day'));
$lastWeek = date('c', strtotime('-7 days'));
$lastMonth = date('c', strtotime('-30 days'));

// -----------------------------------------------------------------------------
// 1. USUÁRIOS E RBAC
// -----------------------------------------------------------------------------
echo "[1/8] Populando Usuários e Perfis de Acesso...\n";

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

$users = [
    [
        'id' => 'usr_001',
        'name' => $existingDev['name'] ?? 'Desenvolvedor Master',
        'username' => 'dev',
        'password_hash' => $devHash,
        'active' => '1',
        'created_at' => $existingDev['created_at'] ?? $lastMonth,
        'updated_at' => $now,
    ],
    [
        'id' => 'usr_002',
        'name' => 'Administrador Geral',
        'username' => 'admin',
        'password_hash' => $adminHash,
        'active' => '1',
        'created_at' => $lastMonth,
        'updated_at' => $now,
    ],
    [
        'id' => 'usr_003',
        'name' => 'Carlos Operador',
        'username' => 'carlos',
        'password_hash' => $userHash,
        'active' => '1',
        'created_at' => $lastWeek,
        'updated_at' => $now,
    ],
    [
        'id' => 'usr_004',
        'name' => 'Mariana Analista',
        'username' => 'mariana',
        'password_hash' => $userHash,
        'active' => '1',
        'created_at' => $lastWeek,
        'updated_at' => $now,
    ],
    [
        'id' => 'usr_005',
        'name' => 'Usuário Inativo (Teste)',
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
    ['id' => 'users.manage', 'name' => 'Gerenciar Usuários'],
    ['id' => 'roles.manage', 'name' => 'Gerenciar Papéis'],
    ['id' => 'dev.diagnostics', 'name' => 'Acessar Diagnósticos Técnicos'],
    ['id' => 'dev.backups', 'name' => 'Acessar e Gerenciar Backups'],
    ['id' => 'dev.logs', 'name' => 'Visualizar Logs de Auditoria'],
    ['id' => 'dev.modules', 'name' => 'Gerenciar Módulos e Entidades'],
    // Módulos
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
    // Administrador tem gestão total dos dados
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

    // Usuário Comum tem visualização e criação de pedidos/avaliações
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

// -----------------------------------------------------------------------------
// 2. CLIENTES
// -----------------------------------------------------------------------------
echo "[2/8] Populando Clientes (Compradores)...\n";
$clientes = [
    [
        'id' => 'cli_001',
        'nome' => 'Ana Paula Silva',
        'email' => 'ana.silva@techmail.com',
        'telefone' => '(11) 98765-4321',
        'cidade' => 'São Paulo / SP',
        'status' => 'Ativo',
        'observacoes' => 'Cliente assídua, prefere entregas no período da tarde.',
        'created_at' => $lastMonth,
        'updated_at' => $now,
    ],
    [
        'id' => 'cli_002',
        'nome' => 'Bruno Mendes Costa',
        'email' => 'bruno.mendes@corporativo.com',
        'telefone' => '(21) 99876-5432',
        'cidade' => 'Rio de Janeiro / RJ',
        'status' => 'Ativo',
        'observacoes' => 'Compras corporativas para equipe de tecnologia.',
        'created_at' => $lastMonth,
        'updated_at' => $now,
    ],
    [
        'id' => 'cli_003',
        'nome' => 'Camila Rodrigues',
        'email' => 'camila.rodrigues@designstudio.com',
        'telefone' => '(31) 98877-6655',
        'cidade' => 'Belo Horizonte / MG',
        'status' => 'Ativo',
        'observacoes' => 'Profissional autônoma, adquire equipamentos de alta fidelidade visual.',
        'created_at' => $lastWeek,
        'updated_at' => $now,
    ],
    [
        'id' => 'cli_004',
        'nome' => 'Diego Fernandez',
        'email' => 'diego.fernandez@sultech.com.br',
        'telefone' => '(41) 97766-5544',
        'cidade' => 'Curitiba / PR',
        'status' => 'Potencial (Lead)',
        'observacoes' => 'Interessado em pedidos por atacado de monitores e teclados.',
        'created_at' => $yesterday,
        'updated_at' => $now,
    ],
    [
        'id' => 'cli_005',
        'nome' => 'Juliana Albuquerque',
        'email' => 'juliana.albuquerque@inovacao.org',
        'telefone' => '(51) 99988-7766',
        'cidade' => 'Porto Alegre / RS',
        'status' => 'Inativo',
        'observacoes' => 'Cadastro suspenso temporariamente a pedido da cliente.',
        'created_at' => $lastMonth,
        'updated_at' => $lastWeek,
    ],
    [
        'id' => 'cli_006',
        'nome' => 'Eduardo Lima',
        'email' => 'eduardo.lima@nordeste.com',
        'telefone' => '(71) 99123-4567',
        'cidade' => 'Salvador / BA',
        'status' => 'Ativo',
        'observacoes' => 'Novo cadastro sem pedidos realizados ainda (ideal para testar exclusão livre).',
        'created_at' => $now,
        'updated_at' => $now,
    ],
];
$storage->write('clientes.csv', ['id', 'nome', 'email', 'telefone', 'cidade', 'status', 'observacoes', 'created_at', 'updated_at'], $clientes);

// -----------------------------------------------------------------------------
// 3. ETIQUETAS / TAGS
// -----------------------------------------------------------------------------
echo "[3/8] Populando Etiquetas / Tags...\n";
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

// -----------------------------------------------------------------------------
// 4. PRODUTOS
// -----------------------------------------------------------------------------
echo "[4/8] Populando Produtos do Catálogo...\n";
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

// -----------------------------------------------------------------------------
// 5. TABELA PIVÔ N:N (PRODUTOS <-> ETIQUETAS)
// -----------------------------------------------------------------------------
echo "[5/8] Criando Vínculos N:N na Tabela Pivô (produto_tags.csv)...\n";
$produtoTags = [
    ['id' => 'ptg_001', 'created_at' => $lastMonth, 'produto_id' => 'prd_001', 'tag_id' => 'tag_001'], // Galaxy: Lançamento
    ['id' => 'ptg_002', 'created_at' => $lastMonth, 'produto_id' => 'prd_001', 'tag_id' => 'tag_002'], // Galaxy: Mais Vendido
    ['id' => 'ptg_003', 'created_at' => $lastMonth, 'produto_id' => 'prd_001', 'tag_id' => 'tag_004'], // Galaxy: Frete Grátis
    ['id' => 'ptg_004', 'created_at' => $lastMonth, 'produto_id' => 'prd_002', 'tag_id' => 'tag_002'], // Notebook: Mais Vendido
    ['id' => 'ptg_005', 'created_at' => $lastMonth, 'produto_id' => 'prd_002', 'tag_id' => 'tag_004'], // Notebook: Frete Grátis
    ['id' => 'ptg_006', 'created_at' => $lastWeek,  'produto_id' => 'prd_003', 'tag_id' => 'tag_003'], // Fone: Super Oferta
    ['id' => 'ptg_007', 'created_at' => $lastWeek,  'produto_id' => 'prd_003', 'tag_id' => 'tag_004'], // Fone: Frete Grátis
    ['id' => 'ptg_008', 'created_at' => $lastMonth, 'produto_id' => 'prd_004', 'tag_id' => 'tag_002'], // Livro: Mais Vendido
    ['id' => 'ptg_009', 'created_at' => $lastWeek,  'produto_id' => 'prd_005', 'tag_id' => 'tag_005'], // Cadeira: Edição Limitada
    ['id' => 'ptg_010', 'created_at' => $lastWeek,  'produto_id' => 'prd_005', 'tag_id' => 'tag_004'], // Cadeira: Frete Grátis
    ['id' => 'ptg_011', 'created_at' => $yesterday, 'produto_id' => 'prd_006', 'tag_id' => 'tag_001'], // Teclado: Lançamento
    ['id' => 'ptg_012', 'created_at' => $yesterday, 'produto_id' => 'prd_007', 'tag_id' => 'tag_003'], // Monitor: Super Oferta
];
$storage->write('produto_tags.csv', ['id', 'created_at', 'produto_id', 'tag_id'], $produtoTags);

// -----------------------------------------------------------------------------
// 6. PEDIDOS (1:N COM CLIENTES E PRODUTOS - RESTRICT)
// -----------------------------------------------------------------------------
echo "[6/8] Populando Pedidos e Vendas...\n";
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

// -----------------------------------------------------------------------------
// 7. AVALIAÇÕES (1:N EM CASCATA COM PRODUTOS E CLIENTES)
// -----------------------------------------------------------------------------
echo "[7/8] Populando Avaliações e Feedback dos Clientes...\n";
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

// -----------------------------------------------------------------------------
// 8. LOGS DE AUDITORIA & BACKUP INICIAL
// -----------------------------------------------------------------------------
echo "[8/8] Gerando Histórico de Auditoria e Snapshot de Backup...\n";
$auditLogs = [
    ['id' => 'aud_001', 'created_at' => $lastMonth, 'user_id' => 'usr_001', 'action' => 'setup', 'details' => 'Instalação inicial da aplicação concluída'],
    ['id' => 'aud_002', 'created_at' => $lastMonth, 'user_id' => 'usr_001', 'action' => 'module_created', 'details' => 'slug=produtos | nome=Produtos'],
    ['id' => 'aud_003', 'created_at' => $lastMonth, 'user_id' => 'usr_001', 'action' => 'module_created', 'details' => 'slug=tags | nome=Etiquetas'],
    ['id' => 'aud_004', 'created_at' => $lastMonth, 'user_id' => 'usr_001', 'action' => 'module_created', 'details' => 'slug=pedidos | nome=Pedidos'],
    ['id' => 'aud_005', 'created_at' => $lastMonth, 'user_id' => 'usr_001', 'action' => 'module_created', 'details' => 'slug=avaliacoes | nome=Avaliações'],
    ['id' => 'aud_006', 'created_at' => $lastWeek,  'user_id' => 'usr_002', 'action' => 'login', 'details' => 'IP=127.0.0.1'],
    ['id' => 'aud_007', 'created_at' => $lastWeek,  'user_id' => 'usr_002', 'action' => 'crud.create', 'details' => 'module=produtos | id=prd_001'],
    ['id' => 'aud_008', 'created_at' => $yesterday, 'user_id' => 'usr_003', 'action' => 'crud.create', 'details' => 'module=pedidos | id=ped_005'],
    ['id' => 'aud_009', 'created_at' => $now,       'user_id' => 'usr_001', 'action' => 'backup_create', 'details' => 'Carga inicial de demonstração'],
];
$storage->write('audit_log.csv', ['id', 'created_at', 'user_id', 'action', 'details'], $auditLogs);

// Backup de demonstração
$app = new \App\Core\Application($config, $storage, new \App\Core\Request());
$backupService = new BackupService($app);
$backupId = $backupService->create('carga_inicial_demonstracao', 'usr_001');

echo "\n" . str_repeat('=', 78) . "\n";
echo "  SUCESSO: Base de dados populada com dados intuitivos de Catálogo & Vendas!\n";
echo str_repeat('=', 78) . "\n";
echo "  Módulos carregados: Clientes (6), Produtos (8), Etiquetas (5), Pedidos (6), Avaliações (5)\n";
echo "  Tabela Pivô N:N: 12 associações em storage/data/produto_tags.csv\n";
echo "  Snapshot de Backup: {$backupId}\n\n";
