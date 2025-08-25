# Teste Fteam - Dev JR PHP/Laravel

Uma API em **Laravel 12** para integração com a **Fake Store API**, permitindo sincronização de produtos, categorias, catálogo com filtros avançados e estatísticas detalhadas.

---

## 📋 Índice
- [Funcionalidades](#-funcionalidades)
- [Tecnologias Utilizadas](#-tecnologias-utilizadas)
- [Pré-requisitos](#-pré-requisitos)
- [Instalação e Configuração](#-instalação-e-configuração)
- [Variáveis de Ambiente](#-variáveis-de-ambiente)
- [Estrutura do Banco de Dados](#-estrutura-do-banco-de-dados)
- [Endpoints da API](#-endpoints-da-api)
- [Executando a Sincronização](#-executando-a-sincronização)
- [Testando os Endpoints](#-testando-os-endpoints)
- [Testes](#-testes)
- [Decisões de Modelagem](#-decisões-de-modelagem)
- [Índices do Banco de Dados](#-índices-do-banco-de-dados)
- [Estratégia de Tratamento de Erros](#-estratégia-de-tratamento-de-erros)

---

## 🚀 Funcionalidades
- **Middleware de Integração**: Validação de headers e logging de requisições  
- **Sincronização de Produtos**: Importação de produtos e categorias da Fake Store API  
- **Catálogo com Filtros**: Listagem com paginação, filtros e ordenação  
- **Estatísticas**: Dados agregados sobre produtos e categorias  
- **Swagger UI**: Documentação da API em `/api/documentation`  
- **Resiliência**: Tratamento robusto de erros e timeouts  

---

## 🛠 Tecnologias Utilizadas
- Laravel 12  
- PHP 8.2+  
- MySQL 8+  
- Guzzle HTTP Client  
- PHPUnit para testes  
- L5 Swagger  

---

## 📋 Pré-requisitos
- PHP 8.2 ou superior  
- Composer  
- MySQL 8 ou superior  
- Git  

---

## 🔧 Instalação e Configuração
Clone o repositório:

```bash
git clone https://github.com/ruansbueno/teste_laravel_fteam.git
cd teste_laravel_fteam
```

Instale as dependências:

```bash
composer install
```

Configure o ambiente:

```bash
cp .env.example .env
php artisan key:generate
```

Edite o arquivo .env com suas credenciais MySQL:

```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=teste_laravel_fteam
DB_USERNAME=root
DB_PASSWORD=
```

Execute as migrações:
```bash
php artisan migrate
```

Inicie o servidor:

```bash
php artisan serve
```

## Variáveis de Ambiente:

| Variável                  | Descrição                 | Exemplo                                              |
| ------------------------- | ------------------------- | ---------------------------------------------------- |
| DB\_CONNECTION            | Tipo de banco de dados    | mysql                                                |
| DB\_HOST                  | Host do banco de dados    | 127.0.0.1                                            |
| DB\_PORT                  | Porta do banco de dados   | 3306                                                 |
| DB\_DATABASE              | Nome do banco de dados    | teste\_laravel\_fteam                                |
| DB\_USERNAME              | Usuário do banco de dados | root                                                 |
| DB\_PASSWORD              | Senha do banco de dados   | secret                                               |
| FAKESTORE\_BASE\_URL      | URL da Fake Store API     | [https://fakestoreapi.com](https://fakestoreapi.com) |
| FAKESTORE\_TIMEOUT        | Timeout em segundos       | 5                                                    |
| FAKESTORE\_RETRIES        | Tentativas de retry       | 3                                                    |
| FAKESTORE\_RETRY\_BACKOFF | Tempo de backoff em ms    | 200                                                  |


## Estrutura do Banco de Dados

Tabela Categories
| Coluna      | Tipo            | Descrição           |
| ----------- | --------------- | ------------------- |
| id          | bigint unsigned | Chave primária      |
| name        | varchar(255)    | Nome da categoria   |
| created\_at | timestamp       | Data de criação     |
| updated\_at | timestamp       | Data de atualização |

Tabela Products
| Coluna       | Tipo            | Descrição                    |
| ------------ | --------------- | ---------------------------- |
| id           | bigint unsigned | Chave primária               |
| external\_id | int             | ID externo da Fake Store API |
| title        | varchar(255)    | Título do produto            |
| price        | decimal(10,2)   | Preço do produto             |
| description  | text            | Descrição do produto         |
| category\_id | bigint unsigned | FK para categories           |
| image\_url   | varchar(255)    | URL da imagem                |
| created\_at  | timestamp       | Data de criação              |
| updated\_at  | timestamp       | Data de atualização          |

## Endpoints da API
Middleware de Integração

Todos os endpoints requerem o header:

```bash
X-Client-Id: seu-client-id
```

1. Sincronização de Produtos

POST /api/integrations/fakestore/sync

Resposta de Sucesso:

```bash
{
  "message": "sync finished",
  "imported": 20,
  "updated": 5,
  "skipped": 2,
  "errors": []
}
```

2. Listagem de Categorias

GET /api/categories
```bash
{
  "version": 2,
  "categories": [
    {"id": 1, "name": "electronics", "products_count": 6},
    {"id": 2, "name": "jewelery", "products_count": 4}
  ]
}
```

3. Listagem de Produtos

GET /api/products

Parâmetros suportados:

- category_id

- q (busca texto em título/descrição)

- min_price

- max_price

- sort (price_asc, price_desc, title_asc, title_desc, created_asc, created_desc)

- per_page, page

4. Estatísticas

GET /api/stats
```bash
{
  "version": 2,
  "total_products": 20,
  "total_categories": 4,
  "min_price": 9.99,
  "max_price": 999.00,
  "avg_price": 114.95
}
```

## Documentação Swagger

A documentação interativa está disponível em:

- http://127.0.0.1:8000/api/documentation

## Executando a Sincronização

Via API:

```bash
curl -X POST http://127.0.0.1:8000/api/integrations/fakestore/sync \
  -H "Content-Type: application/json" \
  -H "X-Client-Id: your-client-id"
```

Via comando Artisan:

```bash
php artisan sync:fakestore --now
```

## Decisões de Modelagem

- external_id garante unicidade dos produtos importados

- Relação 1:N entre categorias e produtos

- Sincronização feita via updateOrCreate

- Cache de versão (catalog_version, stats_version) para invalidação eficiente

## Índices do Banco de Dados

- products_external_id_unique (UNIQUE)

- products_category_id_index

- products_price_index

- products_title_index

- categories_name_unique (UNIQUE)

## Estratégia de Tratamento de Erros

- Middleware: valida X-Client-Id e retorna 400 se ausente

- Fake Store API: retry automático, timeout configurado, erros logados

- Sincronização: erros em itens individuais não interrompem o processo

- Respostas de Erro padronizadas:

```bash
{
  "error": "Mensagem descritiva do erro"
}
```
