<?php
/**
 * TrendingController
 * Endpoint: GET /api/v2/trending
 *
 * Parâmetros opcionais:
 *   ?limite=12        Quantidade de resultados (1–30, padrão 12)
 *   ?tipo=filme       Filtrar por tipo: 'filme' | 'serie' (padrão: ambos)
 *
 * Resposta:
 * {
 *   "sucesso": true,
 *   "total": 10,
 *   "perfil_kids": false,
 *   "tipo": null,
 *   "resultados": [ { ...item } ]
 * }
 */
class TrendingController {
    private $service;

    public function __construct($service) {
        $this->service = $service;
    }

    public function handleRequest(): void {
        // Parâmetro: limite
        $limit = isset($_GET['limite']) ? (int)$_GET['limite'] : 12;
        $limit = max(1, min(30, $limit));

        // Parâmetro: tipo (filme | serie | null = ambos)
        $tipo = isset($_GET['tipo']) ? strtolower(trim($_GET['tipo'])) : null;
        if (!in_array($tipo, ['filme', 'serie'], true)) {
            $tipo = null;
        }

        // Perfil kids via sessão
        $isKids = isset($_SESSION['profile_is_kids']) ? (bool)$_SESSION['profile_is_kids'] : false;

        $data = $this->service->fetchTrending($limit, $isKids, $tipo);

        ResponseUtil::json([
            'sucesso'      => true,
            'total'        => count($data),
            'perfil_kids'  => $isKids,
            'tipo'         => $tipo,
            'resultados'   => $data
        ]);
    }
}
