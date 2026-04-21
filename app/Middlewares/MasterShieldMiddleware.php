<?php
namespace App\Middlewares;

use App\Services\Auth\TokenService;

class MasterShieldMiddleware {
    public static function protect() {
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : null);

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            self::abort();
        }

        $decodedPayload = TokenService::validateToken($matches[1]);

        if (!$decodedPayload || $decodedPayload['role'] !== 'master') {
            self::abort();
        }

        $_SERVER['MASTER_ID'] = $decodedPayload['sub'];
        $_SERVER['MASTER_ROLE'] = $decodedPayload['role'];
    }

    private static function abort() {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Acesso negado.']);
        exit;
    }
}