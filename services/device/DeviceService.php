<?php

declare(strict_types=1);

namespace Services\Device;

use Helpers\Device\DeviceFingerprint;
use Models\Device\DeviceModel;

final class DeviceService
{
    private DeviceModel $model;

    public const LIMIT_FREE = 1;
    public const LIMIT_GOLD = 4;

    public function __construct(DeviceModel $model)
    {
        $this->model = $model;
    }

    public function limitForUser(bool $isGoldOrCourtesy): int
    {
        return $isGoldOrCourtesy ? self::LIMIT_GOLD : self::LIMIT_FREE;
    }

    public function heartbeat(int $userId, bool $isGoldOrCourtesy): array
    {
        $this->model->ensureSchema();
        $this->model->cleanup();

        $deviceId = DeviceFingerprint::resolve();
        $sessionId = session_id() ?: '';
        $ipPartial = DeviceFingerprint::partialIp();
        $uaHash = DeviceFingerprint::uaHash();
        $deviceLabel = DeviceFingerprint::deviceLabel();
        $limit = $this->limitForUser($isGoldOrCourtesy);

        $active = $this->model->countActiveDevices($userId);
        $alreadyActive = $this->model->isDeviceActive($userId, $deviceId);
        $sameSessionActive = $this->model->isSessionActiveForUser($userId, $sessionId);

        if (!$alreadyActive && !$sameSessionActive && $active >= $limit) {
            return [
                'allowed' => false,
                'active' => $active,
                'limit' => $limit,
                'device_id' => $deviceId,
            ];
        }

        if ($sameSessionActive) {
            $this->model->deactivateOtherDevicesForSession($userId, $sessionId, $deviceId);
        }

        $this->model->upsertDevice($userId, $deviceId, $sessionId, $ipPartial, $uaHash, $deviceLabel);

        return [
            'allowed' => true,
            'active' => $this->model->countActiveDevices($userId),
            'limit' => $limit,
            'device_id' => $deviceId,
        ];
    }

    public function check(int $userId, bool $isGoldOrCourtesy): array
    {
        $this->model->ensureSchema();
        $this->model->cleanup();

        $deviceId = DeviceFingerprint::resolve();
        $limit = $this->limitForUser($isGoldOrCourtesy);

        if ($this->model->isDeviceActive($userId, $deviceId)) {
            return [
                'allowed' => true,
                'active' => $this->model->countActiveDevices($userId),
                'limit' => $limit,
                'device_id' => $deviceId,
            ];
        }

        $active = $this->model->countActiveDevices($userId);

        return [
            'allowed' => $active < $limit,
            'active' => $active,
            'limit' => $limit,
            'device_id' => $deviceId,
        ];
    }

    public function release(int $userId): bool
    {
        $deviceId = DeviceFingerprint::resolve();
        $sessionId = session_id() ?: '';
        $released = $this->model->deactivateDevice($userId, $deviceId, $sessionId);

        if (!empty($_SESSION['profile_id'])) {
            $this->model->deactivateProfileSession($userId, (int) $_SESSION['profile_id'], $sessionId);
        }

        return $released;
    }

    public function releaseAll(int $userId): bool
    {
        return $this->model->deactivateAllDevices($userId);
    }

    public function touchProfileSession(int $userId, int $profileId): void
    {
        $this->model->touchProfileSession($userId, $profileId, session_id() ?: '');
    }

    public function listDevices(int $userId): array
    {
        $this->model->ensureSchema();
        $this->model->cleanup();
        return $this->model->listActiveDevices($userId);
    }
}
