<?php

declare(strict_types=1);

namespace Middleware;

use Models\V4\PinModel;

/**
 * Middleware de Rate Limiting para PIN
 * 
 * Bloqueia a inserção de PIN após 3 tentativas mal sucedidas
 * por um período de 30 minutos.
 */
class PinRateLimitMiddleware
{
    private PinModel $pinModel;
    private int $maxAttempts;
    private int $blockMinutes;

    public function __construct(PinModel $pinModel, int $maxAttempts = 3, int $blockMinutes = 30)
    {
        $this->pinModel = $pinModel;
        $this->maxAttempts = $maxAttempts;
        $this->blockMinutes = $blockMinutes;
    }

    /**
     * Obtém o IP do cliente
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
                if (is_string($ip)) {
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
     * Verifica se a requisição está bloqueada
     * 
     * @param int $userId ID do usuário
     * @return array ['blocked' => bool, 'remaining_seconds' => int, 'message' => string]
     */
    public function check(int $userId): array
    {
        $ipAddress = $this->getClientIp();
        
        // Verifica se está bloqueado
        if ($this->pinModel->isBlocked($userId, $ipAddress, $this->maxAttempts, $this->blockMinutes)) {
            $remaining = $this->pinModel->getBlockTimeRemaining($userId, $ipAddress, $this->blockMinutes);
            $minutes = ceil($remaining / 60);
            
            return [
                'blocked' => true,
                'remaining_seconds' => $remaining,
                'remaining_minutes' => $minutes,
                'message' => "Muitas tentativas falhas. Aguarde {$minutes} minutos antes de tentar novamente.",
                'ip_address' => $ipAddress
            ];
        }

        // Obtém tentativas restantes
        $failedAttempts = $this->pinModel->countFailedAttempts($userId, $ipAddress, $this->blockMinutes);
        $remainingAttempts = max(0, $this->maxAttempts - $failedAttempts);

        return [
            'blocked' => false,
            'remaining_seconds' => 0,
            'remaining_attempts' => $remainingAttempts,
            'max_attempts' => $this->maxAttempts,
            'message' => null,
            'ip_address' => $ipAddress
        ];
    }

    /**
     * Registra uma tentativa de validação
     * 
     * @param int $userId ID do usuário
     * @param bool $success Se a tentativa foi bem-sucedida
     * @return bool
     */
    public function logAttempt(int $userId, bool $success): bool
    {
        $ipAddress = $this->getClientIp();
        return $this->pinModel->logAttempt($userId, $ipAddress, $success);
    }

    /**
     * Limpa tentativas antigas
     * 
     * @param int $hours Horas para manter as tentativas
     * @return bool
     */
    public function cleanup(int $hours = 24): bool
    {
        return $this->pinModel->cleanOldAttempts($hours);
    }

    /**
     * Obtém informações de rate limit para resposta
     * 
     * @param int $userId ID do usuário
     * @return array
     */
    public function getRateLimitInfo(int $userId): array
    {
        $ipAddress = $this->getClientIp();
        $failedAttempts = $this->pinModel->countFailedAttempts($userId, $ipAddress, $this->blockMinutes);
        
        return [
            'max_attempts' => $this->maxAttempts,
            'attempts_made' => $failedAttempts,
            'attempts_remaining' => max(0, $this->maxAttempts - $failedAttempts),
            'block_duration_minutes' => $this->blockMinutes,
            'reset_time' => $failedAttempts >= $this->maxAttempts 
                ? date('Y-m-d H:i:s', time() + ($this->blockMinutes * 60))
                : null
        ];
    }
}
