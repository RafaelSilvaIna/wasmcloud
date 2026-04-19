<?php
namespace App\Middlewares;

class FirewallMiddleware {
    public static function shield() {
        self::validateMethod();
        self::validateHeaders();
        self::detectMaliciousPayloads();
    }

    private static function validateMethod() {
        $allowed = ['GET', 'POST', 'PUT', 'DELETE'];
        if (!in_array($_SERVER['REQUEST_METHOD'], $allowed)) {
            self::abort(405);
        }
    }

    private static function validateHeaders() {
        if (empty($_SERVER['HTTP_HOST']) || empty($_SERVER['HTTP_USER_AGENT'])) {
            self::abort(400);
        }
    }

    private static function detectMaliciousPayloads() {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $threats = [
            '/(%3C|<)script(%3E|>)/i',
            '/UNION\s+SELECT/i',
            '/base64_decode\(/i',
            '/(etc\/passwd|windows\/win\.ini)/i',
            '/(\.\.\/|\.\.\\\)/'
        ];

        foreach ($threats as $threat) {
            if (preg_match($threat, $uri)) {
                self::abort(403);
            }
        }
    }

    private static function abort($code) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'error',
            'message' => 'Requisição bloqueada por violação de segurança.'
        ]);
        exit;
    }
}