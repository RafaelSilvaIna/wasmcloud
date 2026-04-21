<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class ThemeRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getTheme() {
        $stmt = $this->db->query("SELECT primary_color, secondary_color, background_color, text_color, accent_color FROM system_themes LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateTheme($data) {
        $stmt = $this->db->prepare("
            UPDATE system_themes 
            SET primary_color = ?, 
                secondary_color = ?, 
                background_color = ?, 
                text_color = ?, 
                accent_color = ?, 
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = 1
        ");
        return $stmt->execute([
            $data['primary_color'],
            $data['secondary_color'],
            $data['background_color'],
            $data['text_color'],
            $data['accent_color']
        ]);
    }
}