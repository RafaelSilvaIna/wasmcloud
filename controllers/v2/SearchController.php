<?php
declare(strict_types=1);

namespace Controllers\V2;

use Services\V2\SearchService;

/**
 * SearchController — lida com as requisicoes da API de busca.
 *
 * Endpoints:
 *   GET /api/v2/busca?q=&tipo=&genero=&ano=&ordem=&pagina=
 *   GET /api/v2/busca/generos
 */
class SearchController {
    private SearchService $service;

    public function __construct(SearchService $service) {
        $this->service = $service;
    }

    public function handle(): void {
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Exige autenticacao — sessao PHP
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['user_id'])) {
            $this->json(['sucesso' => false, 'erro' => 'Nao autorizado.'], 401);
        }

        // Sub-rota: /api/v2/busca/generos
        if (str_ends_with($requestUri, '/generos')) {
            $this->json(['sucesso' => true, 'dados' => $this->service->getGenres()]);
        }

        // Rota principal: /api/v2/busca
        $q       = trim($_GET['q']      ?? '');
        $tipo    = $_GET['tipo']         ?? null;
        $genero  = $_GET['genero']       ?? null;
        $ano     = isset($_GET['ano'])   ? (int) $_GET['ano']    : null;
        $ordem   = $_GET['ordem']        ?? 'relevancia';
        $pagina  = max(1, (int) ($_GET['pagina'] ?? 1));

        if ($q === '') {
            $this->json(['sucesso' => false, 'erro' => 'Parametro q e obrigatorio.'], 400);
        }

        // Sanitiza tipo
        if (!in_array($tipo, ['filme', 'serie', null], true)) $tipo = null;

        // Sanitiza ordem
        if (!in_array($ordem, ['relevancia', 'nota', 'recente', 'antigo'], true)) {
            $ordem = 'relevancia';
        }

        $result = $this->service->search($q, $tipo, $genero, $ano, $ordem, $pagina);
        $this->json($result);
    }

    // ─── Utilitario ──────────────────────────────────────────────────────────

    private function json(array $data, int $status = 200): never {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
