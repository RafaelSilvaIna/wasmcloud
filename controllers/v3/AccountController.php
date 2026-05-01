<?php

require_once __DIR__ . '/../../services/v3/AccountService.php';

/**
 * CONTROLLER: AccountController (v3)
 *
 * Recebe requisições HTTP, valida autenticação, delega ao AccountService
 * e devolve respostas JSON padronizadas.
 */
class AccountController
{
    private AccountService $service;

    public function __construct(AccountService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /api/v3/account/me
     *
     * Retorna os dados da conta CineVEO + perfis PipoCine do utilizador autenticado.
     *
     * Resposta 200:
     * {
     *   "success": true,
     *   "data": {
     *     "account": { id, full_name, username, email, profile_pic_url, plan_type,
     *                  plan_label, plan_active, plan_expiration, role },
     *     "profiles": [ { id, profile_name, username, profile_image,
     *                     is_kids, is_watching, last_active_at }, ... ]
     *   }
     * }
     *
     * Respostas de erro:
     *   401 — utilizador não autenticado
     *   404 — utilizador não encontrado no banco CineVEO
     *   405 — método HTTP não permitido
     */
    public function me(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Apenas GET
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido.']);
            return;
        }

        // Sessão obrigatória
        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Não autenticado.']);
            return;
        }

        $userId  = (int) $_SESSION['user_id'];
        $summary = $this->service->getAccountSummary($userId);

        if (!$summary) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Conta não encontrada.']);
            return;
        }

        http_response_code(200);
        echo json_encode(['success' => true, 'data' => $summary]);
    }
}
