-- Tabela para controle de sessões ativas por PERFIL (não conta)
-- Permite apenas uma sessão ativa por perfil/dispositivo
-- Banco: pipocine

CREATE TABLE IF NOT EXISTS `profile_active_sessions` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Procedimento para limpar sessões inativas
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `CleanupInactiveProfileSessions`()
BEGIN
    DELETE FROM `profile_active_sessions` 
    WHERE `expires_at` < NOW() OR `last_activity` < DATE_SUB(NOW(), INTERVAL 30 MINUTE);
END//
DELIMITER ;
