<?php
namespace App\Middlewares;

use App\Services\Auth\TokenService;

class AdminAuthMiddleware {
    public static function protect() {
        $headers = getallheaders();
        
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : null);

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            self::abort(401, 'Acesso negado. Token de autenticacao ausente.');
        }

        $token = $matches[1];
        $decodedPayload = TokenService::validateToken($token);

        if (!$decodedPayload) {
            self::abort(401, 'Acesso negado. Token invalido ou expirado.');
        }

        $_SERVER['ADMIN_ID'] = $decodedPayload['sub'];
    }

    private static function abort($code, $message) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message]);
        exit;
    }
}