<?php
namespace App\Controllers;

use App\Core\Database;
use App\Middlewares\RateLimitMiddleware;
use App\Services\SecurityVault;

class InternalDbController {
    public function __construct() {
        RateLimitMiddleware::check();
    }

    public function insertSecureData($table, $rawData) {
        $cleanData = SecurityVault::sanitize($rawData);
        $secureData = [];

        foreach ($cleanData as $key => $value) {
            if ($key === 'password') {
                $secureData[$key] = SecurityVault::hashPassword($value);
            } else {
                $secureData[$key] = SecurityVault::encrypt($value);
            }
        }

        $columns = implode(', ', array_keys($secureData));
        $placeholders = implode(', ', array_fill(0, count($secureData), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $db = Database::getInstance();
        $stmt = $db->prepare($sql);
        
        $stmt->execute(array_values($secureData));
        
        return true;
    }
}