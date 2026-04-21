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
            $value = $config['config_value'];
            if ($value === 'true') $mapped[$config['config_key']] = true;
            elseif ($value === 'false') $mapped[$config['config_key']] = false;
            else $mapped[$config['config_key']] = $value;
        }
        return $mapped;
    }

    public function update($key, $value) {
        $stmt = $this->db->prepare("UPDATE system_configs SET config_value = ?, updated_at = CURRENT_TIMESTAMP WHERE config_key = ?");
        return $stmt->execute([$value, $key]);
    }

    public function initializeDefaults() {
        $defaults = [
            ['allow_profile_photos_on_signup', 'true'],
            ['allow_coordinators_login', 'true'],
            ['allow_students_login', 'true'],
            ['system_setup_completed', 'false']
        ];
        
        foreach ($defaults as $default) {
            $stmt = $this->db->prepare("INSERT INTO system_configs (config_key, config_value) VALUES (?, ?) ON CONFLICT (config_key) DO NOTHING");
            $stmt->execute($default);
        }
    }
}