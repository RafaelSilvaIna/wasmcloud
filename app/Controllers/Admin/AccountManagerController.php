<?php
namespace App\Controllers\Admin;

use App\Repositories\CoordinatorRepository;
use App\Repositories\StudentRepository;
use App\Services\SecurityVault;
use App\Services\AuditLogger;

class AccountManagerController {
    public function createAccount() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['first_name']) || empty($input['last_name']) || empty($input['email']) || empty($input['password']) || empty($input['role'])) {
            self::abort(400, 'Dados incompletos.');
        }

        $safeData = [
            'first_name' => SecurityVault::sanitize($input['first_name']),
            'last_name' => SecurityVault::sanitize($input['last_name']),
            'email' => SecurityVault::sanitize($input['email']),
            'password_hash' => SecurityVault::hashPassword($input['password'])
        ];

        try {
            if ($input['role'] === 'coordinator') {
                (new CoordinatorRepository())->create($safeData);
            } elseif ($input['role'] === 'student') {
                (new StudentRepository())->create($safeData);
            } else {
                self::abort(400, 'Papel de usuario invalido.');
            }

            AuditLogger::log($_SERVER['MASTER_ID'], $_SERVER['MASTER_ROLE'], "Criou conta de {$input['role']} para {$safeData['email']}");

            http_response_code(201);
            echo json_encode(['status' => 'success', 'message' => 'Conta criada com sucesso.']);
            exit;
        } catch (\PDOException $e) {
            AuditLogger::log($_SERVER['MASTER_ID'], $_SERVER['MASTER_ROLE'], "Falha ao criar conta para {$safeData['email']}", 'failure');
            self::abort(409, 'O email fornecido ja esta em uso.');
        }
    }

    private static function abort($code, $message) {
        http_response_code($code);
        echo json_encode(['error' => $message]);
        exit;
    }
}