<?php

declare(strict_types=1);

namespace Controllers\Device;

use Services\Device\DeviceService;

/**
 * DeviceController
 *
 * Expõe as rotas da API de dispositivos:
 *
 *   POST /api/devices/heartbeat  — renova presença do dispositivo
 *   POST /api/devices/release    — desativa o dispositivo atual
 *   GET  /api/devices/status     — verifica se o dispositivo pode acessar
 *   GET  /api/devices/list       — lista dispositivos ativos (para o painel)
 */
final class DeviceController
{
    private DeviceService $service;

    public function __construct(DeviceService $service)
    {
        $this->service = $service;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers internos
    // ─────────────────────────────────────────────────────────────────────────

    private function userId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    private function isGold(): bool
    {
        // A sessão armazena o status do plano — definido pelo DeviceHook
        // ao autenticar. Fallback: considera gratuito.
        return !empty($_SESSION['device_plan_gold']);
    }

    private function json(mixed $data, int $status = 200): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code($status);
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function requireAuth(): bool
    {
        if ($this->userId() === null) {
            $this->json(['success' => false, 'error' => 'Nao autenticado.'], 401);
            return false;
        }
        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Endpoints
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * POST /api/devices/heartbeat
     *
     * Deve ser chamado pelo frontend a cada ~30 segundos enquanto a página
     * está aberta. Renova o dispositivo na tabela e retorna se o acesso
     * ainda é permitido.
     *
     * Response JSON:
     *   { allowed: bool, active: int, limit: int }
     */
    public function heartbeat(): void
    {
        if (!$this->requireAuth()) {
            return;
        }

        $result = $this->service->heartbeat($this->userId(), $this->isGold());

        $this->json([
            'success'   => true,
            'allowed'   => $result['allowed'],
            'active'    => $result['active'],
            'limit'     => $result['limit'],
            'device_id' => $result['device_id'],
        ]);
    }

    /**
     * POST /api/devices/release
     *
     * Desativa o dispositivo atual. Deve ser chamado no evento
     * `beforeunload` da página ou no logout.
     */
    public function release(): void
    {
        if (!$this->requireAuth()) {
            return;
        }

        $released = $this->service->release($this->userId());

        $this->json(['success' => $released]);
    }

    /**
     * GET /api/devices/status
     *
     * Verificação leve: retorna se o dispositivo atual pode acessar
     * o conteúdo sem registrar heartbeat.
     */
    public function status(): void
    {
        if (!$this->requireAuth()) {
            return;
        }

        $result = $this->service->check($this->userId(), $this->isGold());

        $this->json([
            'success' => true,
            'allowed' => $result['allowed'],
            'active'  => $result['active'],
            'limit'   => $result['limit'],
        ]);
    }

    /**
     * GET /api/devices/list
     *
     * Lista os dispositivos ativos da conta. Útil para exibição no painel
     * de configurações da conta.
     */
    public function list(): void
    {
        if (!$this->requireAuth()) {
            return;
        }

        $devices = $this->service->listDevices($this->userId());

        $this->json([
            'success' => true,
            'devices' => $devices,
            'limit'   => $this->service->limitForUser($this->isGold()),
        ]);
    }
}
