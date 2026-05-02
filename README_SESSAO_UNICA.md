# Política de Sessão Única - PipoCine

## Visão Geral

Este sistema implementa uma política de segurança que permite apenas uma sessão ativa por perfil/dispositivo. Quando um usuário tenta acessar sua conta em múltiplos dispositivos simultaneamente, o sistema bloqueia o acesso e exibe uma mensagem informativa.

## 🚀 Instalação

### 1. Criar Tabela no Banco de Dados

Execute o SQL abaixo no banco de dados `pipocine`:

```bash
mysql -u pipocine -p pipocine < database/create_active_sessions_table.sql
```

Ou execute manualmente o conteúdo do arquivo `database/create_active_sessions_table.sql`.

### 2. Verificar Dependências

Certifique-se de que os seguintes arquivos estão presentes:

- `database/db.php` - Configuração do banco de dados
- `models/AuthModel.php` - Modelo de autenticação
- `services/AuthService.php` - Serviço de autenticação
- `controllers/AuthController.php` - Controlador de autenticação
- `components/SessionModal.php` - Componente do modal
- `assets/css/session-modal.css` - Estilos do modal

### 3. Limpeza de Sessões (Opcional)

Para limpar sessões expiradas automaticamente, você pode:

#### Opção A: Trigger Automático (Recomendado)
O trigger já está incluído no SQL e funciona automaticamente.

#### Opção B: Cron Job
Adicione ao crontab:

```bash
# Limpa sessões expiradas a cada 30 minutos
*/30 * * * * mysql -u pipocine -p'senha' pipocine -e "CALL CleanupInactiveProfileSessions();"
```

## 🔧 Funcionalidades

### Bloqueio de Acesso Simultâneo
- Apenas **uma sessão ativa** por perfil
- Detecção automática de múltiplos dispositivos
- Bloqueio em tempo real

### Modal de Notificação
- Design profissional e minimalista
- Identidade visual PipoCine
- Informações do dispositivo ativo
- Redirecionamento automático

### Segurança
- Controle por session_id único
- Verificação de IP e User-Agent
- Limpeza automática de sessões expiradas
- Prevenção contra compartilhamento de conta

## 📱 Como Funciona

### Fluxo de Login
1. Usuário faz login normalmente
2. Sistema verifica se já existe sessão ativa
3. Se não houver, cria nova sessão
4. Se houver, exibe modal de bloqueio

### Fluxo de Acesso
1. A cada requisição, sistema verifica sessão ativa
2. Se sessão for inválida/expirada, redireciona para login
3. Se detectar conflito, exibe modal de aviso

### Detecção de Dispositivo
O sistema identifica:
- **Navegador**: Chrome, Firefox, Safari, Edge
- **Sistema**: Windows, macOS, Android, iOS, Linux
- **Tipo**: Mobile vs Desktop
- **IP**: Para futura implementação de geolocalização

## 🎨 Personalização

### Cores e Tema
As cores seguem a identidade PipoCine:
- **Primária**: `#e50914` (Vermelho Netflix)
- **Fundo**: `#141414` (Preto Netflix)
- **Texto**: `rgba(255, 255, 255, 0.8)` (Branco com opacidade)

### Modificar Mensagens
Edite `components/SessionModal.php`:

```php
// Mensagem principal
<p class="session-modal-message">
    // Seu texto personalizado aqui
</p>
```

### Customizar CSS
Edite `assets/css/session-modal.css` para ajustes visuais.

## 🔍 Logs e Monitoramento

### Verificar Sessões Ativas
```sql
SELECT 
    u.username,
    p.profile_name,
    s.session_id,
    s.ip_address,
    s.user_agent,
    s.last_activity,
    s.created_at
FROM profile_active_sessions s
JOIN users u ON u.id = s.user_id
JOIN profiles p ON p.id = s.profile_id
WHERE s.is_active = 1
ORDER BY s.last_activity DESC;
```

### Limpar Sessões Manualmente
```sql
-- Limpar todas as sessões de um perfil
UPDATE profile_active_sessions 
SET is_active = 0 
WHERE profile_id = 123;

-- Limpar sessões expiradas
DELETE FROM profile_active_sessions 
WHERE expires_at < NOW();
```

## 🚨 Solução de Problemas

### Modal Não Aparece
1. Verifique se a tabela foi criada
2. Confirme permissões do banco de dados
3. Verifique se há sessões ativas

### Sessão Não é Criada
1. Verifique conexão com banco
2. Confirme se `session_start()` está sendo chamado
3. Verifique se há erros nos logs PHP

### CSS Não Carrega
1. Verifique caminho do arquivo CSS
2. Confirme permissões da pasta `assets/`
3. Limpe cache do navegador

## 🔒 Considerações de Segurança

### Implementado
- ✅ Validação de sessão por ID único
- ✅ Prevenção de acesso simultâneo
- ✅ Limpeza automática de sessões
- ✅ Detecção de User-Agent
- ✅ Bloqueio por IP

### Futuras Melhorias
- 🔄 Geolocalização por IP
- 🔄 Notificações por email
- 🔄 Histórico de dispositivos
- 🔄 Opção "confiar neste dispositivo"

## 📞 Suporte

Para dúvidas ou problemas:

1. Verifique os logs de erro do PHP
2. Teste a conexão com o banco
3. Confirme a estrutura das tabelas
4. Revise este README

---

**Nota**: Esta implementação foi desenvolvida seguindo as melhores práticas de segurança e usabilidade, mantendo a identidade visual e experiência do PipoCine.
