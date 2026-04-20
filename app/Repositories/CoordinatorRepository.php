<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class CoordinatorRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO coordinators (first_name, last_name, email, password_hash) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$data['first_name'], $data['last_name'], $data['email'], $data['password_hash']]);
    }

    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM coordinators WHERE email = ? AND is_active = TRUE AND deleted_at IS NULL");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}