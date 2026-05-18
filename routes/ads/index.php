<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../helpers/ads/AdsValidator.php';
require_once __DIR__ . '/../../models/ads/AdsAccountModel.php';
require_once __DIR__ . '/../../services/ads/AdsAuthService.php';
require_once __DIR__ . '/../../controllers/ads/AdsAuthController.php';

use Models\Ads\AdsAccountModel;
use Services\Ads\AdsAuthService;
use Controllers\Ads\AdsAuthController;

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>false,'message'=>'Autenticação Pipocine necessária.']);
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$controller = new AdsAuthController(new AdsAuthService(new AdsAccountModel($pdo)));

if ($uri === '/api/ads/register' && $method === 'POST') { $controller->register(); exit; }
if ($uri === '/api/ads/login' && $method === 'POST') { $controller->login(); exit; }
if ($uri === '/api/ads/link' && $method === 'POST') { $controller->link(); exit; }
if ($uri === '/api/ads/onboarding' && $method === 'POST') { $controller->onboarding(); exit; }

http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success'=>false,'message'=>'Endpoint não encontrado.']);
