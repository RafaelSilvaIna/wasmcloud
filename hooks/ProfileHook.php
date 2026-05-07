<?php
class ProfileHook {
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
        ];

        if (strpos($uri, '/api/') === 0 || in_array($uri, $exempt, true) || preg_match('/^\/verify=/', $uri)) {
            return;
        }

        if (isset($_SESSION['user_id']) && !isset($_SESSION['profile_id'])) {
            header('Location: /select-profile');
            exit;
        }
    }
}
