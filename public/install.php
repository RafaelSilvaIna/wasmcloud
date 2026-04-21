<?php
$config = require __DIR__ . '/../config/database.php';
$dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";

try {
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS system_configs (
            id SERIAL PRIMARY KEY,
            config_key VARCHAR(255) UNIQUE NOT NULL,
            config_value VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");

    $defaults = [
        ['allow_profile_photos_on_signup', 'true'],
        ['allow_coordinators_login', 'true'],
        ['allow_students_login', 'true'],
        ['system_setup_completed', 'false']
    ];

    $stmt = $pdo->prepare("INSERT INTO system_configs (config_key, config_value) VALUES (?, ?) ON CONFLICT (config_key) DO NOTHING");
    foreach ($defaults as $row) {
        $stmt->execute($row);
    }

    echo "<h1>Database Fix: Sucesso!</h1><p>A tabela de configuracoes foi sincronizada. Apague este arquivo agora.</p>";

} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}