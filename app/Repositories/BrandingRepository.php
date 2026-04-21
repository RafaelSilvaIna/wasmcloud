<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class BrandingRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getBranding() {
        $stmt = $this->db->query("SELECT * FROM school_branding LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateTexts($name, $abbreviation, $slogan) {
        $stmt = $this->db->prepare("UPDATE school_branding SET institution_name = ?, abbreviation = ?, slogan = ?, updated_at = CURRENT_TIMESTAMP WHERE id = 1");
        return $stmt->execute([$name, $abbreviation, $slogan]);
    }

    public function updateImageFilename($type, $filename) {
        $column = $type . '_filename';
        $allowedColumns = ['logo_filename', 'favicon_filename', 'icon_filename'];
        
        if (!in_array($column, $allowedColumns)) {
            throw new \Exception("Coluna invalida");
        }

        $stmt = $this->db->prepare("UPDATE school_branding SET {$column} = ?, updated_at = CURRENT_TIMESTAMP WHERE id = 1");
        return $stmt->execute([$filename]);
    }
}