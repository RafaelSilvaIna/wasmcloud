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

    public static function enforceProfile(): void {
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
        ];

        if (strpos($uri, '/api/') === 0 || strpos($uri, '/login/') === 0 || strpos($uri, '/webhooks/') === 0 || str_starts_with($uri, '/plan/payment/active=') || in_array($uri, $exempt, true) || preg_match('/^\/verify=/', $uri)) {
            return;
        }

        if (isset($_SESSION['user_id']) && !isset($_SESSION['profile_id'])) {
            header('Location: /select-profile');
            exit;
        }
    }
}
