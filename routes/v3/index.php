<?php

/**
 * ROTEADOR: API v3
 *
 * Todas as rotas /api/v3/* passam por este arquivo.
 * Inclui o hook de autenticação antes de despachar para os controllers.
 *
 * Rotas registadas:
 *   GET  /api/v3/account/me   → AccountController::me()
 */

require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../hooks/v3/AccountHook.php';

$requestUri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// ──────────────────────────────────────────────────────────────────────────────
// ROTAS DE CONTA (CineVEO)
// ──────────────────────────────────────────────────────────────────────────────
if (strpos($requestUri, '/api/v3/account') === 0) {

    AccountHook::guard();

    require_once __DIR__ . '/../../models/v3/AccountModel.php';
    require_once __DIR__ . '/../../services/v3/AccountService.php';
    require_once __DIR__ . '/../../controllers/v3/AccountController.php';

    // Instancia usando os dois bancos já disponíveis em db.php
    $accountModel      = new AccountModel($pdoCineveo, $pdo ?? null);
    $accountService    = new AccountService($accountModel);
    $accountController = new AccountController($accountService);

    // GET /api/v3/account/me
    if ($requestUri === '/api/v3/account/me' && $requestMethod === 'GET') {
        $accountController->me();
        exit;
    }

    // Endpoint dentro do namespace /account/* mas não reconhecido
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Endpoint de conta não encontrado.']);
    exit;
}

// ──────────────────────────────────────────────────────────────────────────────
// ROTAS DE SEGURANÇA — Código de login e QR Code
// ──────────────────────────────────────────────────────────────────────────────

// Carrega dependências de segurança
require_once __DIR__ . '/../../models/v3/SecurityModel.php';
require_once __DIR__ . '/../../services/v3/SecurityService.php';
require_once __DIR__ . '/../../controllers/v3/SecurityController.php';

$secModel      = new SecurityModel($pdo);
$secService    = new SecurityService($secModel, $pdoCineveo);
$secController = new SecurityController($secService);

// ── Código de login (painel de configurações — exige sessão) ──────────────────
if (strpos($requestUri, '/api/v3/security/code') === 0) {

    AccountHook::guard(); // verifica sessão

    if ($requestUri === '/api/v3/security/code/status' && $requestMethod === 'GET') {
        $secController->codeStatus();
        exit;
    }
    if ($requestUri === '/api/v3/security/code/save' && $requestMethod === 'POST') {
        $secController->codeSave();
        exit;
    }
    if ($requestUri === '/api/v3/security/code/remove' && $requestMethod === 'POST') {
        $secController->codeRemove();
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Endpoint de código não encontrado.']);
    exit;
}

// ── Autenticação por código (página de login — público) ───────────────────────
if ($requestUri === '/api/v3/auth/code/login' && $requestMethod === 'POST') {
    $secController->codeLogin();
    exit;
}

// ── QR Code (página de login — público) ──────────────────────────────────────
if (strpos($requestUri, '/api/v3/auth/qr') === 0) {

    if ($requestUri === '/api/v3/auth/qr/generate' && $requestMethod === 'POST') {
        $secController->qrGenerate();
        exit;
    }
    if ($requestUri === '/api/v3/auth/qr/poll' && $requestMethod === 'GET') {
        $secController->qrPoll();
        exit;
    }
    if ($requestUri === '/api/v3/auth/qr/confirm' && $requestMethod === 'POST') {
        $secController->qrConfirm();
        exit;
    }

    header('Content-Type: application/json; charset=utf-8');
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Endpoint QR não encontrado.']);
    exit;
}

// ──────────────────────────────────────────────────────────────────────────────
// FALLBACK — rota v3 desconhecida
// ──────────────────────────────────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
http_response_code(404);
echo json_encode(['success' => false, 'error' => 'Endpoint v3 não encontrado.']);
exit;
