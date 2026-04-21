<?php
namespace App\Controllers\Admin;

use App\Services\ThemeService;
use App\Services\SecurityVault;
use App\Middlewares\RateLimitMiddleware;

class ThemeController {
    public function __construct() {
        RateLimitMiddleware::check();
    }

    public function update() {
        $input = json_decode(file_get_contents('php://input'), true);
        $service = new ThemeService();

        if ($service->updateSystemTheme($input)) {
            http_response_code(200);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'success', 'message' => 'Cores atualizadas com sucesso.']);
            exit;
        }

        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Falha ao gravar tema.']);
        exit;
    }
}