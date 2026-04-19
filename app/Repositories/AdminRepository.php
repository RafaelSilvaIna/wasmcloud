<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class AdminRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM admins WHERE email = :email AND is_active = TRUE");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT id, first_name, last_name, email, role, is_active FROM admins WHERE id = :id AND is_active = TRUE");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateLastLogin($id) {
        $stmt = $this->db->prepare("UPDATE admins SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }
}