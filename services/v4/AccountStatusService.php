<?php
declare(strict_types=1);

namespace Services\V4;

use Models\V4\AccountStatusModel;

final class AccountStatusService
{
    public function __construct(private AccountStatusModel $model)
    {
    }

    public function status(int $userId): array
    {
        $status = $this->model->currentStatus($userId);

        if (!$status) {
            return ['success' => false, 'error' => 'Usuario nao encontrado.'];
        }

        return ['success' => true, 'account' => $status];
    }
}
