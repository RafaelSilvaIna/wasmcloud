<?php

declare(strict_types=1);

namespace Services\V4;

use Models\V4\PinModel;

class PinService
{
    private PinModel $model;

    public function __construct(PinModel $model)
    {
        $this->model = $model;
    }

    /**
     * Cria um hash seguro do PIN
     */
    private function hashPin(string $pin): string
    {
        return hash('sha256', $pin);
    }

    /**
     * Valida formato do PIN (4 dígitos numéricos)
     */
    private function validatePinFormat(string $pin): bool
    {
        return preg_match('/^\d{4}$/', $pin) === 1;
    }

    /**
     * Cria ou atualiza o PIN do usuário
     */
    public function createPin(int $userId, string $pin): array
    {
        if (!$this->validatePinFormat($pin)) {
            return [
                'success' => false,
                'error' => 'O PIN deve conter exatamente 4 dígitos numéricos'
            ];
        }

        $pinHash = $this->hashPin($pin);
        $result = $this->model->createPin($userId, $pinHash);
        
        if ($result['success']) {
            $this->model->clearFailedAttempts($userId);

            return [
                'success' => true,
                'message' => 'PIN criado com sucesso'
            ];
        }

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Erro ao criar PIN. Tente novamente.'
        ];
    }

    /**
     * Verifica se o usuário tem PIN cadastrado
     */
    public function hasPin(int $userId): array
    {
        $hasPin = $this->model->hasPin($userId);
        
        return [
            'success' => true,
            'has_pin' => $hasPin
        ];
    }

    /**
     * Valida o PIN do usuário com proteção contra brute force
     */
    public function validatePin(int $userId, string $pin, string $ipAddress): array
    {
        // Verifica se está bloqueado
        if ($this->model->isBlocked($userId, $ipAddress)) {
            $remaining = $this->model->getBlockTimeRemaining($userId, $ipAddress);
            $minutes = ceil($remaining / 60);
            
            return [
                'success' => false,
                'blocked' => true,
                'error' => "Muitas tentativas falhas. Tente novamente em {$minutes} minutos.",
                'remaining_seconds' => $remaining
            ];
        }

        if (!$this->validatePinFormat($pin)) {
            $this->model->logAttempt($userId, $ipAddress, false);
            
            return [
                'success' => false,
                'error' => 'O PIN deve conter exatamente 4 dígitos numéricos'
            ];
        }

        $pinHash = $this->hashPin($pin);
        $isValid = $this->model->validatePin($userId, $pinHash);
        
        // Registra tentativa
        $this->model->logAttempt($userId, $ipAddress, $isValid);
        
        if ($isValid) {
            $this->model->clearFailedAttempts($userId, $ipAddress);

            return [
                'success' => true,
                'valid' => true,
                'message' => 'PIN válido'
            ];
        }

        // Verifica se bloqueou agora
        $failedAttempts = $this->model->countFailedAttempts($userId, $ipAddress);
        $remainingAttempts = max(0, 3 - $failedAttempts);
        
        return [
            'success' => true,
            'valid' => false,
            'error' => 'PIN incorreto',
            'remaining_attempts' => $remainingAttempts,
            'blocked' => $failedAttempts >= 3,
            'remaining_seconds' => $failedAttempts >= 3 ? $this->model->getBlockTimeRemaining($userId, $ipAddress) : 0
        ];
    }

    /**
     * Remove o PIN do usuário (após validação)
     */
    public function removePin(int $userId, string $currentPin, string $ipAddress): array
    {
        // Primeiro valida o PIN atual
        $validation = $this->validatePin($userId, $currentPin, $ipAddress);
        
        if (!$validation['success'] || !$validation['valid']) {
            return [
                'success' => false,
                'error' => $validation['error'] ?? 'PIN incorreto'
            ];
        }
        
        if ($this->model->deletePin($userId)) {
            $this->model->clearFailedAttempts($userId);

            return [
                'success' => true,
                'message' => 'PIN removido com sucesso'
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Erro ao remover PIN'
        ];
    }

    /**
     * Troca o PIN do usuário
     */
    public function changePin(int $userId, string $currentPin, string $newPin, string $ipAddress): array
    {
        // Valida PIN atual
        $validation = $this->validatePin($userId, $currentPin, $ipAddress);
        
        if (!$validation['success'] || !$validation['valid']) {
            return [
                'success' => false,
                'error' => $validation['error'] ?? 'PIN atual incorreto'
            ];
        }
        
        // Cria novo PIN
        return $this->createPin($userId, $newPin);
    }

    /**
     * Obtém status do bloqueio
     */
    public function getBlockStatus(int $userId, string $ipAddress): array
    {
        $isBlocked = $this->model->isBlocked($userId, $ipAddress);
        $remaining = $this->model->getBlockTimeRemaining($userId, $ipAddress);
        $failedAttempts = $this->model->countFailedAttempts($userId, $ipAddress);
        
        return [
            'success' => true,
            'blocked' => $isBlocked,
            'remaining_seconds' => $remaining,
            'failed_attempts' => $failedAttempts,
            'max_attempts' => 3
        ];
    }
}
