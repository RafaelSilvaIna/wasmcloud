<?php

declare(strict_types=1);

namespace Controllers\V3;

use Services\V3\CommentService;

/**
 * CommentController — Pipocine v3
 *
 * Roteia as requisições HTTP para os métodos corretos do
 * CommentService e devolve respostas JSON padronizadas.
 *
 * Endpoints disponíveis:
 *   GET    /api/v3/comments?content_id=&content_type=&page=
 *   GET    /api/v3/comments/replies?parent_id=
 *   POST   /api/v3/comments/create
 *   PUT    /api/v3/comments/edit
 *   DELETE /api/v3/comments/delete
 *   POST   /api/v3/comments/like
 *   GET    /api/v3/mentions
 *   GET    /api/v3/mentions/unread-count
 *   POST   /api/v3/mentions/read
 */
class CommentController
{
    private CommentService $service;

    public function __construct(CommentService $service)
    {
        $this->service = $service;
    }

    // ──────────────────────────────────────────────────────────────
    // ROTEAMENTO PRINCIPAL
    // ──────────────────────────────────────────────────────────────

    public function handle(string $action, string $method): void
    {
        try {
            $this->requireAuth();

            match (true) {
                $action === 'comments'         && $method === 'GET'    => $this->list(),
                $action === 'comments/replies' && $method === 'GET'    => $this->replies(),
                $action === 'comments/create'  && $method === 'POST'   => $this->create(),
                $action === 'comments/edit'    && $method === 'PUT'    => $this->edit(),
                $action === 'comments/delete'  && $method === 'DELETE' => $this->delete(),
                $action === 'comments/like'    && $method === 'POST'   => $this->like(),
                $action === 'mentions'         && $method === 'GET'    => $this->mentions(),
                $action === 'mentions/unread-count' && $method === 'GET' => $this->unreadCount(),
                $action === 'mentions/read'    && $method === 'POST'   => $this->markRead(),
                default => $this->notFound(),
            };
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage(), 422);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage(), 403);
        } catch (\Throwable $e) {
            $this->error('Erro interno do servidor.', 500);
        }
    }

    // ──────────────────────────────────────────────────────────────
    // ACTIONS
    // ──────────────────────────────────────────────────────────────

    /** GET /api/v3/comments */
    private function list(): void
    {
        $contentId   = (int) ($_GET['content_id']   ?? 0);
        $contentType =       $_GET['content_type']  ?? 'movie';
        $page        = max(1, (int) ($_GET['page']  ?? 1));

        if ($contentId <= 0) {
            $this->error('content_id é obrigatório.', 422);
            return;
        }

        $result = $this->service->list($contentId, $contentType, $this->profileId(), $page);
        $this->ok($result);
    }

    /** GET /api/v3/comments/replies */
    private function replies(): void
    {
        $parentId = (int) ($_GET['parent_id'] ?? 0);

        if ($parentId <= 0) {
            $this->error('parent_id é obrigatório.', 422);
            return;
        }

        $replies = $this->service->listReplies($parentId, $this->profileId());
        $this->ok(['replies' => $replies]);
    }

    /** POST /api/v3/comments/create */
    private function create(): void
    {
        $body = $this->parseJsonBody();

        $comment = $this->service->create(
            $body,
            $this->profileId(),
            $this->userId()
        );

        $this->ok(['comment' => $comment], 201);
    }

    /** PUT /api/v3/comments/edit */
    private function edit(): void
    {
        $body      = $this->parseJsonBody();
        $commentId = (int) ($body['comment_id'] ?? 0);
        $newBody   =       $body['body']        ?? '';

        if ($commentId <= 0) {
            $this->error('comment_id é obrigatório.', 422);
            return;
        }

        $comment = $this->service->edit(
            $commentId,
            $this->profileId(),
            $this->userId(),
            $newBody
        );

        $this->ok(['comment' => $comment]);
    }

    /** DELETE /api/v3/comments/delete */
    private function delete(): void
    {
        $body      = $this->parseJsonBody();
        $commentId = (int) ($body['comment_id'] ?? 0);

        if ($commentId <= 0) {
            $this->error('comment_id é obrigatório.', 422);
            return;
        }

        $isAdmin = ($_SESSION['role'] ?? '') === 'admin';

        $this->service->delete(
            $commentId,
            $this->profileId(),
            $this->userId(),
            $isAdmin
        );

        $this->ok(['deletado' => true]);
    }

    /** POST /api/v3/comments/like */
    private function like(): void
    {
        $body      = $this->parseJsonBody();
        $commentId = (int) ($body['comment_id'] ?? 0);

        if ($commentId <= 0) {
            $this->error('comment_id é obrigatório.', 422);
            return;
        }

        $result = $this->service->toggleLike(
            $commentId,
            $this->profileId(),
            $this->userId()
        );

        $this->ok($result);
    }

    /** GET /api/v3/mentions */
    private function mentions(): void
    {
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->service->getMentions($this->profileId(), $page);
        $this->ok($result);
    }

    /** GET /api/v3/mentions/unread-count */
    private function unreadCount(): void
    {
        $count = $this->service->countUnreadMentions($this->profileId());
        $this->ok(['unread' => $count]);
    }

    /** POST /api/v3/mentions/read */
    private function markRead(): void
    {
        $body      = $this->parseJsonBody();
        $mentionId = isset($body['mention_id']) ? (int) $body['mention_id'] : null;

        $this->service->markMentionsRead($this->profileId(), $mentionId);
        $this->ok(['lido' => true]);
    }

    // ──────────────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────────────

    private function requireAuth(): void
    {
        if (empty($_SESSION['user_id'])) {
            $this->error('Autenticação necessária.', 401);
            exit;
        }
        if (empty($_SESSION['profile_id'])) {
            $this->error('Nenhum perfil selecionado.', 401);
            exit;
        }
    }

    private function userId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    private function profileId(): int
    {
        return (int) ($_SESSION['profile_id'] ?? 0);
    }

    private function parseJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if (!$raw) return [];
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private function ok(array $data, int $status = 200): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($status);
        echo json_encode(array_merge(['sucesso' => true], $data));
        exit;
    }

    private function error(string $message, int $status = 400): void
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($status);
        echo json_encode(['sucesso' => false, 'erro' => $message]);
        exit;
    }

    private function notFound(): void
    {
        $this->error('Rota não encontrada.', 404);
    }
}
