<?php

declare(strict_types=1);

return [
    'name' => 'Projetos',
    'entity' => 'Projeto',
    'slug' => 'projetos',
    'icon' => '📁',
    'description' => 'Controle e acompanhamento de projetos corporativos e iniciativas.',
    'prefix' => 'pro',
    'storage' => 'projetos.csv',
    'permission_prefix' => 'projetos',
    'fields' => [
        'nome' => [
            'label' => 'Nome do Projeto',
            'type' => 'string',
            'required' => true,
            'unique' => true,
            'list' => true,
        ],
        'cliente_id' => [
            'label' => 'Cliente Vinculado',
            'type' => 'relation',
            'target' => 'clientes',
            'display' => 'nome_fantasia',
            'required' => false,
            'unique' => false,
            'list' => true,
            'help' => 'Organização ou cliente atendido por este projeto.',
        ],
        'natureza' => [
            'label' => 'Natureza',
            'type' => 'select',
            'required' => true,
            'unique' => false,
            'list' => true,
            'options' => [
                'Gestão',
                'Desenvolvimento',
                'Suporte',
                'Infraestrutura',
                'Outro',
            ],
        ],
        'prioridade' => [
            'label' => 'Prioridade',
            'type' => 'select',
            'required' => true,
            'unique' => false,
            'list' => true,
            'options' => [
                'Alta',
                'Média',
                'Baixa',
                'Não definido',
            ],
        ],
        'descricao' => [
            'label' => 'Descrição',
            'type' => 'text',
            'required' => false,
            'unique' => false,
            'list' => false,
        ],
        'observacoes' => [
            'label' => 'Observações',
            'type' => 'string',
            'required' => false,
            'unique' => false,
            'list' => false,
        ],
        'ativo' => [
            'label' => 'Ativo',
            'type' => 'boolean',
            'required' => true,
            'unique' => false,
            'list' => true,
            'default' => true,
        ],
    ],
];
