<?php

declare(strict_types=1);

return array (
  'name' => 'Projetos',
  'entity' => 'Projeto',
  'slug' => 'projetos',
  'icon' => '📌',
  'description' => 'Controle e acompanhamento de projetos corporativos e iniciativas.',
  'prefix' => 'pro',
  'storage' => 'projetos.csv',
  'permission_prefix' => 'projetos',
  'fields' => 
  array (
    'nome' => 
    array (
      'label' => 'Nome do Projeto',
      'type' => 'string',
      'required' => true,
      'unique' => true,
      'list' => true,
    ),
    'cliente_id' => 
    array (
      'label' => 'Cliente Vinculado',
      'type' => 'relation',
      'required' => false,
      'unique' => false,
      'list' => true,
      'target' => 'clientes',
      'display' => 'nome_fantasia',
      'on_delete' => 'restrict',
    ),
    'natureza' => 
    array (
      'label' => 'Natureza',
      'type' => 'select',
      'required' => true,
      'unique' => false,
      'list' => true,
      'options' => 
      array (
        0 => 'Gestão',
        1 => 'Desenvolvimento',
        2 => 'Suporte',
        3 => 'Infraestrutura',
        4 => 'Outro',
      ),
    ),
    'prioridade' => 
    array (
      'label' => 'Prioridade',
      'type' => 'select',
      'required' => true,
      'unique' => false,
      'list' => true,
      'options' => 
      array (
        0 => 'Alta',
        1 => 'Média',
        2 => 'Baixa',
        3 => 'Não definido',
      ),
    ),
    'descricao' => 
    array (
      'label' => 'Descrição',
      'type' => 'text',
      'required' => false,
      'unique' => false,
      'list' => false,
    ),
    'observacoes' => 
    array (
      'label' => 'Observações',
      'type' => 'string',
      'required' => false,
      'unique' => false,
      'list' => false,
    ),
    'equipamentos' => 
    array (
      'label' => 'Equipamentos Alocados',
      'type' => 'many_to_many',
      'required' => false,
      'unique' => false,
      'list' => true,
      'target' => 'equipamentos',
      'display' => 'nome',
      'pivot_file' => 'projeto_equipamentos.csv',
      'parent_key' => 'projeto_id',
      'target_key' => 'equipamento_id',
    ),
    'ativo' => 
    array (
      'label' => 'Ativo',
      'type' => 'boolean',
      'required' => true,
      'unique' => false,
      'list' => true,
      'default' => true,
    ),
  ),
);
