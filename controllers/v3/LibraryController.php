<?php

declare(strict_types=1);

namespace Controllers\V3;

use Models\V3\LibraryModel;

/**
 * LibraryController — endpoints /api/v3/library/*
 *
 * GET  /library/status?content_id=X&content_type=movie  → estado saved/liked
 * POST /library/save                                     → toggle salvar
 * POST /library/like                                     → toggle curtir
 * POST /library/watch                                    → registrar visualização
 * GET  /library/saved                                    → lista salvos
 * GET  /library/liked                                    → lista curtidos
 * GET  /library/history                                  → histórico
 * GET  /library/all                                      → os três juntos (página Minha Lista)
 */
class LibraryController
{
    public function __construct(private LibraryModel $model) {}

    public function handle(string $action, string $method): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $profileId = (int) ($_SESSION['profile_id'] ?? 0);
        $userId    = (int) ($_SESSION['user_id']    ?? 0);

        if (!$profileId || !$userId) {
            $this->json(['sucesso' => false, 'erro' => 'Não autenticado.'], 401);
            return;
        }

        match ($action) {
            'library/status'  => $this->status($profileId),
            'library/save'    => $this->toggleSave($profileId, $userId),
            'library/like'    => $this->toggleLike($profileId, $userId),
            'library/watch'   => $this->recordWatch($profileId, $userId),
            'library/saved'   => $this->getSaved($profileId),
            'library/liked'   => $this->getLiked($profileId),
            'library/history' => $this->getHistory($profileId),
            'library/all'     => $this->getAll($profileId),
            default           => $this->json(['sucesso' => false, 'erro' => 'Ação inválida.'], 404),
        };
    }

    // ── GET /library/status ───────────────────────────────────
    private function status(int $profileId): void
    {
        $contentId   = (int)    ($_GET['content_id']   ?? 0);
        $contentType = (string) ($_GET['content_type'] ?? 'movie');

        if (!$contentId) {
            $this->json(['sucesso' => false, 'erro' => 'content_id obrigatório.'], 400);
            return;
        }

        $status = $this->model->getStatus($profileId, $contentId, $contentType);
        $this->json(['sucesso' => true, 'dados' => $status]);
    }

    // ── POST /library/save ────────────────────────────────────
    private function toggleSave(int $profileId, int $userId): void
    {
        $body = $this->body();
        $meta = $this->extractMeta($body);

        if (!$meta) {
            $this->json(['sucesso' => false, 'erro' => 'Dados incompletos.'], 400);
            return;
        }

        $result = $this->model->toggleSave($profileId, $userId, $meta);
        $this->json(['sucesso' => true, 'dados' => $result]);
    }

    // ── POST /library/like ────────────────────────────────────
    private function toggleLike(int $profileId, int $userId): void
    {
        $body = $this->body();
        $meta = $this->extractMeta($body);

        if (!$meta) {
            $this->json(['sucesso' => false, 'erro' => 'Dados incompletos.'], 400);
            return;
        }

        $result = $this->model->toggleLike($profileId, $userId, $meta);
        $this->json(['sucesso' => true, 'dados' => $result]);
    }

    // ── POST /library/watch ───────────────────────────────────
    private function recordWatch(int $profileId, int $userId): void
    {
        $body = $this->body();
        $meta = $this->extractMeta($body);

        if (!$meta) {
            $this->json(['sucesso' => false, 'erro' => 'Dados incompletos.'], 400);
            return;
        }

        $meta['season']  = isset($body['season'])  ? (int) $body['season']  : null;
        $meta['episode'] = isset($body['episode']) ? (int) $body['episode'] : null;

        $this->model->recordWatch($profileId, $userId, $meta);
        $this->json(['sucesso' => true]);
    }

    // ── GET /library/saved ────────────────────────────────────
    private function getSaved(int $profileId): void
    {
        $items = $this->model->getSaved($profileId);
        $this->json(['sucesso' => true, 'dados' => $items]);
    }

    // ── GET /library/liked ────────────────────────────────────
    private function getLiked(int $profileId): void
    {
        $items = $this->model->getLiked($profileId);
        $this->json(['sucesso' => true, 'dados' => $items]);
    }

    // ── GET /library/history ──────────────────────────────────
    private function getHistory(int $profileId): void
    {
        $items = $this->model->getHistory($profileId);
        $this->json(['sucesso' => true, 'dados' => $items]);
    }

    // ── GET /library/all ──────────────────────────────────────
    private function getAll(int $profileId): void
    {
        $this->json([
            'sucesso' => true,
            'dados'   => [
                'history' => $this->model->getHistory($profileId),
                'saved'   => $this->model->getSaved($profileId),
                'liked'   => $this->model->getLiked($profileId),
            ],
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────
    private function body(): array
    {
        $raw = file_get_contents('php://input');
        return $raw ? (json_decode($raw, true) ?? []) : [];
    }

    private function extractMeta(array $body): ?array
    {
        $contentId   = (int)    ($body['content_id']   ?? 0);
        $contentType = (string) ($body['content_type'] ?? 'movie');

        if (!$contentId) return null;

        return [
            'content_id'       => $contentId,
            'content_type'     => in_array($contentType, ['movie','serie']) ? $contentType : 'movie',
            'content_title'    => substr((string) ($body['content_title']    ?? ''), 0, 500),
            'content_poster'   => substr((string) ($body['content_poster']   ?? ''), 0, 500),
            'content_backdrop' => substr((string) ($body['content_backdrop'] ?? ''), 0, 500),
            'content_year'     => isset($body['content_year']) ? (int) $body['content_year'] : null,
        ];
    }

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
