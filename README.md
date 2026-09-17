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
│   ├── Core/
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

O **Entity Builder** é um assistente visual operacional dentro do Dev-End (`/dev/entity-builder`), permitindo a criação declarativa e instantânea de novas entidades administrativas diretamente pelo navegador.

Recursos implementados:

* Formulário visual para definição de nome, entidade no singular, slug com auto-geração, prefixo de ID e ícone/emoji;
* Construtor dinâmico de campos com suporte a: `string` (texto curto), `text` (texto longo), `number` (número), `date` (data), `select` (múltiplas opções separadas por vírgula) e `boolean` (ativo / sim-não);
* Controles granulares por campo: obrigatório, único e visibilidade na listagem principal;
* **Salvaguarda de segurança pré-geração:** geração automática de backup snapshot (`pre_entity_create_*`) antes de qualquer alteração física no disco;
* **Proteção contra sobrescrita:** impede a criação de módulos que colidam com slugs existentes ou rotas reservadas da plataforma;
* Geração do arquivo `modules/<slug>/module.php` e inicialização física do CSV em `storage/data/<slug>.csv`;
* Sincronização automática de permissões no RBAC (`{slug}.view`, `{slug}.create`, `{slug}.edit`, `{slug}.delete`) e concessão ao perfil `role_admin`;
* Exclusão segura de módulos personalizados via Dev-End com salvaguarda pré-remoção (`pre_entity_delete_*`);
* Registro de auditoria completo de criação e exclusão de entidades.

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

O `ModuleManager` descobre os módulos em tempo de execução, registra automaticamente as rotas RESTful pelo `GenericCrudController`, assegura as permissões no RBAC (`{slug}.view`, `{slug}.create`, `{slug}.edit`, `{slug}.delete`) e expõe um painel de inspeção técnica no Dev-End (`/dev/modules`).

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
11. [x] Entity Builder (assistente visual para criação de entidades no Dev-End)
12. [x] CRUD declarativo por metadados (persistência CSV dinâmica e renderização automática)

## 41. Contribuições

O projeto pretende ser mantido como repositório público.

Contribuições deverão preservar os princípios de:

* simplicidade;
* legibilidade;
* segurança;
* baixa dependência;
* arquitetura proporcional ao porte da aplicação.

Mudanças arquiteturais significativas deverão ser justificadas.

## 42. Licença

A licença do projeto será definida antes da primeira versão pública estável.

## 43. Fundação implementada

A fundação funcional utiliza PHP 8.2+, Composer exclusivamente para autoload PSR-4, sessões nativas PHP com cookies protegidos e persistência CSV. O DocumentRoot do servidor web deve apontar para `public/`.

### Instalação e execução local

1. Copie `.env.example` para `.env` e configure `APP_URL`, `APP_DEBUG` e, opcionalmente, `APP_SETUP_KEY`.
2. Execute `composer install` na raiz do projeto para gerar o autoloader PSR-4.
3. Inicie o servidor embutido do PHP (`php -S localhost:8000 -t public public/index.php`) ou configure o virtualhost do Apache para o diretório `public/`.
4. Acesse `/setup`, crie o primeiro usuário (com perfil automático Desenvolvedor) e entre no sistema.
5. Para rodar a suíte completa de verificação automatizada:
   ```bash
   php tests/verify.php
   ```
   O teste roda de forma isolada em diretório temporário, validando instalação, integridade CSV, autenticação, RBAC, backups (criação/restauração com salvaguarda) e auditoria (escrita em append e consultas).
