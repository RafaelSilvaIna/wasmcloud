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

    public function createTwoFactorChallenge(int $userId, string $tokenHash, string $expiresAt): bool {
        if (!$this->dbPipocine) return false;

        $this->ensureTwoFactorChallengeTable();
        $this->cleanupTwoFactorChallenges();

        $stmt = $this->dbPipocine->prepare("
            INSERT INTO two_factor_login_challenges
            (user_id, token_hash, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $userId,
            $tokenHash,
            $_SERVER['REMOTE_ADDR'] ?? '',
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            $expiresAt
        ]);
    }

    public function getTwoFactorChallenge(string $tokenHash): ?array {
        if (!$this->dbPipocine) return null;

        $this->ensureTwoFactorChallengeTable();

        $stmt = $this->dbPipocine->prepare("
            SELECT id, user_id, token_hash, expires_at
            FROM two_factor_login_challenges
            WHERE token_hash = ? AND expires_at > NOW() AND consumed_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$tokenHash]);
        $challenge = $stmt->fetch(PDO::FETCH_ASSOC);

        return $challenge ?: null;
    }

    public function consumeTwoFactorChallenge(string $tokenHash): bool {
        if (!$this->dbPipocine) return false;

        $stmt = $this->dbPipocine->prepare("
            UPDATE two_factor_login_challenges
            SET consumed_at = NOW()
            WHERE token_hash = ? AND consumed_at IS NULL
        ");
        return $stmt->execute([$tokenHash]);
    }

    public function deleteTwoFactorChallenge(string $tokenHash): bool {
        if (!$this->dbPipocine) return false;

        $stmt = $this->dbPipocine->prepare("DELETE FROM two_factor_login_challenges WHERE token_hash = ?");
        return $stmt->execute([$tokenHash]);
    }

    private function cleanupTwoFactorChallenges(): void {
        if (!$this->dbPipocine) return;

        $stmt = $this->dbPipocine->prepare("
            DELETE FROM two_factor_login_challenges
            WHERE expires_at < NOW() OR consumed_at IS NOT NULL
        ");
        $stmt->execute();
    }

    private function ensureTwoFactorChallengeTable(): void {
        if (!$this->dbPipocine) return;

        $this->dbPipocine->exec("
            CREATE TABLE IF NOT EXISTS two_factor_login_challenges (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token_hash VARCHAR(64) NOT NULL,
                ip_address VARCHAR(45) NULL,
                user_agent VARCHAR(255) NULL,
                expires_at DATETIME NOT NULL,
                consumed_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_token_hash (token_hash),
                KEY idx_user_id (user_id),
                KEY idx_expires_at (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Verifica se o usuário tem assinatura ativa (paga ou cortesia).
     * O sistema novo de planos fica no banco Pipocine; o fallback mantém compatibilidade.
     */
    public function hasActivePremiumSubscription(int $userId): bool {
        if ($this->hasActiveRealPremiumSubscription($userId)) {
            return true;
        }

        foreach ([$this->dbPipocine, $this->dbCineveo] as $db) {
            if (!$db) {
                continue;
            }

            try {
                $stmt = $db->prepare("
                    SELECT COUNT(*) AS cnt
                    FROM user_subscriptions
                    WHERE user_id = ?
                      AND status = 'active'
                      AND expires_at > NOW()
                      AND plan_code <> 'casual'
                    LIMIT 1
                ");
                $stmt->execute([$userId]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ((int) ($row['cnt'] ?? 0) > 0) {
                    return true;
                }
            } catch (Throwable $e) {}

            try {
                $stmt = $db->prepare("
                    SELECT 1
                    FROM family_memberships fm
                    JOIN user_subscriptions s
                      ON s.user_id = fm.owner_user_id
                     AND s.status = 'active'
                     AND s.expires_at > NOW()
                    JOIN subscription_plans p
                      ON p.code = s.plan_code
                     AND p.is_active = 1
                     AND p.family_member_limit > 0
                    WHERE fm.member_user_id = ?
                      AND fm.status = 'active'
                    LIMIT 1
                ");
                $stmt->execute([$userId]);
                if ($stmt->fetchColumn()) {
                    return true;
                }
            } catch (Throwable $e) {}
        }

        return false;
    }

    public function hasActiveRealPremiumSubscription(int $userId): bool {
        foreach ([$this->dbPipocine, $this->dbCineveo] as $db) {
            if (!$db) {
                continue;
            }

            try {
                $stmt = $db->prepare("
                    SELECT 1
                    FROM user_subscriptions
                    WHERE user_id = ?
                      AND status = 'active'
                      AND expires_at > NOW()
                      AND plan_code <> 'casual'
                    LIMIT 1
                ");
                $stmt->execute([$userId]);
                if ($stmt->fetchColumn()) {
                    return true;
                }
            } catch (Throwable $e) {}
        }

        return false;
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

        $this->dropBrokenSessionCleanupTrigger();
        $this->cleanupExpiredSessions();

        $stmt = $this->dbPipocine->prepare("
            UPDATE profile_active_sessions
            SET is_active = 0
            WHERE user_id = ? AND is_active = 1
        ");
        $stmt->execute([$userId]);

        $stmt = $this->dbPipocine->prepare("
            UPDATE profiles
            SET is_watching = 0,
                current_session_id = NULL,
                last_active_at = NOW()
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        
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
        
        try {
            return $stmt->execute([$profileId, $userId, $sessionId, $ipAddress, $userAgent, $expiresAt]);
        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1442) {
                $this->dropBrokenSessionCleanupTrigger(true);
                try {
                    return $stmt->execute([$profileId, $userId, $sessionId, $ipAddress, $userAgent, $expiresAt]);
                } catch (Throwable $retryError) {
                    return false;
                }
            }

            throw $e;
        }
    }

    /**
     * MySQL não permite que um trigger altere a mesma tabela que disparou o INSERT.
     * A limpeza agora é feita em PHP antes de criar a sessão.
     */
    private function dropBrokenSessionCleanupTrigger(bool $force = false): void {
        if (!$this->dbPipocine) return;

        static $attempted = false;
        if ($attempted && !$force) return;
        $attempted = true;

        try {
            $this->dbPipocine->exec("DROP TRIGGER IF EXISTS cleanup_expired_profile_sessions");
        } catch (Throwable $e) {}
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

        $ttl = 95;

        try {
            $this->dbPipocine->exec("
                UPDATE profile_active_sessions pas
                LEFT JOIN account_devices ad
                  ON ad.user_id = pas.user_id
                 AND ad.session_id = pas.session_id
                 AND ad.is_active = 1
                 AND ad.last_heartbeat >= DATE_SUB(NOW(), INTERVAL {$ttl} SECOND)
                SET pas.is_active = 0
                WHERE pas.is_active = 1
                  AND (
                      pas.expires_at <= NOW()
                      OR pas.last_activity < DATE_SUB(NOW(), INTERVAL {$ttl} SECOND)
                      OR ad.id IS NULL
                  )
            ");
        } catch (Throwable $e) {
            $stmt = $this->dbPipocine->prepare("
                UPDATE profile_active_sessions
                SET is_active = 0
                WHERE is_active = 1
                  AND (expires_at <= NOW() OR last_activity < DATE_SUB(NOW(), INTERVAL {$ttl} SECOND))
            ");
            $stmt->execute();
        }

        try {
            $this->dbPipocine->exec("
                UPDATE profiles p
                LEFT JOIN profile_active_sessions pas
                  ON pas.profile_id = p.id
                 AND pas.session_id = p.current_session_id
                 AND pas.is_active = 1
                 AND pas.expires_at > NOW()
                SET p.is_watching = 0,
                    p.current_session_id = NULL,
                    p.last_active_at = NOW()
                WHERE p.is_watching = 1
                  AND p.current_session_id IS NOT NULL
                  AND pas.id IS NULL
            ");
        } catch (Throwable $e) {}

        $stmt = $this->dbPipocine->prepare("
            DELETE FROM profile_active_sessions
            WHERE (is_active = 0 AND last_activity < DATE_SUB(NOW(), INTERVAL 10 MINUTE))
               OR expires_at < NOW()
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
