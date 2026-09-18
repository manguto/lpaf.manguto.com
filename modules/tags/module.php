<?php

declare(strict_types=1);

return [
    'name' => 'Etiquetas',
    'entity' => 'Etiqueta',
    'slug' => 'tags',
    'icon' => '🏷️',
    'description' => 'Etiquetas temáticas e marcadores promocionais para produtos.',
    'prefix' => 'tag',
    'storage' => 'tags.csv',
    'permission_prefix' => 'tags',
    'fields' => [
        'nome' => [
            'label' => 'Nome da Etiqueta',
            'type' => 'string',
            'required' => true,
            'unique' => true,
            'list' => true,
            'help' => 'Identificador da etiqueta (ex: Oferta, Lançamento, Frete Grátis).',
        ],
        'cor' => [
            'label' => 'Cor Visual',
            'type' => 'select',
            'options' => [
                'Azul (Destaque)',
                'Verde (Sucesso / Frete Grátis)',
                'Amarelo (Atenção / Limitado)',
                'Vermelho (Super Oferta)',
                'Roxo (Exclusivo / Premium)',
                'Cinza (Neutro)',
            ],
            'required' => true,
            'unique' => false,
            'list' => true,
        ],
        'descricao' => [
            'label' => 'Descrição',
            'type' => 'text',
            'required' => false,
            'unique' => false,
            'list' => false,
            'help' => 'Finalidade e regras de aplicação desta etiqueta.',
        ],
    ],
];
