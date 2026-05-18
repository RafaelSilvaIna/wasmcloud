<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../helpers/ads/AdsFeature.php';
require_once __DIR__ . '/../../helpers/ads/AdsValidator.php';
require_once __DIR__ . '/../../helpers/ads/AdsCampaignValidator.php';
require_once __DIR__ . '/../../helpers/ads/AdsDraftPresenter.php';
require_once __DIR__ . '/../../models/ads/AdsAccountModel.php';
require_once __DIR__ . '/../../models/ads/AdsCampaignModel.php';
require_once __DIR__ . '/../../services/ads/AdsAuthService.php';
require_once __DIR__ . '/../../services/ads/VidsStClient.php';
require_once __DIR__ . '/../../services/ads/AdsCampaignService.php';
require_once __DIR__ . '/../../controllers/ads/AdsAuthController.php';
require_once __DIR__ . '/../../controllers/ads/AdsCampaignController.php';

use Models\Ads\AdsAccountModel;
use Models\Ads\AdsCampaignModel;
use Services\Ads\AdsAuthService;
use Services\Ads\AdsCampaignService;
use Services\Ads\VidsStClient;
use Controllers\Ads\AdsAuthController;
use Controllers\Ads\AdsCampaignController;

if (!\Helpers\Ads\AdsFeature::isPublicEnabled()) {
    \Helpers\Ads\AdsFeature::denyPublicApiAccess();
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>false,'message'=>'Autenticação Pipocine necessária.']);
    exit;
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$accountModel = new AdsAccountModel($pdo);
$authController = new AdsAuthController(new AdsAuthService($accountModel));

if ($uri === '/api/ads/register' && $method === 'POST') { $authController->register(); exit; }
if ($uri === '/api/ads/login' && $method === 'POST') { $authController->login(); exit; }
if ($uri === '/api/ads/link' && $method === 'POST') { $authController->link(); exit; }
if ($uri === '/api/ads/onboarding' && $method === 'POST') { $authController->onboarding(); exit; }

if (!empty($_SESSION['ads_account_id'])) {
    $campaignController = new AdsCampaignController(
        new AdsCampaignService(
            $pdo,
            new AdsCampaignModel($pdo),
            $accountModel,
            new VidsStClient()
        )
    );
    $accountId = (int) $_SESSION['ads_account_id'];

    if ($uri === '/api/ads/campaigns' && $method === 'GET') { $campaignController->list($accountId); exit; }
    if ($uri === '/api/ads/campaigns/status-board' && $method === 'GET') { $campaignController->statusBoard($accountId); exit; }
    if ($uri === '/api/ads/campaigns/draft' && $method === 'POST') { $campaignController->draft($accountId); exit; }
    if (preg_match('#^/api/ads/campaigns/([a-f0-9]{64})/draft$#', $uri, $m) && $method === 'DELETE') { $campaignController->deleteDraft($accountId, $m[1]); exit; }
    if (preg_match('#^/api/ads/campaigns/([a-f0-9]{64})/image$#', $uri, $m) && $method === 'POST') { $campaignController->uploadImage($accountId, $m[1]); exit; }
    if (preg_match('#^/api/ads/campaigns/([a-f0-9]{64})/video-token$#', $uri, $m) && $method === 'POST') { $campaignController->videoToken($accountId, $m[1]); exit; }
    if (preg_match('#^/api/ads/campaigns/([a-f0-9]{64})/video-complete$#', $uri, $m) && $method === 'POST') { $campaignController->completeVideo($accountId, $m[1]); exit; }
    if (preg_match('#^/api/ads/campaigns/([a-f0-9]{64})/details$#', $uri, $m) && $method === 'POST') { $campaignController->details($accountId, $m[1]); exit; }
    if (preg_match('#^/api/ads/campaigns/([a-f0-9]{64})/submit$#', $uri, $m) && $method === 'POST') { $campaignController->submit($accountId, $m[1]); exit; }
}

if (str_starts_with($uri, '/api/ads/campaigns')) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Sessão comercial necessária.'], JSON_UNESCAPED_UNICODE);
    exit;
}

http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['success'=>false,'message'=>'Endpoint não encontrado.']);
