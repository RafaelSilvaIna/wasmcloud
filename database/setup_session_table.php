<?php
/**
 * Script para criar/atualizar a tabela profile_active_sessions
 * Acesse via navegador: http://localhost/database/setup_session_table.php
 * DELETE ESTE ARQUIVO APÓS A EXECUÇÃO!
 */

require_once __DIR__ . '/db.php';

echo "<h1>Setup: Tabela profile_active_sessions</h1>";

try {
    // Verifica se a tabela existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'profile_active_sessions'");
    $exists = $stmt->fetch();

    if ($exists) {
        echo "<p style='color:orange;'>⚠️ Tabela já existe. Verificando estrutura...</p>";
        
        // Verifica se tem a UNIQUE constraint problemática
        $stmt = $pdo->query("SHOW CREATE TABLE profile_active_sessions");
        $createTable = $stmt->fetch();
        $createSQL = $createTable['Create Table'] ?? '';
        
        if (strpos($createSQL, 'unique_profile_session') !== false) {
            echo "<p style='color:red;'>❌ Encontrada UNIQUE constraint problemática. Removendo...</p>";
            $pdo->exec("ALTER TABLE profile_active_sessions DROP INDEX unique_profile_session");
            echo "<p style='color:green;'>✅ UNIQUE constraint removida com sucesso!</p>";
        } else {
            echo "<p style='color:green;'>✅ Estrutura OK (sem UNIQUE constraint problemática)</p>";
        }
    } else {
        echo "<p style='color:blue;'>📋 Criando tabela profile_active_sessions...</p>";
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS `profile_active_sessions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `profile_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `session_id` varchar(255) NOT NULL,
            `device_info` text DEFAULT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` text DEFAULT NULL,
            `last_activity` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `expires_at` timestamp NOT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`),
            KEY `idx_profile_id` (`profile_id`),
            KEY `idx_user_id` (`user_id`),
            KEY `idx_session_id` (`session_id`),
            KEY `idx_expires_at` (`expires_at`),
            KEY `idx_last_activity` (`last_activity`),
            KEY `idx_profile_active` (`profile_id`, `is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        echo "<p style='color:green;'>✅ Tabela criada com sucesso!</p>";
    }

    // Mostra sessões ativas (se houver)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM profile_active_sessions WHERE is_active = 1");
    $count = $stmt->fetch();
    echo "<p>📊 Sessões ativas atualmente: <strong>" . $count['total'] . "</strong></p>";

    // Lista sessões ativas
    if ($count['total'] > 0) {
        $stmt = $pdo->query("SELECT pas.*, p.profile_name FROM profile_active_sessions pas LEFT JOIN profiles p ON p.id = pas.profile_id WHERE pas.is_active = 1 ORDER BY pas.last_activity DESC");
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
        echo "<tr><th>Perfil</th><th>Session ID</th><th>IP</th><th>User Agent</th><th>Última Atividade</th></tr>";
        foreach ($sessions as $s) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($s['profile_name'] ?? 'ID:' . $s['profile_id']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($s['session_id'], 0, 20)) . "...</td>";
            echo "<td>" . htmlspecialchars($s['ip_address']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($s['user_agent'], 0, 50)) . "...</td>";
            echo "<td>" . htmlspecialchars($s['last_activity']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    echo "<hr><p style='color:red;'><strong>⚠️ DELETE ESTE ARQUIVO APÓS A EXECUÇÃO!</strong></p>";

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
}
