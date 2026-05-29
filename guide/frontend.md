# Frontend

## Estrutura

A home inicial esta dividida em:

- `resources/views/pages/home.blade.php`
- `resources/views/components/site/header.blade.php`
- `resources/views/pages/home/*.blade.php`
- `resources/css/app.css`

Use componentes para elementos reutilizaveis e partials para blocos especificos de uma pagina.

## Direcao visual

Wasm Cloud e uma plataforma operacional de hospedagem. A interface deve ser:

- clara;
- profissional;
- escaneavel;
- responsiva;
- preparada para uso diario.

Evite telas genericas de marketing quando o pedido for uma aplicacao. Priorize fluxos reais, estados e dados organizados.

## Boas praticas

- Nao colocar CSS inline.
- Nao misturar grandes blocos de markup repetido.
- Criar estados de vazio, carregando, erro e sucesso.
- Garantir que textos caibam em mobile.
- Usar imagens reais da marca quando fizer sentido.
- Nunca expor segredo em JavaScript ou variaveis `VITE_*`.
