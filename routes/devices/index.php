<?php

declare(strict_types=1);

/**
 * Rotas de dispositivos — /api/devices/*
 *
 * Incluído a partir de routes/index.php quando a URI começa com /api/devices/
 *
 * Dependências esperadas no escopo pai:
 *   - $pdo         (PDO Pipocine)
 *   - $requestUri  (string)
 *   - $requestMethod (string)
 */

require_once __DIR__ . '/../../models/device/DeviceModel.php';
require_once __DIR__ . '/../../helpers/device/DeviceFingerprint.php';
require_once __DIR__ . '/../../services/device/DeviceService.php';
require_once __DIR__ . '/../../controllers/device/DeviceController.php';

use Models\Device\DeviceModel;
use Services\Device\DeviceService;
use Controllers\Device\DeviceController;

$deviceModel      = new DeviceModel($pdo);
$deviceService    = new DeviceService($deviceModel);
$deviceController = new DeviceController($deviceService);

// POST /api/devices/heartbeat
if ($requestUri === '/api/devices/heartbeat' && $requestMethod === 'POST') {
    $deviceController->heartbeat();
    exit;
}

// POST /api/devices/release
if ($requestUri === '/api/devices/release' && $requestMethod === 'POST') {
    $deviceController->release();
    exit;
}

// GET /api/devices/status
if ($requestUri === '/api/devices/status' && $requestMethod === 'GET') {
    $deviceController->status();
    exit;
}

// GET /api/devices/list
if ($requestUri === '/api/devices/list' && $requestMethod === 'GET') {
    $deviceController->list();
    exit;
}

// Rota não encontrada dentro do namespace /api/devices/
header('Content-Type: application/json; charset=utf-8');
http_response_code(404);
echo json_encode(['error' => 'Endpoint de dispositivo nao encontrado.']);
exit;
