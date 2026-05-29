# Workflow

## Antes de alterar

1. Leia o arquivo que sera modificado.
2. Entenda o padrao existente.
3. Planeje a menor alteracao que resolve o problema.

## Durante a alteracao

- Mantenha arquivos pequenos.
- Divida paginas em blocos.
- Nomeie classes e arquivos com clareza.
- Atualize documentacao quando o fluxo mudar.
- Evite dependencias novas sem necessidade.

## Antes de finalizar

Rode o que for relevante:

```bash
php artisan test
npm run build
```

Se algum comando falhar, registre o motivo e corrija quando estiver no escopo.
