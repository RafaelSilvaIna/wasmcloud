# Environment Setup & Dependencies

Este documento descreve os requisitos técnicos e os procedimentos necessários para configurar
o ambiente de desenvolvimento local para o projeto **Customizable School System**.

A arquitetura de desenvolvimento utiliza uma abordagem híbrida: o servidor web (Apache/PHP)
é executado nativamente via XAMPP, enquanto o banco de dados é isolado em um contêiner Docker.

---

## 1. Stack Tecnológica

| Camada         | Tecnologia                               |
|----------------|------------------------------------------|
| Backend        | PHP 8.x ou superior                      |
| Frontend       | HTML5, CSS3, JavaScript (ES6+)           |
| Servidor Web   | Apache 2.4+ (Hospedagem Local via XAMPP) |
| Banco de Dados | PostgreSQL 15+ (Conteinerizado — Docker) |

---

## 2. Pré-requisitos (Ferramentas Base)

Para garantir o funcionamento da arquitetura híbrida, instale os seguintes softwares:

1. **[XAMPP Control Panel](https://www.apachefriends.org/)**
   > **Nota:** Durante a instalação ou no uso diário, você precisará apenas do módulo **Apache**.
   > O módulo MySQL do XAMPP **não** será utilizado.

2. **[Docker Desktop](https://www.docker.com/products/docker-desktop/)**
   - Baixe o instalador para Windows, execute o arquivo `.exe` e siga as instruções padrão.
   - Mantenha a opção do **WSL 2** ativada, se solicitada.
   - Após a instalação, reinicie o computador e abra o Docker Desktop para garantir que a
     engine está rodando em segundo plano.

3. **Git** — Para versionamento e controle de branches.

---

## 3. Configuração do Banco de Dados (Docker)

Com o **Docker Desktop** aberto, execute o comando abaixo no seu terminal (PowerShell ou CMD)
para baixar a imagem oficial do PostgreSQL e iniciar o banco de dados:

```bash
docker run --name school-postgres \
  -e POSTGRES_USER=root \
  -e POSTGRES_PASSWORD=root \
  -e POSTGRES_DB=school_system \
  -p 5432:5432 \
  -d postgres
```

### Descrição das flags

| Flag     | Descrição                                                                 |
|----------|---------------------------------------------------------------------------|
| `--name` | Define o nome do contêiner                                                |
| `-e`     | Define as variáveis de ambiente (usuário, senha e nome do banco)          |
| `-p`     | Expõe a porta padrão do Postgres para que o XAMPP consiga se conectar     |
| `-d`     | Roda o processo em segundo plano (*detached*)                             |

---

## 4. Configuração do Servidor Web (XAMPP)

### 4.1. Habilitando a Extensão PostgreSQL no PHP

Por padrão, o XAMPP vem configurado para MySQL. Para que o PHP se comunique com o banco de
dados no Docker, ative os drivers do Postgres:

1. Abra o **Painel do XAMPP**.
2. Na linha do **Apache**, clique em **Config** → **PHP (php.ini)**.
3. Use `Ctrl+F` e pesquise por `pdo_pgsql`.
4. **Descomente** as duas linhas abaixo removendo o ponto e vírgula (`;`) do início:

```ini
extension=pdo_pgsql
extension=pgsql
```

5. **Salve** o arquivo e **reinicie** o módulo Apache no painel do XAMPP.

---

### 4.2. Localização do Projeto

O diretório raiz do projeto deve estar dentro da pasta de serviços públicos do XAMPP:

```
C:\xampp\htdocs\customizable-school-system
```

---

## 5. Execução do Projeto

Com o contêiner Docker rodando (verificável pelo Docker Desktop) e o Apache iniciado no XAMPP,
acesse a aplicação via navegador:

```
http://localhost/customizable-school-system/
```

> As credenciais internas de conexão PHP (via PDO) devem apontar para:
> - **Host:** `localhost`
> - **Porta:** `5432`
> - **Usuário/Senha:** conforme definidos no [Passo 3](#3-configuração-do-banco-de-dados-docker)
