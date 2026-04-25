<?php
class AuthController {
    private $authService;

    public function __construct(AuthService $authService) {
        $this->authService = $authService;
    }

    public function getStatus(): void {
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
        
        $status = $this->authService->checkRealTimeAuth();
        
        if (!$status['isAuthenticated']) {
            http_response_code(401);
        } else {
            http_response_code(200);
        }
        
        echo json_encode($status);
        exit;
    }
}