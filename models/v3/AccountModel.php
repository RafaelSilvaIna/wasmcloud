<?php

/**
 * MODEL: AccountModel (v3)
 *
 * Responsável pela camada de acesso a dados da conta CineVEO e dos
 * perfis PipoCine vinculados ao utilizador autenticado.
 *
 * Bancos de dados:
 *   - $dbCineveo  → banco "cineveo"   (dados da conta do utilizador)
 *   - $dbPipcine  → banco "pipcine"   (perfis vinculados ao utilizador)
 */
class AccountModel
{
    private PDO $dbCineveo;
    private ?PDO $dbPipcine;

    public function __construct(PDO $dbCineveo, ?PDO $dbPipcine = null)
    {
        $this->dbCineveo = $dbCineveo;
        $this->dbPipcine = $dbPipcine;
    }

    /**
     * Retorna os dados completos da conta CineVEO do utilizador autenticado.
     *
     * Colunas retornadas:
     *   id, full_name, username, email, profile_pic_url, plan_type, plan_expiration, role
     */
    public function getAccountById(int $userId): ?array
    {
        try {
            $stmt = $this->dbCineveo->prepare(
                "SELECT
                    id,
                    full_name,
                    username,
                    email,
                    profile_pic_url,
                    plan_type,
                    plan_expiration,
                    role
                 FROM users
                 WHERE id = ?
                 LIMIT 1"
            );
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Retorna a lista de perfis PipoCine vinculados ao utilizador (via user_id).
     *
     * Colunas retornadas:
     *   id, profile_name, username, profile_image, is_kids, is_watching, last_active_at
     */
    public function getProfilesByUserId(int $userId): array
    {
        if (!$this->dbPipcine) {
            return [];
        }

        try {
            $stmt = $this->dbPipcine->prepare(
                "SELECT
                    id,
                    profile_name,
                    username,
                    profile_image,
                    is_kids,
                    is_watching,
                    last_active_at
                 FROM profiles
                 WHERE user_id = ?
                 ORDER BY id ASC"
            );
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }
}
