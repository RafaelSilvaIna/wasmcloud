<?php
$config = require __DIR__ . '/../config/database.php';
$dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";

try {
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "
    CREATE TABLE IF NOT EXISTS system_themes (
        id SERIAL PRIMARY KEY,
        primary_color VARCHAR(7) NOT NULL DEFAULT '#2563eb',
        secondary_color VARCHAR(7) NOT NULL DEFAULT '#64748b',
        background_color VARCHAR(7) NOT NULL DEFAULT '#f8fafc',
        text_color VARCHAR(7) NOT NULL DEFAULT '#0f172a',
        accent_color VARCHAR(7) NOT NULL DEFAULT '#10b981',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );";

    $pdo->exec($sql);

    $stmt = $pdo->query("SELECT COUNT(*) FROM system_themes");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO system_themes (primary_color, secondary_color, background_color, text_color, accent_color) 
                    VALUES ('#2563eb', '#64748b', '#f8fafc', '#0f172a', '#10b981')");
        echo "<h1>Tabela de Temas criada e configurada com sucesso!</h1>";
    } else {
        echo "<h1>A tabela de Temas ja existe.</h1>";
    }

    echo "<p><strong>AVISO:</strong> Elimine este ficheiro imediatamente por segurança.</p>";

} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}