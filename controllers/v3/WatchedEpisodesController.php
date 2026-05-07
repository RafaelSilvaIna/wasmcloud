<?php

declare(strict_types=1);

namespace Controllers\V3;

use Models\V3\WatchedEpisodesModel;
use ResponseUtil;

class WatchedEpisodesController
{
    public function __construct(private WatchedEpisodesModel $model) {}

    public function handle(string $action, string $method): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $profileId = (int) ($_SESSION['profile_id'] ?? 0);
        $userId    = (int) ($_SESSION['user_id'] ?? 0);

        if ($profileId <= 0 || $userId <= 0) {
            ResponseUtil::json(['sucesso' => false, 'erro' => 'Não autenticado.'], 401);
            return;
        }

        match ($action) {
            'watched-episodes/toggle' => $this->toggle($profileId, $userId, $method),
            'watched-episodes/mark'   => $this->mark($profileId, $userId, $method),
            'watched-episodes/unmark' => $this->unmark($profileId, $method),
            'watched-episodes/map'    => $this->map($profileId, $method),
            'watched-episodes/get'    => $this->getOne($profileId, $method),
            default                  => ResponseUtil::json(['sucesso' => false, 'erro' => 'Rota não encontrada.'], 404),
        };
    }

    private function toggle(int $profileId, int $userId, string $method): void
    {
        if ($method !== 'POST') {
            ResponseUtil::json(['sucesso' => false, 'erro' => 'Método inválido.'], 405);
            return;
        }

        $body = $this->body();
        $serieId  = (int) ($body['serie_id'] ?? 0);
        $season   = (int) ($body['season'] ?? 0);
        $episode  = (int) ($body['episode'] ?? 0);

        if ($serieId <= 0 || $season <= 0 || $episode <= 0) {
            ResponseUtil::json(['sucesso' => false, 'erro' => 'Dados inválidos.'], 422);
            return;
        }

        $result = $this->model->toggle($profileId, $userId, $serieId, $season, $episode);
        ResponseUtil::json(['sucesso' => true, 'dados' => $result]);
    }

    private function mark(int $profileId, int $userId, string $method): void
    {
        if ($method !== 'POST') {
            ResponseUtil::json(['sucesso' => false, 'erro' => 'Método inválido.'], 405);
            return;
        }

        $body = $this->body();
        $serieId  = (int) ($body['serie_id'] ?? 0);
        $season   = (int) ($body['season'] ?? 0);
        $episode  = (int) ($body['episode'] ?? 0);

        if ($serieId <= 0 || $season <= 0 || $episode <= 0) {
            ResponseUtil::json(['sucesso' => false, 'erro' => 'Dados inválidos.'], 422);
            return;
        }

        $result = $this->model->mark($profileId, $userId, $serieId, $season, $episode);
        ResponseUtil::json(['sucesso' => true, 'dados' => $result]);
    }

    private function unmark(int $profileId, string $method): void
    {
        if ($method !== 'POST') {
            ResponseUtil::json(['sucesso' => false, 'erro' => 'Método inválido.'], 405);
            return;
        }

        $body = $this->body();
        $serieId  = (int) ($body['serie_id'] ?? 0);
        $season   = (int) ($body['season'] ?? 0);
        $episode  = (int) ($body['episode'] ?? 0);

        if ($serieId <= 0 || $season <= 0 || $episode <= 0) {
            ResponseUtil::json(['sucesso' => false, 'erro' => 'Dados inválidos.'], 422);
            return;
        }

        $result = $this->model->unmark($profileId, $serieId, $season, $episode);
        ResponseUtil::json(['sucesso' => true, 'dados' => $result]);
    }

    private function map(int $profileId, string $method): void
    {
        if ($method !== 'GET') {
            ResponseUtil::json(['sucesso' => false, 'erro' => 'Método inválido.'], 405);
            return;
        }

        $serieId  = (int) ($_GET['serie_id'] ?? 0);
        $season   = (int) ($_GET['season'] ?? 0);

        if ($serieId <= 0 || $season <= 0) {
            ResponseUtil::json(['sucesso' => false, 'erro' => 'Parâmetros inválidos.'], 422);
            return;
        }

        $map = $this->model->getMap($profileId, $serieId, $season);
        ResponseUtil::json(['sucesso' => true, 'dados' => ['watched' => $map]]);
    }

    private function getOne(int $profileId, string $method): void
    {
        if ($method !== 'GET') {
            ResponseUtil::json(['sucesso' => false, 'erro' => 'Método inválido.'], 405);
            return;
        }

        $serieId  = (int) ($_GET['serie_id'] ?? 0);
        $season   = (int) ($_GET['season'] ?? 0);
        $episode  = (int) ($_GET['episode'] ?? 0);

        if ($serieId <= 0 || $season <= 0 || $episode <= 0) {
            ResponseUtil::json(['sucesso' => false, 'erro' => 'Parâmetros inválidos.'], 422);
            return;
        }

        $watched = $this->model->isWatched($profileId, $serieId, $season, $episode);
        ResponseUtil::json(['sucesso' => true, 'dados' => ['watched' => $watched]]);
    }

    private function body(): array
    {
        $raw = file_get_contents('php://input');
        $data = $raw ? json_decode($raw, true) : null;
        return is_array($data) ? $data : [];
    }
}
