<?php
namespace App\Controllers\Auth;

use App\Core\Database;
use App\Services\Auth\TokenService;
use App\Services\AuditLogger;
use PDO;

class AppAuthController {
    public function login() {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        if (!$email || !$password) {
            self::abort(400, 'Credenciais ausentes.');
        }

        $db = Database::getInstance();
        $tables = ['admins' => 'master', 'coordinators' => 'coordinator', 'students' => 'student'];

        $user = null;
        $role = null;
        $tableName = null;

        foreach ($tables as $table => $r) {
            $hasDeleted = $r !== 'master';
            $deletedSelect = $hasDeleted ? "deleted_at" : "NULL as deleted_at";

            $stmt = $db->prepare("SELECT id, password_hash, is_active, {$deletedSelect} FROM {$table} WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $user = $row;
                $role = $r;
                $tableName = $table;
                break;
            }
        }

        if (!$user || !password_verify($password, $user['password_hash'])) {
            self::abort(401, 'Credenciais invalidas.');
        }

        if ($user['deleted_at'] !== null) {
            self::abort(403, 'Acesso negado: Conta arquivada e inoperante.');
        }

        if (!filter_var($user['is_active'], FILTER_VALIDATE_BOOLEAN)) {
            self::abort(403, 'Acesso negado: Conta bloqueada por diretrizes administrativas.');
        }

        if ($tableName) {
            $upd = $db->prepare("UPDATE {$tableName} SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?");
            $upd->execute([$user['id']]);
        }

        $token = TokenService::generateTemporaryToken($user['id'], $role);
        AuditLogger::log($user['id'], $role, "Sessao iniciada com sucesso");

        echo json_encode(['status' => 'success', 'token' => $token, 'role' => $role]);
        exit;
    }

    public function verifyTokenStatus() {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (!preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
            self::abort(401, 'Token ausente.');
        }

        $payload = TokenService::validateToken($matches[1]);
        if (!$payload) {
            self::abort(401, 'Token invalido ou expirado.');
        }

        $db = Database::getInstance();
        $role = $payload['role'];
        $id = $payload['sub'];

        $table = $role === 'master' ? 'admins' : ($role === 'coordinator' ? 'coordinators' : 'students');
        $hasDeleted = $role !== 'master';
        $delQuery = $hasDeleted ? "AND deleted_at IS NULL" : "";

        $stmt = $db->prepare("SELECT is_active FROM {$table} WHERE id = ? {$delQuery} LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            self::abort(403, 'Sessao derrubada: Conta foi arquivada.');
        }

        if (!filter_var($row['is_active'], FILTER_VALIDATE_BOOLEAN)) {
            self::abort(403, 'Sessao derrubada: Conta foi bloqueada.');
        }

        echo json_encode(['status' => 'success']);
        exit;
    }

    private static function abort($code, $msg) {
        http_response_code($code);
        echo json_encode(['status' => 'error', 'error' => $msg]);
        exit;
    }
}