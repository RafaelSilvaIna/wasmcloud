<?php

declare(strict_types=1);

namespace Models\V3;

use PDO;

/**
 * CommentModel — Pipocine v3
 *
 * Responsável por toda a persistência de comentários, respostas,
 * likes e menções nas tabelas pip_comments, pip_comment_likes
 * e pip_comment_mentions (banco pipcine).
 */
class CommentModel
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ──────────────────────────────────────────────────────────────
    // LEITURA
    // ──────────────────────────────────────────────────────────────

    /**
     * Retorna os comentários raiz de um conteúdo, paginados,
     * acompanhados dos dados do perfil do autor.
     * Inclui o número de respostas de cada comentário.
     */
    public function listByContent(
        int    $contentId,
        string $contentType,
        int    $profileId,
        int    $page   = 1,
        int    $limit  = 20
    ): array {
        $offset = ($page - 1) * $limit;

        $sql = "
            SELECT
                c.id,
                c.content_id,
                c.content_type,
                c.profile_id,
                c.user_id,
                c.parent_id,
                c.body,
                c.body_html,
                c.is_edited,
                c.edited_at,
                c.likes_count,
                c.created_at,
                -- Dados do perfil do autor (banco pipcine via cross-join não disponível,
                -- portanto retornamos IDs e o PHP resolve via JOIN lógico)
                (SELECT COUNT(*) FROM pip_comments r
                 WHERE r.parent_id = c.id AND r.is_deleted = 0) AS replies_count,
                (SELECT COUNT(*) FROM pip_comment_likes l
                 WHERE l.comment_id = c.id AND l.profile_id = :profileId) AS viewer_liked
            FROM pip_comments c
            WHERE c.content_id   = :contentId
              AND c.content_type = :contentType
              AND c.parent_id    IS NULL
              AND c.is_deleted   = 0
            ORDER BY c.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':contentId',   $contentId,   PDO::PARAM_INT);
        $stmt->bindValue(':contentType', $contentType, PDO::PARAM_STR);
        $stmt->bindValue(':profileId',   $profileId,   PDO::PARAM_INT);
        $stmt->bindValue(':limit',       $limit,       PDO::PARAM_INT);
        $stmt->bindValue(':offset',      $offset,      PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Conta o total de comentários raiz de um conteúdo.
     */
    public function countByContent(int $contentId, string $contentType): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM pip_comments
            WHERE content_id   = ?
              AND content_type = ?
              AND parent_id    IS NULL
              AND is_deleted   = 0
        ");
        $stmt->execute([$contentId, $contentType]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Retorna as respostas de um comentário específico.
     */
    public function listReplies(int $parentId, int $profileId): array
    {
        $sql = "
            SELECT
                c.id,
                c.content_id,
                c.content_type,
                c.profile_id,
                c.user_id,
                c.parent_id,
                c.body,
                c.body_html,
                c.is_edited,
                c.edited_at,
                c.likes_count,
                c.created_at,
                (SELECT COUNT(*) FROM pip_comment_likes l
                 WHERE l.comment_id = c.id AND l.profile_id = :profileId) AS viewer_liked
            FROM pip_comments c
            WHERE c.parent_id  = :parentId
              AND c.is_deleted = 0
            ORDER BY c.created_at ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':parentId',  $parentId,  PDO::PARAM_INT);
        $stmt->bindValue(':profileId', $profileId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Busca um comentário por ID (sem filtro de deleção — para validações internas).
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM pip_comments WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // ──────────────────────────────────────────────────────────────
    // ESCRITA
    // ──────────────────────────────────────────────────────────────

    /**
     * Insere um novo comentário e retorna o ID gerado.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO pip_comments
                (content_id, content_type, profile_id, user_id, parent_id, body, body_html)
            VALUES
                (:content_id, :content_type, :profile_id, :user_id, :parent_id, :body, :body_html)
        ");

        $stmt->execute([
            ':content_id'   => $data['content_id'],
            ':content_type' => $data['content_type'],
            ':profile_id'   => $data['profile_id'],
            ':user_id'      => $data['user_id'],
            ':parent_id'    => $data['parent_id'] ?? null,
            ':body'         => $data['body'],
            ':body_html'    => $data['body_html'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Edita o corpo de um comentário.
     * Só permite edição pelo próprio dono (profile_id e user_id).
     */
    public function update(int $id, int $profileId, int $userId, string $body, string $bodyHtml): bool
    {
        $stmt = $this->db->prepare("
            UPDATE pip_comments
            SET body      = :body,
                body_html = :body_html,
                is_edited = 1,
                edited_at = NOW()
            WHERE id         = :id
              AND profile_id = :profile_id
              AND user_id    = :user_id
              AND is_deleted = 0
        ");

        $stmt->execute([
            ':body'       => $body,
            ':body_html'  => $bodyHtml,
            ':id'         => $id,
            ':profile_id' => $profileId,
            ':user_id'    => $userId,
        ]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Soft-delete de um comentário.
     * Admins (role != null) podem deletar qualquer comentário.
     * Usuários normais só deletam os seus.
     */
    public function delete(int $id, int $profileId, int $userId, bool $isAdmin = false): bool
    {
        if ($isAdmin) {
            $stmt = $this->db->prepare("
                UPDATE pip_comments
                SET is_deleted = 1, deleted_at = NOW(), deleted_by = :userId
                WHERE id = :id AND is_deleted = 0
            ");
            $stmt->execute([':id' => $id, ':userId' => $userId]);
        } else {
            $stmt = $this->db->prepare("
                UPDATE pip_comments
                SET is_deleted = 1, deleted_at = NOW(), deleted_by = :userId
                WHERE id         = :id
                  AND profile_id = :profileId
                  AND user_id    = :userId
                  AND is_deleted = 0
            ");
            $stmt->execute([':id' => $id, ':profileId' => $profileId, ':userId' => $userId]);
        }

        return $stmt->rowCount() > 0;
    }

    // ──────────────────────────────────────────────────────────────
    // LIKES
    // ──────────────────────────────────────────────────────────────

    /**
     * Alterna o like de um perfil num comentário.
     * Retorna true se adicionou, false se removeu.
     */
    public function toggleLike(int $commentId, int $profileId, int $userId): bool
    {
        // Verifica se já curtiu
        $check = $this->db->prepare("
            SELECT id FROM pip_comment_likes
            WHERE comment_id = ? AND profile_id = ?
            LIMIT 1
        ");
        $check->execute([$commentId, $profileId]);

        if ($check->fetchColumn()) {
            // Remove like
            $this->db->prepare("
                DELETE FROM pip_comment_likes
                WHERE comment_id = ? AND profile_id = ?
            ")->execute([$commentId, $profileId]);

            $this->db->prepare("
                UPDATE pip_comments
                SET likes_count = GREATEST(0, likes_count - 1)
                WHERE id = ?
            ")->execute([$commentId]);

            return false;
        }

        // Adiciona like
        $this->db->prepare("
            INSERT INTO pip_comment_likes (comment_id, profile_id, user_id)
            VALUES (?, ?, ?)
        ")->execute([$commentId, $profileId, $userId]);

        $this->db->prepare("
            UPDATE pip_comments SET likes_count = likes_count + 1 WHERE id = ?
        ")->execute([$commentId]);

        return true;
    }

    // ──────────────────────────────────────────────────────────────
    // MENÇÕES
    // ──────────────────────────────────────────────────────────────

    /**
     * Registra as menções extraídas de um comentário.
     * Ignora menção ao próprio perfil.
     */
    public function saveMentions(int $commentId, array $mentionedProfileIds, int $byProfileId, int $byUserId): void
    {
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO pip_comment_mentions
                (comment_id, mentioned_profile_id, mentioned_user_id, mentioned_by_profile_id)
            SELECT :commentId, p.id, p.user_id, :byProfileId
            FROM profiles p
            WHERE p.id = :mentionedProfileId
              AND p.id != :byProfileId
        ");

        foreach ($mentionedProfileIds as $mentionedProfileId) {
            $stmt->execute([
                ':commentId'         => $commentId,
                ':byProfileId'       => $byProfileId,
                ':mentionedProfileId'=> $mentionedProfileId,
            ]);
        }
    }

    /**
     * Busca menções não lidas para um perfil (notificações).
     */
    public function getUnreadMentions(int $profileId, int $limit = 20): array
    {
        $stmt = $this->db->prepare("
            SELECT
                m.id             AS mention_id,
                m.comment_id,
                m.mentioned_by_profile_id AS by_profile_id,
                m.is_read,
                m.created_at,
                c.body,
                c.body_html,
                c.content_id,
                c.content_type,
                c.created_at     AS comment_date
            FROM pip_comment_mentions m
            JOIN pip_comments c ON c.id = m.comment_id AND c.is_deleted = 0
            WHERE m.mentioned_profile_id = :profileId
              AND m.is_read              = 0
            ORDER BY m.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':profileId', $profileId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',     $limit,     PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna TODAS as menções de um perfil (com paginação).
     */
    public function getMentionsByProfile(int $profileId, int $page = 1, int $limit = 30): array
    {
        $offset = ($page - 1) * $limit;

        $stmt = $this->db->prepare("
            SELECT
                m.id             AS mention_id,
                m.comment_id,
                m.mentioned_by_profile_id AS by_profile_id,
                m.is_read,
                m.created_at,
                c.body,
                c.body_html,
                c.content_id,
                c.content_type,
                c.created_at     AS comment_date
            FROM pip_comment_mentions m
            JOIN pip_comments c ON c.id = m.comment_id AND c.is_deleted = 0
            WHERE m.mentioned_profile_id = :profileId
            ORDER BY m.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':profileId', $profileId, PDO::PARAM_INT);
        $stmt->bindValue(':limit',     $limit,     PDO::PARAM_INT);
        $stmt->bindValue(':offset',    $offset,    PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Conta menções não lidas de um perfil (badge de notificação).
     */
    public function countUnreadMentions(int $profileId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM pip_comment_mentions m
            JOIN pip_comments c ON c.id = m.comment_id AND c.is_deleted = 0
            WHERE m.mentioned_profile_id = ? AND m.is_read = 0
        ");
        $stmt->execute([$profileId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Marca menções como lidas.
     */
    public function markMentionsRead(int $profileId, ?int $mentionId = null): void
    {
        if ($mentionId) {
            $stmt = $this->db->prepare("
                UPDATE pip_comment_mentions
                SET is_read = 1, read_at = NOW()
                WHERE id = ? AND mentioned_profile_id = ?
            ");
            $stmt->execute([$mentionId, $profileId]);
        } else {
            $stmt = $this->db->prepare("
                UPDATE pip_comment_mentions
                SET is_read = 1, read_at = NOW()
                WHERE mentioned_profile_id = ? AND is_read = 0
            ");
            $stmt->execute([$profileId]);
        }
    }
}
