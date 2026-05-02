<?php

declare(strict_types=1);

namespace Controllers\V3;

use Models\V3\WatchProgressModel;
use ResponseUtil;

/**
 * WatchProgressController
 *
 * Rotas:
 *   POST   /api/v3/watch-progress/save     — salva/atualiza progresso
 *   GET    /api/v3/watch-progress/get      — retorna progresso de um conteúdo/ep
 *   GET    /api/v3/watch-progress/continue — lista "Continua Assistindo"
 */
class WatchProgressController
{
    public function __construct(private WatchProgressModel $model) {}

    public function handle(string $action, string $method): void
    {
        // Requer sessão válida
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
        if ($userId <= 0) {
            ResponseUtil::json(['sucesso' => false, 'erro' => 'Não autenticado.'], 401);
            return;
        }

        match (true) {
            $action === 'watch-progress/save'     => $this->save($userId, $method),
            $action === 'watch-progress/get'      => $this->getProgress($userId, $method),
            $action === 'watch-progress/continue' => $this->continueWatching($userId),
            default => ResponseUtil::json(['sucesso' => false, 'erro' => 'Rota não encontrada.'], 404),
        };
    }

    // ── POST /api/v3/watch-progress/save ─────────────────────────────────

    private function save(int $userId, string $method): void
    {
        if ($method !== 'POST') {
            ResponseUtil::json(['sucesso' => false, 'erro' => 'Método inválido.'], 405);
            return;
        }

        $body = (array) json_decode(file_get_contents('php://input') ?: '{}', true);

        $contentId   = isset($body['content_id'])   ? (int)   $body['content_id']   : 0;
        $contentType = isset($body['content_type'])  ? (string) $body['content_type'] : 'filme';
        $season      = isset($body['season'])        ? (int)   $body['season']        : 1;
        $episode     = isset($body['episode'])       ? (int)   $body['episode']       : 1;
        $progressTime = isset($body['progress_time']) ? (float) $body['progress_time'] : 0;
        $duration    = isset($body['duration'])      ? (float) $body['duration']      : 0;

        if ($contentId <= 0) {
            ResponseUtil::json(['sucesso' => false, 'erro' => 'content_id inválido.'], 422);
            return;
        }

        // Ignora saves muito curtos (primeiros 5s) para evitar spam
        if ($progressTime < 5) {
            ResponseUtil::json(['sucesso' => true, 'mensagem' => 'Ignorado (muito cedo).']);
            return;
        }

        $ok = $this->model->upsert([
            'user_id'        => $userId,
            'content_id'     => $contentId,
            'content_type'   => $contentType,
            'season'         => $season,
            'episode'        => $episode,
            'progress_time'  => $progressTime,
            'duration'       => $duration,
            'content_title'  => $body['content_title']  ?? '',
            'content_poster' => $body['content_poster'] ?? '',
            'content_year'   => isset($body['content_year']) ? (int) $body['content_year'] : null,
            'audio'          => $body['audio'] ?? '',
        ]);

        ResponseUtil::json(['sucesso' => $ok]);
    }

    // ── GET /api/v3/watch-progress/get ───────────────────────────────────

    private function getProgress(int $userId, string $method): void
    {
        if ($method !== 'GET') {
            ResponseUtil::json(['sucesso' => false, 'erro' => 'Método inválido.'], 405);
            return;
        }

        $contentId   = isset($_GET['content_id'])   ? (int)    $_GET['content_id']   : 0;
        $contentType = isset($_GET['content_type'])  ? (string) $_GET['content_type'] : 'filme';
        $season      = isset($_GET['season'])        ? (int)    $_GET['season']        : 1;
        $episode     = isset($_GET['episode'])       ? (int)    $_GET['episode']       : 1;

        if ($contentId <= 0) {
            ResponseUtil::json(['sucesso' => false, 'erro' => 'content_id inválido.'], 422);
            return;
        }

        $row = $this->model->get($userId, $contentId, $contentType, $season, $episode);

        ResponseUtil::json([
            'sucesso' => true,
            'dados'   => $row ?: null,
        ]);
    }

    // ── GET /api/v3/watch-progress/continue ──────────────────────────────

    private function continueWatching(int $userId): void
    {
        $limit = isset($_GET['limit']) ? min((int) $_GET['limit'], 50) : 20;
        $items = $this->model->getContinueWatching($userId, $limit);

        ResponseUtil::json([
            'sucesso' => true,
            'dados'   => $items,
        ]);
    }
}
