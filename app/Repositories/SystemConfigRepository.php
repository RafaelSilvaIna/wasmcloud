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

    public function arePhotosEnabled() {
        $stmt = $this->db->prepare("SELECT config_value FROM system_configs WHERE config_key = 'allow_profile_photos_on_signup'");
        $stmt->execute();
        return ($stmt->fetchColumn() === 'true');
    }
}