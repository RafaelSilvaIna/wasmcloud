<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class ProfileImageRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    private function getTableByRole($role) {
        $tables = ['master' => 'admins', 'coordinator' => 'coordinators', 'student' => 'students'];
        if (!isset($tables[$role])) throw new \Exception("Papel inválido.");
        return $tables[$role];
    }

    public function getFilename($role, $userId) {
        $table = $this->getTableByRole($role);
        $stmt = $this->db->prepare("SELECT profile_photo FROM {$table} WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    public function updateFilename($role, $userId, $filename) {
        $table = $this->getTableByRole($role);
        $stmt = $this->db->prepare("UPDATE {$table} SET profile_photo = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$filename, $userId]);
    }
}