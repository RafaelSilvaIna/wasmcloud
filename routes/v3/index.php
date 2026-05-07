<?php

declare(strict_types=1);

/**
 * ROUTER — API v3 Pipocine
 *
 * Registra e despacha todas as rotas da v3.
 * Inclui o sistema de Comentários, Menções e Biblioteca.
 *
 * Acesso: /api/v3/*
 * Incluído por: routes/index.php
 */

ini_set('display_errors', '0');
error_reporting(E_ALL);

// ── Dependências base ────────────────────────────────────────
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../utils/v2/ResponseUtil.php';

// ── Classes do sistema de comentários ───────────────────────
require_once __DIR__ . '/../../models/v3/CommentModel.php';
require_once __DIR__ . '/../../services/v3/CommentService.php';
require_once __DIR__ . '/../../controllers/v3/CommentController.php';

// ── Classes do sistema de biblioteca ─────────────────────────
require_once __DIR__ . '/../../models/v3/LibraryModel.php';
require_once __DIR__ . '/../../controllers/v3/LibraryController.php';

// ── Classes do progresso de reprodução ───────────────────────
require_once __DIR__ . '/../../models/v3/WatchProgressModel.php';
require_once __DIR__ . '/../../controllers/v3/WatchProgressController.php';

// ── Classes de episódios assistidos ─────────────────────────
require_once __DIR__ . '/../../models/v3/WatchedEpisodesModel.php';
require_once __DIR__ . '/../../controllers/v3/WatchedEpisodesController.php';

use Models\V3\CommentModel;
use Services\V3\CommentService;
use Controllers\V3\CommentController;
use Models\V3\LibraryModel;
use Controllers\V3\LibraryController;
use Models\V3\WatchProgressModel;
use Controllers\V3\WatchProgressController;
use Models\V3\WatchedEpisodesModel;
use Controllers\V3\WatchedEpisodesController;

// ── Inicia sessão ────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Cabeçalhos CORS / JSON ───────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ── Extrai a sub-rota após /api/v3/ ─────────────────────────
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$action     = ltrim(str_replace('/api/v3/', '', $requestUri), '/');
$method     = $_SERVER['REQUEST_METHOD'];

// ── Despacha ─────────────────────────────────────────────────
try {

    // ── Comentários ──────────────────────────────────────────
    if (
        $action === 'comments'          ||
        $action === 'comments/replies'  ||
        $action === 'comments/create'   ||
        $action === 'comments/edit'     ||
        $action === 'comments/delete'   ||
        $action === 'comments/like'
    ) {
        $model      = new CommentModel($pdo);
        $service    = new CommentService($model, $pdo);
        $controller = new CommentController($service);
        $controller->handle($action, $method);
        exit;
    }

    // ── Menções ──────────────────────────────────────────────
    if (
        $action === 'mentions'              ||
        $action === 'mentions/unread-count' ||
        $action === 'mentions/read'
    ) {
        $model      = new CommentModel($pdo);
        $service    = new CommentService($model, $pdo);
        $controller = new CommentController($service);
        $controller->handle($action, $method);
        exit;
    }

    // ── Biblioteca (saved / liked / history) ─────────────────
    if (str_starts_with($action, 'library')) {
        $model      = new LibraryModel($pdo);
        $controller = new LibraryController($model);
        $controller->handle($action, $method);
        exit;
    }

    // ── Progresso de reprodução ───────────────────────────────
    if (str_starts_with($action, 'watch-progress')) {
        $model      = new WatchProgressModel($pdo);
        $controller = new WatchProgressController($model);
        $controller->handle($action, $method);
        exit;
    }

    // ── Episódios assistidos (por perfil) ─────────────────────
    if (str_starts_with($action, 'watched-episodes')) {
        $model      = new WatchedEpisodesModel($pdo);
        $controller = new WatchedEpisodesController($model);
        $controller->handle($action, $method);
        exit;
    }

    // ── Rota não encontrada ──────────────────────────────────
    ResponseUtil::json(['sucesso' => false, 'erro' => 'Rota v3 não encontrada.'], 404);

} catch (Throwable $e) {
    ResponseUtil::json([
        'sucesso'  => false,
        'erro'     => 'Erro interno do servidor.',
        'detalhe'  => $e->getMessage(),
        'arquivo'  => basename($e->getFile()),
        'linha'    => $e->getLine(),
    ], 500);
}
