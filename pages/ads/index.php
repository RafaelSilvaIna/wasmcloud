<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../helpers/ads/AdsFeature.php';
require_once __DIR__ . '/../../models/ads/AdsAccountModel.php';

if (!\Helpers\Ads\AdsFeature::isPublicEnabled()) {
    \Helpers\Ads\AdsFeature::denyPublicAccess();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /login?redirect=' . urlencode('/ads'));
    exit;
}

$view = $_GET['view'] ?? 'main';
$allowed = [
    'main',
    'presentation',
    'register',
    'login',
    'link',
    'dashboard',
    'dashboard_onboarding',
    'campaigns',
    'campaign_status',
    'campaign_create_select',
    'campaign_create_upload',
    'campaign_create_details',
    'campaign_create_review',
];
if (!in_array($view, $allowed, true)) {
    $view = 'main';
}

$adsAccountModel = $pdo ? new \Models\Ads\AdsAccountModel($pdo) : null;

if (!empty($_SESSION['ads_account_id']) && $adsAccountModel) {
    $activeAdsAccount = $adsAccountModel->findById((int) $_SESSION['ads_account_id']);
    if (!$activeAdsAccount) {
        unset($_SESSION['ads_account_id']);
    }
}

if (empty($_SESSION['ads_account_id']) && !empty($_SESSION['user_id']) && $adsAccountModel) {
    $linkedAdsAccount = $adsAccountModel->findByPipocineUserId((int) $_SESSION['user_id']);
    if ($linkedAdsAccount) {
        $_SESSION['ads_account_id'] = (int) $linkedAdsAccount['id'];
    }
}

$publicCommercialViews = ['main', 'presentation', 'register', 'login'];
if (!empty($_SESSION['ads_account_id']) && in_array($view, $publicCommercialViews, true)) {
    header('Location: /ads/dashboard');
    exit;
}

if (!empty($_SESSION['ads_account_id']) && $adsAccountModel) {
    $activeAdsAccount = $adsAccountModel->findById((int) $_SESSION['ads_account_id']);
    $onboardingComplete = !empty($activeAdsAccount['onboarding_completed_at']);
    $protectedCommercialViews = [
        'dashboard',
        'campaigns',
        'campaign_status',
        'campaign_create_select',
        'campaign_create_upload',
        'campaign_create_details',
        'campaign_create_review',
    ];

    if (in_array($view, $protectedCommercialViews, true) && !$onboardingComplete) {
        header('Location: /ads/dashboard/onboarding');
        exit;
    }

    if ($view === 'dashboard_onboarding' && $onboardingComplete) {
        header('Location: /ads/dashboard');
        exit;
    }
}

$viewFiles = [
    'dashboard_onboarding' => __DIR__ . '/dashboard/onboarding.php',
    'campaigns' => __DIR__ . '/announcements/index.php',
    'campaign_status' => __DIR__ . '/announcements/status.php',
    'campaign_create_select' => __DIR__ . '/announcements/create/select.php',
    'campaign_create_upload' => __DIR__ . '/announcements/create/upload.php',
    'campaign_create_details' => __DIR__ . '/announcements/create/details.php',
    'campaign_create_review' => __DIR__ . '/announcements/create/review.php',
];

require_once $viewFiles[$view] ?? (__DIR__ . "/{$view}.php");
