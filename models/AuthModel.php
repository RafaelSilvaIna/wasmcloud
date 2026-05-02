<?php
class AuthModel {
    private $dbCineveo;
    private $dbPipocine;

    /**
     * Construtor da classe: recebe a conexão com o banco de dados.
     */
    public function __construct(PDO $pdoCineveo, PDO $pdoPipocine = null) {
        $this->dbCineveo = $pdoCineveo;
        $this->dbPipocine = $pdoPipocine;
    }

    /**
     * Procura um utilizador na base de dados pelo seu e-mail.
     * Retorna todos os dados do utilizador.
     */
    public function getUserByEmail(string $email) {
        $stmt = $this->dbCineveo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Recolhe dados específicos de um utilizador através do seu ID para carregar o perfil.
     */
    public function getUserData(int $id) {
        // CORREÇÃO: Utilizando 'plan_expiration', que é o nome correto da coluna
        // na tabela users, conforme evidenciado pelos componentes Premium.
        $stmt = $this->dbCineveo->prepare("SELECT id, username, name, full_name, profile_pic_url, email, plan_type, plan_expiration, role FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cria uma nova sessão para o utilizador após o login na tabela user_sessions.
     */
    public function createSession(int $userId, string $tokenHash, string $expiresAt) {
        $stmt = $this->dbCineveo->prepare("INSERT INTO user_sessions (token_hash, user_id, expires_at) VALUES (?, ?, ?)");
        return $stmt->execute([$tokenHash, $userId, $expiresAt]);
    }

    public function getDbPipocine(): ?PDO {
        return $this->dbPipocine;
    }

    /**
     * Verifica se o perfil já possui uma sessão ativa em outro dispositivo
     */
    public function hasActiveSession(int $profileId): bool {
        if (!$this->dbPipocine) return false;
        
        $stmt = $this->dbPipocine->prepare("
            SELECT COUNT(*) as count 
            FROM profile_active_sessions 
            WHERE profile_id = ? AND is_active = 1 AND expires_at > NOW()
        ");
        $stmt->execute([$profileId]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    }

    /**
     * Cria uma sessão ativa para controle de acesso único por perfil
     */
    public function createActiveSession(int $profileId, int $userId, string $sessionId, string $expiresAt): bool {
        if (!$this->dbPipocine) return false;
        
        // Desativa apenas a sessão anterior do MESMO session_id (se houver)
        $stmt = $this->dbPipocine->prepare("
            UPDATE profile_active_sessions 
            SET is_active = 0 
            WHERE session_id = ? AND is_active = 1
        ");
        $stmt->execute([$sessionId]);
        
        $stmt = $this->dbPipocine->prepare("
            INSERT INTO profile_active_sessions 
            (profile_id, user_id, session_id, ip_address, user_agent, expires_at) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        return $stmt->execute([$profileId, $userId, $sessionId, $ipAddress, $userAgent, $expiresAt]);
    }

    /**
     * Desativa todas as sessões de um perfil
     */
    public function deactivateProfileSessions(int $profileId): bool {
        if (!$this->dbPipocine) return false;
        
        $stmt = $this->dbPipocine->prepare("
            UPDATE profile_active_sessions 
            SET is_active = 0 
            WHERE profile_id = ?
        ");
        return $stmt->execute([$profileId]);
    }

    /**
     * Atualiza a última atividade da sessão do perfil
     */
    public function updateSessionActivity(string $sessionId): bool {
        if (!$this->dbPipocine) return false;
        
        $stmt = $this->dbPipocine->prepare("
            UPDATE profile_active_sessions 
            SET last_activity = NOW() 
            WHERE session_id = ? AND is_active = 1
        ");
        return $stmt->execute([$sessionId]);
    }

    /**
     * Limpa sessões expiradas de perfis
     */
    public function cleanupExpiredSessions(): bool {
        if (!$this->dbPipocine) return false;
        
        $stmt = $this->dbPipocine->prepare("
            DELETE FROM profile_active_sessions 
            WHERE expires_at < NOW() OR last_activity < DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ");
        return $stmt->execute();
    }

    /**
     * Obtém informações da sessão ativa do perfil
     */
    public function getActiveProfileSession(int $profileId): array {
        if (!$this->dbPipocine) return [];
        
        $stmt = $this->dbPipocine->prepare("
            SELECT * FROM profile_active_sessions 
            WHERE profile_id = ? AND is_active = 1 AND expires_at > NOW()
            ORDER BY last_activity DESC
            LIMIT 1
        ");
        $stmt->execute([$profileId]);
        return $stmt->fetch() ?: [];
    }
}