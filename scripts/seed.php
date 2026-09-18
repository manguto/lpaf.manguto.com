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

use App\Core\Application;
use App\Core\Config;
use App\Core\CsvStorage;
use App\Core\Request;
use App\Services\SeedService;

$root = dirname(__DIR__);
$config = new Config($root);
$storage = new CsvStorage($config);
$app = new Application($config, $storage, new Request());

echo "\n" . str_repeat('=', 78) . "\n";
echo "  LPAF - PHP Admin Framework | Carga de Dados de Teste & Demonstração\n";
echo str_repeat('=', 78) . "\n\n";

echo "Populando catálogo e vendas demonstrativas...\n";

$service = new SeedService($app);
$stats = $service->run('usr_001');

echo "\n" . str_repeat('=', 78) . "\n";
echo "  SUCESSO: Base de dados populada com dados intuitivos de Catálogo & Vendas!\n";
echo str_repeat('=', 78) . "\n";
echo "  Módulos carregados: Clientes ({$stats['clientes']}), Produtos ({$stats['produtos']}), Etiquetas ({$stats['tags']}), Pedidos ({$stats['pedidos']}), Avaliações ({$stats['avaliacoes']})\n";
echo "  Tabela Pivô N:N: {$stats['produto_tags']} associações em storage/data/produto_tags.csv\n";
echo "  Snapshot de Backup: {$stats['backup_id']}\n\n";
