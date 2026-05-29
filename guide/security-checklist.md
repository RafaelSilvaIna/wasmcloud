# Checklist De Seguranca

Antes de concluir uma funcionalidade, verifique:

- A rota exige autenticacao quando necessario?
- Existe autorizacao por policy, gate ou middleware?
- Toda entrada externa e validada?
- Operacoes destrutivas usam CSRF e confirmacao?
- O usuario so acessa dados do seu proprio escopo?
- Uploads validam tipo, tamanho e nome?
- Logs nao exibem senhas, tokens ou dados sensiveis?
- Erros nao vazam stack trace em producao?
- Migrations nao destroem dados sem plano?
- Testes cobrem o fluxo principal e casos de negacao?

Em caso de duvida, siga `SECURITY.md`.
