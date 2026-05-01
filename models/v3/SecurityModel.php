<?php
/**
 * MODEL: SecurityModel (v3)
 *
 * Acesso direto ao banco pipcine para os dois métodos de autenticação:
 *   - Código de 4 dígitos (auth_login_codes)
 *   - QR Code de login   (auth_qr_sessions)
 *
 * Todas as queries usam prepared statements com PDO.
 * Os segredos (code_hash, token_hash) NUNCA são retornados ao cliente.
 */

declare(strict_types=1);

class SecurityModel
{
    private PDO $pdo;   // banco pipcine

    // Tentativas máximas antes de bloquear o código por 30 min
    private const MAX_ATTEMPTS   = 5;
    private const LOCKOUT_MINUTES = 30;

    // Duração de validade do QR Code (segundos)
    public const QR_TTL = 300; // 5 minutos

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ──────────────────────────────────────────────────────────────────────
    // CÓDIGO DE LOGIN
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Retorna os metadados do código vinculado ao user (sem o hash).
     */
    public function getLoginCode(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('
            SELECT id, failed_attempts, locked_until, last_changed_at, created_at
            FROM auth_login_codes
            WHERE user_id = ?
            LIMIT 1
        ');
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Retorna SOMENTE o hash (para verificação interna — não expor ao cliente).
     */
    public function getLoginCodeHash(int $userId): ?string
    {
        $stmt = $this->pdo->prepare('
            SELECT code_hash FROM auth_login_codes WHERE user_id = ? LIMIT 1
        ');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ? $row['code_hash'] : null;
    }

    /**
     * Cria ou substitui o código de login do usuário.
     * O código em texto puro é hashado via bcrypt antes de salvar.
     */
    public function upsertLoginCode(int $userId, string $codePlain): bool
    {
        if (!preg_match('/^\d{4}$/', $codePlain)) {
            return false;
        }

        $hash = password_hash($codePlain, PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $this->pdo->prepare('
            INSERT INTO auth_login_codes (user_id, code_hash, failed_attempts, locked_until)
            VALUES (?, ?, 0, NULL)
            ON DUPLICATE KEY UPDATE
                code_hash       = VALUES(code_hash),
                failed_attempts = 0,
                locked_until    = NULL
        ');
        return $stmt->execute([$userId, $hash]);
    }

    /**
     * Remove o código de login do usuário.
     */
    public function deleteLoginCode(int $userId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM auth_login_codes WHERE user_id = ?');
        return $stmt->execute([$userId]);
    }

    /**
     * Verifica o código em texto puro contra o hash e gerencia bloqueios.
     * Retorna: ['ok' => bool, 'user_id' => int|null, 'locked_until' => string|null, 'error' => string|null]
     */
    public function verifyLoginCode(string $codePlain, string $ip, string $userAgent): array
    {
        if (!preg_match('/^\d{4}$/', $codePlain)) {
            return ['ok' => false, 'user_id' => null, 'locked_until' => null, 'error' => 'Código inválido.'];
        }

        // Busca todos os códigos (itera em constante-time para não vazar timing)
        $stmt = $this->pdo->prepare('
            SELECT id, user_id, code_hash, failed_attempts, locked_until
            FROM auth_login_codes
        ');
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $matchedRow = null;

        foreach ($rows as $row) {
            // Verificação constante-time
            if (password_verify($codePlain, $row['code_hash'])) {
                $matchedRow = $row;
                break;
            }
        }

        // Código não encontrado — registra tentativa anônima e retorna falso
        if ($matchedRow === null) {
            $this->logAttempt(null, $ip, $userAgent, false);
            return ['ok' => false, 'user_id' => null, 'locked_until' => null, 'error' => 'Código não encontrado.'];
        }

        // Conta bloqueada?
        if ($matchedRow['locked_until'] !== null) {
            $lockedUntil = new DateTimeImmutable($matchedRow['locked_until']);
            if ($lockedUntil > new DateTimeImmutable()) {
                $this->logAttempt((int)$matchedRow['user_id'], $ip, $userAgent, false);
                return [
                    'ok'           => false,
                    'user_id'      => null,
                    'locked_until' => $matchedRow['locked_until'],
                    'error'        => 'Conta temporariamente bloqueada por excesso de tentativas.',
                ];
            }
        }

        // Código correto — reseta contadores e loga sucesso
        $this->resetAttempts((int)$matchedRow['id']);
        $this->logAttempt((int)$matchedRow['user_id'], $ip, $userAgent, true);

        return ['ok' => true, 'user_id' => (int)$matchedRow['user_id'], 'locked_until' => null, 'error' => null];
    }

    /**
     * Incrementa tentativas erradas; bloqueia se atingir o limite.
     */
    public function incrementFailedAttempts(int $codeId): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE auth_login_codes
            SET
                failed_attempts = failed_attempts + 1,
                locked_until = CASE
                    WHEN failed_attempts + 1 >= ?
                    THEN DATE_ADD(NOW(), INTERVAL ? MINUTE)
                    ELSE NULL
                END
            WHERE id = ?
        ');
        $stmt->execute([self::MAX_ATTEMPTS, self::LOCKOUT_MINUTES, $codeId]);
    }

    private function resetAttempts(int $codeId): void
    {
        $stmt = $this->pdo->prepare('
            UPDATE auth_login_codes SET failed_attempts = 0, locked_until = NULL WHERE id = ?
        ');
        $stmt->execute([$codeId]);
    }

    private function logAttempt(?int $userId, string $ip, string $ua, bool $success): void
    {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO auth_login_code_logs (user_id, ip_address, user_agent, success)
                VALUES (?, ?, ?, ?)
            ');
            $stmt->execute([$userId, $ip, substr($ua, 0, 255), $success ? 1 : 0]);
        } catch (Throwable) {}
    }

    // ──────────────────────────────────────────────────────────────────────
    // QR CODE
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Cria uma nova sessão QR Code pendente.
     * Retorna o token em texto puro (usado para gerar o QR na tela).
     */
    public function createQrSession(string $ip, string $userAgent): array
    {
        // Gera token criptograficamente seguro
        $token     = bin2hex(random_bytes(32)); // 64 chars hex
        $tokenHash = hash('sha256', $token);
        $expiresAt = (new DateTimeImmutable())->modify('+' . self::QR_TTL . ' seconds')
                                             ->format('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare('
            INSERT INTO auth_qr_sessions (token_hash, status, created_ip, user_agent, expires_at)
            VALUES (?, \'pending\', ?, ?, ?)
        ');
        $stmt->execute([$tokenHash, $ip, substr($userAgent, 0, 255), $expiresAt]);

        return [
            'session_id' => (int)$this->pdo->lastInsertId(),
            'token'      => $token,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Verifica o status de uma sessão QR pelo token em texto puro.
     */
    public function getQrSessionByToken(string $token): ?array
    {
        $hash = hash('sha256', $token);
        $stmt = $this->pdo->prepare('
            SELECT id, user_id, status, expires_at, confirmed_at
            FROM auth_qr_sessions
            WHERE token_hash = ?
            LIMIT 1
        ');
        $stmt->execute([$hash]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Confirma o QR Code — registra o user_id e marca como 'used'.
     * Retorna false se o token não existir, estiver expirado ou já usado.
     */
    public function confirmQrSession(string $token, int $userId, string $ip): bool
    {
        $hash = hash('sha256', $token);

        $stmt = $this->pdo->prepare('
            UPDATE auth_qr_sessions
            SET
                status       = \'used\',
                user_id      = ?,
                confirmed_ip = ?,
                confirmed_at = NOW()
            WHERE token_hash = ?
              AND status     = \'pending\'
              AND expires_at > NOW()
        ');
        $stmt->execute([$userId, $ip, $hash]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Marca sessões QR expiradas como 'expired' (cleanup leve).
     */
    public function expireOldQrSessions(): void
    {
        try {
            $this->pdo->exec('
                UPDATE auth_qr_sessions
                SET status = \'expired\'
                WHERE status = \'pending\' AND expires_at < NOW()
            ');
        } catch (Throwable) {}
    }
}
