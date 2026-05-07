<?php

declare(strict_types=1);

namespace Controllers\V4;

use Services\V4\PinService;

class PinController
{
    private PinService $service;

    public function __construct(PinService $service)
    {
        $this->service = $service;
    }

    /**
     * Obtém o endereço IP do cliente
     */
    private function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',  // Proxy
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (is_string($ip)) {
                    // Se for uma lista de IPs, pega o primeiro
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                    if (filter_var($ip, FILTER_VALIDATE_IP)) {
                        return $ip;
                    }
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Verifica autenticação do usuário
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
     * Roteador de ações
     */
    public function handle(string $action, string $method): void
    {
        switch (true) {
            case $action === 'pin/check':
                $this->checkPin();
                break;
                
            case $action === 'pin/create' && $method === 'POST':
                $this->createPin();
                break;
                
            case $action === 'pin/validate' && $method === 'POST':
                $this->validatePin();
                break;
                
            case $action === 'pin/change' && $method === 'POST':
                $this->changePin();
                break;
                
            case $action === 'pin/remove' && $method === 'POST':
                $this->removePin();
                break;
                
            case $action === 'pin/status':
                $this->getStatus();
                break;
                
            default:
                \ResponseUtil::json([
                    'success' => false,
                    'error' => 'Rota PIN não encontrada'
                ], 404);
        }
    }

    /**
     * Verifica se o usuário possui PIN
     */
    private function checkPin(): void
    {
        $userId = $this->requireAuth();
        if (!$userId) return;

        $result = $this->service->hasPin($userId);
        \ResponseUtil::json($result);
    }

    /**
     * Cria um novo PIN
     */
    private function createPin(): void
    {
        $userId = $this->requireAuth();
        if (!$userId) return;

        $data = $this->getJsonInput();
        
        if (empty($data['pin'])) {
            \ResponseUtil::json([
                'success' => false,
                'error' => 'PIN não fornecido'
            ], 400);
            return;
        }

        $result = $this->service->createPin($userId, (string) $data['pin']);
        \ResponseUtil::json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Valida um PIN
     */
    private function validatePin(): void
    {
        $userId = $this->requireAuth();
        if (!$userId) return;

        $data = $this->getJsonInput();
        $ipAddress = $this->getClientIp();
        
        if (empty($data['pin'])) {
            \ResponseUtil::json([
                'success' => false,
                'error' => 'PIN não fornecido'
            ], 400);
            return;
        }

        $result = $this->service->validatePin($userId, (string) $data['pin'], $ipAddress);
        
        // Se PIN válido, marca sessão para acesso às configurações
        if ($result['success'] && $result['valid']) {
            $_SESSION['pin_validated'] = true;
            $_SESSION['pin_validate_time'] = time();
        }
        
        $statusCode = 200;
        if (isset($result['blocked']) && $result['blocked']) {
            $statusCode = 429; // Too Many Requests
        }
        
        \ResponseUtil::json($result, $statusCode);
    }

    /**
     * Altera o PIN atual
     */
    private function changePin(): void
    {
        $userId = $this->requireAuth();
        if (!$userId) return;

        $data = $this->getJsonInput();
        $ipAddress = $this->getClientIp();
        
        if (empty($data['current_pin']) || empty($data['new_pin'])) {
            \ResponseUtil::json([
                'success' => false,
                'error' => 'PIN atual e novo PIN são obrigatórios'
            ], 400);
            return;
        }

        $result = $this->service->changePin(
            $userId, 
            (string) $data['current_pin'], 
            (string) $data['new_pin'], 
            $ipAddress
        );
        
        \ResponseUtil::json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Remove o PIN
     */
    private function removePin(): void
    {
        $userId = $this->requireAuth();
        if (!$userId) return;

        $data = $this->getJsonInput();
        $ipAddress = $this->getClientIp();
        
        if (empty($data['pin'])) {
            \ResponseUtil::json([
                'success' => false,
                'error' => 'PIN atual é obrigatório'
            ], 400);
            return;
        }

        $result = $this->service->removePin($userId, (string) $data['pin'], $ipAddress);
        \ResponseUtil::json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Obtém status de bloqueio
     */
    private function getStatus(): void
    {
        $userId = $this->requireAuth();
        if (!$userId) return;

        $ipAddress = $this->getClientIp();
        $result = $this->service->getBlockStatus($userId, $ipAddress);
        
        \ResponseUtil::json($result);
    }

    /**
     * Obtém dados JSON da requisição
     */
    private function getJsonInput(): array
    {
        $json = file_get_contents('php://input');
        if (empty($json)) {
            return [];
        }
        
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }
}
