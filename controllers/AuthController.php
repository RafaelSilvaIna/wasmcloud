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

    /**
     * Processa login do usuário
     */
    public function login(): void {
        header('Content-Type: application/json');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $input['password'] ?? '';
        
        if (!$email || !$password) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'E-mail e senha são obrigatórios.']);
            exit;
        }
        
        $result = $this->authService->authenticate($email, $password);
        
        if ($result['success']) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => $result['message']]);
        }
        exit;
    }

    /**
     * Verifica se há tentativa de acesso com sessão duplicada
     */
    public function checkSessionConflict(): void {
        // Se não estiver autenticado ou não houver perfil selecionado, não há conflito
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['profile_id'])) {
            return;
        }

        $profileId = (int)$_SESSION['profile_id'];
        
        // Verifica se há sessão ativa em outro dispositivo para este perfil
        if ($this->authService->hasActiveSessionElsewhere($profileId)) {
            // Obtém informações do dispositivo ativo
            $activeDeviceInfo = $this->authService->getActiveDeviceInfo($profileId);
            
            // Renderiza o modal
            $this->showSessionConflictModal($activeDeviceInfo);
            exit;
        }
    }

    /**
     * Exibe o modal de conflito de sessão
     */
    private function showSessionConflictModal(array $deviceInfo): void {
        // Inclui o componente do modal
        require_once __DIR__ . '/../components/SessionModal.php';
        
        // Configura headers para evitar cache
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        
        // Renderiza uma página básica com o modal
        echo '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil em Uso - PipoCine</title>
    <link rel="stylesheet" href="/assets/css/session-modal.css">
</head>
<body style="margin: 0; padding: 0; background: #000;">';
        
        // Renderiza o modal
        SessionModal::render($deviceInfo);
        SessionModal::renderScript();
        
        echo '</body></html>';
    }

    /**
     * Ativa um perfil específico (usado na página de seleção de perfil)
     */
    public function activateProfile(): void {
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            exit;
        }
        
        $profileId = filter_input(INPUT_POST, 'profile_id', FILTER_VALIDATE_INT);
        if (!$profileId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID de perfil inválido']);
            exit;
        }
        
        $userId = (int)$_SESSION['user_id'];
        
        // Tenta autenticar o perfil
        $result = $this->authService->authenticateProfile($profileId, $userId);
        
        if ($result['success']) {
            echo json_encode(['success' => true]);
        } else {
            if (isset($result['code']) && $result['code'] === 'SESSION_ACTIVE') {
                http_response_code(409); // Conflict
                echo json_encode([
                    'success' => false, 
                    'message' => $result['message'],
                    'code' => 'SESSION_ACTIVE',
                    'deviceInfo' => $this->authService->getActiveDeviceInfo($profileId)
                ]);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $result['message']]);
            }
        }
        exit;
    }
}