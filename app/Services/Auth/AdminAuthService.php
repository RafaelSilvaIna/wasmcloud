<?php
namespace App\Services\Auth;

use App\Repositories\AdminRepository;
use App\Services\SecurityVault;

class AdminAuthService {
    private $repository;

    public function __construct() {
        $this->repository = new AdminRepository();
    }

    public function authenticate($email, $password) {
        $cleanEmail = SecurityVault::sanitize($email);
        
        $admin = $this->repository->findByEmail($cleanEmail);
        
        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            return false;
        }

        $this->repository->updateLastLogin($admin['id']);
        
        return TokenService::generateTemporaryToken($admin['id'], $admin['role']);
    }
}