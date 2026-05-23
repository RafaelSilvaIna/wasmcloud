<?php
declare(strict_types=1);

namespace Controllers\Player;

use Services\Player\PlayerLogService;

final class PlayerLogController
{
    public function __construct(private PlayerLogService $logs)
    {
    }

    public function handle(string $method): void
    {
        if ($method !== 'POST') {
            $this->json(['success' => false, 'error' => 'Metodo nao permitido.'], 405);
        }

        $raw = file_get_contents('php://input') ?: '';
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $this->json(['success' => false, 'error' => 'Payload invalido.'], 422);
        }

        try {
            $this->json($this->logs->record($payload));
        } catch (\Throwable $e) {
            error_log('[PlayerLog] ' . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Nao foi possivel registrar o erro.'], 500);
        }
    }

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }
}
