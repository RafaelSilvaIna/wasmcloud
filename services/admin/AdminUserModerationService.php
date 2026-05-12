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

    public function list(string $search = '', string $filter = 'all'): array
    {
        return [
            'success' => true,
            'users' => $this->users->listUsers(trim($search), $filter),
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

    public function suspend(int $userId, int $adminId, string $reason, string $duration): array
    {
        $reason = trim($reason);
        $durationMinutes = $this->parseDurationMinutes($duration);

        if ($userId <= 0 || $adminId <= 0 || $reason === '' || $durationMinutes < 1) {
            return ['success' => false, 'error' => 'Informe motivo e duracao. Exemplos: 30 minutos, 12 horas, 2 dias.'];
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

    public function adminLogs(): array
    {
        return [
            'success' => true,
            'logs' => $this->users->adminAuditLogs(),
        ];
    }

    private function parseDurationMinutes(string $duration): int
    {
        $duration = strtolower(trim($duration));
        $duration = str_replace(',', '.', $duration);

        if ($duration === '') {
            return 0;
        }

        if (preg_match('/^(\d+(?:\.\d+)?)$/', $duration, $m)) {
            return (int) round((float) $m[1]);
        }

        if (!preg_match('/(\d+(?:\.\d+)?)\s*([a-z]+)/', $duration, $m)) {
            return 0;
        }

        $value = (float) $m[1];
        $unit = $m[2];

        if (str_starts_with($unit, 'min')) {
            return (int) round($value);
        }

        if (str_starts_with($unit, 'h') || str_starts_with($unit, 'hora')) {
            return (int) round($value * 60);
        }

        if (str_starts_with($unit, 'd') || str_starts_with($unit, 'dia')) {
            return (int) round($value * 1440);
        }

        if (str_starts_with($unit, 'sem')) {
            return (int) round($value * 10080);
        }

        if (str_starts_with($unit, 'mes') || str_starts_with($unit, 'mês')) {
            return (int) round($value * 43200);
        }

        return 0;
    }
}
