<?php

declare(strict_types=1);

return [
    'name' => 'Clientes',
    'entity' => 'Cliente',
    'slug' => 'clientes',
    'icon' => '👥',
    'description' => 'Cadastro e gestão de clientes, compradores e parceiros comerciais.',
    'prefix' => 'cli',
    'storage' => 'clientes.csv',
    'permission_prefix' => 'clientes',
    'fields' => [
        'nome' => [
            'label' => 'Nome Completo / Razão Social',
            'type' => 'string',
            'required' => true,
            'unique' => false,
            'list' => true,
            'help' => 'Nome do cliente ou empresa.',
        ],
        'email' => [
            'label' => 'E-mail',
            'type' => 'string',
            'required' => true,
            'unique' => true,
            'list' => true,
            'help' => 'Endereço de e-mail para contato e notificações.',
        ],
        'telefone' => [
            'label' => 'Telefone / WhatsApp',
            'type' => 'string',
            'required' => false,
            'unique' => false,
            'list' => true,
            'help' => 'Telefone com DDD ou número do WhatsApp.',
        ],
        'cidade' => [
            'label' => 'Cidade / UF',
            'type' => 'string',
            'required' => false,
            'unique' => false,
            'list' => true,
            'help' => 'Cidade e estado de localização.',
        ],
        'status' => [
            'label' => 'Status do Cliente',
            'type' => 'select',
            'options' => [
                'Ativo',
                'Potencial (Lead)',
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
            'help' => 'Anotações gerais sobre o perfil de compras do cliente.',
        ],
    ],
];
