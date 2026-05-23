<?php
declare(strict_types=1);

namespace Models\Player;

use PDO;

final class PlayerLogModel
{
    public function __construct(private PDO $db)
    {
    }

    public function ensureSchema(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS pipocine_player_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                event_id CHAR(32) NOT NULL,
                severity ENUM('info', 'warning', 'error', 'fatal') NOT NULL DEFAULT 'error',
                event_type VARCHAR(80) NOT NULL DEFAULT 'player_error',
                stage VARCHAR(80) NOT NULL DEFAULT 'unknown',
                status ENUM('open', 'reviewing', 'resolved') NOT NULL DEFAULT 'open',
                user_id BIGINT UNSIGNED NULL,
                profile_id BIGINT UNSIGNED NULL,
                content_id INT UNSIGNED NULL,
                content_title VARCHAR(255) NULL,
                content_type VARCHAR(20) NULL,
                season_number INT UNSIGNED NULL,
                episode_number INT UNSIGNED NULL,
                audio VARCHAR(12) NULL,
                error_title VARCHAR(180) NULL,
                error_message VARCHAR(500) NULL,
                technical_message TEXT NULL,
                player_url VARCHAR(500) NULL,
                api_url VARCHAR(500) NULL,
                media_type VARCHAR(40) NULL,
                media_url_hash CHAR(64) NULL,
                is_embedded_browser TINYINT(1) NOT NULL DEFAULT 0,
                is_vpn_suspected TINYINT(1) NOT NULL DEFAULT 0,
                browser_name VARCHAR(80) NULL,
                user_agent VARCHAR(255) NULL,
                ip_address VARCHAR(45) NULL,
                referer VARCHAR(255) NULL,
                client_network VARCHAR(255) NULL,
                diagnostics_json JSON NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_ppl_event_id (event_id),
                KEY idx_ppl_created (created_at),
                KEY idx_ppl_status_created (status, created_at),
                KEY idx_ppl_severity_created (severity, created_at),
                KEY idx_ppl_stage_created (stage, created_at),
                KEY idx_ppl_content_created (content_id, content_type, created_at),
                KEY idx_ppl_content_title_created (content_title, created_at),
                KEY idx_ppl_user_created (user_id, created_at),
                KEY idx_ppl_embedded_created (is_embedded_browser, created_at),
                KEY idx_ppl_vpn_created (is_vpn_suspected, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $this->ensureColumn('content_title', "ALTER TABLE pipocine_player_logs ADD COLUMN content_title VARCHAR(255) NULL AFTER content_id");
        $this->ensureIndex('idx_ppl_content_title_created', "ALTER TABLE pipocine_player_logs ADD KEY idx_ppl_content_title_created (content_title, created_at)");
    }

    public function record(array $log): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO pipocine_player_logs (
                event_id, severity, event_type, stage, user_id, profile_id, content_id,
                content_title, content_type, season_number, episode_number, audio, error_title,
                error_message, technical_message, player_url, api_url, media_type,
                media_url_hash, is_embedded_browser, is_vpn_suspected, browser_name,
                user_agent, ip_address, referer, client_network, diagnostics_json
            ) VALUES (
                :event_id, :severity, :event_type, :stage, :user_id, :profile_id, :content_id,
                :content_title, :content_type, :season_number, :episode_number, :audio, :error_title,
                :error_message, :technical_message, :player_url, :api_url, :media_type,
                :media_url_hash, :is_embedded_browser, :is_vpn_suspected, :browser_name,
                :user_agent, :ip_address, :referer, :client_network, :diagnostics_json
            )
        ");
        $stmt->execute($log);
    }

    private function ensureColumn(string $column, string $alterSql): void
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'pipocine_player_logs'
              AND COLUMN_NAME = ?
        ");
        $stmt->execute([$column]);
        if ((int) $stmt->fetchColumn() === 0) {
            $this->db->exec($alterSql);
        }
    }

    private function ensureIndex(string $index, string $alterSql): void
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'pipocine_player_logs'
              AND INDEX_NAME = ?
        ");
        $stmt->execute([$index]);
        if ((int) $stmt->fetchColumn() === 0) {
            $this->db->exec($alterSql);
        }
    }
}
