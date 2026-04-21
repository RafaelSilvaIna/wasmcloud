<?php
namespace App\Controllers\Admin;

use App\Repositories\AccountManagerRepository;
use App\Repositories\SystemConfigRepository;
use App\Services\UploadService;
use App\Services\SecurityVault;
use App\Services\AuditLogger;

class AccountManagerController {
    private $repo;

    public function __construct() {
        $this->repo = new AccountManagerRepository();
    }

    public function create() {
        if (empty($_POST['first_name']) || empty($_POST['email']) || empty($_POST['password']) || empty($_POST['role']) || empty($_POST['cpf'])) {
            self::abort(400, 'Dados cadastrais ou biometricos incompletos.');
        }

        $cleanCpf = preg_replace('/[^0-9]/', '', $_POST['cpf']);
        if (strlen($cleanCpf) !== 11) self::abort(400, 'CPF invalido.');

        $cpfHash = SecurityVault::generateBlindIndex($cleanCpf);
        
        if ($this->repo->isCpfUsed($cpfHash)) {
            self::abort(409, 'CPF ja vinculado a uma credencial no sistema.');
        }

        if ($_POST['role'] === 'master' && $this->repo->countAdmins() >= 3) {
            self::abort(403, 'Limite de Administradores Master atingido.');
        }

        $cleanData = [
            'first_name' => SecurityVault::sanitize($_POST['first_name']),
            'last_name' => SecurityVault::sanitize($_POST['last_name'] ?? ''),
            'email' => SecurityVault::sanitize($_POST['email']),
            'password_hash' => SecurityVault::hashPassword($_POST['password']),
            'cpf_encrypted' => SecurityVault::encrypt($cleanCpf),
            'cpf_hash' => $cpfHash,
            'profile_photo' => null
        ];

        $configRepo = new SystemConfigRepository();
        if ($configRepo->arePhotosEnabled()) {
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                $uploadService = new UploadService();
                $filename = $uploadService->handleImageUpload($_FILES['profile_photo']);
                if ($filename) {
                    $cleanData['profile_photo'] = $filename;
                } else {
                    self::abort(422, 'Formato ou tamanho de imagem invalido.');
                }
            }
        } else {
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                AuditLogger::log($_SERVER['MASTER_ID'], $_SERVER['MASTER_ROLE'], "Tentativa de upload de foto em registo com funcionalidade desativada", 'failure');
                self::abort(403, 'A funcionalidade de fotos de perfil esta desabilitada.');
            }
        }

        try {
            $this->repo->createAccount($_POST['role'], $cleanData);
            AuditLogger::log($_SERVER['MASTER_ID'], $_SERVER['MASTER_ROLE'], "Criou conta de {$_POST['role']} para {$cleanData['email']}");
            
            http_response_code(201);
            echo json_encode(['status' => 'success', 'message' => 'Conta criada com sucesso.']);
            exit;
        } catch (\PDOException $e) {
            self::abort(409, 'O e-mail fornecido ja esta em uso.');
        }
    }

    public function listAll() {
        $accounts = $this->repo->getAllAccounts();
        $safeAccounts = [];

        foreach ($accounts as $acc) {
            $cpfRaw = SecurityVault::decrypt($acc['cpf_encrypted']);
            $safeAccounts[] = [
                'id' => $acc['id'],
                'name' => $acc['first_name'] . ' ' . $acc['last_name'],
                'email' => $acc['email'],
                'role' => $acc['role'],
                'is_active' => $acc['is_active'],
                'is_archived' => $acc['deleted_at'] !== null,
                'last_login_at' => $acc['last_login_at'] ?? 'Nunca acedeu',
                'cpf_masked' => SecurityVault::maskCpf($cpfRaw)
            ];
        }

        AuditLogger::log($_SERVER['MASTER_ID'], $_SERVER['MASTER_ROLE'], "Acedeu a grelha de credenciais");
        http_response_code(200);
        echo json_encode(['status' => 'success', 'data' => $safeAccounts]);
        exit;
    }

    public function archive() {
        $input = json_decode(file_get_contents('php://input'), true);
        if ($this->repo->archiveAccount($input['role'], $input['id'])) {
            AuditLogger::log($_SERVER['MASTER_ID'], $_SERVER['MASTER_ROLE'], "Arquivou a conta ID {$input['id']} [{$input['role']}]");
            echo json_encode(['status' => 'success']);
            exit;
        }
        self::abort(500, 'Falha ao arquivar.');
    }

    public function toggleBlock() {
        $input = json_decode(file_get_contents('php://input'), true);
        if ($this->repo->toggleBlockStatus($input['role'], $input['id'], $input['status'])) {
            $acao = $input['status'] ? 'Desbloqueou' : 'Bloqueou';
            AuditLogger::log($_SERVER['MASTER_ID'], $_SERVER['MASTER_ROLE'], "{$acao} a conta ID {$input['id']} [{$input['role']}]");
            echo json_encode(['status' => 'success']);
            exit;
        }
        self::abort(500, 'Acao rejeitada. Conta pode estar arquivada.');
    }

    public function changePassword() {
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['reason']) || empty($input['new_password'])) {
            self::abort(400, 'A alteracao requer nova senha e justificativa.');
        }

        $hash = SecurityVault::hashPassword($input['new_password']);
        if ($this->repo->updatePassword($input['role'], $input['id'], $hash)) {
            $reason = SecurityVault::sanitize($input['reason']);
            AuditLogger::log($_SERVER['MASTER_ID'], $_SERVER['MASTER_ROLE'], "Alterou senha da conta ID {$input['id']} [{$input['role']}]. Justificativa: {$reason}");
            echo json_encode(['status' => 'success']);
            exit;
        }
        self::abort(500, 'Acao rejeitada. Conta arquivada.');
    }

    private static function abort($code, $message) {
        http_response_code($code);
        echo json_encode(['error' => $message]);
        exit;
    }
}