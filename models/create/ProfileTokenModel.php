<?php

namespace Models\Create;

/**
 * Gerencia tokens temporários de criação/edição de perfil.
 *
 * Fluxo:
 *  1. generateToken()  → cria um token de 1 hora no banco
 *  2. validateToken()  → valida, verifica expiração e se já foi usado
 *  3. consumeToken()   → marca como usado (chamado logo após a criação)
 *  4. cleanExpired()   → limpeza periódica de tokens vencidos
 */
class ProfileTokenModel
{
    public function __construct(private \PDO $pdo) {}

    // ── Geração ──────────────────────────────────────────────────────────────

    public function generateToken(int $userId, string $action = 'create', ?int $profileId = null): string
    {
        // Remove tokens antigos não usados do mesmo user
        $this->cleanUserOldTokens($userId, $action);

        $token     = bin2hex(random_bytes(48));  // 96 chars hex
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hora

        $stmt = $this->pdo->prepare(
            'INSERT INTO profile_creation_tokens
                (user_id, token, action, profile_id, expires_at)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $token, $action, $profileId, $expiresAt]);

        return $token;
    }

    // ── Validação ─────────────────────────────────────────────────────────────

    /**
     * Retorna a linha do token se válido e não expirado, ou null.
     */
    public function validateToken(string $token, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM profile_creation_tokens
              WHERE token = ?
                AND user_id = ?
                AND used = 0
                AND expires_at > NOW()
              LIMIT 1'
        );
        $stmt->execute([$token, $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    // ── Consumo ───────────────────────────────────────────────────────────────

    public function consumeToken(string $token): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE profile_creation_tokens SET used = 1 WHERE token = ?'
        );
        $stmt->execute([$token]);
    }

    // ── Limpeza ───────────────────────────────────────────────────────────────

    public function cleanExpired(): void
    {
        $this->pdo->exec('DELETE FROM profile_creation_tokens WHERE expires_at < NOW()');
    }

    private function cleanUserOldTokens(int $userId, string $action): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM profile_creation_tokens
              WHERE user_id = ? AND action = ? AND used = 0'
        );
        $stmt->execute([$userId, $action]);
    }
}
