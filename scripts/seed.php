<?php

declare(strict_types=1);

/**
 * Seeder do LPAF - População de Banco de Dados (CSV) para Demonstração e Testes
 * 
 * Executa a carga de dados realistas e conectados para todos os módulos e RBAC:
 * - Usuários e Perfis (Desenvolvedor, Administrador, Usuário comum, Inativo)
 * - Clientes (com múltiplos status)
 * - Projetos (vinculados a Clientes via on_delete = restrict)
 * - Tarefas (vinculadas a Projetos via on_delete = cascade)
 * - Equipamentos (TI, infraestrutura, status ativo/inativo)
 * - Manutenções (vinculadas a Equipamentos com histórico e custos)
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
echo "[1/7] Populando Usuários e Perfis de Acesso...\n";

// Preserva o hash existente de usr_001 (dev) se já existir para não quebrar login atual
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
        'name' => 'Roberto Inativo',
        'username' => 'inativo',
        'password_hash' => $userHash,
        'active' => '0',
        'created_at' => $lastMonth,
        'updated_at' => $yesterday,
    ],
];
$storage->write('users.csv', ['id', 'name', 'username', 'password_hash', 'active', 'created_at', 'updated_at'], $users);

$userRoles = [
    ['user_id' => 'usr_001', 'role_id' => 'role_dev'],
    ['user_id' => 'usr_002', 'role_id' => 'role_admin'],
    ['user_id' => 'usr_003', 'role_id' => 'role_user'],
    ['user_id' => 'usr_004', 'role_id' => 'role_user'],
    ['user_id' => 'usr_005', 'role_id' => 'role_user'],
];
$storage->write('user_roles.csv', ['user_id', 'role_id'], $userRoles);

// Atualiza role_permissions para permitir visualização aos usuários comuns (para testes de RBAC)
$rolePermissions = [
    ['role_id' => 'role_dev', 'permission_id' => '*'],
    // role_admin: permissões administrativas completas
    ['role_id' => 'role_admin', 'permission_id' => 'dashboard.view'],
    ['role_id' => 'role_admin', 'permission_id' => 'profile.edit'],
    ['role_id' => 'role_admin', 'permission_id' => 'users.view'],
    ['role_id' => 'role_admin', 'permission_id' => 'users.create'],
    ['role_id' => 'role_admin', 'permission_id' => 'users.edit'],
    ['role_id' => 'role_admin', 'permission_id' => 'users.manage'],
    ['role_id' => 'role_admin', 'permission_id' => 'roles.view'],
    ['role_id' => 'role_admin', 'permission_id' => 'roles.create'],
    ['role_id' => 'role_admin', 'permission_id' => 'roles.edit'],
    ['role_id' => 'role_admin', 'permission_id' => 'roles.manage'],
    ['role_id' => 'role_admin', 'permission_id' => 'audit.view'],
    ['role_id' => 'role_admin', 'permission_id' => 'clientes.view'],
    ['role_id' => 'role_admin', 'permission_id' => 'clientes.create'],
    ['role_id' => 'role_admin', 'permission_id' => 'clientes.edit'],
    ['role_id' => 'role_admin', 'permission_id' => 'clientes.delete'],
    ['role_id' => 'role_admin', 'permission_id' => 'equipamentos.view'],
    ['role_id' => 'role_admin', 'permission_id' => 'equipamentos.create'],
    ['role_id' => 'role_admin', 'permission_id' => 'equipamentos.edit'],
    ['role_id' => 'role_admin', 'permission_id' => 'equipamentos.delete'],
    ['role_id' => 'role_admin', 'permission_id' => 'manutencoes.view'],
    ['role_id' => 'role_admin', 'permission_id' => 'manutencoes.create'],
    ['role_id' => 'role_admin', 'permission_id' => 'manutencoes.edit'],
    ['role_id' => 'role_admin', 'permission_id' => 'manutencoes.delete'],
    ['role_id' => 'role_admin', 'permission_id' => 'projetos.view'],
    ['role_id' => 'role_admin', 'permission_id' => 'projetos.create'],
    ['role_id' => 'role_admin', 'permission_id' => 'projetos.edit'],
    ['role_id' => 'role_admin', 'permission_id' => 'projetos.delete'],
    ['role_id' => 'role_admin', 'permission_id' => 'tarefas.view'],
    ['role_id' => 'role_admin', 'permission_id' => 'tarefas.create'],
    ['role_id' => 'role_admin', 'permission_id' => 'tarefas.edit'],
    ['role_id' => 'role_admin', 'permission_id' => 'tarefas.delete'],
    // role_user: permissões de visualização e perfil (leitura)
    ['role_id' => 'role_user', 'permission_id' => 'dashboard.view'],
    ['role_id' => 'role_user', 'permission_id' => 'profile.edit'],
    ['role_id' => 'role_user', 'permission_id' => 'clientes.view'],
    ['role_id' => 'role_user', 'permission_id' => 'projetos.view'],
    ['role_id' => 'role_user', 'permission_id' => 'tarefas.view'],
    ['role_id' => 'role_user', 'permission_id' => 'equipamentos.view'],
    ['role_id' => 'role_user', 'permission_id' => 'manutencoes.view'],
];
$storage->write('role_permissions.csv', ['role_id', 'permission_id'], $rolePermissions);

// -----------------------------------------------------------------------------
// 2. CLIENTES (clientes.csv)
// -----------------------------------------------------------------------------
echo "[2/7] Populando Módulo: Clientes (6 registros)...\n";
$clientesHeaders = ['id', 'created_at', 'updated_at', 'razao_social', 'nome_fantasia', 'cnpj', 'cidade', 'status', 'observacoes'];
$clientes = [
    [
        'id' => 'cli_001',
        'created_at' => $lastMonth,
        'updated_at' => $lastMonth,
        'razao_social' => 'TechLog Logística e Transportes S/A',
        'nome_fantasia' => 'TechLog Brasil',
        'cnpj' => '12.345.678/0001-90',
        'cidade' => 'Campinas / SP',
        'status' => 'Ativo',
        'observacoes' => 'Contrato Enterprise de monitoramento de frotas e telemetria em tempo real.',
    ],
    [
        'id' => 'cli_002',
        'created_at' => $lastMonth,
        'updated_at' => $lastWeek,
        'razao_social' => 'Hospital Santa Clara Assistência Médica Ltda',
        'nome_fantasia' => 'Hospital Santa Clara',
        'cnpj' => '98.765.432/0001-10',
        'cidade' => 'São Paulo / SP',
        'status' => 'Ativo',
        'observacoes' => 'Ambiente hospitalar com alta criticidade, telemedicina e conformidade com a LGPD.',
    ],
    [
        'id' => 'cli_003',
        'created_at' => $lastWeek,
        'updated_at' => $lastWeek,
        'razao_social' => 'Varejo Global Comércio e Distribuição S/A',
        'nome_fantasia' => 'Global Express',
        'cnpj' => '45.678.901/0001-23',
        'cidade' => 'Rio de Janeiro / RJ',
        'status' => 'Em Implantação',
        'observacoes' => 'Plataforma e-commerce e centro de distribuição automatizado.',
    ],
    [
        'id' => 'cli_004',
        'created_at' => $lastMonth,
        'updated_at' => $lastMonth,
        'razao_social' => 'Instituto Alfa de Inovação e Educação',
        'nome_fantasia' => 'Instituto Alfa',
        'cnpj' => '33.444.555/0001-67',
        'cidade' => 'Curitiba / PR',
        'status' => 'Ativo',
        'observacoes' => 'Portal de cursos, ambiente virtual de aprendizado e pesquisa aplicada.',
    ],
    [
        'id' => 'cli_005',
        'created_at' => $lastWeek,
        'updated_at' => $yesterday,
        'razao_social' => 'Prisma Soluções Financeiras e Meios de Pagamento',
        'nome_fantasia' => 'Prisma Fintech',
        'cnpj' => '77.888.999/0001-44',
        'cidade' => 'Belo Horizonte / MG',
        'status' => 'Prospect',
        'observacoes' => 'Em processo de homologação regulatória e testes de segurança bancária.',
    ],
    [
        'id' => 'cli_006',
        'created_at' => $lastMonth,
        'updated_at' => $yesterday,
        'razao_social' => 'Prime Consultoria Estratégica Ltda',
        'nome_fantasia' => 'Prime Consultoria',
        'cnpj' => '55.666.777/0001-88',
        'cidade' => 'Porto Alegre / RS',
        'status' => 'Inativo',
        'observacoes' => 'Conta inativa sem projetos vinculados (ideal para testar exclusão direta permitida).',
    ],
];
$storage->write('clientes.csv', $clientesHeaders, $clientes);

// -----------------------------------------------------------------------------
// 3. PROJETOS (projetos.csv)
// -----------------------------------------------------------------------------
echo "[3/7] Populando Módulo: Projetos (8 registros vinculados a Clientes)...\n";
$projetosHeaders = ['id', 'created_at', 'updated_at', 'nome', 'cliente_id', 'natureza', 'prioridade', 'descricao', 'observacoes', 'ativo'];
$projetos = [
    [
        'id' => 'pro_001',
        'created_at' => $lastMonth,
        'updated_at' => $lastWeek,
        'nome' => 'Portal de Telemetria e Rastreamento em Tempo Real',
        'cliente_id' => 'cli_001',
        'natureza' => 'Desenvolvimento',
        'prioridade' => 'Alta',
        'descricao' => 'Desenvolvimento da suíte web e mobile de telemetria veicular com localização GPS via WebSockets.',
        'observacoes' => 'Fase 2 de expansão de funcionalidades.',
        'ativo' => '1',
    ],
    [
        'id' => 'pro_002',
        'created_at' => $lastMonth,
        'updated_at' => $lastMonth,
        'nome' => 'Migração de Servidores para Nuvem Privada',
        'cliente_id' => 'cli_001',
        'natureza' => 'Infraestrutura',
        'prioridade' => 'Média',
        'descricao' => 'Modernização do parque computacional local para cluster em datacenter certificado Tier III.',
        'observacoes' => 'Janela de manutenção concluída.',
        'ativo' => '1',
    ],
    [
        'id' => 'pro_003',
        'created_at' => $lastMonth,
        'updated_at' => $yesterday,
        'nome' => 'Modernização do Prontuário Clínico Eletrônico',
        'cliente_id' => 'cli_002',
        'natureza' => 'Desenvolvimento',
        'prioridade' => 'Alta',
        'descricao' => 'Refatoração da arquitetura de registros médicos, laudos laboratoriais e integração de prescrição.',
        'observacoes' => 'Exige conformidade rígida com LGPD e auditoria médica.',
        'ativo' => '1',
    ],
    [
        'id' => 'pro_004',
        'created_at' => $lastWeek,
        'updated_at' => $lastWeek,
        'nome' => 'Auditoria e Avaliação de Vulnerabilidades (Pentest)',
        'cliente_id' => 'cli_002',
        'natureza' => 'Suporte',
        'prioridade' => 'Alta',
        'descricao' => 'Auditoria preventiva externa e interna contra ameaças cibernéticas nas redes clínicas.',
        'observacoes' => 'Relatório trimestral apresentado à diretoria.',
        'ativo' => '1',
    ],
    [
        'id' => 'pro_005',
        'created_at' => $lastWeek,
        'updated_at' => $now,
        'nome' => 'Plataforma de Pagamentos Multicanal (PIX & Checkout)',
        'cliente_id' => 'cli_003',
        'natureza' => 'Desenvolvimento',
        'prioridade' => 'Alta',
        'descricao' => 'Gateway unificado de transações instantâneas com webhook bancário para o e-commerce.',
        'observacoes' => 'Em testes de stress e homologação.',
        'ativo' => '1',
    ],
    [
        'id' => 'pro_006',
        'created_at' => $lastMonth,
        'updated_at' => $lastMonth,
        'nome' => 'Otimização de Banco de Dados e Cache de Catálogo',
        'cliente_id' => 'cli_003',
        'natureza' => 'Infraestrutura',
        'prioridade' => 'Baixa',
        'descricao' => 'Redução de tempo de carregamento de páginas de categorias e busca de produtos para a Black Friday.',
        'observacoes' => 'Ganhos de 40% em latência.',
        'ativo' => '1',
    ],
    [
        'id' => 'pro_007',
        'created_at' => $lastMonth,
        'updated_at' => $lastWeek,
        'nome' => 'Ambiente Virtual de Aprendizagem Interativo (AVA)',
        'cliente_id' => 'cli_004',
        'natureza' => 'Desenvolvimento',
        'prioridade' => 'Média',
        'descricao' => 'Plataforma EAD com transmissão de videoaulas, fóruns moderados e geração automática de certificados.',
        'observacoes' => 'Mais de 12.000 alunos previstos.',
        'ativo' => '1',
    ],
    [
        'id' => 'pro_008',
        'created_at' => $lastMonth,
        'updated_at' => $lastMonth,
        'nome' => 'Painel Interno de Métricas Corporativas (BI)',
        'cliente_id' => '', // Sem cliente vinculado para teste de campos relacionais opcionais
        'natureza' => 'Gestão',
        'prioridade' => 'Baixa',
        'descricao' => 'Iniciativa departamental interna para visualização de indicadores estratégicos e metas da equipe.',
        'observacoes' => 'Projeto interno sem vínculo com cliente terceiro.',
        'ativo' => '1',
    ],
];
$storage->write('projetos.csv', $projetosHeaders, $projetos);

// -----------------------------------------------------------------------------
// 4. TAREFAS (tarefas.csv)
// -----------------------------------------------------------------------------
echo "[4/7] Populando Módulo: Tarefas (12 registros vinculados a Projetos)...\n";
$tarefasHeaders = ['id', 'created_at', 'updated_at', 'titulo', 'projeto_id', 'prioridade', 'status', 'prazo', 'descricao'];
$tarefas = [
    [
        'id' => 'tar_001',
        'created_at' => $lastMonth,
        'updated_at' => $lastWeek,
        'titulo' => 'Desenvolver endpoint de ingestão de telemetria GPS',
        'projeto_id' => 'pro_001',
        'prioridade' => 'Urgente',
        'status' => 'Concluído',
        'prazo' => '2026-09-15',
        'descricao' => 'Criar rota de alta performance com validação de assinatura digital das viaturas.',
    ],
    [
        'id' => 'tar_002',
        'created_at' => $lastWeek,
        'updated_at' => $yesterday,
        'titulo' => 'Integrar notificações push no aplicativo dos motoristas',
        'projeto_id' => 'pro_001',
        'prioridade' => 'Alta',
        'status' => 'Em Andamento',
        'prazo' => '2026-10-05',
        'descricao' => 'Alertas de trânsito intenso, rota otimizada e parada obrigatória de descanso.',
    ],
    [
        'id' => 'tar_003',
        'created_at' => $lastWeek,
        'updated_at' => $now,
        'titulo' => 'Testes de carga com 5.000 requisições simultâneas',
        'projeto_id' => 'pro_001',
        'prioridade' => 'Média',
        'status' => 'A Fazer',
        'prazo' => '2026-10-18',
        'descricao' => 'Simular comportamento da API em horários de pico comercial.',
    ],
    [
        'id' => 'tar_004',
        'created_at' => $lastMonth,
        'updated_at' => $lastMonth,
        'titulo' => 'Provisionamento de túnel IPsec entre matriz e datacenter',
        'projeto_id' => 'pro_002',
        'prioridade' => 'Alta',
        'status' => 'Concluído',
        'prazo' => '2026-09-08',
        'descricao' => 'Conexão segura dedicada para sincronização de base de dados.',
    ],
    [
        'id' => 'tar_005',
        'created_at' => $lastMonth,
        'updated_at' => $now,
        'titulo' => 'Implementar assinatura digital ICP-Brasil nas prescrições',
        'projeto_id' => 'pro_003',
        'prioridade' => 'Urgente',
        'status' => 'Em Andamento',
        'prazo' => '2026-09-30',
        'descricao' => 'Integração com certificados digitais A1/A3 e carimbo de tempo eletrônico.',
    ],
    [
        'id' => 'tar_006',
        'created_at' => $lastMonth,
        'updated_at' => $lastWeek,
        'titulo' => 'Revisão de conformidade de prontuários com a LGPD',
        'projeto_id' => 'pro_003',
        'prioridade' => 'Alta',
        'status' => 'Concluído',
        'prazo' => '2026-09-12',
        'descricao' => 'Anonimização de dados para visualização de equipe de triagem e recepção.',
    ],
    [
        'id' => 'tar_007',
        'created_at' => $lastWeek,
        'updated_at' => $lastWeek,
        'titulo' => 'Varredura de portas e auditoria de firewalls perimetrais',
        'projeto_id' => 'pro_004',
        'prioridade' => 'Alta',
        'status' => 'Concluído',
        'prazo' => '2026-09-10',
        'descricao' => 'Identificação e fechamento de portas de diagnóstico legadas desnecessárias.',
    ],
    [
        'id' => 'tar_008',
        'created_at' => $lastWeek,
        'updated_at' => $now,
        'titulo' => 'Implementar webhook bancário de baixa automática de PIX',
        'projeto_id' => 'pro_005',
        'prioridade' => 'Urgente',
        'status' => 'Em Andamento',
        'prazo' => '2026-10-02',
        'descricao' => 'Confirmação de recebimento em menos de 3 segundos para liberação de pedidos.',
    ],
    [
        'id' => 'tar_009',
        'created_at' => $lastWeek,
        'updated_at' => $yesterday,
        'titulo' => 'Desenvolver checkout simplificado em uma única tela',
        'projeto_id' => 'pro_005',
        'prioridade' => 'Média',
        'status' => 'A Fazer',
        'prazo' => '2026-10-15',
        'descricao' => 'Redução de fricção na finalização da compra pelo celular.',
    ],
    [
        'id' => 'tar_010',
        'created_at' => $lastMonth,
        'updated_at' => $lastMonth,
        'titulo' => 'Indexação de busca de produtos por múltiplos filtros',
        'projeto_id' => 'pro_006',
        'prioridade' => 'Baixa',
        'status' => 'Concluído',
        'prazo' => '2026-09-02',
        'descricao' => 'Otimização das tabelas relacionais de estoque e categorias.',
    ],
    [
        'id' => 'tar_011',
        'created_at' => $lastMonth,
        'updated_at' => $yesterday,
        'titulo' => 'Módulo de correção automática de simulados online',
        'projeto_id' => 'pro_007',
        'prioridade' => 'Média',
        'status' => 'Em Andamento',
        'prazo' => '2026-10-25',
        'descricao' => 'Algoritmo de cálculo de notas com feedback imediato para os alunos.',
    ],
    [
        'id' => 'tar_012',
        'created_at' => $lastMonth,
        'updated_at' => $lastMonth,
        'titulo' => 'Mapear KPIs da diretoria operacional',
        'projeto_id' => 'pro_008',
        'prioridade' => 'Baixa',
        'status' => 'Cancelado',
        'prazo' => '2026-08-30',
        'descricao' => 'Escopo substituído por nova ferramenta analítica externa.',
    ],
];
$storage->write('tarefas.csv', $tarefasHeaders, $tarefas);

// -----------------------------------------------------------------------------
// 5. EQUIPAMENTOS (equipamentos.csv)
// -----------------------------------------------------------------------------
echo "[5/7] Populando Módulo: Equipamentos de TI (8 registros)...\n";
$equipamentosHeaders = ['id', 'created_at', 'updated_at', 'patrimonio', 'nome', 'categoria', 'fabricante', 'modelo', 'ativo', 'observacoes'];
$equipamentos = [
    [
        'id' => 'eqp_001',
        'created_at' => $lastMonth,
        'updated_at' => $yesterday,
        'patrimonio' => 'PAT-1001',
        'nome' => 'Notebook Dell Latitude 5420',
        'categoria' => 'Notebook',
        'fabricante' => 'Dell',
        'modelo' => 'Latitude 5420 Core i7 16GB 512GB SSD',
        'ativo' => '1',
        'observacoes' => 'Equipamento de desenvolvimento com histórico completo de manutenções.',
    ],
    [
        'id' => 'eqp_002',
        'created_at' => $lastMonth,
        'updated_at' => $lastWeek,
        'patrimonio' => 'PAT-1002',
        'nome' => 'Notebook Lenovo ThinkPad T14 Gen 3',
        'categoria' => 'Notebook',
        'fabricante' => 'Lenovo',
        'modelo' => 'ThinkPad T14 AMD Ryzen 7 PRO 32GB',
        'ativo' => '1',
        'observacoes' => 'Alocado com a coordenação de projetos.',
    ],
    [
        'id' => 'eqp_003',
        'created_at' => $lastMonth,
        'updated_at' => $yesterday,
        'patrimonio' => 'PAT-1003',
        'nome' => 'Servidor Dell PowerEdge R740',
        'categoria' => 'Servidor',
        'fabricante' => 'Dell',
        'modelo' => 'PowerEdge R740 2x Intel Xeon Gold 128GB',
        'ativo' => '1',
        'observacoes' => 'Servidor de virtualização e banco de dados de homologação.',
    ],
    [
        'id' => 'eqp_004',
        'created_at' => $lastMonth,
        'updated_at' => $lastWeek,
        'patrimonio' => 'PAT-1004',
        'nome' => 'Switch Gerenciável Cisco Catalyst 2960X',
        'categoria' => 'Rede / Switch',
        'fabricante' => 'Cisco',
        'modelo' => 'WS-C2960X-48FPS-L Gigabit PoE+',
        'ativo' => '1',
        'observacoes' => 'Switch central do rack principal do datacenter.',
    ],
    [
        'id' => 'eqp_005',
        'created_at' => $lastMonth,
        'updated_at' => $lastMonth,
        'patrimonio' => 'PAT-1005',
        'nome' => 'Monitor Dell UltraSharp 27" 4K',
        'categoria' => 'Monitor',
        'fabricante' => 'Dell',
        'modelo' => 'UltraSharp U2723QE IPS Black USB-C',
        'ativo' => '1',
        'observacoes' => 'Estação de design e testes de acessibilidade visual.',
    ],
    [
        'id' => 'eqp_006',
        'created_at' => $lastMonth,
        'updated_at' => $lastWeek,
        'patrimonio' => 'PAT-1006',
        'nome' => 'Impressora Multifuncional HP LaserJet Pro',
        'categoria' => 'Impressora',
        'fabricante' => 'HP',
        'modelo' => 'LaserJet Pro M428fdw Wireless duplex',
        'ativo' => '1',
        'observacoes' => 'Impressora departamental da recepção e suporte administrativo.',
    ],
    [
        'id' => 'eqp_007',
        'created_at' => $lastMonth,
        'updated_at' => $yesterday,
        'patrimonio' => 'PAT-1007',
        'nome' => 'Desktop Dell OptiPlex 7090 Micro',
        'categoria' => 'Desktop',
        'fabricante' => 'Dell',
        'modelo' => 'OptiPlex 7090 Micro Core i5 16GB',
        'ativo' => '1',
        'observacoes' => 'Terminal de atendimento da tesouraria.',
    ],
    [
        'id' => 'eqp_008',
        'created_at' => $lastMonth,
        'updated_at' => $lastMonth,
        'patrimonio' => 'PAT-1008',
        'nome' => 'Roteador Mikrotik Cloud Router CCR1009',
        'categoria' => 'Rede / Switch',
        'fabricante' => 'Mikrotik',
        'modelo' => 'CCR1009-7G-1C-1S+ 9 Cores',
        'ativo' => '0',
        'observacoes' => 'Equipamento reserva de contingência (sem manutenções vinculadas, permite teste de exclusão).',
    ],
];
$storage->write('equipamentos.csv', $equipamentosHeaders, $equipamentos);

// -----------------------------------------------------------------------------
// 6. MANUTENÇÕES (manutencoes.csv)
// -----------------------------------------------------------------------------
echo "[6/7] Populando Módulo: Manutenções (8 registros vinculados a Equipamentos)...\n";
$manutencoesHeaders = ['id', 'created_at', 'updated_at', 'equipamento_id', 'tipo', 'tecnico', 'data_servico', 'custo', 'concluido', 'observacoes'];
$manutencoes = [
    [
        'id' => 'man_001',
        'created_at' => $lastMonth,
        'updated_at' => $lastMonth,
        'equipamento_id' => 'eqp_001',
        'tipo' => 'Preventiva',
        'tecnico' => 'Lucas Mendes',
        'data_servico' => '2026-08-15',
        'custo' => '180',
        'concluido' => '1',
        'observacoes' => 'Desmontagem, desobstrução de dutos de ventilação e substituição de pasta térmica por prata.',
    ],
    [
        'id' => 'man_002',
        'created_at' => $lastWeek,
        'updated_at' => $lastWeek,
        'equipamento_id' => 'eqp_001',
        'tipo' => 'Upgrade / Expansão',
        'tecnico' => 'Lucas Mendes',
        'data_servico' => '2026-09-05',
        'custo' => '450',
        'concluido' => '1',
        'observacoes' => 'Instalação de pente adicional de 16GB DDR4 3200MHz para execução de contêineres pesados.',
    ],
    [
        'id' => 'man_003',
        'created_at' => $lastMonth,
        'updated_at' => $lastMonth,
        'equipamento_id' => 'eqp_002',
        'tipo' => 'Preventiva',
        'tecnico' => 'Amanda Souza',
        'data_servico' => '2026-08-22',
        'custo' => '150',
        'concluido' => '1',
        'observacoes' => 'Diagnóstico de bateria, limpeza do teclado retroiluminado e calibração do trackpoint.',
    ],
    [
        'id' => 'man_004',
        'created_at' => $lastMonth,
        'updated_at' => $lastMonth,
        'equipamento_id' => 'eqp_003',
        'tipo' => 'Preventiva',
        'tecnico' => 'Fernando Reis',
        'data_servico' => '2026-07-20',
        'custo' => '850',
        'concluido' => '1',
        'observacoes' => 'Manutenção semestral preventiva de fontes redundantes e ventilação do chassi.',
    ],
    [
        'id' => 'man_005',
        'created_at' => $lastWeek,
        'updated_at' => $lastWeek,
        'equipamento_id' => 'eqp_003',
        'tipo' => 'Upgrade / Expansão',
        'tecnico' => 'Fernando Reis',
        'data_servico' => '2026-09-02',
        'custo' => '2200',
        'concluido' => '1',
        'observacoes' => 'Adição de 2 discos SAS de 2.4TB 10K RPM no arranjo RAID-10 existente.',
    ],
    [
        'id' => 'man_006',
        'created_at' => $lastMonth,
        'updated_at' => $lastMonth,
        'equipamento_id' => 'eqp_004',
        'tipo' => 'Corretiva',
        'tecnico' => 'Roberto Carlos',
        'data_servico' => '2026-08-10',
        'custo' => '320',
        'concluido' => '1',
        'observacoes' => 'Substituição de módulo de porta PoE avariada após tempestade elétrica e atualização do Cisco IOS.',
    ],
    [
        'id' => 'man_007',
        'created_at' => $lastWeek,
        'updated_at' => $lastWeek,
        'equipamento_id' => 'eqp_006',
        'tipo' => 'Limpeza / Calibração',
        'tecnico' => 'Amanda Souza',
        'data_servico' => '2026-09-11',
        'custo' => '190',
        'concluido' => '1',
        'observacoes' => 'Substituição dos roletes de tração do alimentador ADF e limpeza do espelho óptico.',
    ],
    [
        'id' => 'man_008',
        'created_at' => $yesterday,
        'updated_at' => $now,
        'equipamento_id' => 'eqp_007',
        'tipo' => 'Corretiva',
        'tecnico' => 'Lucas Mendes',
        'data_servico' => '2026-09-17',
        'custo' => '280',
        'concluido' => '0',
        'observacoes' => 'Investigação de tela azul (BSOD) intermitente; aguardando liberação do usuário para reinstalação do SO.',
    ],
];
$storage->write('manutencoes.csv', $manutencoesHeaders, $manutencoes);

// -----------------------------------------------------------------------------
// 7. AUDITORIA E BACKUP DE DEMONSTRAÇÃO
// -----------------------------------------------------------------------------
echo "[7/7] Gerando Histórico de Auditoria e Snapshot de Backup...\n";
$auditHeaders = ['id', 'created_at', 'user_id', 'action', 'details'];
$auditRows = [
    ['id' => 'aud_20260818100000_a001', 'created_at' => $lastMonth, 'user_id' => 'usr_001', 'action' => 'setup_completed', 'details' => 'Instalação inicial do framework'],
    ['id' => 'aud_20260818100500_a002', 'created_at' => $lastMonth, 'user_id' => 'usr_001', 'action' => 'module_created', 'details' => 'slug=clientes'],
    ['id' => 'aud_20260818101000_a003', 'created_at' => $lastMonth, 'user_id' => 'usr_001', 'action' => 'module_created', 'details' => 'slug=projetos'],
    ['id' => 'aud_20260818101500_a004', 'created_at' => $lastMonth, 'user_id' => 'usr_001', 'action' => 'module_created', 'details' => 'slug=tarefas'],
    ['id' => 'aud_20260818102000_a005', 'created_at' => $lastMonth, 'user_id' => 'usr_001', 'action' => 'module_created', 'details' => 'slug=equipamentos'],
    ['id' => 'aud_20260818102500_a006', 'created_at' => $lastMonth, 'user_id' => 'usr_001', 'action' => 'module_created', 'details' => 'slug=manutencoes'],
    ['id' => 'aud_20260901083000_a007', 'created_at' => $lastWeek, 'user_id' => 'usr_002', 'action' => 'login', 'details' => 'Administrador conectado via web'],
    ['id' => 'aud_20260901091500_a008', 'created_at' => $lastWeek, 'user_id' => 'usr_002', 'action' => 'user_created', 'details' => 'Novo operador criado: carlos'],
    ['id' => 'aud_20260910142000_a009', 'created_at' => $yesterday, 'user_id' => 'usr_003', 'action' => 'login', 'details' => 'Operador Carlos iniciou turno'],
    ['id' => 'aud_20260918080000_a010', 'created_at' => $now, 'user_id' => 'usr_001', 'action' => 'backup_created', 'details' => 'Snapshot inicial da base populada'],
];
$storage->write('audit_log.csv', $auditHeaders, $auditRows);

// Cria um snapshot de backup inicial em storage/backups/
$app = new \App\Core\Application($config, $storage, new \App\Core\Request());
$backupService = new BackupService($app);
$backupId = $backupService->create('carga_inicial_demonstracao', 'usr_001');

echo "\n" . str_repeat('=', 78) . "\n";
echo "  BANCO DE DADOS POPULADO COM SUCESSO!\n";
echo str_repeat('=', 78) . "\n\n";

echo " Resumo dos Dados Carregados:\n";
echo "  * Usuários: " . count($users) . " registros (dev, admin, carlos, mariana, inativo)\n";
echo "  * Clientes: " . count($clientes) . " registros (Ativos, Prospect, Em Implantação e Inativo)\n";
echo "  * Projetos: " . count($projetos) . " registros (vinculados a Clientes com on_delete: restrict)\n";
echo "  * Tarefas: " . count($tarefas) . " registros (vinculadas a Projetos com on_delete: cascade)\n";
echo "  * Equipamentos: " . count($equipamentos) . " registros (Notebooks, Servidores, Switches, etc.)\n";
echo "  * Manutenções: " . count($manutencoes) . " registros (com custos, técnicos e histórico)\n";
echo "  * Auditoria: " . count($auditRows) . " eventos registrados em storage/logs/audit_log.csv\n";
echo "  * Snapshot de Backup: " . $backupId . " gerado em storage/backups/\n\n";

echo " Contas de Acesso Prontas para Testes:\n";
echo "  ----------------------------------------------------------------------------\n";
echo "  Perfil          | Usuário   | Senha        | Papel       | Observação\n";
echo "  ----------------------------------------------------------------------------\n";
echo "  Desenvolvedor   | dev       | (sua senha)  | role_dev    | Acesso total + Dev-End\n";
echo "  Administrador   | admin     | admin123456  | role_admin  | Gestão + CRUDs completos\n";
echo "  Usuário Padrão  | carlos    | user123456   | role_user   | Visão geral (somente leitura)\n";
echo "  Usuário Padrão  | mariana   | user123456   | role_user   | Visão geral (somente leitura)\n";
echo "  Desativado      | inativo   | user123456   | role_user   | Bloqueio ativo de login\n";
echo "  ----------------------------------------------------------------------------\n\n";

echo " Cenários de Teste Habilitados:\n";
echo "  1. Visão 360° reversa: Abra o Cliente 'TechLog' e veja seus projetos listados.\n";
echo "  2. Restrição de exclusão: Tente excluir o Cliente 'TechLog' (bloqueado por restrict).\n";
echo "  3. Exclusão permitida: Tente excluir o Cliente 'Prime Consultoria' (sem projetos).\n";
echo "  4. Exclusão em cascata: Abra o Projeto 'Portal de Telemetria' (possui 3 tarefas).\n";
echo "  5. Filtros por relação: Na listagem de Projetos, clique no badge do Cliente.\n";
echo "  6. Dev-End: Acesse /dev/backups e /dev/logs para ver o histórico e o snapshot.\n";
echo "  7. RBAC: Faça login com 'carlos' para verificar a interface com permissões restritas.\n";
echo str_repeat('=', 78) . "\n\n";
