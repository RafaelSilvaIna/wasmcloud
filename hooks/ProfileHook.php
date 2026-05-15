<?php
class ProfileHook {
    public static function redirectTvToQrLogin(): void {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if (isset($_SESSION['user_id'])) {
            return;
        }

        if (strpos($uri, '/api/') === 0 || strpos($uri, '/login/qrcode') === 0) {
            return;
        }

        if (!self::isTvUserAgent($_SERVER['HTTP_USER_AGENT'] ?? '')) {
            return;
        }

        header('Location: /login/qrcode');
        exit;
    }

    private static function isTvUserAgent(string $userAgent): bool {
        $ua = strtolower($userAgent);

        if ($ua === '') {
            return false;
        }

        $tvSignals = [
            'smart-tv',
            'smarttv',
            'hbbtv',
            'netcast',
            'webos.tv',
            'web0s',
            'tizen',
            'bravia',
            'viera',
            'aquos',
            'appletv',
            'apple tv',
            'googletv',
            'google tv',
            'android tv',
            'roku',
            'aftb',
            'aftm',
            'aftt',
            'aftss',
            'aftka',
            'aftmm',
            'aftn',
            'aftkm',
            'aftso',
            'aftjmst12',
            'smart-tv',
            'dtv',
            'tv safari'
        ];

        foreach ($tvSignals as $signal) {
            if (strpos($ua, $signal) !== false) {
                return true;
            }
        }

        return (bool) preg_match('/\b(tv|smarttv|smart-tv)\b/', $ua)
            && strpos($ua, 'mobile') === false
            && strpos($ua, 'iphone') === false
            && strpos($ua, 'ipad') === false
            && strpos($ua, 'android; mobile') === false;
    }

    public static function enforceProfile(?PDO $pdo = null): void {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $exempt = [
            '/',
            '/main',
            '/login',
            '/verify',
            '/select-profile',
            '/manage-profiles',
            '/settings',
            '/d2xs8d3sdfsegequ6249f',
            '/plan',
            '/plan/',
            '/plan/checkout',
            '/plan/pix',
            '/plan/payment',
            '/plan/me',
            '/create/profile',
        ];

        if (strpos($uri, '/api/') === 0 || strpos($uri, '/login/') === 0 || strpos($uri, '/webhooks/') === 0 || str_starts_with($uri, '/plan/payment/active=') || str_starts_with($uri, '/create/profile/edit=') || in_array($uri, $exempt, true) || preg_match('/^\/verify=/', $uri)) {
            return;
        }

        self::clearHiddenProfileSession($pdo);

        if (isset($_SESSION['user_id']) && !isset($_SESSION['profile_id'])) {
            header('Location: /select-profile');
            exit;
        }
    }

    private static function clearHiddenProfileSession(?PDO $pdo): void {
        if (!$pdo || empty($_SESSION['user_id']) || empty($_SESSION['profile_id'])) {
            return;
        }

        $userId = (int) $_SESSION['user_id'];
        $profileId = (int) $_SESSION['profile_id'];

        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM user_subscriptions
                WHERE user_id = ? AND status = 'active' AND expires_at > NOW()
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            if ((int) $stmt->fetchColumn() > 0) {
                return;
            }

            $stmt = $pdo->prepare("SELECT id FROM profiles WHERE user_id = ? ORDER BY id ASC LIMIT 2");
            $stmt->execute([$userId]);
            $allowedIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
            if (in_array($profileId, $allowedIds, true)) {
                return;
            }

            $pdo->prepare("UPDATE profiles SET is_watching = 0, current_session_id = NULL WHERE id = ? AND user_id = ?")
                ->execute([$profileId, $userId]);
            $pdo->prepare("UPDATE profile_active_sessions SET is_active = 0 WHERE profile_id = ? AND session_id = ?")
                ->execute([$profileId, session_id()]);
        } catch (Throwable $e) {}

        unset($_SESSION['profile_id'], $_SESSION['profile_name'], $_SESSION['profile_image'], $_SESSION['profile_is_kids']);
    }
}
