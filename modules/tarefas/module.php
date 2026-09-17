<?php

declare(strict_types=1);

return [
    'name' => 'Tarefas',
    'entity' => 'Tarefa',
    'slug' => 'tarefas',
    'icon' => '📋',
    'description' => 'Controle de atividades, prazos e entregáveis dos projetos corporativos.',
    'prefix' => 'tar',
    'storage' => 'tarefas.csv',
    'permission_prefix' => 'tarefas',
    'fields' => [
        'titulo' => [
            'label' => 'Título da Tarefa',
            'type' => 'string',
            'required' => true,
            'unique' => false,
            'list' => true,
            'help' => 'Resumo claro da entrega ou ação a ser executada.',
        ],
        'projeto_id' => [
            'label' => 'Projeto Vinculado',
            'type' => 'relation',
            'target' => 'projetos',
            'display' => 'nome',
            'required' => true,
            'unique' => false,
            'list' => true,
            'help' => 'Projeto ao qual esta tarefa pertence.',
        ],
        'prioridade' => [
            'label' => 'Prioridade',
            'type' => 'select',
            'options' => [
                'Baixa',
                'Média',
                'Alta',
                'Urgente',
            ],
            'required' => true,
            'unique' => false,
            'list' => true,
        ],
        'status' => [
            'label' => 'Status',
            'type' => 'select',
            'options' => [
                'A Fazer',
                'Em Andamento',
                'Concluído',
                'Cancelado',
            ],
            'required' => true,
            'unique' => false,
            'list' => true,
        ],
        'prazo' => [
            'label' => 'Prazo de Entrega',
            'type' => 'date',
            'required' => false,
            'unique' => false,
            'list' => true,
        ],
        'descricao' => [
            'label' => 'Descrição Detalhada',
            'type' => 'text',
            'required' => false,
            'unique' => false,
            'list' => false,
        ],
    ],
];
