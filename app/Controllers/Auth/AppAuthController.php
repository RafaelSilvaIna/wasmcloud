<?php
namespace App\Controllers\Auth;

use App\Repositories\CoordinatorRepository;
use App\Repositories\StudentRepository;
use App\Services\Auth\TokenService;
use App\Services\SecurityVault;
use App\Middlewares\RateLimitMiddleware;
use App\Services\AuditLogger;

class AppAuthController {
    public function __construct() {
        RateLimitMiddleware::check();
    }

    public function login() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['email']) || empty($input['password']) || empty($input['role'])) {
            self::abort(400, 'Credenciais incompletas.');
        }

        $email = SecurityVault::sanitize($input['email']);
        $user = null;

        if ($input['role'] === 'coordinator') {
            $user = (new CoordinatorRepository())->findByEmail($email);
        } elseif ($input['role'] === 'student') {
            $user = (new StudentRepository())->findByEmail($email);
        }

        if (!$user || !password_verify($input['password'], $user['password_hash'])) {
            AuditLogger::log(0, 'unknown', "Falha de login para {$email} ({$input['role']})", 'failure');
            self::abort(401, 'Credenciais invalidas.');
        }

        $token = TokenService::generateTemporaryToken($user['id'], $user['role']);
        AuditLogger::log($user['id'], $user['role'], 'Login realizado com sucesso');

        http_response_code(200);
        echo json_encode(['status' => 'success', 'token' => $token]);
        exit;
    }

    private static function abort($code, $message) {
        http_response_code($code);
        echo json_encode(['error' => $message]);
        exit;
    }
}