<?php
class ProfileHook {
    public static function enforceProfile(): void {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        if (strpos($uri, '/api/') === 0 || $uri === '/select-profile' || $uri === '/login') {
            return;
        }

        if (isset($_SESSION['user_id']) && !isset($_SESSION['profile_id'])) {
            header('Location: /select-profile');
            exit;
        }
    }
}