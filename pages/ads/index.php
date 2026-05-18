<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../models/ads/AdsAccountModel.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login?redirect=' . urlencode('/ads'));
    exit;
}

$view = $_GET['view'] ?? 'main';
$allowed = ['main', 'presentation', 'register', 'login', 'link', 'dashboard', 'dashboard_onboarding'];
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

    if ($view === 'dashboard' && !$onboardingComplete) {
        header('Location: /ads/dashboard/onboarding');
        exit;
    }

    if ($view === 'dashboard_onboarding' && $onboardingComplete) {
        header('Location: /ads/dashboard');
        exit;
    }
}

if ($view === 'dashboard_onboarding') {
    require_once __DIR__ . '/dashboard/onboarding.php';
    exit;
}

require_once __DIR__ . "/{$view}.php";
