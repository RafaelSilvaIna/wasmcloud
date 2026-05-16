<?php

declare(strict_types=1);

namespace Models\Device;

use PDO;
use Throwable;

/**
 * DeviceModel
 *
 * Gerencia a persistência de dispositivos ativos no banco de dados.
 * Opera sobre a tabela `account_devices` que registra cada dispositivo
 * associado a uma conta de usuário, com suporte a heartbeat e expiração.
 */
final class DeviceModel
{
    private PDO $db;

    // Tolerância (em segundos) antes de um dispositivo ser marcado como inativo
    // após parar de enviar heartbeats.
    // Heartbeat frontend ocorre a cada 30s → 45s garante 1 ciclo de folga
    // sem deixar o slot preso por um longo período após saída do usuário.
    public const HEARTBEAT_TTL = 45;

    // Após este tempo sem atividade, o registro é removido definitivamente.
    public const CLEANUP_AFTER_SECONDS = 300;

    public function __construct(PDO $pdo)
    {
        $this->db = $pdo;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Schema auto-provisionado
    // ─────────────────────────────────────────────────────────────────────────

    public function ensureSchema(): void
    {
        // Tabela principal de dispositivos ativos por conta
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS account_devices (
                id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id        INT            NOT NULL,
                device_id      VARCHAR(128)   NOT NULL COMMENT 'Hash multifator do dispositivo',
                session_id     VARCHAR(128)   NOT NULL COMMENT 'ID de sessão PHP',
                ip_partial     VARCHAR(20)    NOT NULL DEFAULT '' COMMENT 'Primeiros 3 octetos do IP (privacidade)',
                user_agent_hash VARCHAR(64)   NOT NULL DEFAULT '' COMMENT 'SHA-256 do user-agent',
                device_label   VARCHAR(80)    NOT NULL DEFAULT '' COMMENT 'Rótulo legível (ex: Chrome no Windows)',
                is_active      TINYINT(1)     NOT NULL DEFAULT 1,
                last_heartbeat DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                created_at     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_device_session (device_id, session_id),
                KEY idx_user_active    (user_id, is_active),
                KEY idx_heartbeat      (last_heartbeat),
                KEY idx_user_device    (user_id, device_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Escrita
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Registra ou atualiza o heartbeat de um dispositivo.
     * Retorna o id do registro.
     */
    public function upsertDevice(
        int    $userId,
        string $deviceId,
        string $sessionId,
        string $ipPartial,
        string $userAgentHash,
        string $deviceLabel
    ): bool {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO account_devices
                    (user_id, device_id, session_id, ip_partial, user_agent_hash, device_label, is_active, last_heartbeat)
                VALUES
                    (?, ?, ?, ?, ?, ?, 1, NOW())
                ON DUPLICATE KEY UPDATE
                    is_active      = 1,
                    last_heartbeat = NOW(),
                    ip_partial     = VALUES(ip_partial),
                    device_label   = VALUES(device_label)
            ");
            return $stmt->execute([
                $userId,
                $deviceId,
                $sessionId,
                $ipPartial,
                $userAgentHash,
                $deviceLabel,
            ]);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Marca o dispositivo atual como inativo (logout / fechamento de aba).
     */
    public function deactivateDevice(int $userId, string $deviceId, string $sessionId): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE account_devices
                SET is_active = 0
                WHERE user_id = ? AND device_id = ? AND session_id = ?
            ");
            return $stmt->execute([$userId, $deviceId, $sessionId]);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Desativa todos os dispositivos de um usuário (logout global).
     */
    public function deactivateAllDevices(int $userId): bool
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE account_devices SET is_active = 0 WHERE user_id = ?
            ");
            return $stmt->execute([$userId]);
        } catch (Throwable) {
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Leitura
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Retorna o número de dispositivos ativos (com heartbeat recente) para
     * um determinado usuário.
     */
    public function countActiveDevices(int $userId): int
    {
        try {
            $ttl  = self::HEARTBEAT_TTL;
            $stmt = $this->db->prepare("
                SELECT COUNT(DISTINCT device_id) AS cnt
                FROM account_devices
                WHERE user_id   = ?
                  AND is_active = 1
                  AND last_heartbeat >= DATE_SUB(NOW(), INTERVAL {$ttl} SECOND)
            ");
            $stmt->execute([$userId]);
            return (int) ($stmt->fetchColumn() ?: 0);
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * Retorna lista de dispositivos ativos para um usuário.
     */
    public function listActiveDevices(int $userId): array
    {
        try {
            $ttl  = self::HEARTBEAT_TTL;
            $stmt = $this->db->prepare("
                SELECT id, device_id, session_id, ip_partial, device_label, last_heartbeat
                FROM account_devices
                WHERE user_id   = ?
                  AND is_active = 1
                  AND last_heartbeat >= DATE_SUB(NOW(), INTERVAL {$ttl} SECOND)
                ORDER BY last_heartbeat DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * Verifica se o device_id atual já consta como ativo para o usuário.
     */
    public function isDeviceActive(int $userId, string $deviceId): bool
    {
        try {
            $ttl  = self::HEARTBEAT_TTL;
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM account_devices
                WHERE user_id   = ?
                  AND device_id = ?
                  AND is_active = 1
                  AND last_heartbeat >= DATE_SUB(NOW(), INTERVAL {$ttl} SECOND)
            ");
            $stmt->execute([$userId, $deviceId]);
            return (int) $stmt->fetchColumn() > 0;
        } catch (Throwable) {
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Manutenção
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Remove registros antigos sem heartbeat recente.
     * Deve ser chamado periodicamente (ex: a cada heartbeat).
     */
    public function cleanup(): void
    {
        try {
            $after = self::CLEANUP_AFTER_SECONDS;
            $this->db->exec("
                DELETE FROM account_devices
                WHERE last_heartbeat < DATE_SUB(NOW(), INTERVAL {$after} SECOND)
            ");
        } catch (Throwable) {
            // silencioso
        }
    }
}
