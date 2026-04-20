<?php
namespace App\Controllers\Auth;

use App\Repositories\AdminRepository;
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

        if (empty($input['email']) || empty($input['password'])) {
            self::abort(400, 'Credenciais incompletas.');
        }

        $email = SecurityVault::sanitize($input['email']);
        
        $adminRepo = new AdminRepository();
        $coordRepo = new CoordinatorRepository();
        $studentRepo = new StudentRepository();

        $user = $adminRepo->findByEmail($email);
        $role = 'master';

        if (!$user) {
            $user = $coordRepo->findByEmail($email);
            $role = 'coordinator';
        }

        if (!$user) {
            $user = $studentRepo->findByEmail($email);
            $role = 'student';
        }

        if (!$user || !password_verify($input['password'], $user['password_hash'])) {
            AuditLogger::log(0, 'unknown', "Falha de login para {$email}", 'failure');
            self::abort(401, 'Credenciais invalidas.');
        }

        if ($role === 'master') {
            $adminRepo->updateLastLogin($user['id']);
        }

        $token = TokenService::generateTemporaryToken($user['id'], $role);
        AuditLogger::log($user['id'], $role, 'Login realizado com sucesso');

        http_response_code(200);
        echo json_encode(['status' => 'success', 'token' => $token, 'role' => $role]);
        exit;
    }

    private static function abort($code, $message) {
        http_response_code($code);
        echo json_encode(['error' => $message]);
        exit;
    }
}