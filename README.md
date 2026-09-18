# PHP Admin Framework

Framework administrativo leve em PHP para criação de pequenos sistemas internos, com autenticação, controle de acesso baseado em perfis e permissões, armazenamento em CSV e um ambiente integrado de desenvolvimento denominado **Dev-End**.

> Projeto em estágio inicial de desenvolvimento.

## 1. Objetivo

Este projeto tem como objetivo fornecer uma base reutilizável para o desenvolvimento rápido de pequenos sistemas administrativos em PHP.

O foco principal é atender aplicações com:

* poucos usuários;
* utilização esporádica;
* baixo volume de dados;
* necessidade de instalação simples;
* facilidade de manutenção;
* dados facilmente inspecionáveis;
* controle de acesso;
* possibilidade de expansão modular.

O projeto não pretende competir com frameworks completos como Laravel ou Symfony.

A proposta é oferecer uma estrutura intermediária entre PHP puro sem organização e frameworks de grande porte.

## 2. Público-alvo

A arquitetura foi pensada principalmente para:

* pequenos sistemas administrativos;
* aplicações internas;
* ferramentas departamentais;
* sistemas utilizados por pequenas equipes;
* protótipos funcionais;
* aplicações com aproximadamente até algumas dezenas de usuários e baixa concorrência.

O cenário inicial considerado é de aproximadamente 10 usuários, com utilização esporádica.

## 3. Princípios

O projeto deverá seguir os seguintes princípios:

1. Simplicidade antes de sofisticação.
2. Segurança desde a estrutura inicial.
3. Código compreensível e facilmente modificável.
4. Poucas dependências externas.
5. Separação clara de responsabilidades.
6. Uso de padrões conhecidos do ecossistema PHP.
7. Baixo acoplamento entre regras de negócio e persistência.
8. Dados administrativos legíveis diretamente.
9. Possibilidade de evolução sem reescrita integral.
10. Não superdimensionar a arquitetura para necessidades inexistentes.
11. Documentação viva e sincronizada: todo e qualquer ajuste técnico, refatoração ou acréscimo que comprometa a corretude ou completude deste README deve ser obrigatoriamente atualizado no mesmo.

## 4. Tecnologias

Base prevista:

* PHP 8.2 ou superior;
* Composer;
* PSR-4 para autoload;
* PSR-12 para estilo de código;
* HTML5;
* CSS;
* JavaScript puro;
* arquivos CSV para persistência inicial;
* sessões PHP para autenticação.

Dependências externas deverão ser utilizadas somente quando houver benefício concreto.

## 5. Arquitetura

O projeto utilizará uma arquitetura inspirada em MVC, complementada por Service, Repository e Middleware.

Fluxo principal:

```text
Request
   ↓
Front Controller
   ↓
Router
   ↓
Middleware
   ↓
Controller
   ↓
Service (quando necessário)
   ↓
Repository
   ↓
Storage
   ↓
CSV
```

Resposta:

```text
Controller
   ↓
View
   ↓
Response
```

Services não precisam existir para operações triviais.

Uma operação simples poderá seguir:

```text
Controller
   ↓
Repository
   ↓
CsvStorage
```

Regras de negócio relevantes deverão utilizar Service.

## 6. Estrutura prevista

```text
/
├── app/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Services/
│   ├── Repositories/
│   ├── Core/ (Application, Config, Preflight, CsvStorage, Router, etc.)
│   └── Helpers/
│
├── config/
│
├── modules/
│
├── routes/
│   ├── web.php
│   ├── admin.php
│   └── dev.php
│
├── storage/
│   ├── data/
│   ├── logs/
│   ├── backups/
│   └── tmp/
│
├── views/
│   ├── layouts/
│   ├── public/
│   ├── auth/
│   ├── app/
│   ├── admin/
│   ├── dev/
│   └── errors/
│
├── public/
│   ├── index.php
│   └── assets/
│       ├── css/
│       ├── js/
│       └── img/
│
├── scripts/
│   └── seed.php
│
├── tests/
│
├── .env
├── .env.example
├── .gitignore
├── composer.json
└── README.md
```

A estrutura poderá evoluir quando houver justificativa técnica, mantendo os princípios deste documento.

## 7. Front Controller

Todas as requisições da aplicação deverão passar por:

```text
public/index.php
```

O diretório público do servidor web deverá apontar para `public/`.

Como primeira instrução de inicialização, o `public/index.php` aciona a camada de **Preflight** (`App\Core\Preflight::check()`), verificando a existência das dependências e a sanidade do ambiente antes de invocar o autoloader ou qualquer outro componente da aplicação.

Arquivos internos da aplicação e arquivos CSV nunca deverão ser disponibilizados diretamente pela web.

## 8. Roteamento

O projeto deverá possuir roteamento centralizado.

Exemplo conceitual:

```php
$router->get('/', [HomeController::class, 'index']);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);

$router->get('/app', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->permission('dashboard.view');

$router->get('/admin/users', [UserController::class, 'index'])
    ->middleware('auth')
    ->permission('users.view');

$router->get('/dev', [DevController::class, 'index'])
    ->middleware('auth')
    ->permission('dev.access');
```

As rotas serão organizadas inicialmente em:

```text
routes/web.php
routes/admin.php
routes/dev.php
```

## 9. Métodos HTTP

Convenção:

```text
GET     leitura
POST    criação ou execução de ação
PUT     atualização
PATCH   atualização parcial
DELETE  exclusão
```

Como formulários HTML suportam diretamente apenas GET e POST, poderá ser utilizado method spoofing:

```html
<input type="hidden" name="_method" value="DELETE">
```

O Router deverá interpretar esse mecanismo.

## 10. Controllers

Controllers deverão ser pequenos.

Responsabilidades:

* receber a requisição;
* validar os aspectos relacionados ao HTTP;
* chamar Repository ou Service;
* selecionar uma View;
* produzir uma Response.

Controllers não deverão:

* abrir arquivos CSV diretamente;
* implementar autenticação;
* implementar autorização manual;
* concentrar regras complexas de negócio;
* produzir SQL futuramente;
* conhecer detalhes físicos da persistência.

## 11. Services

Services deverão concentrar regras de negócio quando elas existirem.

Exemplo:

```text
UserController
      ↓
UserService
      ↓
UserRepository
```

Nem toda operação precisa obrigatoriamente de um Service.

Evitar classes vazias ou camadas criadas apenas por formalidade.

## 12. Repositories

Repositories deverão representar o acesso lógico aos dados.

Exemplo:

```php
$userRepository->findById($id);
$userRepository->findByUsername($username);
$userRepository->create($data);
$userRepository->update($id, $data);
```

O Repository não deverá conhecer:

* HTML;
* Views;
* sessões;
* requisições HTTP.

## 13. CsvStorage

A manipulação física dos arquivos CSV deverá ser centralizada.

Exemplo conceitual:

```text
UserRepository
      ↓
CsvStorage
      ↓
users.csv
```

Controllers e Services nunca deverão utilizar diretamente:

```php
fopen()
fgetcsv()
fputcsv()
```

A responsabilidade será do mecanismo de Storage.

## 14. Persistência CSV

Os dados deverão permanecer facilmente legíveis por pessoas.

Padrão:

* UTF-8;
* primeira linha contendo cabeçalho;
* `;` como delimitador;
* `fgetcsv()` para leitura;
* `fputcsv()` para escrita;
* nenhum array serializado em células;
* nenhum JSON armazenado dentro de células;
* relacionamentos muitos-para-muitos em arquivos próprios.

Arquivos iniciais previstos:

```text
storage/data/users.csv
storage/data/roles.csv
storage/data/permissions.csv
storage/data/user_roles.csv
storage/data/role_permissions.csv
storage/data/settings.csv
```

Logs poderão utilizar:

```text
storage/logs/audit_log.csv
```

## 15. Relacionamentos

Não utilizar listas de identificadores dentro de uma célula.

Errado:

```text
id;name;roles
usr_001;João;"role_user,role_admin"
```

Correto:

`users.csv`

```text
id;name
usr_001;João
```

`user_roles.csv`

```text
user_id;role_id
usr_001;role_user
usr_001;role_admin
```

### Relacionamentos 1:N Declarativos (Módulos & Entity Builder)

O sistema conta com um motor completo de **Relacionamentos 1:N (Chaves Estrangeiras)** entre entidades:

* **Armazenamento Seguro:** As entidades filhas gravam exclusivamente o identificador da entidade pai na coluna correspondente (ex: `cliente_id` armazenando `cli_001`), preservando a atomicidade e legibilidade direta do CSV;
* **Definição Declarativa:** Campos do tipo `relation` no `module.php` declaram o módulo de destino (`target`) e o campo a ser exibido como rótulo (`display`);
* **Seleção Visual no Entity Builder:** Ao configurar ou editar uma entidade no Dev-End, o tipo `Relação 1:N` permite vincular o campo a qualquer outro módulo existente através de menu seletor inteligente;
* **Formulários Dinâmicos:** As telas de criação e edição renderizam `<select>` populados dinamicamente com os registros da entidade pai (`Rótulo (ID)`) e oferecem atalho imediato para cadastro caso a entidade pai ainda esteja vazia;
* **Exibição nas Listagens e Detalhes:** As tabelas de listagem (`/app/{slug}`) e detalhes (`show`) substituem os códigos brutos pelos nomes dos registros vinculados, com links diretos para a entidade relacionada;
* **Sub-listagem Reversa na Visualização do Pai (Visão 360°):** Na visualização de um registro pai (`/app/<pai>/<id>`), o sistema descobre e renderiza automaticamente tabelas com todos os registros filhos vinculados (ex.: contratos do cliente, equipamentos do cliente), além de botão de atalho para cadastrar novos filhos já pré-vinculados;
* **Filtros Rápidos por Relação na Listagem (`index.php`):** A barra superior de pesquisa identifica campos relacionais e renderiza dinamicamente seletores `<select>` para filtragem imediata em 1 clique (com envio automático `onchange`), combinável com pesquisa textual, atalhos contextuais diretos (`🔍`) nas linhas da tabela e botão para limpar filtros;
* **Integridade Referencial Dupla:**
  - *No Salvamento:* Validação estrita impedindo o envio de chaves estrangeiras inexistentes;
  - *Na Exclusão:* Bloqueio ativo de exclusão de registros pai que possuam vínculos ativos em outros módulos, emitindo alerta amigável e prevenindo a geração de registros órfãos.

### Relacionamentos N:N com Tabelas Pivô Declarativas (Módulos & Entity Builder)

O sistema conta com suporte completo a **Relacionamentos N:N (Muitos para Muitos)** desacoplados e baseados em tabelas de junção (*pivot tables*):

* **Tabelas Pivô Dedicadas em CSV:** As associações são armazenadas exclusivamente em arquivos CSV intermediários (ex: `projeto_equipamentos.csv` ou padrão `{pai}_{destino}.csv`), estruturados com `id`, `created_at`, `{parent_key}` e `{target_key}`, mantendo os CSVs principais das entidades limpos e sem quebra da primeira forma normal;
* **Configuração Declarativa em `module.php`:** Definição simples via tipo `many_to_many`, indicando o módulo de destino (`target`), campo descritivo (`display`), arquivo pivô opcional (`pivot_file`), e chaves (`parent_key`, `target_key`);
* **Seleção Visual no Entity Builder:** O Dev-End permite selecionar o tipo `Relação N:N`, selecionar o módulo relacionado e opcionalmente personalizar o nome do arquivo pivô CSV;
* **Interface de Associação Confortável com Busca em Tempo Real (`form.php`):** Formulários de cadastro e edição renderizam um painel contrastado de cartões/checkboxes com filtro de pesquisa instantâneo via JavaScript e botões "Marcar Todos / Desmarcar Todos";
* **Listagem Inteligente (`index.php`):** Colunas N:N exibem badges dos primeiros itens associados acompanhados de contador cumulativo (`+N`) para preservar a densidade visual;
* **Detalhamento e Links Diretos (`show.php`):** Na tela de detalhes da entidade, os registros associados são renderizados como badges clicáveis com atalho imediato para o registro correspondente;
* **Visão 360° Reversa Bidirecional:** A visualização de qualquer entidade que participe como destino de um relacionamento N:N descobre automaticamente e exibe as entidades de origem vinculadas via tabela pivô;
* **Sincronização e Limpeza em Cascata Atômica:** O salvamento sincroniza atomicamente as adições e remoções de vínculos na tabela pivô com `flock()`, e ao excluir qualquer um dos lados do relacionamento, todas as linhas correspondentes na tabela pivô são expurgadas automaticamente, impedindo chaves órfãs.

### Evoluções planejadas para o motor de relacionamentos:

1. **Busca Assistida / Autocomplete em Relações:** Otimização com paginação e busca assíncrona para catálogos com centenas ou milhares de registros;
2. **Atributos Extras em Tabelas Pivô:** Suporte a metadados adicionais na linha de junção N:N (ex: quantidade, papel específico, data de início da alocação).

## 16. Identificadores

Preferir identificadores curtos e legíveis.

Exemplos:

```text
usr_001
usr_002

role_user
role_admin
role_dev
```

Os identificadores deverão ser estáveis.

Nomes, e-mails e descrições não deverão ser utilizados como chaves primárias.

## 17. Autenticação

A autenticação inicial será baseada em sessão PHP.

Utilizar obrigatoriamente:

```php
password_hash()
password_verify()
```

Nunca armazenar senhas em texto puro.

Após login bem-sucedido deverá ocorrer regeneração do ID da sessão.

## 18. Autorização

A autorização utilizará RBAC — Role-Based Access Control.

Modelo:

```text
Usuário
   ↓
Perfil
   ↓
Permissões
   ↓
Rotas / funcionalidades
```

As rotas deverão exigir permissões, e não nomes específicos de perfis.

Exemplo:

```text
users.manage
```

e não:

```text
role == admin
```

## 19. Perfis iniciais

O sistema deverá possuir inicialmente:

```text
Usuário
Administrador
Desenvolvedor
```

Outros perfis poderão ser cadastrados posteriormente.

## 20. Desenvolvedor

O perfil Desenvolvedor possui acesso integral à plataforma.

Somente um Desenvolvedor poderá:

* conceder perfil Desenvolvedor;
* remover perfil Desenvolvedor;
* acessar o Dev-End;
* realizar alterações estruturais;
* acessar ferramentas técnicas críticas.

O perfil Desenvolvedor deverá ser protegido contra alterações inadequadas.

## 21. Permissões

Convenção:

```text
recurso.acao
```

Exemplos:

```text
dashboard.view
profile.edit

users.view
users.create
users.edit
users.delete
users.manage

roles.view
roles.manage

settings.manage

audit.view

dev.access
dev.logs
dev.settings
```

O conjunto poderá crescer conforme novos módulos forem adicionados.

## 22. Áreas da aplicação

A plataforma possuirá três grandes contextos.

### Área pública

Não exige autenticação.

Exemplos:

```text
/
 /login
```

### Área restrita

Utilizada pelos usuários autenticados.

Exemplos:

```text
/app
/profile
```

O acesso dependerá das permissões do usuário.

### Área administrativa

Funcionalidades administrativas da aplicação.

Exemplo:

```text
/admin
/admin/users
/admin/roles
```

Também controlada por permissões.

## 23. Dev-End

**Dev-End** é o nome utilizado neste projeto para o ambiente estrutural reservado ao Desenvolvedor.

Exemplo:

```text
/dev
```

O Dev-End não é apenas uma tela de diagnóstico.

A intenção é que evolua para uma camada de metaprogramação/low-code da plataforma.

Funcionalidades previstas:

* diagnóstico do sistema;
* visualização de logs;
* backups;
* inspeção dos CSV;
* gerenciamento estrutural;
* gerenciamento de permissões;
* gerenciamento de módulos;
* gerenciamento de entidades;
* criação de entidades;
* criação automática de CRUDs;
* criação de campos;
* criação de rotas;
* criação de permissões;
* criação de menus;
* geração controlada de código.

## 24. Entity Builder

O **Entity Builder** é um assistente visual operacional dentro do Dev-End (`/dev/entity-builder`), permitindo a criação e edição declarativa de entidades administrativas diretamente pelo navegador, sem necessidade de escrita manual de código repetitivo.

Recursos implementados:

* **Criação e Edição de Entidades:** Formulário visual unificado para definição e alteração de nome, entidade no singular, slug (imutável na edição), prefixo de ID (imutável na edição), ícone/emoji e descrição da funcionalidade;
* **Construtor dinâmico de campos:** Suporte a tipos de dados fundamentais: `string` (texto curto), `text` (texto longo), `number` (número), `date` (data), `select` (múltiplas opções separadas por vírgula) e `boolean` (ativo / sim-não);
* **Controles granulares por campo:** Configuração visual de obrigatoriedade, unicidade e visibilidade na tabela de listagem principal;
* **Reordenação Flexível de Campos:** Ajuste dinâmico da ordem dos campos via botões intuitivos (▲ / ▼) e recurso drag-and-drop com reindexação automática, permitindo posicionar novos ou existentes campos em qualquer ordem (ex.: torná-los o segundo item a ser preenchido), com sincronização imediata na renderização dos formulários, listagens e cabeçalho do CSV;
* **Expansão de Schema com Preservação de Dados:** Ao adicionar ou reorganizar campos em uma entidade existente (`/dev/modules/{slug}/edit`), todos os registros previamente gravados em `storage/data/<slug>.csv` são preservados integralmente, com atualização atômica do cabeçalho de colunas;
* **Salvaguardas automáticas de segurança:** Geração de backups instantâneos automáticos com snapshots completos antes de qualquer alteração física (`pre_entity_create_*`, `pre_entity_edit_*` e `pre_entity_delete_*`);
* **Proteção contra sobrescrita:** Validação de colisões de slug contra módulos existentes e rotas reservadas do sistema (`admin`, `app`, `dev`, `login`, etc.);
* **Persistência declarativa:** Geração atômica da configuração do módulo em `modules/<slug>/module.php` e criação/manutenção do armazenamento CSV em `storage/data/<slug>.csv`;
* **Sincronização imediata de permissões:** Atualização dinâmica das permissões RBAC (`{slug}.view`, `{slug}.create`, `{slug}.edit`, `{slug}.delete`) e concessão automática ao perfil de administrador (`role_admin`);
* **Gestão e Exclusão Segura:** Painel de controle em `/dev/modules` com acesso direto à edição da entidade, cadastro rápido, exploração do módulo e exclusão assistida com confirmação e salvaguarda;
* **Auditoria Completa:** Registro rastreável de todas as operações (`module_created`, `module_updated`, `module_deleted`) contendo o ID do usuário responsável e metadados no log de auditoria.

## 25. Desenvolvimento declarativo

CRUDs simples deverão, sempre que possível, ser definidos por metadados em vez de código repetitivo.

Conceitualmente:

```text
Definição da entidade
        ↓
Motor CRUD
        ↓
Listagem
Cadastro
Edição
Validação
Permissões
CSV
```

Exemplo conceitual de definição:

```php
return [
    'entity' => 'Equipment',
    'slug' => 'equipamentos',

    'fields' => [
        'patrimonio' => [
            'type' => 'string',
            'required' => true,
            'unique' => true,
        ],

        'nome' => [
            'type' => 'string',
            'required' => true,
        ],

        'ativo' => [
            'type' => 'boolean',
            'default' => true,
        ],
    ],
];
```

## 26. CRUD genérico

A arquitetura poderá possuir componentes como:

```text
GenericCrudController
GenericRepository
GenericFormRenderer
GenericTableRenderer
EntityDefinition
```

CRUDs simples poderão funcionar exclusivamente por metadados.

Regras especiais deverão permanecer implementáveis por código PHP.

Princípio:

```text
CRUD comum
→ configuração

Regra especial
→ código PHP
```

## 27. Módulos

O sistema possui um motor de módulos isolados totalmente operacional.

Estrutura implementada:

```text
modules/
├── equipamentos/
│   └── module.php
│
└── ...
```

Cada módulo define sua estrutura em `modules/<slug>/module.php`:

* `name`: nome de exibição do módulo;
* `entity`: nome singular da entidade;
* `slug`: identificador na URL (`/app/<slug>`);
* `icon`: ícone de exibição no painel;
* `description`: descrição resumida da funcionalidade;
* `prefix`: prefixo dos IDs gerados (`eqp_001`, `eqp_002`, etc.);
* `storage`: arquivo de persistência em `storage/data/<slug>.csv`;
* `fields`: array associativo definindo tipo (`string`, `text`, `number`, `select`, `boolean`, `date`), obrigatoriedade, unicidade, exibição em listagem e textos de ajuda.

O `ModuleManager` descobre os módulos em tempo de execução, registra automaticamente as rotas RESTful pelo `GenericCrudController`, assegura as permissões no RBAC (`{slug}.view`, `{slug}.create`, `{slug}.edit`, `{slug}.delete`) e expõe um painel pontual de controle e acesso direto às entidades no Dev-End (`/dev/modules`), com busca em tempo real, atalhos imediatos de operação e inspeção de campos sob demanda.

## 28. Código gerado e código personalizado

O Dev-End nunca deverá sobrescrever silenciosamente código personalizado.

Deverá existir distinção entre:

```text
Código gerenciado automaticamente
```

e:

```text
Código personalizado pelo Desenvolvedor
```

Uma regeneração não poderá destruir alterações manuais.

## 29. Segurança do Dev-End

O Dev-End não deverá permitir execução arbitrária de PHP informado pelo navegador.

A geração deverá utilizar estruturas e templates previamente definidos.

Antes de alterações estruturais relevantes:

```text
backup
   ↓
validação
   ↓
geração
   ↓
verificação
   ↓
ativação
```

Se possível, alterações deverão permitir rollback.

## 30. Setup inicial

Na primeira execução, se nenhuma instalação válida for detectada, o sistema deverá redirecionar para:

```text
/setup
```

O setup deverá solicitar pelo menos:

* nome da aplicação;
* nome do primeiro usuário;
* login (nome de usuário único, sem @);
* senha;
* confirmação da senha.

O primeiro usuário criado receberá automaticamente o perfil:

```text
Desenvolvedor
```

Após instalação concluída, `/setup` deverá ficar bloqueado.

### 30.1. Verificação prévia de requisitos (Preflight Checks)

Antes que o assistente `/setup` ou qualquer outra rota seja executada, a aplicação passa por uma camada de verificação prévia (`App\Core\Preflight`) no ponto de entrada `public/index.php` (e na suíte de testes `tests/verify.php`):

1. **Detecção Proativa de Dependências**: Identifica se o projeto acabou de ser clonado do repositório Git e a pasta `vendor/` ainda não foi criada (já que `vendor/` não é versionado).
2. **Prevenção contra Erros Fatais**: Intercepta a ausência de `vendor/autoload.php` antes que o PHP lance avisos (*Warning*) e erros fatais (*Fatal error*).
3. **Interface Visual Amigável (Web)**: Retorna status HTTP 503 com tela de diagnóstico estruturada contendo:
   - Explicação transparente do motivo da ausência;
   - Passo a passo para terminal (`cd <diretório>` e `composer install`) com botão para copiar comandos;
   - Detecção automática de binário do Composer no servidor e botão para auto-instalação direta pelo navegador;
   - Opção de geração de autoloader nativo de emergência (para ambientes locais sem Composer instalado);
   - Tabela de verificação de ambiente (PHP >= 8.2, extensões `session`, `json`, `mbstring`, `filter`, escrita em `storage/` e status do `.env`).
4. **Relatório Estruturado no Terminal (CLI)**: Caso executado por linha de comando (`php public/index.php`), exibe orientações formatadas no console e finaliza com código 1 sem poluição de stack traces.

## 31. Proteção do setup

Deverá existir suporte a:

```text
APP_SETUP_KEY
```

Em produção, essa chave poderá ser exigida antes da criação do primeiro Desenvolvedor.

Ela nunca poderá ser armazenada em arquivos públicos ou exposta no navegador.

## 32. Configuração

Configurações técnicas poderão utilizar `.env`.

Exemplos:

```text
APP_NAME
APP_ENV
APP_DEBUG
APP_URL
APP_SETUP_KEY
```

O repositório deverá possuir:

```text
.env.example
```

O arquivo:

```text
.env
```

não deverá ser versionado.

## 33. Segurança mínima

Implementar desde a base:

* `password_hash()`;
* `password_verify()`;
* proteção CSRF;
* escape de HTML;
* validação de entradas;
* regeneração de sessão;
* cookies HttpOnly;
* SameSite;
* Secure quando HTTPS estiver ativo;
* controle de autorização no servidor;
* proteção dos arquivos internos;
* tratamento apropriado de erros HTTP;
* proteção básica contra tentativas repetidas de login.

## 34. Integridade dos CSV

Mesmo considerando o pequeno número de usuários, as operações de escrita deverão ser seguras.

Utilizar:

```php
flock()
```

ou mecanismo equivalente.

Preferencialmente:

```text
bloquear
↓
ler
↓
alterar em memória
↓
gravar arquivo temporário
↓
validar
↓
substituir original
```

O sistema não precisa ser otimizado para alta concorrência.

## 35. Backup

O Dev-End possui mecanismo integrado de backups e recuperação de dados.

Recursos implementados:

* Geração de snapshots com carimbo de data/hora (`storage/backups/YYYY-MM-DD_HH-mm-ss_rotulo/`);
* Metadados em `meta.json` (tamanho total, arquivos copiados, autor e data);
* Empacotamento dinâmico e download do snapshot em formato `.zip`;
* Restauração atômica (rollback) dos arquivos CSV para `storage/data/`;
* Salvaguarda automática (`pre_restore_*`) gerada antes de qualquer substituição de dados;
* Registro dos eventos de backup e restauração na auditoria.

## 36. Auditoria

Alterações administrativas relevantes são registradas em `storage/logs/audit_log.csv`.

Recursos implementados:

* Gravação atômica em modo append (`fopen(..., 'ab')` com `flock(LOCK_EX)`), garantindo $O(1)$ por evento e prevenindo corrupção de concorrência;
* Identificadores únicos e seguros por evento (`aud_YYYYmmddHis_xxxx`);
* Visualizador de logs no Dev-End (`/dev/logs`), protegido pela permissão `dev.logs`;
* Filtragem dinâmica por tipo de ação e resolução dos nomes e logins dos usuários responsáveis;
* Eventos monitorados: login, falha de autenticação, logout, criação/atualização de usuários, alteração de perfis, criação e restauração de backups.

Nunca registrar senhas ou segredos.

## 37. Convenções de nomes

### PHP

```text
Classes      PascalCase
Métodos      camelCase
Variáveis    camelCase
```

### URLs

```text
kebab-case
```

Exemplo:

```text
/admin/system-settings
```

### Permissões

```text
recurso.acao
```

Exemplo:

```text
users.manage
```

### Arquivos CSV

```text
snake_case.csv
```

Exemplo:

```text
user_roles.csv
```

### Colunas CSV

```text
snake_case
```

Exemplos:

```text
created_at
updated_at
user_id
```

## 38. Filosofia arquitetural

Este projeto deverá permanecer:

> Estruturado, mas não superdimensionado.

Não utilizar sem necessidade:

* ORM;
* Redis;
* filas;
* cache distribuído;
* event bus;
* microserviços;
* containers complexos de injeção de dependência;
* factories para operações triviais;
* interfaces sem necessidade concreta;
* abstrações empresariais para problemas simples.

Cada abstração deverá resolver um problema real.

## 39. Evolução futura

A persistência em CSV é uma decisão inicial, não uma dependência arquitetural permanente.

A existência de Repository + Storage deverá permitir futuramente utilizar:

```text
SQLite
MySQL
PostgreSQL
```

sem reescrever Controllers e regras de negócio.

## 40. Estado atual

O projeto conta com sua fundação central concluída e operacional.

Status do roadmap:

1. [x] Estrutura base (Front Controller, Config, Request, Response, Session)
2. [x] Roteamento centralizado com parâmetros regex e method spoofing
3. [x] Camada de Views nativas com engine de layout e tela moderna de erros (403, 404, 500)
4. [x] Setup inicial guiado e protegido (`/setup`)
5. [x] Autenticação segura por sessões PHP com `password_hash()` e login estrito
6. [x] Controle de acesso RBAC granular (`role_user`, `role_admin`, `role_dev`)
7. [x] Persistência em arquivos CSV com travas `flock()` e escrita atômica
8. [x] Administração básica (gestão de usuários, perfis de acesso e perfil pessoal com troca de senha)
9. [x] Dev-End consolidado (diagnóstico, backups com rollback/download e visualizador de logs)
10. [x] Motor de módulos isolados (`modules/`)
11. [x] Entity Builder (assistente visual para criação e edição de entidades no Dev-End com salvaguardas e expansão de schema)
12. [x] CRUD declarativo por metadados (persistência CSV dinâmica, renderização automática e integridade de dados)
13. [x] Sistema de Design & Alto Contraste Global (identificação visual nítida de formulários, inputs com bordas e sombras definidas, rótulos destacados, checkboxes ampliados e tabelas contrastadas)
14. [x] Relacionamentos entre Entidades (Chaves Estrangeiras 1:N no Entity Builder e CRUD, seleção dinâmica, integridade referencial com validação de existência e proteção ativa contra registros órfãos)
15. [x] Sub-listagem Reversa / Visão 360° (exibição automática de registros dependentes na tela de visualização do registro pai com badges de política e criação contextual com pré-preenchimento)
16. [x] Filtros Rápidos por Relação na Listagem (atalho de filtro contextual direto na coluna de relação da tabela com badge ativa e remoção rápida de filtro)
17. [x] Políticas Granulares de Exclusão - `on_delete` (suporte a `restrict`, `set_null` e `cascade` declarativos no Entity Builder e no motor CRUD; prevenção contra exclusões parciais com verificação recursiva; auditoria de registros desvinculados ou removidos em cascata; e botões contextuais `🔒 Excluir` para restrição e `💥 Excluir` para cascata com confirmações detalhadas)
18. [x] Verificação Prévia de Ambiente e Diagnóstico de Requisitos (Preflight Checks: detecção de dependências ausentes, validação de PHP 8.2+, extensões e permissões com interface web amigável, auto-instalação e saída formatada no CLI)
19. [x] Relacionamentos N:N com Tabelas Pivot Declarativas (associações muitos-para-muitos via CSVs intermediários de junção, interface de checkboxes com busca em tempo real, resolução bidirecional na Visão 360°, exibição resumida na listagem e sincronização atômica)
20. [ ] Busca Assistida / Autocomplete em Relações (próximo aprimoramento previsto: otimização para catálogos com centenas ou milhares de registros)

## 41. Contribuições e Manutenção da Documentação

O projeto pretende ser mantido como repositório público.

> **Regra Obrigatória de Manutenção do README**: Todo e qualquer ajuste técnico, refatoração, acréscimo de componente ou modificação no fluxo operacional que comprometa a corretude ou a completude deste documento deve ser obrigatoriamente refletido e atualizado neste `README.md`. Este documento é a fonte primária e fidedigna da especificação e do estado da aplicação.

Contribuições deverão preservar os princípios de:

* simplicidade;
* legibilidade;
* segurança;
* baixa dependência;
* arquitetura proporcional ao porte da aplicação;
* integridade e completude documental contínua.

Mudanças arquiteturais significativas deverão ser justificadas.

## 42. Licença

A licença do projeto será definida antes da primeira versão pública estável.

## 43. Fundação implementada

A fundação funcional utiliza PHP 8.2+, Composer exclusivamente para autoload PSR-4, sessões nativas PHP com cookies protegidos e persistência CSV. O DocumentRoot do servidor web deve apontar para `public/`.

### Instalação e execução local

1. Copie `.env.example` para `.env` e configure `APP_URL`, `APP_DEBUG` e, opcionalmente, `APP_SETUP_KEY`.
2. Execute `composer install` na raiz do projeto para gerar o autoloader PSR-4. *(Nota: caso acesse a aplicação antes deste passo, o sistema de **Preflight** interceptará a execução e oferecerá diagnósticos e botões para auto-instalação ou autoloader de emergência diretamente no navegador ou instruções no terminal).*
3. Inicie o servidor embutido do PHP (`php -S localhost:8000 -t public public/index.php`) ou configure o virtualhost do Apache/XAMPP para o diretório `public/`.
4. Acesse a raiz da aplicação (ou `/setup`), crie o primeiro usuário (com perfil automático Desenvolvedor) e entre no sistema.
5. Para rodar a suíte completa de verificação automatizada:
   ```bash
   php tests/verify.php
   ```
   O teste roda de forma isolada em diretório temporário, validando inicialização com preflight, integridade CSV, autenticação, RBAC, backups (criação/restauração com salvaguarda), auditoria (escrita em append e consultas), perfil de usuário com troca de senha, motor de módulos isolados, Entity Builder (criação, edição, expansão e reordenação de campos), Relacionamentos 1:N (com integridade referencial e políticas `on_delete`: `restrict`, `set_null` e `cascade`) e Relacionamentos N:N com Tabelas Pivô Declarativas (`sync`, `resolve`, `reverse 360` e `cascade cleanup`).

### Carga de dados para testes e demonstração (Seed)

Para analisar todas as funcionalidades atuais e validar implementações futuras (filtros, paginação, integridade referencial 1:N, visão 360°, auditoria e relações N:N), execute o seeder da aplicação:

```bash
php scripts/seed.php
```

#### Contas de acesso disponíveis

| Perfil | Usuário | Senha | Papel (`role`) | Finalidade de Teste |
|---|---|---|---|---|
| **Desenvolvedor** | `dev` | *(sua senha do setup)* | `role_dev` | Acesso integral, menus `/dev`, backups, logs e Entity Builder |
| **Administrador** | `admin` | `admin123456` | `role_admin` | Gestão de usuários, perfis e operações completas de CRUD |
| **Usuário Padrão** | `carlos` | `user123456` | `role_user` | Acesso operacional padrão (leitura nos módulos) |
| **Usuária Padrão** | `mariana` | `user123456` | `role_user` | Acesso operacional padrão para testes simultâneos |
| **Desativado** | `inativo` | `user123456` | `role_user` | Validação de bloqueio de autenticação (`active = 0`) |

#### Módulos e dados populados

* **Clientes (6 registros)**: Empresas com múltiplos status (`Ativo`, `Prospect`, `Em Implantação`, `Inativo`), incluindo clientes com múltiplos projetos e clientes sem projetos (para testar exclusão livre).
* **Projetos (8 registros)**: Vinculados a Clientes via chave estrangeira com política `on_delete = restrict`. Permite testar proteção contra exclusão do pai e visão 360° reversa.
* **Tarefas (12 registros)**: Vinculadas a Projetos com política `on_delete = cascade`, múltiplos status (`A Fazer`, `Em Andamento`, `Concluído`, `Cancelado`), prioridades e prazos variados.
* **Equipamentos (8 registros)**: Notebooks, Desktops, Servidores, Switches e Impressoras com tombo/patrimônio, fabricantes e status ativo/inativo.
* **Manutenções (8 registros)**: Ordens de serviço preventivas, corretivas e de upgrade vinculadas aos equipamentos com custos em R$, técnicos responsáveis e descrições detalhadas.
* **Tabela Pivô N:N Projetos <-> Equipamentos (10 registros)**: Arquivo `storage/data/projeto_equipamentos.csv` demonstrando associações compartilhadas de notebooks, servidores e switches entre múltiplos projetos corporativos.
* **Auditoria (10 registros)**: Eventos de auditoria em `storage/logs/audit_log.csv` simulando histórico operacional.
* **Backups**: Snapshot inicial funcional em `storage/backups/` para teste imediato de download e restauração no Dev-End.

