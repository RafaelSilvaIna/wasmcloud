<?php

/**
 * HOOK: AccountHook (v3)
 *
 * Middleware executado antes de qualquer rota /api/v3/account/*.
 * Garante que:
 *   1. A sessão CineVEO está ativa (user_id presente).
 *   2. O cabeçalho CORS está configurado para chamadas internas.
 *   3. Requisições OPTIONS (preflight) são respondidas imediatamente.
 *
 * Uso em routes/v3/index.php:
 *   AccountHook::guard();
 */
class AccountHook
{
    /**
     * Aplica os cabeçalhos CORS padrão do projeto e responde ao preflight.
     * Deve ser chamado antes de qualquer lógica de rota.
     */
    public static function cors(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    /**
     * Verifica se o utilizador está autenticado.
     * Em caso negativo, retorna 401 e encerra a execução.
     */
    public static function guard(): void
    {
        self::cors();

        if (empty($_SESSION['user_id'])) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error'   => 'Sessão inválida ou expirada. Faça login novamente.',
            ]);
            exit;
        }
    }
}
