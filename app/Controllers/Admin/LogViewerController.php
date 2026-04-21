<?php
namespace App\Controllers\Admin;

use App\Repositories\AuditLogRepository;
use App\Middlewares\RateLimitMiddleware;
use App\Services\AuditLogger;

class LogViewerController {
    public function __construct() {
        RateLimitMiddleware::check();
    }

    public function getExtendedLogs() {
        $repo = new AuditLogRepository();
        $logs = $repo->getAllLogsExtended();

        AuditLogger::log(
            $_SERVER['MASTER_ID'], 
            $_SERVER['MASTER_ROLE'], 
            'Acessou a area de logs extensos de auditoria LGPD'
        );

        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'success', 'data' => $logs]);
        exit;
    }
}