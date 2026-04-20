<?php
namespace App\Middlewares;

use App\Services\Auth\TokenService;

class MasterAuthMiddleware {
    public static function protect() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? ($headers['authorization'] ?? null);

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            self::abort(401, 'Acesso negado. Token ausente.');
        }

        $decodedPayload = TokenService::validateToken($matches[1]);

        if (!$decodedPayload || $decodedPayload['role'] !== 'master') {
            self::abort(403, 'Acesso negado. Nivel de privilegio insuficiente.');
        }

        $_SERVER['MASTER_ID'] = $decodedPayload['sub'];
        $_SERVER['MASTER_ROLE'] = $decodedPayload['role'];
    }

    private static function abort($code, $message) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message]);
        exit;
    }
}