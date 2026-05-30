# Wasm Cloud

Wasm Cloud e uma plataforma de hospedagem em Laravel para gerenciar sites, clientes, planos, dominios, bancos de dados, arquivos, deploys e operacoes administrativas de hospedagem.

Este repositorio contem a base backend/frontend da aplicacao. A aplicacao roda em Laravel 12, PHP 8.2+, Vite e PostgreSQL.

## Objetivo

O projeto deve evoluir como um painel de hospedagem simples, seguro e escalavel. As primeiras entregas devem priorizar:

- cadastro e autenticacao de usuarios;
- gestao de clientes e contas de hospedagem;
- planos de hospedagem;
- dominios e status de DNS;
- controle de arquivos, sites e recursos;
- painel administrativo;
- registros de auditoria;
- operacoes seguras de banco de dados;
- base pronta para futuras integracoes com Docker, filas, storage e automacoes.

## Stack

- PHP 8.2+
- Laravel 12
- PostgreSQL
- Vite
- Tailwind CSS
- PHPUnit
- Apache/XAMPP em desenvolvimento local

## Requisitos Locais

- PHP disponivel no terminal
- Composer
- Node.js e npm
- PostgreSQL rodando no Docker
- Apache do XAMPP apontando para `public/`

## Configuracao Do Banco

O ambiente local usa PostgreSQL no Docker com as seguintes variaveis no `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5433
DB_DATABASE=hospedagem
DB_USERNAME=admin
DB_PASSWORD=admin123
```

Nunca versionar o arquivo `.env`. Atualize tambem o `.env.example` apenas com valores seguros e sem segredos reais.

## Uploads De Perfil

Fotos de perfil e banners sao enviados pelo backend para o ImgBB. Configure a chave apenas no `.env` local ou no ambiente de producao:

```env
IMGBB_API_KEY=
IMGBB_ENDPOINT=https://api.imgbb.com/1/upload
```

Nao exponha essa chave em variaveis `VITE_*`, no frontend, em logs ou em documentacao publica.

## Configuracoes E Sessoes

A pagina de configuracoes gerais usa a tabela `sessions` do Laravel para listar dispositivos conectados e permitir encerrar uma sessao especifica ou todas as outras sessoes. A identificacao de navegador/sistema usa `ua-parser/uap-php`.

Para localizacao aproximada por IP, o endpoint pode ser configurado por ambiente:

```env
IP_GEOLOCATION_ENDPOINT=https://ipwho.is/%s
```

IPs locais, privados, VPNs e proxies podem retornar localizacao aproximada ou `Rede local`.

## Instalacao

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

No Windows com XAMPP, caso o projeto ja esteja em `C:\xampp\htdocs`, confirme que o Apache esta servindo a pasta:

```text
C:/xampp/htdocs/public
```

O Laravel deve ser acessado por:

```text
http://localhost
```

## Desenvolvimento

Para rodar os assets em modo desenvolvimento:

```bash
npm run dev
```

Para limpar caches quando alterar configuracoes:

```bash
php artisan optimize:clear
```

Para rodar migrations:

```bash
php artisan migrate
```

Para executar testes:

```bash
php artisan test
```

## Servidor Web

O servidor web deve sempre apontar para `public/`, conforme a recomendacao do Laravel. A raiz do projeto nao deve ser exposta ao navegador.

Exemplo de VirtualHost local:

```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot "C:/xampp/htdocs/public"

    <Directory "C:/xampp/htdocs/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## Padroes De Desenvolvimento

- Usar os recursos nativos do Laravel antes de criar solucoes proprias.
- Validar toda entrada externa com Form Requests ou validadores dedicados.
- Usar migrations para qualquer alteracao estrutural no banco.
- Usar Eloquent com relacionamentos claros e casts quando fizer sentido.
- Evitar logica pesada em controllers.
- Proteger rotas administrativas com middleware de autenticacao e autorizacao.
- Criar testes para fluxos criticos de autenticacao, hospedagem, permissoes, faturamento e operacoes destrutivas.
- Manter mensagens, nomes de classes e arquivos em um padrao consistente.

## Seguranca

As politicas detalhadas estao em `SECURITY.md`. Como regra geral:

- nao expor `.env`, `storage/`, `vendor/`, `database/` ou a raiz do projeto;
- nao salvar senhas, tokens ou chaves em codigo;
- usar CSRF nas rotas web;
- usar policies/gates para autorizacao;
- registrar acoes sensiveis em auditoria;
- nunca confiar em dados vindos do navegador;
- usar prepared statements/Eloquent Query Builder em vez de SQL concatenado;
- proteger uploads por tipo, tamanho, nome e local de armazenamento.

## Estrutura Importante

```text
app/          Codigo da aplicacao Laravel
config/       Configuracoes versionadas
database/     Migrations, seeders e factories
public/       Unica pasta exposta pelo servidor web
resources/    Views, estilos e scripts fonte
routes/       Rotas web, API e console
storage/      Logs, cache e arquivos locais privados
tests/        Testes automatizados
```

## Agentes E Automacoes

Qualquer agente, assistente ou automacao que modificar este projeto deve seguir o arquivo `AGENTS.md`.

## Licenca

Projeto privado em desenvolvimento. A licenca final deve ser definida antes de qualquer distribuicao publica.
