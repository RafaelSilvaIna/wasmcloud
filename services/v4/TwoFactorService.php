<?php

declare(strict_types=1);

namespace Services\V4;

use Models\V4\TwoFactorModel;
use GoogleAuthenticatorHelper;

class TwoFactorService
{
    private TwoFactorModel $model;
    
    public function __construct(TwoFactorModel $model)
    {
        $this->model = $model;
    }
    
    /**
     * Inicia setup do 2FA (gera secret e backup codes)
     */
    public function setup(int $userId, string $username): array
    {
        // Gera chave secreta e códigos de backup
        $secret = GoogleAuthenticatorHelper::generateSecret();
        $backupCodes = GoogleAuthenticatorHelper::generateBackupCodes(8);
        
        // Salva no banco (não ativado ainda)
        if (!$this->model->createConfig($userId, $secret, $backupCodes)) {
            $errorMsg = $this->model->getLastError() ?? 'Erro desconhecido ao salvar no banco';
            return [
                'success' => false,
                'error' => 'Erro ao salvar configuração 2FA: ' . $errorMsg
            ];
        }
        
        // Gera QR Code
        $otpauthUrl = GoogleAuthenticatorHelper::getQRCodeUrl($username, $secret, 'PipoCine');
        $qrCodeUrl = GoogleAuthenticatorHelper::getQRCodeImageUrl($otpauthUrl, 200);
        
        return [
            'success' => true,
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'backup_codes' => $backupCodes,
            'manual_entry' => chunk_split($secret, 4, ' ')
        ];
    }
    
    /**
     * Verifica código e ativa 2FA
     */
    public function verifyAndEnable(int $userId, string $code, string $ipAddress, string $userAgent, ?string $deviceToken = null): array
    {
        // Rate limiting
        $attempts = $this->model->countRecentAttempts($userId, $ipAddress);
        if ($attempts >= 5) {
            return [
                'success' => false,
                'error' => 'Muitas tentativas. Aguarde 15 minutos.',
                'blocked' => true
            ];
        }
        
        // Registra tentativa
        $this->model->recordAttempt($userId, $ipAddress);
        
        // Obtém configuração
        $config = $this->model->getConfig($userId);
        if (!$config) {
            return [
                'success' => false,
                'error' => 'Configuração 2FA não encontrada'
            ];
        }
        
        // Verifica código TOTP ou backup code
        $isBackupCode = in_array($code, $config['backup_codes'] ?? []);
        $isValidTOTP = GoogleAuthenticatorHelper::verifyCode($config['secret_key'], $code);
        
        if (!$isValidTOTP && !$isBackupCode) {
            $this->model->logEvent($userId, 'verify', $ipAddress, $userAgent, 'failed', ['reason' => 'invalid_code']);
            
            return [
                'success' => false,
                'error' => 'Código inválido',
                'remaining_attempts' => 5 - ($attempts + 1)
            ];
        }
        
        // Se usou backup code, remove da lista
        if ($isBackupCode) {
            $newBackupCodes = array_diff($config['backup_codes'], [$code]);
            $this->model->createConfig($userId, $config['secret_key'], array_values($newBackupCodes));
        }
        
        // Ativa 2FA
        if ($this->model->enable($userId)) {
            // Adiciona dispositivo como confiável se solicitado
            if ($deviceToken) {
                $deviceName = GoogleAuthenticatorHelper::parseUserAgent($userAgent);
                $this->model->addTrustedDevice($userId, $deviceToken, $deviceName, $ipAddress, 30);
            }
            
            $this->model->logEvent($userId, 'enable', $ipAddress, $userAgent, 'success', [
                'used_backup_code' => $isBackupCode
            ]);
            
            return [
                'success' => true,
                'message' => 'Verificação em duas etapas ativada com sucesso'
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Erro ao ativar 2FA'
        ];
    }
    
    /**
     * Verifica código durante login
     */
    public function verifyLogin(int $userId, string $code, string $ipAddress, string $userAgent, ?string $deviceToken = null): array
    {
        $attempts = $this->model->countRecentAttempts($userId, $ipAddress);
        if ($attempts >= 5) {
            return [
                'success' => false,
                'error' => 'Muitas tentativas. Aguarde 15 minutos.',
                'blocked' => true
            ];
        }
        
        $this->model->recordAttempt($userId, $ipAddress);
        
        // Verifica se dispositivo é confiável
        if ($deviceToken && $this->model->isTrustedDevice($userId, $deviceToken)) {
            return [
                'success' => true,
                'trusted_device' => true
            ];
        }
        
        $config = $this->model->getConfig($userId);
        if (!$config || !$config['is_enabled']) {
            return [
                'success' => false,
                'error' => '2FA não configurado'
            ];
        }
        
        // Verifica TOTP ou backup
        $isBackupCode = in_array($code, $config['backup_codes'] ?? []);
        $isValidTOTP = GoogleAuthenticatorHelper::verifyCode($config['secret_key'], $code);
        
        if (!$isValidTOTP && !$isBackupCode) {
            $this->model->logEvent($userId, 'login_verify', $ipAddress, $userAgent, 'failed');
            
            return [
                'success' => false,
                'error' => 'Código inválido',
                'remaining_attempts' => 5 - ($attempts + 1)
            ];
        }
        
        // Se usou backup code, remove
        if ($isBackupCode) {
            $newBackupCodes = array_diff($config['backup_codes'], [$code]);
            $this->model->createConfig($userId, $config['secret_key'], array_values($newBackupCodes));
        }
        
        // Adiciona dispositivo confiável se solicitado
        if ($deviceToken) {
            $deviceName = GoogleAuthenticatorHelper::parseUserAgent($userAgent);
            $this->model->addTrustedDevice($userId, $deviceToken, $deviceName, $ipAddress, 30);
        }
        
        $this->model->logEvent($userId, 'login_verify', $ipAddress, $userAgent, 'success');
        
        return [
            'success' => true,
            'used_backup' => $isBackupCode
        ];
    }
    
    /**
     * Desativa 2FA
     */
    public function disable(int $userId, string $pin, string $ipAddress, string $userAgent): array
    {
        // Verifica PIN antes de permitir desativação
        $pinModel = new \Models\V4\PinModel($this->model->getPdo());
        $pinHash = hash('sha256', $pin);
        
        if (!$pinModel->validatePin($userId, $pinHash)) {
            return [
                'success' => false,
                'error' => 'PIN incorreto'
            ];
        }
        
        // Remove configuração e dispositivos
        $this->model->disable($userId);
        $this->model->removeAllTrustedDevices($userId);
        
        $this->model->logEvent($userId, 'disable', $ipAddress, $userAgent, 'success');
        
        return [
            'success' => true,
            'message' => 'Verificação em duas etapas desativada'
        ];
    }
    
    /**
     * Obtém status do 2FA
     */
    public function getStatus(int $userId): array
    {
        $config = $this->model->getConfig($userId);
        $devices = $this->model->getTrustedDevices($userId);
        
        return [
            'enabled' => $config ? (bool) $config['is_enabled'] : false,
            'verified_at' => $config['verified_at'] ?? null,
            'trusted_devices_count' => count($devices),
            'backup_codes_remaining' => $config ? count($config['backup_codes']) : 0
        ];
    }

    /**
     * Verifica se um token de dispositivo ainda e confiavel.
     */
    public function isTrustedDevice(int $userId, string $deviceToken): bool
    {
        return $this->model->isTrustedDevice($userId, $deviceToken);
    }
    
    /**
     * Regenera códigos de backup
     */
    public function regenerateBackupCodes(int $userId, string $pin): array
    {
        // Verifica PIN
        $pinModel = new \Models\V4\PinModel($this->model->getPdo());
        $pinHash = hash('sha256', $pin);
        if (!$pinModel->validatePin($userId, $pinHash)) {
            return [
                'success' => false,
                'error' => 'PIN incorreto'
            ];
        }
        
        $config = $this->model->getConfig($userId);
        if (!$config || !$config['is_enabled']) {
            return [
                'success' => false,
                'error' => '2FA não ativado'
            ];
        }
        
        $newCodes = GoogleAuthenticatorHelper::generateBackupCodes(8);
        
        if ($this->model->createConfig($userId, $config['secret_key'], $newCodes)) {
            return [
                'success' => true,
                'backup_codes' => $newCodes
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Erro ao regenerar códigos'
        ];
    }
    
    /**
     * Obtém logs de atividade
     */
    public function getLogs(int $userId, int $limit = 20): array
    {
        return $this->model->getLogs($userId, $limit);
    }
    
    /**
     * Obtém dispositivos confiáveis do usuário
     */
    public function getTrustedDevices(int $userId): array
    {
        return $this->model->getTrustedDevices($userId);
    }
    
    /**
     * Remove dispositivo confiável específico
     */
    public function removeDevice(int $userId, string $deviceToken): array
    {
        if ($this->model->removeTrustedDevice($userId, $deviceToken)) {
            return [
                'success' => true,
                'message' => 'Dispositivo removido'
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Erro ao remover dispositivo'
        ];
    }
    
    /**
     * Remove todos os dispositivos confiáveis
     */
    public function removeAllDevices(int $userId): array
    {
        if ($this->model->removeAllTrustedDevices($userId)) {
            return [
                'success' => true,
                'message' => 'Todos os dispositivos foram desconectados'
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Erro ao remover dispositivos'
        ];
    }
}
