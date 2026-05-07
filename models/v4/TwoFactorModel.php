<?php

declare(strict_types=1);

namespace Models\V4;

use PDO;
use PDOException;

class TwoFactorModel
{
    private PDO $pdo;
    private ?string $lastError = null;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    /**
     * Retorna instância PDO
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }
    
    /**
     * Retorna último erro
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }
    
    /**
     * Obtém configuração 2FA do usuário
     */
    public function getConfig(int $userId): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM user_two_factor WHERE user_id = :user_id LIMIT 1");
            $stmt->execute([':user_id' => $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $result['backup_codes'] = $result['backup_codes'] ? json_decode($result['backup_codes'], true) : [];
            }
            
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("[TwoFactorModel] Erro ao obter config: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Cria configuração 2FA inicial
     */
    public function createConfig(int $userId, string $secretKey, array $backupCodes): bool
    {
        $this->lastError = null;
        
        try {
            $backupCodesJson = json_encode($backupCodes);
            
            $stmt = $this->pdo->prepare("INSERT INTO user_two_factor (user_id, secret_key, backup_codes) VALUES (:user_id, :secret_key, :backup_codes) ON DUPLICATE KEY UPDATE secret_key = :secret_key_update, backup_codes = :backup_codes_update, is_enabled = 0, verified_at = NULL");
            
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':secret_key', $secretKey, PDO::PARAM_STR);
            $stmt->bindValue(':backup_codes', $backupCodesJson, PDO::PARAM_STR);
            $stmt->bindValue(':secret_key_update', $secretKey, PDO::PARAM_STR);
            $stmt->bindValue(':backup_codes_update', $backupCodesJson, PDO::PARAM_STR);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            error_log("[TwoFactorModel] Erro ao criar config: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ativa 2FA após verificação
     */
    public function enable(int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE user_two_factor SET is_enabled = 1, verified_at = NOW(), updated_at = NOW() WHERE user_id = :user_id");
            return $stmt->execute([':user_id' => $userId]);
        } catch (PDOException $e) {
            error_log("[TwoFactorModel] Erro ao ativar 2FA: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Desativa 2FA
     */
    public function disable(int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM user_two_factor WHERE user_id = :user_id");
            return $stmt->execute([':user_id' => $userId]);
        } catch (PDOException $e) {
            error_log("[TwoFactorModel] Erro ao desativar 2FA: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se 2FA está ativado
     */
    public function isEnabled(int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare("SELECT is_enabled FROM user_two_factor WHERE user_id = :user_id LIMIT 1");
            $stmt->execute([':user_id' => $userId]);
            return (bool) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("[TwoFactorModel] Erro ao verificar status: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra log de evento 2FA
     */
    public function logEvent(int $userId, string $action, string $ipAddress, string $userAgent, string $status, ?array $details = null): bool
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO two_factor_logs (user_id, action, ip_address, user_agent, status, details) VALUES (:user_id, :action, :ip_address, :user_agent, :status, :details)");
            
            return $stmt->execute([
                ':user_id' => $userId,
                ':action' => $action,
                ':ip_address' => $ipAddress,
                ':user_agent' => $userAgent,
                ':status' => $status,
                ':details' => $details ? json_encode($details) : null
            ]);
        } catch (PDOException $e) {
            error_log("[TwoFactorModel] Erro ao registrar log: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém logs do usuário
     */
    public function getLogs(int $userId, int $limit = 50): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM two_factor_logs WHERE user_id = :user_id ORDER BY created_at DESC LIMIT :limit");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("[TwoFactorModel] Erro ao obter logs: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Adiciona dispositivo confiável
     */
    public function addTrustedDevice(int $userId, string $deviceToken, string $deviceName, string $ipAddress, int $expiresDays = 30): bool
    {
        try {
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiresDays} days"));
            
            $stmt = $this->pdo->prepare("INSERT INTO two_factor_trusted_devices (user_id, device_token, device_name, ip_address, expires_at) VALUES (:user_id, :device_token, :device_name, :ip_address, :expires_at) ON DUPLICATE KEY UPDATE device_name = :device_name, ip_address = :ip_address, expires_at = :expires_at, created_at = NOW()");
            
            return $stmt->execute([
                ':user_id' => $userId,
                ':device_token' => hash('sha256', $deviceToken),
                ':device_name' => $deviceName,
                ':ip_address' => $ipAddress,
                ':expires_at' => $expiresAt
            ]);
        } catch (PDOException $e) {
            error_log("[TwoFactorModel] Erro ao adicionar dispositivo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verifica se dispositivo é confiável
     */
    public function isTrustedDevice(int $userId, string $deviceToken): bool
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM two_factor_trusted_devices WHERE user_id = :user_id AND device_token = :device_token AND expires_at > NOW() LIMIT 1");
            $stmt->execute([
                ':user_id' => $userId,
                ':device_token' => hash('sha256', $deviceToken)
            ]);
            return (bool) $stmt->fetch();
        } catch (PDOException $e) {
            error_log("[TwoFactorModel] Erro ao verificar dispositivo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove dispositivo confiável
     */
    public function removeTrustedDevice(int $userId, string $deviceToken): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM two_factor_trusted_devices WHERE user_id = :user_id AND device_token = :device_token");
            return $stmt->execute([
                ':user_id' => $userId,
                ':device_token' => hash('sha256', $deviceToken)
            ]);
        } catch (PDOException $e) {
            error_log("[TwoFactorModel] Erro ao remover dispositivo: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtém dispositivos confiáveis do usuário
     */
    public function getTrustedDevices(int $userId): array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT id, device_name, ip_address, created_at, expires_at FROM two_factor_trusted_devices WHERE user_id = :user_id AND expires_at > NOW() ORDER BY created_at DESC");
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("[TwoFactorModel] Erro ao obter dispositivos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Remove todos os dispositivos confiáveis
     */
    public function removeAllTrustedDevices(int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM two_factor_trusted_devices WHERE user_id = :user_id");
            return $stmt->execute([':user_id' => $userId]);
        } catch (PDOException $e) {
            error_log("[TwoFactorModel] Erro ao remover dispositivos: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra tentativa de verificação
     */
    public function recordAttempt(int $userId, string $ipAddress): bool
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO two_factor_attempts (user_id, ip_address) VALUES (:user_id, :ip_address)");
            return $stmt->execute([
                ':user_id' => $userId,
                ':ip_address' => $ipAddress
            ]);
        } catch (PDOException $e) {
            error_log("[TwoFactorModel] Erro ao registrar tentativa: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Conta tentativas recentes (últimos 15 minutos)
     */
    public function countRecentAttempts(int $userId, string $ipAddress): int
    {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM two_factor_attempts WHERE user_id = :user_id AND ip_address = :ip_address AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
            $stmt->execute([
                ':user_id' => $userId,
                ':ip_address' => $ipAddress
            ]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("[TwoFactorModel] Erro ao contar tentativas: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Limpa tentativas antigas
     */
    public function cleanupAttempts(): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM two_factor_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("[TwoFactorModel] Erro ao limpar tentativas: " . $e->getMessage());
            return false;
        }
    }
}
