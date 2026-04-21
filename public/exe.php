<?php
$config = require __DIR__ . '/../config/database.php';
$dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";

try {
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "
    ALTER TABLE admins ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255);
    ALTER TABLE coordinators ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255);
    ALTER TABLE students ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255);
    ";

    $pdo->exec($sql);
    echo "<h1>Coluna 'profile_photo' aplicada com sucesso nas 3 tabelas!</h1>";
    echo "<p>Por favor, elimine este ficheiro imediatamente por segurança.</p>";

    // Regista log na auditoria LGPD (ID 0 = Sistema)
    $pdo->exec("INSERT INTO audit_logs (actor_id, actor_role, action, ip_address, status) 
                VALUES (0, 'system', 'Database Fix: Colunas de Foto de Perfil aplicadas', '127.0.0.1', 'success')");

} catch (PDOException $e) {
    die("Erro fatal: " . $e->getMessage());
}