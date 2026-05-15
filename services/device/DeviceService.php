<?php

declare(strict_types=1);

namespace Services\Device;

use Models\Device\DeviceModel;
use Helpers\Device\DeviceFingerprint;

/**
 * DeviceService
 *
 * Regras de negócio para o controle de dispositivos simultâneos por conta.
 *
 * Limites:
 *   - Plano gratuito   → 1 dispositivo simultâneo
 *   - Plano Gold / cortesia → 4 dispositivos simultâneos
 *
 * O serviço opera no nível da CONTA (user_id), não do perfil.
 */
final class DeviceService
{
    private DeviceModel $model;

    public const LIMIT_FREE = 1;
    public const LIMIT_GOLD = 4;

    public function __construct(DeviceModel $model)
    {
        $this->model = $model;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Consulta de limite por plano
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Retorna o limite de dispositivos simultâneos para o usuário.
     * Recebe o status de plano vindo da sessão ou banco de dados.
     */
    public function limitForUser(bool $isGoldOrCourtesy): int
    {
        return $isGoldOrCourtesy ? self::LIMIT_GOLD : self::LIMIT_FREE;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Heartbeat / Registro
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Registra ou renova o heartbeat do dispositivo atual.
     *
     * Deve ser chamado:
     *   - No carregamento de qualquer página protegida (via hook)
     *   - Periodicamente via requisição AJAX (a cada ~30s enquanto a aba está aberta)
     *
     * @return array{allowed: bool, active: int, limit: int, device_id: string}
     */
    public function heartbeat(int $userId, bool $isGoldOrCourtesy): array
    {
        $this->model->ensureSchema();

        $deviceId    = DeviceFingerprint::resolve();
        $sessionId   = session_id() ?: '';
        $ipPartial   = DeviceFingerprint::partialIp();
        $uaHash      = DeviceFingerprint::uaHash();
        $deviceLabel = DeviceFingerprint::deviceLabel();
        $limit       = $this->limitForUser($isGoldOrCourtesy);

        // Limpeza ocasional de registros antigos (probabilidade 5%)
        if (random_int(1, 20) === 1) {
            $this->model->cleanup();
        }

        // Verifica se este dispositivo já está registrado como ativo
        $alreadyActive = $this->model->isDeviceActive($userId, $deviceId);

        if ($alreadyActive) {
            // Apenas renova o heartbeat — nenhuma verificação de limite necessária
            $this->model->upsertDevice($userId, $deviceId, $sessionId, $ipPartial, $uaHash, $deviceLabel);
            $active = $this->model->countActiveDevices($userId);

            return [
                'allowed'   => true,
                'active'    => $active,
                'limit'     => $limit,
                'device_id' => $deviceId,
            ];
        }

        // Dispositivo NOVO: verifica se há vaga disponível
        $active = $this->model->countActiveDevices($userId);

        if ($active >= $limit) {
            // Limite excedido — não registra, retorna bloqueio
            return [
                'allowed'   => false,
                'active'    => $active,
                'limit'     => $limit,
                'device_id' => $deviceId,
            ];
        }

        // Há vaga — registra o dispositivo
        $this->model->upsertDevice($userId, $deviceId, $sessionId, $ipPartial, $uaHash, $deviceLabel);

        return [
            'allowed'   => true,
            'active'    => $active + 1,
            'limit'     => $limit,
            'device_id' => $deviceId,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Verificação rápida (sem registrar)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Verifica se o dispositivo atual pode acessar o conteúdo.
     * Usado no hook de proteção de páginas (sem side effects de escrita).
     *
     * @return array{allowed: bool, active: int, limit: int, device_id: string}
     */
    public function check(int $userId, bool $isGoldOrCourtesy): array
    {
        $this->model->ensureSchema();

        $deviceId = DeviceFingerprint::resolve();
        $limit    = $this->limitForUser($isGoldOrCourtesy);

        $alreadyActive = $this->model->isDeviceActive($userId, $deviceId);

        if ($alreadyActive) {
            $active = $this->model->countActiveDevices($userId);
            return [
                'allowed'   => true,
                'active'    => $active,
                'limit'     => $limit,
                'device_id' => $deviceId,
            ];
        }

        $active = $this->model->countActiveDevices($userId);

        return [
            'allowed'   => $active < $limit,
            'active'    => $active,
            'limit'     => $limit,
            'device_id' => $deviceId,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Encerramento
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Desativa o dispositivo atual (chamado no logout ou ao fechar a aba).
     */
    public function release(int $userId): bool
    {
        $deviceId  = DeviceFingerprint::resolve();
        $sessionId = session_id() ?: '';
        return $this->model->deactivateDevice($userId, $deviceId, $sessionId);
    }

    /**
     * Desativa todos os dispositivos do usuário (logout global).
     */
    public function releaseAll(int $userId): bool
    {
        return $this->model->deactivateAllDevices($userId);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Listagem
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Retorna lista de dispositivos ativos para exibição (ex: painel de conta).
     */
    public function listDevices(int $userId): array
    {
        $this->model->ensureSchema();
        return $this->model->listActiveDevices($userId);
    }
}
