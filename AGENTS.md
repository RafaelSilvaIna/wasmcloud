# Diretrizes Para Agentes - Wasm Cloud

Este arquivo orienta agentes de IA, automacoes e desenvolvedores que alterarem este projeto. Siga estas regras antes de modificar codigo, documentacao, configuracao ou banco de dados.

## Identidade Do Projeto

- Nome do produto: Wasm Cloud.
- Dominio funcional: plataforma de hospedagem.
- Stack principal: Laravel 12, PHP 8.2+, PostgreSQL, Vite e Tailwind CSS.
- Ambiente local atual: XAMPP em `C:\xampp\htdocs`, Apache servindo `public/`.

## Prioridades

1. Seguranca.
2. Corretude dos dados.
3. Clareza de arquitetura.
4. Boa experiencia para o usuario final.
5. Simplicidade operacional.

## Regras De Servidor

- Nunca expor a raiz do projeto no navegador.
- O `DocumentRoot` deve apontar para `public/`.
- Nao mover `public/index.php` para a raiz.
- Nao copiar `.env`, `vendor/`, `storage/`, `database/` ou `config/` para pastas publicas.

## Regras De Codigo

- Preferir recursos nativos do Laravel.
- Controllers devem ser finos; regras de negocio devem ir para services, actions, jobs ou models quando fizer sentido.
- Validacao deve ficar em Form Requests ou validadores dedicados.
- Autorizacao deve usar Policies, Gates ou middleware.
- Operacoes demoradas devem ser preparadas para filas.
- Migrations devem ser reversiveis sempre que possivel.
- Evitar SQL manual; quando necessario, usar parametros.
- Nao introduzir dependencias sem necessidade clara.

## Banco De Dados

- Banco local: PostgreSQL.
- Usar migrations para qualquer schema.
- Seeders nao devem conter segredos reais.
- Nomes de tabelas e colunas devem ser claros e consistentes.
- Toda tabela sensivel deve considerar auditoria, ownership e indices.
- Cuidado especial com dados multiusuario: sempre aplicar escopo de dono/cliente/organizacao.

## Seguranca Obrigatoria

- Seguir `SECURITY.md`.
- Nao versionar segredos.
- Nao imprimir credenciais em logs.
- Nao retornar stack traces ao usuario.
- Validar, autorizar e auditar operacoes sensiveis.
- Proteger uploads.
- Manter CSRF ativo em rotas web.
- Aplicar rate limit em login, APIs e acoes criticas.

## Frontend

- Construir telas reais e utilizaveis, nao landing pages genericas quando o pedido for uma aplicacao.
- Priorizar painel claro, denso e operacional para gestao de hospedagem.
- Evitar componentes decorativos que atrapalhem leitura ou repeticao de uso.
- Usar estados de loading, vazio, erro e sucesso.
- Nao expor segredos em variaveis `VITE_*`.
- Toda validacao visual deve ter equivalente no backend.

## Testes

- Rodar testes relevantes antes de concluir alteracoes.
- Criar testes para autenticacao, autorizacao, CRUDs criticos, uploads, operacoes destrutivas e regras financeiras quando existirem.
- Para mudancas pequenas, ao menos executar `php artisan test` quando viavel.
- Para mudancas em assets, executar `npm run build` quando viavel.

## Comandos Uteis

```bash
php artisan optimize:clear
php artisan migrate
php artisan test
npm run build
npm run dev
```

## Documentacao

- Atualizar `README.md` quando mudar setup, stack, comandos ou fluxo principal.
- Atualizar `SECURITY.md` quando criar novas superficies de risco.
- Documentar decisoes importantes de arquitetura em arquivos especificos quando necessario.

## Git E Alteracoes

- Nao reverter alteracoes de outra pessoa sem pedido explicito.
- Manter commits pequenos e coesos quando houver commits.
- Nao incluir arquivos gerados, caches, logs, `.env` ou `vendor/`.
- Antes de finalizar, revisar `git diff` e listar o que foi alterado.

## Definicao De Pronto

Uma tarefa so deve ser considerada pronta quando:

- a implementacao atende ao pedido;
- o comportamento foi verificado;
- a seguranca basica foi considerada;
- os arquivos relevantes foram atualizados;
- testes/comandos foram executados ou a impossibilidade foi informada.
