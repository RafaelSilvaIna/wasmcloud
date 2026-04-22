<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class SystemConfigRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getAll() {
        $stmt = $this->db->prepare("SELECT config_key, config_value FROM system_configs");
        $stmt->execute();
        $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $mapped = [];
        foreach ($configs as $config) {
            $val = $config['config_value'];
            $mapped[$config['config_key']] = ($val === 'true' ? true : ($val === 'false' ? false : $val));
        }
        return $mapped;
    }

    public function set($key, $value) {
        $stmt = $this->db->prepare("
            INSERT INTO system_configs (config_key, config_value, updated_at) 
            VALUES (:key, :val, CURRENT_TIMESTAMP)
            ON CONFLICT (config_key) 
            DO UPDATE SET config_value = EXCLUDED.config_value, updated_at = CURRENT_TIMESTAMP
        ");
        return $stmt->execute([':key' => $key, ':val' => $value]);
    }

    public function initializeDefaults() {
        $defaults = [
            ['allow_profile_photos_on_signup', 'true'],
            ['allow_coordinators_login', 'true'],
            ['allow_students_login', 'true'],
            ['allow_auth_routing', 'true'],
            ['allow_student_panel_access', 'true'],
            ['allow_coordinator_panel_access', 'true'],
            ['system_setup_completed', 'false']
        ];
        foreach ($defaults as $d) {
            $this->set($d[0], $d[1]);
        }
    }
}