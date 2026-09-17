<?php

declare(strict_types=1);

return array (
  'name' => 'Projetos',
  'entity' => 'Projeto',
  'slug' => 'projetos',
  'icon' => '📁',
  'description' => 'Controle de projetos',
  'prefix' => 'pro',
  'storage' => 'projetos.csv',
  'permission_prefix' => 'projetos',
  'fields' => 
  array (
    'nome' => 
    array (
      'label' => 'Nome',
      'type' => 'string',
      'required' => true,
      'unique' => true,
      'list' => true,
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
        3 => 'Outro',
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
