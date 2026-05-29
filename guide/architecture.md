# Arquitetura

## Principio central

O Wasm Cloud deve crescer por modulos pequenos, claros e testaveis. Evite arquivos gigantes, controllers cheios e regras misturadas com HTML.

## Onde colocar cada coisa

- `routes/`: definicao de rotas.
- `app/Http/Controllers`: entrada HTTP e coordenacao.
- `app/Http/Requests`: validacao de formularios.
- `app/Models`: modelos Eloquent e relacionamentos.
- `app/Policies`: autorizacao por recurso.
- `app/Actions` ou `app/Services`: regras de negocio reutilizaveis.
- `resources/views/components`: componentes Blade reutilizaveis.
- `resources/views/pages`: paginas completas.
- `resources/views/pages/*`: blocos especificos de uma pagina.
- `database/migrations`: estrutura do banco.
- `tests/Feature`: testes de fluxo HTTP.
- `tests/Unit`: testes de regra isolada.

## Regra de tamanho

Nenhum arquivo deve passar de 1000 linhas. Ao se aproximar desse limite, divida em:

- componentes Blade;
- partials por secao;
- services/actions;
- Form Requests;
- classes de suporte;
- arquivos CSS por responsabilidade quando o build permitir.

## Controllers

Controllers devem:

- receber a requisicao;
- chamar validacao/autorizacao;
- delegar regra de negocio;
- retornar response/view/redirect.

Controllers nao devem:

- montar queries complexas demais;
- validar manualmente tudo em linha;
- conter regras de dominio longas;
- acessar dados sem autorizacao clara.
