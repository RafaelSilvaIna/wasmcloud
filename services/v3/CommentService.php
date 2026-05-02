<?php

declare(strict_types=1);

namespace Services\V3;

use Models\V3\CommentModel;
use PDO;

/**
 * CommentService — Pipocine v3
 *
 * Contém toda a lógica de negócio do sistema de comentários:
 * validação, sanitização, resolução de perfis via pipcine.profiles
 * e orquestração das operações no CommentModel.
 */
class CommentService
{
    private CommentModel $model;
    private PDO          $pipoDB;   // banco pipcine (comentários + perfis)

    public function __construct(CommentModel $model, PDO $pipoDB)
    {
        $this->model  = $model;
        $this->pipoDB = $pipoDB;
    }

    // ──────────────────────────────────────────────────────────────
    // LISTAGEM
    // ──────────────────────────────────────────────────────────────

    /**
     * Lista comentários raiz de um conteúdo com dados de perfil resolvidos.
     */
    public function list(int $contentId, string $contentType, int $profileId, int $page = 1): array
    {
        $contentType = $this->validateContentType($contentType);
        $rows        = $this->model->listByContent($contentId, $contentType, $profileId, $page);
        $total       = $this->model->countByContent($contentId, $contentType);

        $profileIds = array_unique(array_column($rows, 'profile_id'));
        $profiles   = $this->resolveProfiles($profileIds);

        $comments = [];
        foreach ($rows as $row) {
            $comments[] = $this->formatComment($row, $profiles);
        }

        return [
            'comments'    => $comments,
            'total'       => $total,
            'page'        => $page,
            'has_more'    => ($page * 20) < $total,
        ];
    }

    /**
     * Lista respostas de um comentário específico.
     */
    public function listReplies(int $parentId, int $profileId): array
    {
        $rows       = $this->model->listReplies($parentId, $profileId);
        $profileIds = array_unique(array_column($rows, 'profile_id'));
        $profiles   = $this->resolveProfiles($profileIds);

        return array_map(fn($r) => $this->formatComment($r, $profiles), $rows);
    }

    // ──────────────────────────────────────────────────────────────
    // CRIAR
    // ──────────────────────────────────────────────────────────────

    /**
     * Cria um novo comentário (ou resposta).
     * Extrai e persiste menções @username automaticamente.
     */
    public function create(array $data, int $profileId, int $userId): array
    {
        $contentId   = (int) ($data['content_id']   ?? 0);
        $contentType = $this->validateContentType($data['content_type'] ?? 'movie');
        $parentId    = isset($data['parent_id']) ? (int) $data['parent_id'] : null;
        $body        = $this->sanitizeBody($data['body'] ?? '');

        if (mb_strlen($body) < 1) {
            throw new \InvalidArgumentException('O comentário não pode estar vazio.');
        }
        if (mb_strlen($body) > 2000) {
            throw new \InvalidArgumentException('Comentário muito longo (máximo 2000 caracteres).');
        }
        if ($contentId <= 0) {
            throw new \InvalidArgumentException('Conteúdo inválido.');
        }

        // Valida parent_id se for resposta
        if ($parentId !== null) {
            $parent = $this->model->findById($parentId);
            if (!$parent || $parent['is_deleted']) {
                throw new \InvalidArgumentException('Comentário original não encontrado.');
            }
            // Não permite reply de reply (máx 1 nível de aninhamento)
            if ($parent['parent_id'] !== null) {
                $parentId = (int) $parent['parent_id'];
            }
        }

        // Gera HTML com menções linkadas
        [$bodyHtml, $mentionedUsernames] = $this->buildBodyHtml($body);

        // Persiste
        $commentId = $this->model->create([
            'content_id'   => $contentId,
            'content_type' => $contentType,
            'profile_id'   => $profileId,
            'user_id'      => $userId,
            'parent_id'    => $parentId,
            'body'         => $body,
            'body_html'    => $bodyHtml,
        ]);

        // Resolve perfis mencionados pelo username (banco pipcine)
        if (!empty($mentionedUsernames)) {
            $mentionedIds = $this->resolveUsernamestoIds($mentionedUsernames);
            if (!empty($mentionedIds)) {
                $this->model->saveMentions($commentId, $mentionedIds, $profileId, $userId);
            }
        }

        // Retorna o comentário criado formatado
        $comment    = $this->model->findById($commentId);
        $profiles   = $this->resolveProfiles([$profileId]);

        return $this->formatComment(
            array_merge($comment, ['viewer_liked' => 0, 'replies_count' => 0]),
            $profiles
        );
    }

    // ──────────────────────────────────────────────────────────────
    // EDITAR
    // ──────────────────────────────────────────────────────────────

    /**
     * Edita o texto de um comentário do próprio usuário.
     */
    public function edit(int $commentId, int $profileId, int $userId, string $newBody): array
    {
        $body = $this->sanitizeBody($newBody);

        if (mb_strlen($body) < 1) {
            throw new \InvalidArgumentException('O comentário não pode estar vazio.');
        }
        if (mb_strlen($body) > 2000) {
            throw new \InvalidArgumentException('Comentário muito longo.');
        }

        [$bodyHtml,] = $this->buildBodyHtml($body);

        $ok = $this->model->update($commentId, $profileId, $userId, $body, $bodyHtml);
        if (!$ok) {
            throw new \RuntimeException('Não foi possível editar o comentário.');
        }

        $comment  = $this->model->findById($commentId);
        $profiles = $this->resolveProfiles([$profileId]);

        return $this->formatComment(
            array_merge($comment, ['viewer_liked' => 0, 'replies_count' => 0]),
            $profiles
        );
    }

    // ──────────────────────────────────────────────────────────────
    // DELETAR
    // ──────────────────────────────────────────────────────────────

    /**
     * Soft-delete de um comentário.
     */
    public function delete(int $commentId, int $profileId, int $userId, bool $isAdmin = false): void
    {
        $ok = $this->model->delete($commentId, $profileId, $userId, $isAdmin);
        if (!$ok) {
            throw new \RuntimeException('Comentário não encontrado ou sem permissão.');
        }
    }

    // ──────────────────────────────────────────────────────────────
    // LIKES
    // ──────────────────────────────────────────────────────────────

    public function toggleLike(int $commentId, int $profileId, int $userId): array
    {
        $added = $this->model->toggleLike($commentId, $profileId, $userId);
        $row   = $this->model->findById($commentId);

        return [
            'liked'       => $added,
            'likes_count' => $row ? (int) $row['likes_count'] : 0,
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // MENÇÕES
    // ──────────────────────────────────────────────────────────────

    /**
     * Retorna menções não lidas de um perfil (para badge/notificação).
     */
    public function getUnreadMentions(int $profileId): array
    {
        $rows     = $this->model->getUnreadMentions($profileId);
        $byIds    = array_unique(array_column($rows, 'by_profile_id'));
        $profiles = $this->resolveProfiles($byIds);

        return array_map(function ($row) use ($profiles) {
            $byProfileId = (int) $row['by_profile_id'];
            return [
                'mention_id'   => $row['mention_id'],
                'comment_id'   => $row['comment_id'],
                'body'         => $row['body'],
                'body_html'    => $row['body_html'],
                'content_id'   => $row['content_id'],
                'content_type' => $row['content_type'],
                'created_at'   => $row['created_at'],
                'by_profile'   => $profiles[$byProfileId] ?? null,
            ];
        }, $rows);
    }

    /**
     * Retorna todas as menções de um perfil (histórico).
     */
    public function getMentions(int $profileId, int $page = 1): array
    {
        $rows     = $this->model->getMentionsByProfile($profileId, $page);
        $byIds    = array_unique(array_column($rows, 'by_profile_id'));
        $profiles = $this->resolveProfiles($byIds);

        $items = array_map(function ($row) use ($profiles) {
            $byProfileId = (int) $row['by_profile_id'];
            return [
                'mention_id'   => $row['mention_id'],
                'comment_id'   => $row['comment_id'],
                'body'         => $row['body'],
                'body_html'    => $row['body_html'],
                'content_id'   => $row['content_id'],
                'content_type' => $row['content_type'],
                'is_read'      => (bool) $row['is_read'],
                'created_at'   => $row['created_at'],
                'by_profile'   => $profiles[$byProfileId] ?? null,
            ];
        }, $rows);

        return [
            'mentions' => $items,
            'total'    => count($items),
            'page'     => $page,
        ];
    }

    /**
     * Conta menções não lidas (badge).
     */
    public function countUnreadMentions(int $profileId): int
    {
        return $this->model->countUnreadMentions($profileId);
    }

    /**
     * Marca menção(ões) como lida(s).
     */
    public function markMentionsRead(int $profileId, ?int $mentionId = null): void
    {
        $this->model->markMentionsRead($profileId, $mentionId);
    }

    // ──────────────────────────────────────────────────────────────
    // HELPERS INTERNOS
    // ──────────────────────────────────────────────────────────────

    /**
     * Resolve dados de perfil da tabela pipcine.profiles por IDs.
     * Retorna mapa [profileId => profileData].
     */
    private function resolveProfiles(array $profileIds): array
    {
        if (empty($profileIds)) return [];

        $placeholders = implode(',', array_fill(0, count($profileIds), '?'));
        $stmt = $this->pipoDB->prepare("
            SELECT id, profile_name, username, profile_image
            FROM profiles
            WHERE id IN ({$placeholders})
        ");
        $stmt->execute($profileIds);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['id']] = [
                'id'            => (int) $row['id'],
                'profile_name'  => $row['profile_name'],
                'username'      => $row['username'],
                'profile_image' => $row['profile_image'],
            ];
        }
        return $map;
    }

    /**
     * Resolve IDs de perfil a partir de uma lista de usernames.
     */
    private function resolveUsernamestoIds(array $usernames): array
    {
        if (empty($usernames)) return [];

        $placeholders = implode(',', array_fill(0, count($usernames), '?'));
        $stmt = $this->pipoDB->prepare("
            SELECT id FROM profiles WHERE username IN ({$placeholders})
        ");
        $stmt->execute($usernames);
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id');
    }

    /**
     * Formata um comentário para a resposta da API,
     * incluindo o perfil do autor resolvido.
     */
    private function formatComment(array $row, array $profiles): array
    {
        $profileId = (int) $row['profile_id'];

        return [
            'id'            => (int) $row['id'],
            'content_id'    => (int) $row['content_id'],
            'content_type'  => $row['content_type'],
            'parent_id'     => $row['parent_id'] ? (int) $row['parent_id'] : null,
            'body'          => $row['body'],
            'body_html'     => $row['body_html'],
            'is_edited'     => (bool) $row['is_edited'],
            'edited_at'     => $row['edited_at'],
            'likes_count'   => (int) $row['likes_count'],
            'viewer_liked'  => (bool) ($row['viewer_liked'] ?? false),
            'replies_count' => (int) ($row['replies_count'] ?? 0),
            'created_at'    => $row['created_at'],
            'profile'       => $profiles[$profileId] ?? [
                'id'            => $profileId,
                'profile_name'  => 'Usuário',
                'username'      => 'usuario',
                'profile_image' => 'https://api.dicebear.com/7.x/adventurer/svg?seed=' . $profileId,
            ],
        ];
    }

    /**
     * Sanitiza o corpo do comentário: strip tags, trim, normaliza espaços.
     */
    private function sanitizeBody(string $body): string
    {
        $body = strip_tags($body);
        $body = preg_replace('/\s+/', ' ', $body);
        return trim($body);
    }

    /**
     * Constrói o HTML do comentário, linkando menções @username.
     * Retorna [$bodyHtml, $mentionedUsernames].
     */
    private function buildBodyHtml(string $body): array
    {
        $mentioned = [];

        $html = htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // Detecta @username e cria spans linkáveis
        $html = preg_replace_callback(
            '/@([a-zA-Z0-9_]{1,30})/',
            function ($m) use (&$mentioned) {
                $username    = $m[1];
                $mentioned[] = strtolower($username);
                return '<span class="pip-mention" data-username="' . htmlspecialchars($username) . '">@' . htmlspecialchars($username) . '</span>';
            },
            $html
        );

        // Converte quebras de linha em <br>
        $html = nl2br($html);

        return [$html, array_unique($mentioned)];
    }

    private function validateContentType(string $type): string
    {
        $map = [
            'movie'  => 'movie',
            'filme'  => 'movie',
            'serie'  => 'serie',
            'series' => 'serie',
            'tv'     => 'serie',
        ];
        return $map[strtolower($type)] ?? 'movie';
    }
}
