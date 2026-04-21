<?php
namespace App\Controllers\Public;

use App\Services\ThemeService;

class ThemeDeliveryController {
    public function getThemeColors() {
        $service = new ThemeService();
        $colors = $service->getActiveTheme();

        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'success', 'theme' => $colors]);
        exit;
    }
}