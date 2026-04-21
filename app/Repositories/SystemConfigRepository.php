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
            $mapped[$config['config_key']] = ($config['config_value'] === 'true');
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
            ['allow_students_login', 'true']
        ];
        
        $this->db->beginTransaction();
        $stmt = $this->db->prepare("INSERT INTO system_configs (config_key, config_value) VALUES (?, ?) ON CONFLICT DO NOTHING");
        
        foreach ($defaults as $default) {
            $stmt->execute($default);
        }
        
        $this->db->commit();
    }
}