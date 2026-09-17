<?php

declare(strict_types=1);

return [
    'name' => 'Equipamentos',
    'entity' => 'Equipamento',
    'slug' => 'equipamentos',
    'icon' => '💻',
    'description' => 'Controle de patrimônio, computadores e periféricos de TI.',
    'prefix' => 'eqp',
    'storage' => 'equipamentos.csv',
    'permission_prefix' => 'equipamentos',

    'fields' => [
        'patrimonio' => [
            'label' => 'Patrimônio / Tombo',
            'type' => 'string',
            'required' => true,
            'unique' => true,
            'list' => true,
            'help' => 'Código identificador único do equipamento (ex: PAT-0102).',
        ],
        'nome' => [
            'label' => 'Nome do Equipamento',
            'type' => 'string',
            'required' => true,
            'list' => true,
            'help' => 'Descrição principal do item (ex: Notebook Dell Latitude 5420).',
        ],
        'categoria' => [
            'label' => 'Categoria',
            'type' => 'select',
            'options' => [
                'Notebook',
                'Desktop',
                'Monitor',
                'Impressora',
                'Servidor',
                'Rede / Switch',
                'Periférico',
                'Outro',
            ],
            'required' => true,
            'list' => true,
        ],
        'fabricante' => [
            'label' => 'Fabricante',
            'type' => 'string',
            'required' => false,
            'list' => true,
        ],
        'modelo' => [
            'label' => 'Modelo',
            'type' => 'string',
            'required' => false,
            'list' => false,
        ],
        'ativo' => [
            'label' => 'Equipamento Ativo em Operação',
            'type' => 'boolean',
            'default' => true,
            'list' => true,
        ],
        'observacoes' => [
            'label' => 'Observações Adicionais',
            'type' => 'text',
            'required' => false,
            'list' => false,
        ],
    ],
];
