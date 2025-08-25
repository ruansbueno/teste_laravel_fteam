# E-commerce Integration API

Uma API Laravel para integração com a Fake Store API, proporcionando sincronização de produtos, catálogo com filtros avançados e estatísticas detalhadas.

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
- **Resiliência**: Tratamento robusto de erros e timeouts  

---

## 🛠 Tecnologias Utilizadas
- Laravel 10+  
- PHP 8.1+  
- MySQL/PostgreSQL  
- Guzzle HTTP Client  
- PHPUnit para testes  

---

## 📋 Pré-requisitos
- PHP 8.1 ou superior  
- Composer  
- MySQL 5.7+ ou PostgreSQL 9.6+  
- Git  

---

## 🔧 Instalação e Configuração
Clone o repositório:

```bash
git clone https://github.com/ruansbueno/teste_laravel_fteam.git
cd teste_laravel_fteam
Instale as dependências:

bash
Copiar
Editar
composer install
Configure o ambiente:

bash
Copiar
Editar
cp .env.example .env
php artisan key:generate
Configure o banco de dados no arquivo .env:

dotenv
Copiar
Editar
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=
Execute as migrações:

bash
Copiar
Editar
php artisan migrate
Inicie o servidor:

bash
Copiar
Editar
php artisan serve
O servidor estará disponível em http://localhost:8000

🔌 Variáveis de Ambiente
Variável	Descrição	Exemplo
DB_CONNECTION	Tipo de banco de dados	mysql
DB_HOST	Host do banco de dados	127.0.0.1
DB_PORT	Porta do banco de dados	3306
DB_DATABASE	Nome do banco de dados	laravel
DB_USERNAME	Usuário do banco de dados	root
DB_PASSWORD	Senha do banco de dados	secret
FAKE_STORE_API_URL	URL da Fake Store API	https://fakestoreapi.com

🗃 Estrutura do Banco de Dados
Tabela: categories
Coluna	Tipo	Descrição
id	bigint unsigned	Chave primária
name	varchar(255)	Nome da categoria
created_at	timestamp	Data de criação
updated_at	timestamp	Data de atualização

Tabela: products
Coluna	Tipo	Descrição
id	bigint unsigned	Chave primária
external_id	int	ID externo da Fake Store API
title	varchar(255)	Título do produto
price	decimal(10,2)	Preço do produto
description	text	Descrição do produto
category_id	bigint unsigned	Chave estrangeira para categories
image	varchar(255)	URL da imagem
rating_rate	decimal(3,2)	Avaliação do produto
rating_count	int	Contagem de avaliações
created_at	timestamp	Data de criação
updated_at	timestamp	Data de atualização

🌐 Endpoints da API
🔐 Middleware de Integração
Todos os endpoints (exceto sincronização) requerem o header:

arduino
Copiar
Editar
X-Client-Id: seu-client-id
1. Sincronização de Produtos
POST /integrations/fakestore/sync

Sincroniza produtos e categorias com a Fake Store API.

Resposta de Sucesso:

json
Copiar
Editar
{
  "message": "Synchronization completed successfully",
  "products_synced": 20,
  "categories_synced": 4
}
2. Listagem de Produtos
GET /products

Parâmetros opcionais:

category

min_price

max_price

search

sort_by (price, title, created_at)

sort_order (asc, desc)

per_page (default: 15)

Exemplo:

sql
Copiar
Editar
GET /products?category=electronics&min_price=10&max_price=100&search=phone&sort_by=price&sort_order=desc&per_page=20
Resposta:

json
Copiar
Editar
{
  "data": [...],
  "links": {...},
  "meta": {...}
}
3. Buscar Produto por ID
GET /products/{id}

Resposta:

json
Copiar
Editar
{
  "id": 1,
  "external_id": 1,
  "title": "Product Name",
  "price": 109.95,
  "description": "Product description...",
  "category_id": 1,
  "image": "https://fakestoreapi.com/img/81fPKd-2AYL._AC_SL1500_.jpg",
  "rating_rate": 3.9,
  "rating_count": 120,
  "category": {
    "id": 1,
    "name": "electronics"
  }
}
4. Estatísticas
GET /statistics

Resposta:

json
Copiar
Editar
{
  "total_products": 20,
  "products_by_category": [
    {"category_name": "electronics", "count": 6},
    {"category_name": "jewelery", "count": 4},
    {"category_name": "men's clothing", "count": 4},
    {"category_name": "women's clothing", "count": 6}
  ],
  "average_price": 114.95
}
🔄 Executando a Sincronização
Via API:
bash
Copiar
Editar
curl -X POST http://localhost:8000/integrations/fakestore/sync \
  -H "Content-Type: application/json" \
  -H "X-Client-Id: your-client-id-here"
Via Comando Artisan:
bash
Copiar
Editar
php artisan products:sync
🧪 Testando os Endpoints
Testar Sincronização

bash
Copiar
Editar
curl -X POST http://localhost:8000/integrations/fakestore/sync \
  -H "X-Client-Id: test-client"
Testar Listagem de Produtos

bash
Copiar
Editar
curl -X GET "http://localhost:8000/products?category=electronics&min_price=10&max_price=1000" \
  -H "X-Client-Id: test-client"
Testar Busca de Produto

bash
Copiar
Editar
curl -X GET http://localhost:8000/products/1 \
  -H "X-Client-Id: test-client"
Testar Estatísticas

bash
Copiar
Editar
curl -X GET http://localhost:8000/statistics \
  -H "X-Client-Id: test-client"
🧪 Testes
Rodar todos os testes:

bash
Copiar
Editar
php artisan test
Testes Implementados
ProductSyncTest: Testa a sincronização de produtos

ProductControllerTest: Testa os endpoints de produtos

StatisticsControllerTest: Testa o endpoint de estatísticas

IntegrationMiddlewareTest: Testa o middleware de integração

🏗 Decisões de Modelagem
Estrutura de Dados

Produtos têm external_id único para evitar duplicatas

Relação 1:N entre categorias e produtos

Campos rating_rate e rating_count separados

Estratégia de Sincronização

updateOrCreate para evitar duplicação

Sincronização item a item para evitar falhas em lote

Log de erros sem interromper a execução

Design da API

Paginação configurável

Filtros flexíveis

Ordenação dinâmica

📊 Índices do Banco de Dados
products

products_external_id_unique (UNIQUE)

products_category_id_index

products_price_index

products_title_index

categories

categories_name_unique (UNIQUE)

🛡 Estratégia de Tratamento de Erros
Middleware de Integração

Valida header X-Client-Id

Retorna 400 se ausente

Loga todas as requisições

API Externa

Timeout 30s

Retry com backoff

Trata erros HTTP

Sincronização

Continua mesmo com falhas individuais

Registra erros em log

Respostas de Erro

json
Copiar
Editar
{
  "error": "Mensagem descritiva do erro",
  "code": "Código do erro"
}