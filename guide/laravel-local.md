# Laravel Local

## Rodando o projeto

Instale dependencias:

```bash
composer install
npm install
```

Configure o `.env`, gere a chave e rode migrations:

```bash
php artisan key:generate
php artisan migrate
```

Compile assets:

```bash
npm run build
```

Durante desenvolvimento frontend:

```bash
npm run dev
```

## Banco local

O projeto usa PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5433
DB_DATABASE=hospedagem
DB_USERNAME=admin
DB_PASSWORD=admin123
```

## Caches comuns

Quando alterar `.env`, config ou rotas:

```bash
php artisan optimize:clear
```

Em producao, cache deve ser criado so depois de validar o ambiente.
