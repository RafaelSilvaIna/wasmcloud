<?php
namespace App\Services;

use App\Core\Database;

class AuditLogger {
    public static function log($actorId, $actorRole, $action, $status = 'success') {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        $db = Database::getInstance();
        $stmt = $db->prepare("INSERT INTO audit_logs (actor_id, actor_role, action, ip_address, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$actorId, $actorRole, $action, $ip, $status]);
    }
}