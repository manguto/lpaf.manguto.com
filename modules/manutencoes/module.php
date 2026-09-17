<?php

declare(strict_types=1);

return [
    'name' => 'Manutenções',
    'entity' => 'Manutenção',
    'slug' => 'manutencoes',
    'icon' => '🔧',
    'description' => 'Ordens de serviço, manutenção preventiva, corretiva e melhorias de equipamentos.',
    'prefix' => 'man',
    'storage' => 'manutencoes.csv',
    'permission_prefix' => 'manutencoes',
    'fields' => [
        'equipamento_id' => [
            'label' => 'Equipamento',
            'type' => 'relation',
            'target' => 'equipamentos',
            'display' => 'nome',
            'required' => true,
            'unique' => false,
            'list' => true,
            'help' => 'Equipamento sob manutenção ou intervenção técnica.',
        ],
        'tipo' => [
            'label' => 'Tipo de Serviço',
            'type' => 'select',
            'options' => [
                'Preventiva',
                'Corretiva',
                'Upgrade / Expansão',
                'Limpeza / Calibração',
            ],
            'required' => true,
            'unique' => false,
            'list' => true,
        ],
        'tecnico' => [
            'label' => 'Técnico Responsável',
            'type' => 'string',
            'required' => true,
            'unique' => false,
            'list' => true,
        ],
        'data_servico' => [
            'label' => 'Data do Serviço',
            'type' => 'date',
            'required' => true,
            'unique' => false,
            'list' => true,
        ],
        'custo' => [
            'label' => 'Custo Total (R$)',
            'type' => 'number',
            'required' => false,
            'unique' => false,
            'list' => true,
        ],
        'concluido' => [
            'label' => 'Serviço Concluído',
            'type' => 'boolean',
            'required' => true,
            'unique' => false,
            'list' => true,
            'default' => true,
        ],
        'observacoes' => [
            'label' => 'Detalhes da Intervenção',
            'type' => 'text',
            'required' => false,
            'unique' => false,
            'list' => false,
        ],
    ],
];
