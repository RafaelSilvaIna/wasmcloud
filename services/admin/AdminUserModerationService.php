<?php
declare(strict_types=1);

namespace Services\Admin;

use Models\Admin\AdminUserModerationModel;

final class AdminUserModerationService
{
    public function __construct(private AdminUserModerationModel $users)
    {
        $this->users->ensureSchema();
    }

    public function list(string $search = ''): array
    {
        return [
            'success' => true,
            'users' => $this->users->listUsers(trim($search)),
        ];
    }

    public function details(int $userId): array
    {
        $details = $this->users->userDetails($userId);

        if (!$details) {
            return ['success' => false, 'error' => 'Usuario nao encontrado.'];
        }

        return ['success' => true] + $details;
    }

    public function suspend(int $userId, int $adminId, string $reason, int $durationMinutes): array
    {
        $reason = trim($reason);
        if ($userId <= 0 || $adminId <= 0 || $reason === '' || $durationMinutes < 15) {
            return ['success' => false, 'error' => 'Informe motivo e duracao minima de 15 minutos.'];
        }

        $this->users->suspendUser($userId, $adminId, $reason, $durationMinutes);
        return ['success' => true, 'message' => 'Conta suspensa temporariamente.'];
    }

    public function ban(int $userId, int $adminId, string $reason): array
    {
        $reason = trim($reason);
        if ($userId <= 0 || $adminId <= 0 || $reason === '') {
            return ['success' => false, 'error' => 'Informe o motivo do banimento.'];
        }

        $this->users->banUser($userId, $adminId, $reason);
        return ['success' => true, 'message' => 'Conta banida permanentemente.'];
    }

    public function reactivate(int $userId, int $adminId, string $reason): array
    {
        $reason = trim($reason) ?: 'Reativacao manual pelo administrador.';

        if ($userId <= 0 || $adminId <= 0) {
            return ['success' => false, 'error' => 'Usuario invalido.'];
        }

        $this->users->reactivateUser($userId, $adminId, $reason);
        return ['success' => true, 'message' => 'Conta reativada.'];
    }
}
