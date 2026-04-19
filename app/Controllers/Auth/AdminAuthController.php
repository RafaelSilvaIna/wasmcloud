<?php
namespace App\Controllers\Auth;

use App\Services\Auth\AdminAuthService;
use App\Middlewares\RateLimitMiddleware;
use App\Repositories\AdminRepository;

class AdminAuthController {
    private $authService;

    public function __construct() {
        RateLimitMiddleware::check();
        $this->authService = new AdminAuthService();
    }

    public function login() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['email']) || empty($input['password'])) {
            self::abort(400, 'Credenciais incompletas.');
        }

        $token = $this->authService->authenticate($input['email'], $input['password']);

        if (!$token) {
            self::abort(401, 'Credenciais invalidas ou conta inativa.');
        }

        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'success',
            'token' => $token,
            'expires_in' => 900 
        ]);
        exit;
    }

    public function validateSession() {
        $adminId = $_SERVER['ADMIN_ID'] ?? null;

        if (!$adminId) {
            self::abort(401, 'Sessao invalida.');
        }

        $repository = new AdminRepository();
        $admin = $repository->findById($adminId);

        if (!$admin) {
            self::abort(404, 'Usuario nao encontrado ou inativo.');
        }

        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status' => 'success',
            'data' => [
                'id' => $admin['id'],
                'first_name' => $admin['first_name'],
                'last_name' => $admin['last_name'],
                'email' => $admin['email'],
                'role' => $admin['role']
            ]
        ]);
        exit;
    }

    private static function abort($code, $message) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message]);
        exit;
    }
}