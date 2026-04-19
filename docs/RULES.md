# Diretrizes de Código — Customizable School System

> **Aviso:** Este documento é destinado exclusivamente a desenvolvedores que irão modificar ou contribuir com o código-fonte do sistema. As diretrizes aqui descritas são obrigatórias e devem ser seguidas em todo o ciclo de desenvolvimento.

---

## Índice

1. [Separação entre Backend e Frontend](#1-separação-entre-backend-e-frontend)
2. [Validação e Sanitização de Dados](#2-validação-e-sanitização-de-dados)
3. [Autenticação e Controle de Acesso](#3-autenticação-e-controle-de-acesso)
4. [Proteção de Dados Sensíveis](#4-proteção-de-dados-sensíveis)
5. [Registro e Monitoramento de Ações](#5-registro-e-monitoramento-de-ações)
6. [Tratamento de Erros e Exposição de Informações](#6-tratamento-de-erros-e-exposição-de-informações)
7. [Limitação e Controle de Requisições](#7-limitação-e-controle-de-requisições)
8. [Atualização de Dependências e Revisão de Código](#8-atualização-de-dependências-e-revisão-de-código)

---

## 1. Separação entre Backend e Frontend

O código PHP deve ser executado **exclusivamente no servidor** e nunca exposto diretamente ao cliente. Apenas o resultado processado — HTML, CSS e JavaScript — deve ser enviado ao navegador.

### Regras obrigatórias

- Nunca retornar código PHP em respostas HTTP.
- Não armazenar arquivos sensíveis em diretórios públicos (`/public`).
- Separar claramente as responsabilidades:
  - **Backend:** PHP (regras de negócio, acesso ao banco, autenticação)
  - **Frontend:** HTML, CSS e JavaScript (apresentação e interação)
- Desabilitar a exibição de erros PHP em produção:

```ini
; php.ini — ambiente de produção
display_errors = Off
log_errors = On
error_log = /var/log/php/errors.log
```

---

## 2. Validação e Sanitização de Dados

Todo dado recebido pelo sistema — seja via formulários, URLs, headers ou requisições assíncronas — deve ser tratado como **não confiável** e obrigatoriamente validado e sanitizado no **backend** antes de qualquer processamento, armazenamento ou exibição.

### Regras obrigatórias

- **Validação:** garantir que os dados estejam no formato, tipo e tamanho esperados (ex.: e-mails válidos, números dentro de limites definidos).
- **Sanitização:** remover ou neutralizar qualquer conteúdo potencialmente malicioso, como scripts ou comandos injetados.
- **Nunca confiar apenas na validação do frontend**, pois ela pode ser facilmente contornada.
- Todas as interações com o banco de dados devem utilizar **consultas preparadas (prepared statements)** para evitar SQL Injection.
- Toda saída de dados para o navegador deve ser **devidamente escapada** para prevenir ataques XSS.

```php
// Exemplo: consulta preparada com PDO
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);

// Exemplo: escape de saída HTML
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
```

---

## 3. Autenticação e Controle de Acesso

O sistema deve garantir que apenas usuários autenticados acessem funcionalidades restritas e que cada usuário tenha acesso exclusivamente aos recursos permitidos pelo seu **nível de permissão**.

### Regras obrigatórias

- Toda ação sensível — visualizar dados de alunos, publicar conteúdos, acessar áreas administrativas — deve ser protegida por verificações de autenticação e autorização realizadas no **backend**.
- O controle de acesso deve ser baseado em **papéis (roles)**:

| Papel          | Nível de Acesso                          |
|----------------|------------------------------------------|
| Aluno          | Acesso às próprias informações           |
| Professor      | Acesso às turmas e conteúdos vinculados  |
| Administrador  | Acesso completo ao sistema               |

- Nunca depender apenas de verificações no frontend para proteger rotas ou funcionalidades.
- Sessões devem ser gerenciadas de forma segura:
  - Regenerar o identificador de sessão após o login (`session_regenerate_id(true)`).
  - Implementar proteção contra sequestro de sessão.
- Tentativas de acesso não autorizado devem ser **bloqueadas e registradas** para auditoria.

---

## 4. Proteção de Dados Sensíveis

Todos os dados sensíveis devem ser protegidos tanto **em trânsito** quanto **em repouso**, garantindo que informações de alunos, responsáveis e usuários não sejam expostas ou acessadas indevidamente.

### Regras obrigatórias

- A comunicação entre cliente e servidor deve ocorrer **exclusivamente via HTTPS**.
- Senhas devem ser obrigatoriamente protegidas por **hashing seguro**, nunca armazenadas em texto puro:

```php
// Armazenar senha
$hash = password_hash($password, PASSWORD_BCRYPT);

// Verificar senha
password_verify($inputPassword, $hash);
```

- Evitar o armazenamento desnecessário de dados sensíveis (**princípio da minimização de dados**).
- Arquivos e informações confidenciais devem ser armazenados **fora de diretórios públicos**.
- Dados sensíveis adicionais devem ser criptografados antes de serem persistidos no banco de dados.

---

## 5. Registro e Monitoramento de Ações

O sistema deve registrar e monitorar todas as ações relevantes realizadas pelos usuários e pelo próprio sistema, garantindo **rastreabilidade completa** em caso de falhas, uso indevido ou incidentes de segurança.

### Eventos que devem ser registrados

- Tentativas de login (bem-sucedidas e malsucedidas)
- Alterações de dados de usuários ou conteúdos
- Acessos a informações sensíveis
- Ações administrativas

### Formato mínimo de um registro de log

| Campo       | Descrição                                 |
|-------------|-------------------------------------------|
| `timestamp` | Data e hora do evento                     |
| `user_id`   | Identificador do usuário responsável      |
| `action`    | Descrição da ação realizada               |
| `ip`        | Endereço IP de origem da requisição       |
| `status`    | Resultado da ação (`success` / `failure`) |

### Regras obrigatórias

- Logs devem ser armazenados de forma **segura**, protegidos contra alterações não autorizadas.
- Logs devem ser acessíveis apenas a usuários com **privilégios adequados**.
- Implementar monitoramento básico para identificar comportamentos suspeitos, como múltiplas tentativas de login ou acessos incomuns.

---

## 6. Tratamento de Erros e Exposição de Informações

O sistema deve garantir que nenhuma informação sensível, estrutural ou interna seja exposta ao usuário final por meio de mensagens de erro, logs públicos ou respostas da aplicação.

### Regras obrigatórias

- Em produção, erros devem ser exibidos de forma **genérica e amigável**, sem revelar:
  - Caminhos de arquivos do servidor
  - Consultas ao banco de dados
  - Estrutura interna do sistema
  - Credenciais ou chaves
- Todas as exceções devem ser **registradas internamente** em logs seguros, nunca exibidas diretamente ao usuário.
- A API deve retornar **apenas o estritamente necessário** nas respostas, evitando o envio desnecessário de dados.

```php
// Correto — mensagem genérica para o usuário
try {
    // operação sensível
} catch (Exception $e) {
    error_log($e->getMessage()); // registra internamente
    http_response_code(500);
    echo json_encode(['error' => 'Ocorreu um erro interno. Tente novamente mais tarde.']);
}
```

---

## 7. Limitação e Controle de Requisições

O sistema deve implementar mecanismos para limitar, controlar e validar o volume e a frequência das requisições recebidas, prevenindo abusos, ataques automatizados e tentativas de exploração por **força bruta**.

### Regras obrigatórias

- Funcionalidades críticas (login, envio de formulários, endpoints de API) devem possuir **rate limiting** — bloqueando ou retardando acessos excessivos do mesmo usuário ou IP.
- Considerar a implementação de proteções contra automação indevida (ex.: verificação de comportamento ou desafios adicionais em situações suspeitas).
- Tentativas repetidas de acesso não autorizado devem ser **registradas** e, quando necessário, resultar em bloqueio temporário ou permanente.

### Exemplo de referência de configuração (Apache)

```apache
# Limitar requisições por IP via mod_evasive
DOSHashTableSize    2048
DOSPageCount        10
DOSSiteCount        50
DOSPageInterval     1
DOSSiteInterval     1
DOSBlockingPeriod   10
```

---

## 8. Atualização de Dependências e Revisão de Código

O sistema deve garantir que todas as suas dependências, bibliotecas, frameworks e componentes de infraestrutura sejam **mantidos atualizados** e monitorados continuamente quanto a vulnerabilidades conhecidas.

### Regras obrigatórias

- Manter atualizados:
  - Ambiente de execução (PHP, Apache)
  - Banco de dados (PostgreSQL)
  - Quaisquer ferramentas ou bibliotecas externas utilizadas
- Atualizações de segurança devem ser aplicadas de forma **rápida e controlada**, evitando versões obsoletas.
- O código deve ser revisado periodicamente para identificar possíveis vulnerabilidades introduzidas durante o desenvolvimento.
- **Evitar dependências desnecessárias**, reduzindo a superfície de ataque.
- Toda dependência nova adicionada ao projeto deve ser justificada e documentada.

---

> Dúvidas ou sugestões de melhoria nestas diretrizes devem ser discutidas com o time antes de qualquer alteração no documento.
