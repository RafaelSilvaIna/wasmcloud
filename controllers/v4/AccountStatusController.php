<?php
declare(strict_types=1);

namespace Controllers\V4;

use Services\V4\AccountStatusService;

final class AccountStatusController
{
    public function __construct(private AccountStatusService $service)
    {
    }

    public function handle(string $action, string $method, int $userId): void
    {
        if ($action === 'account/status' && $method === 'GET') {
            \ResponseUtil::json($this->service->status($userId));
            return;
        }

        \ResponseUtil::json(['success' => false, 'error' => 'Rota de conta nao encontrada'], 404);
    }
}
