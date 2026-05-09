<?php

declare(strict_types=1);

namespace Models\V4;

use PDO;
use PDOException;

class PinModel
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Cria um novo PIN para o usuário
     */
    public function createPin(int $userId, string $pinHash): array
    {
        try {
            // Usar REPLACE INTO ou INSERT com ON DUPLICATE KEY UPDATE
            $stmt = $this->pdo->prepare("
                INSERT INTO profile_pins (user_id, pin_hash) 
                VALUES (:user_id, :pin_hash) 
                ON DUPLICATE KEY UPDATE 
                    pin_hash = VALUES(pin_hash), 
                    updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':pin_hash' => $pinHash
            ]);
            return ['success' => true];
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            error_log("[PinModel] Erro ao criar PIN: " . $errorMsg);
            return [
                'success' => false,
                'error' => 'Erro no banco de dados: ' . $errorMsg
            ];
        }
    }

    /**
     * Verifica se o usuário possui um PIN criado
     */
    public function hasPin(int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM profile_pins WHERE user_id = :user_id LIMIT 1");
            $stmt->execute([':user_id' => $userId]);
            return (bool) $stmt->fetch();
        } catch (PDOException $e) {
            error_log("[PinModel] Erro ao verificar PIN: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Valida o PIN do usuário
     */
    public function validatePin(int $userId, string $pinHash): bool
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM profile_pins WHERE user_id = :user_id AND pin_hash = :pin_hash LIMIT 1");
            $stmt->execute([
                ':user_id' => $userId,
                ':pin_hash' => $pinHash
            ]);
            return (bool) $stmt->fetch();
        } catch (PDOException $e) {
            error_log("[PinModel] Erro ao validar PIN: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove o PIN do usuário
     */
    public function deletePin(int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM profile_pins WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("[PinModel] Erro ao remover PIN: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra uma tentativa de acesso ao PIN
     */
    public function logAttempt(int $userId, string $ipAddress, bool $success): bool
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO pin_attempts (user_id, ip_address, success) VALUES (:user_id, :ip_address, :success)");
            $stmt->execute([
                ':user_id' => $userId,
                ':ip_address' => $ipAddress,
                ':success' => $success ? 1 : 0
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("[PinModel] Erro ao registrar tentativa: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Conta tentativas falhas nas últimas 30 minutos
     */
    public function clearFailedAttempts(int $userId, ?string $ipAddress = null): bool
    {
        try {
            if ($ipAddress !== null) {
                $stmt = $this->pdo->prepare("DELETE FROM pin_attempts WHERE user_id = :user_id AND ip_address = :ip_address AND success = 0");
                return $stmt->execute([
                    ':user_id' => $userId,
                    ':ip_address' => $ipAddress
                ]);
            }

            $stmt = $this->pdo->prepare("DELETE FROM pin_attempts WHERE user_id = :user_id AND success = 0");
            return $stmt->execute([':user_id' => $userId]);
        } catch (PDOException $e) {
            error_log("[PinModel] Erro ao limpar tentativas falhas: " . $e->getMessage());
            return false;
        }
    }

    public function countFailedAttempts(int $userId, string $ipAddress, int $minutes = 30): int
    {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM pin_attempts WHERE user_id = :user_id AND ip_address = :ip_address AND success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL :minutes MINUTE)");
            $stmt->execute([
                ':user_id' => $userId,
                ':ip_address' => $ipAddress,
                ':minutes' => $minutes
            ]);
            $result = $stmt->fetch();
            return (int) ($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("[PinModel] Erro ao contar tentativas: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Verifica se o usuário está bloqueado por excesso de tentativas
     */
    public function isBlocked(int $userId, string $ipAddress, int $maxAttempts = 3, int $blockMinutes = 30): bool
    {
        return $this->countFailedAttempts($userId, $ipAddress, $blockMinutes) >= $maxAttempts;
    }

    /**
     * Tempo restante de bloqueio em segundos
     */
    public function getBlockTimeRemaining(int $userId, string $ipAddress, int $blockMinutes = 30): int
    {
        try {
            $stmt = $this->pdo->prepare("SELECT MAX(attempted_at) as last_attempt FROM pin_attempts WHERE user_id = :user_id AND ip_address = :ip_address AND success = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL :minutes MINUTE)");
            $stmt->execute([
                ':user_id' => $userId,
                ':ip_address' => $ipAddress,
                ':minutes' => $blockMinutes
            ]);
            $result = $stmt->fetch();
            
            if (!$result || !$result['last_attempt']) {
                return 0;
            }
            
            $lastAttempt = strtotime($result['last_attempt']);
            $expiresAt = $lastAttempt + ($blockMinutes * 60);
            $remaining = $expiresAt - time();
            
            return max(0, $remaining);
        } catch (PDOException $e) {
            error_log("[PinModel] Erro ao calcular tempo de bloqueio: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Limpa tentativas antigas
     */
    public function cleanOldAttempts(int $hours = 24): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM pin_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL :hours HOUR)");
            $stmt->execute([':hours' => $hours]);
            return true;
        } catch (PDOException $e) {
            error_log("[PinModel] Erro ao limpar tentativas antigas: " . $e->getMessage());
            return false;
        }
    }
}
