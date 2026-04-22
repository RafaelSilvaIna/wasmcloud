<?php
$config = require __DIR__ . '/../config/database.php';
$dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";

try {
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $newConfigs = [
        ['allow_auth_routing', 'true'],
        ['allow_student_panel_access', 'true'],
        ['allow_coordinator_panel_access', 'true']
    ];

    $stmt = $pdo->prepare("INSERT INTO system_configs (config_key, config_value) VALUES (?, ?) ON CONFLICT (config_key) DO NOTHING");
    foreach ($newConfigs as $cfg) {
        $stmt->execute($cfg);
    }

    echo "<h1>Políticas de Acesso Sincronizadas!</h1><p>Apague este ficheiro agora.</p>";

} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}