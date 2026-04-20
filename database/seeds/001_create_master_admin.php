<?php
require_once __DIR__ . '/../../config/database.php';

class MasterAdminSeeder {
    public static function run() {
        $config = require __DIR__ . '/../../config/database.php';
        $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
        
        try {
            $pdo = new PDO($dsn, $config['username'], $config['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $email = 'admin@school.local';
            $password = password_hash('Admin@123', PASSWORD_BCRYPT);

            $stmt = $pdo->prepare("SELECT id FROM admins WHERE email = :email");
            $stmt->execute([':email' => $email]);
            
            if ($stmt->fetch()) {
                echo "O Admin Master ja existe no banco de dados.\n";
                return;
            }

            $sql = "INSERT INTO admins (first_name, last_name, email, password_hash, role, is_active) 
                    VALUES ('Master', 'Admin', :email, :password, 'master', TRUE)";
            
            $insert = $pdo->prepare($sql);
            $insert->execute([
                ':email' => $email,
                ':password' => $password
            ]);

            echo "Admin Master criado com sucesso!\n";
            echo "Email: admin@school.local\n";
            echo "Senha: Admin@123\n";

        } catch (PDOException $e) {
            echo "Erro ao conectar no banco: " . $e->getMessage() . "\n";
        }
    }
}

MasterAdminSeeder::run();