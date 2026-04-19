# Environment Setup & Dependencies

Este documento descreve os requisitos técnicos e os procedimentos necessários para configurar o ambiente de desenvolvimento local para o projeto **Customizable School System**.

## 1. Stack Tecnológica
* **Backend:** PHP 8.x (ou superior)
* **Frontend:** HTML5, CSS3, JavaScript (ES6+)
* **Servidor Web:** Apache 2.4+
* **Banco de Dados:** MySQL 8.0+ / MariaDB

## 2. Pré-requisitos (Ambiente Local)
Para garantir a paridade entre os ambientes de desenvolvimento, recomenda-se o uso do **XAMPP v3.3.0+**.

### Componentes Necessários:
1.  **XAMPP Control Panel**: [Download aqui](https://www.apachefriends.org/)
    * Módulo Apache (Portas 80, 443)
    * Módulo MySQL (Porta 3306)
2.  **Git**: Para versionamento e controle de branches.

## 3. Procedimento de Instalação

### 3.1 Localização do Projeto
O diretório raiz do projeto **deve** ser alocado dentro da pasta de serviços públicos do servidor Web para que o interpretador PHP funcione corretamente:
* **Caminho padrão:** `C:\xampp\htdocs\customizable-school-system`

### 3.2 Configuração do Banco de Dados
1.  Inicie os módulos **Apache** e **MySQL** no XAMPP Control Panel.
2.  Acesse o painel administrativo: `http://localhost/phpmyadmin`.
3.  Crie um novo schema de banco de dados (UTF-8 Unicode).
4.  Importe o arquivo SQL localizado em `/database/schema.sql` (se disponível).

## 4. Execução do Projeto
Após a clonagem e configuração do diretório no `htdocs`, o acesso local deve ser feito via navegador através da URL:

```url
http://localhost/customizable-school-system/