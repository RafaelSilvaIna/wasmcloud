<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class AuditLogRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAllLogsExtended() {
        $stmt = $this->db->prepare("SELECT id, actor_id, actor_role, action, ip_address, status, created_at FROM audit_logs ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}