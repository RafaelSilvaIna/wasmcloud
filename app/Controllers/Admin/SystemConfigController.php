<?php
namespace App\Controllers\Admin;

use App\Services\SystemConfigService;
use App\Middlewares\RateLimitMiddleware;

class SystemConfigController {
    private $service;

    public function __construct() {
        RateLimitMiddleware::check();
        $this->service = new SystemConfigService();
    }

    public function getConfigs() {
        $result = $this->service->getOrInitializeConfigs();
        
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'success', 'data' => $result]);
        exit;
    }

    public function updateConfigs() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input)) {
            self::abort(400, 'Dados invalidos.');
        }

        if ($this->service->updateGlobalConfigs($input)) {
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'success', 'message' => 'Configuracoes atualizadas com sucesso.']);
            exit;
        } else {
            self::abort(500, 'Erro interno ao atualizar configuracoes.');
        }
    }

    private static function abort($code, $message) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => $message]);
        exit;
    }
}