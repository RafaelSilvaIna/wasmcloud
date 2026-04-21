<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class AccountManagerRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    private function getTable($role) {
        $tables = ['master' => 'admins', 'coordinator' => 'coordinators', 'student' => 'students'];
        if (!isset($tables[$role])) throw new \Exception("Papel inválido.");
        return $tables[$role];
    }

    public function isCpfUsed($cpfHash) {
        $tables = ['admins', 'coordinators', 'students'];
        foreach ($tables as $table) {
            $stmt = $this->db->prepare("SELECT id FROM {$table} WHERE cpf_hash = ?");
            $stmt->execute([$cpfHash]);
            if ($stmt->fetch()) return true;
        }
        return false;
    }

    public function countAdmins() {
        $stmt = $this->db->query("SELECT COUNT(*) FROM admins");
        return (int) $stmt->fetchColumn();
    }

    public function createAccount($role, $data) {
        $table = $this->getTable($role);
        $stmt = $this->db->prepare("INSERT INTO {$table} (first_name, last_name, email, password_hash, cpf_encrypted, cpf_hash) VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$data['first_name'], $data['last_name'], $data['email'], $data['password_hash'], $data['cpf_encrypted'], $data['cpf_hash']]);
    }

    public function getAllAccounts() {
        $accounts = [];
        $tables = ['master' => 'admins', 'coordinator' => 'coordinators', 'student' => 'students'];
        
        foreach ($tables as $role => $table) {
            $hasDeletedAt = $role !== 'master';
            $deletedSelect = $hasDeletedAt ? "deleted_at" : "NULL as deleted_at";
            
            $stmt = $this->db->query("SELECT id, first_name, last_name, email, role, is_active, last_login_at, cpf_encrypted, {$deletedSelect} FROM {$table} ORDER BY id DESC");
            $accounts = array_merge($accounts, $stmt->fetchAll(PDO::FETCH_ASSOC));
        }
        return $accounts;
    }

    public function archiveAccount($role, $id) {
        if ($role === 'master') throw new \Exception("Não é permitido arquivar Administradores Master.");
        $table = $this->getTable($role);
        $stmt = $this->db->prepare("UPDATE {$table} SET deleted_at = CURRENT_TIMESTAMP, is_active = FALSE WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function toggleBlockStatus($role, $id, $status) {
        $table = $this->getTable($role);
        $stmt = $this->db->prepare("UPDATE {$table} SET is_active = ? WHERE id = ? AND (deleted_at IS NULL OR deleted_at = NULL)");
        return $stmt->execute([$status, $id]);
    }

    public function updateEmail($role, $id, $email) {
        $table = $this->getTable($role);
        $stmt = $this->db->prepare("UPDATE {$table} SET email = ? WHERE id = ? AND (deleted_at IS NULL OR deleted_at = NULL)");
        return $stmt->execute([$email, $id]);
    }

    public function updatePassword($role, $id, $passwordHash) {
        $table = $this->getTable($role);
        $stmt = $this->db->prepare("UPDATE {$table} SET password_hash = ? WHERE id = ? AND (deleted_at IS NULL OR deleted_at = NULL)");
        return $stmt->execute([$passwordHash, $id]);
    }
}