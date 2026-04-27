<?php
class ApiHook {
    public static function init() {
        // Inicia sessão para que dados do perfil ativo estejam disponíveis
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}