<?php

declare(strict_types=1);

namespace Controllers\V4;

use Services\V4\TwoFactorService;

class TwoFactorController
{
    private TwoFactorService $service;
    
    public function __construct(TwoFactorService $service)
    {
        $this->service = $service;
    }
    
    /**
     * Roteador de ações
     */
    public function handle(string $action, string $method): void
    {
        try {
            switch ($action) {
                case '2fa/setup':
                    if ($method === 'POST') {
                        $this->setup();
                    }
                    break;
                    
                case '2fa/verify':
                    if ($method === 'POST') {
                        $this->verify();
                    }
                    break;
                    
                case '2fa/status':
                    if ($method === 'GET') {
                        $this->status();
                    }
                    break;
                    
                case '2fa/disable':
                    if ($method === 'POST') {
                        $this->disable();
                    }
                    break;
                    
                case '2fa/backup-codes':
                    if ($method === 'POST') {
                        $this->regenerateBackupCodes();
                    }
                    break;
                    
                case '2fa/logs':
                    if ($method === 'GET') {
                        $this->logs();
                    }
                    break;
                    
                case '2fa/devices':
                    if ($method === 'GET') {
                        $this->devices();
                    } elseif ($method === 'DELETE') {
                        $this->removeDevice();
                    }
                    break;
                    
                default:
                    \ResponseUtil::json([
                        'success' => false,
                        'error' => 'Ação não encontrada'
                    ], 404);
            }
        } catch (\Throwable $e) {
            error_log("[TwoFactorController] Erro: " . $e->getMessage());
            \ResponseUtil::json([
                'success' => false,
                'error' => 'Erro interno do servidor',
                'detalhe' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Inicia setup do 2FA
     */
    private function setup(): void
    {
        $userId = $this->requireAuth();
        if (!$userId) return;
        
        $username = $_SESSION['user_email'] ?? $_SESSION['user_name'] ?? 'user' . $userId;
        
        $result = $this->service->setup($userId, $username);
        
        $statusCode = $result['success'] ? 200 : 400;
        \ResponseUtil::json($result, $statusCode);
    }
    
    /**
     * Verifica código e ativa 2FA
     */
    private function verify(): void
    {
        $userId = $this->requireAuth();
        if (!$userId) return;
        
        $data = $this->getJsonInput();
        
        if (empty($data['code'])) {
            \ResponseUtil::json([
                'success' => false,
                'error' => 'Código não fornecido'
            ], 400);
            return;
        }
        
        $ipAddress = $this->getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $deviceToken = $data['device_token'] ?? null;
        $rememberDevice = $data['remember_device'] ?? false;
        
        $result = $this->service->verifyAndEnable(
            $userId,
            (string) $data['code'],
            $ipAddress,
            $userAgent,
            $rememberDevice ? $deviceToken : null
        );
        
        $statusCode = $result['success'] ? 200 : ($result['blocked'] ? 429 : 400);
        \ResponseUtil::json($result, $statusCode);
    }
    
    /**
     * Obtém status do 2FA
     */
    private function status(): void
    {
        $userId = $this->requireAuth();
        if (!$userId) return;
        
        $result = $this->service->getStatus($userId);
        
        \ResponseUtil::json([
            'success' => true,
            'data' => $result
        ]);
    }
    
    /**
     * Desativa 2FA
     */
    private function disable(): void
    {
        $userId = $this->requireAuth();
        if (!$userId) return;
        
        $data = $this->getJsonInput();
        
        if (empty($data['pin'])) {
            \ResponseUtil::json([
                'success' => false,
                'error' => 'PIN necessário para desativar 2FA'
            ], 400);
            return;
        }
        
        $ipAddress = $this->getClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $result = $this->service->disable($userId, (string) $data['pin'], $ipAddress, $userAgent);
        
        $statusCode = $result['success'] ? 200 : 400;
        \ResponseUtil::json($result, $statusCode);
    }
    
    /**
     * Regenera códigos de backup
     */
    private function regenerateBackupCodes(): void
    {
        $userId = $this->requireAuth();
        if (!$userId) return;
        
        $data = $this->getJsonInput();
        
        if (empty($data['pin'])) {
            \ResponseUtil::json([
                'success' => false,
                'error' => 'PIN necessário'
            ], 400);
            return;
        }
        
        $result = $this->service->regenerateBackupCodes($userId, (string) $data['pin']);
        
        $statusCode = $result['success'] ? 200 : 400;
        \ResponseUtil::json($result, $statusCode);
    }
    
    /**
     * Obtém logs de atividade
     */
    private function logs(): void
    {
        $userId = $this->requireAuth();
        if (!$userId) return;
        
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $logs = $this->service->getLogs($userId, $limit);
        
        \ResponseUtil::json([
            'success' => true,
            'data' => $logs
        ]);
    }
    
    /**
     * Obtém dispositivos confiáveis
     */
    private function devices(): void
    {
        $userId = $this->requireAuth();
        if (!$userId) return;
        
        $devices = $this->service->getTrustedDevices($userId);
        
        \ResponseUtil::json([
            'success' => true,
            'data' => $devices
        ]);
    }
    
    /**
     * Remove dispositivo confiável
     */
    private function removeDevice(): void
    {
        $userId = $this->requireAuth();
        if (!$userId) return;
        
        $data = $this->getJsonInput();
        
        if (empty($data['device_token'])) {
            \ResponseUtil::json([
                'success' => false,
                'error' => 'Token do dispositivo necessário'
            ], 400);
            return;
        }
        
        if ($data['device_token'] === 'ALL') {
            $result = $this->service->removeAllDevices($userId);
        } else {
            $result = $this->service->removeDevice($userId, (string) $data['device_token']);
        }
        
        $statusCode = $result['success'] ? 200 : 400;
        \ResponseUtil::json($result, $statusCode);
    }
    
    /**
     * Verifica autenticação
     */
    private function requireAuth(): ?int
    {
        if (empty($_SESSION['user_id'])) {
            \ResponseUtil::json([
                'success' => false,
                'error' => 'Usuário não autenticado'
            ], 401);
            return null;
        }
        return (int) $_SESSION['user_id'];
    }
    
    /**
     * Obtém dados JSON da requisição
     */
    private function getJsonInput(): array
    {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }
    
    /**
     * Obtém IP do cliente
     */
    private function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                return $ip;
            }
        }
        
        return '0.0.0.0';
    }
}
