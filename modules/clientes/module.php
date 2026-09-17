<?php

declare(strict_types=1);

return [
    'name' => 'Clientes',
    'entity' => 'Cliente',
    'slug' => 'clientes',
    'icon' => '🏢',
    'description' => 'Cadastro e gestão de clientes, parceiros e organizações atendidas.',
    'prefix' => 'cli',
    'storage' => 'clientes.csv',
    'permission_prefix' => 'clientes',
    'fields' => [
        'razao_social' => [
            'label' => 'Razão Social',
            'type' => 'string',
            'required' => true,
            'unique' => true,
            'list' => true,
            'help' => 'Razão social oficial da empresa ou organização.',
        ],
        'nome_fantasia' => [
            'label' => 'Nome Fantasia',
            'type' => 'string',
            'required' => true,
            'unique' => false,
            'list' => true,
            'help' => 'Nome comercial ou de exibição rápida.',
        ],
        'cnpj' => [
            'label' => 'CNPJ / CPF',
            'type' => 'string',
            'required' => false,
            'unique' => true,
            'list' => true,
            'help' => 'Documento de identificação fiscal.',
        ],
        'cidade' => [
            'label' => 'Cidade / UF',
            'type' => 'string',
            'required' => false,
            'unique' => false,
            'list' => true,
        ],
        'status' => [
            'label' => 'Status da Conta',
            'type' => 'select',
            'options' => [
                'Ativo',
                'Prospect',
                'Em Implantação',
                'Inativo',
            ],
            'required' => true,
            'unique' => false,
            'list' => true,
        ],
        'observacoes' => [
            'label' => 'Observações',
            'type' => 'text',
            'required' => false,
            'unique' => false,
            'list' => false,
        ],
    ],
];
