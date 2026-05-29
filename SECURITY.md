# Politica De Seguranca - Wasm Cloud

Este documento define as regras minimas de seguranca para o projeto Wasm Cloud. Como o sistema lida com hospedagem, clientes, dominios, arquivos e possivelmente credenciais de infraestrutura, qualquer falha pode impactar dados e disponibilidade.

## Versoes Suportadas

Enquanto o projeto estiver em desenvolvimento inicial, apenas a branch principal de desenvolvimento sera considerada suportada. Versoes publicadas devem declarar explicitamente seu periodo de suporte antes de serem usadas em producao.

## Comunicacao De Vulnerabilidades

Nao abra issues publicas com dados sensiveis, exploits funcionais, credenciais, dumps ou detalhes que facilitem abuso.

Relatos de vulnerabilidade devem incluir:

- resumo do problema;
- impacto esperado;
- passos de reproducao;
- ambiente afetado;
- evidencias sem expor dados sensiveis;
- sugestao de correcao, se houver.

## Principios Obrigatorios

- O servidor web deve apontar apenas para `public/`.
- O arquivo `.env` nunca deve ser versionado ou exposto.
- Segredos reais nunca devem aparecer em commits, logs, screenshots, seeders ou documentacao publica.
- Toda entrada externa deve ser validada.
- Toda operacao sensivel deve exigir autenticacao e autorizacao.
- Toda acao destrutiva deve ser protegida contra CSRF, abuso e erro operacional.
- Erros exibidos ao usuario nao devem vazar stack traces, caminhos locais, SQL, variaveis de ambiente ou credenciais.

## Autenticacao

- Usar os mecanismos oficiais do Laravel para autenticacao sempre que possivel.
- Senhas devem ser armazenadas somente com hashing seguro fornecido pelo Laravel.
- Implementar confirmacao de senha para operacoes sensiveis.
- Aplicar rate limiting em login, recuperacao de senha e endpoints criticos.
- Invalidar sessoes quando houver troca de senha, alteracao de email sensivel ou suspeita de comprometimento.

## Autorizacao

- Usar Policies, Gates ou middleware dedicado.
- Nunca confiar apenas em campos enviados pelo frontend para determinar dono, permissao ou escopo.
- Toda consulta multiusuario deve filtrar pelo tenant, cliente, usuario ou organizacao correta.
- Contas administrativas devem ter privilegios minimos e trilha de auditoria.

## Banco De Dados

- Usar migrations para schema.
- Usar Eloquent ou Query Builder parametrizado.
- Nao concatenar SQL com dados do usuario.
- Validar tamanho, tipo e formato antes de persistir dados.
- Criar indices para chaves de acesso frequente, especialmente em tabelas multiusuario.
- Planejar backups, restore testado e retencao antes de producao.

## Uploads E Arquivos

- Validar MIME type, extensao e tamanho.
- Gerar nomes internos seguros; nao confiar no nome original do arquivo.
- Armazenar uploads privados fora de `public/` quando nao forem arquivos publicos.
- Bloquear execucao de arquivos enviados por usuarios.
- Fazer scan ou validacao adicional para arquivos potencialmente perigosos.

## Sessao, Cookies E CSRF

- Manter CSRF ativo em rotas web.
- Usar cookies `HttpOnly`, `SameSite` e `Secure` em producao com HTTPS.
- Nao armazenar dados sensiveis diretamente em cookies.
- Preferir sessoes no banco, Redis ou driver controlado para ambientes reais.

## API

- APIs devem usar autenticacao apropriada.
- Aplicar rate limiting por usuario, IP e tipo de operacao quando necessario.
- Respostas de erro devem ser consistentes e nao revelar detalhes internos.
- Endpoints administrativos devem ser separados e protegidos por autorizacao explicita.

## Logs E Auditoria

- Logs nao devem conter senhas, tokens, chaves privadas, cookies, dados bancarios ou documentos completos.
- Registrar acoes sensiveis: login, logout, alteracao de permissao, criacao/remocao de hospedagem, alteracao de dominio, upload, deploy e exclusoes.
- Logs devem ter retencao definida antes de producao.

## Frontend

- Escapar dados dinamicos por padrao.
- Evitar `v-html`, `innerHTML` e renderizacao de HTML vindo do usuario.
- Validacoes no frontend sao auxiliares; a validacao real deve existir no backend.
- Nao expor segredos em variaveis `VITE_*`, pois elas podem ir para o bundle publico.

## Dependencias

- Manter Composer e npm atualizados.
- Revisar vulnerabilidades com `composer audit` e `npm audit`.
- Evitar pacotes sem manutencao ou com permissao excessiva.
- Toda dependencia nova deve ter necessidade clara.

## Deploy E Producao

- `APP_DEBUG=false`.
- `APP_ENV=production`.
- HTTPS obrigatorio.
- `php artisan config:cache`, `route:cache` e `view:cache` somente apos validar ambiente.
- Permissoes de arquivo devem limitar escrita a `storage/` e `bootstrap/cache/`.
- Banco, cache, filas e storage devem ter credenciais distintas por ambiente.

## Checklist Antes De Producao

- Servidor apontando para `public/`.
- `.env` fora do controle de versao.
- HTTPS ativo.
- Backups configurados e testados.
- Logs sem segredos.
- Testes principais passando.
- Rate limits aplicados.
- Autorizacao revisada.
- Uploads protegidos.
- `APP_DEBUG=false`.
