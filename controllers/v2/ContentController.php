<?php
class ContentController {
    private $service;

    public function __construct($service) {
        $this->service = $service;
    }

    public function handleRequest() {
        $category = $_GET['categoria'] ?? 'lancamentos';
        $limit = isset($_GET['limite']) ? (int)$_GET['limite'] : 20;

        if ($limit > 100) $limit = 100;
        if ($limit < 1) $limit = 20;

        // Detecta o tipo do perfil ativo na sessão
        $isKids = isset($_SESSION['profile_is_kids']) ? (bool)$_SESSION['profile_is_kids'] : false;

        $data = $this->service->fetchCategory($category, $limit, $isKids);

        ResponseUtil::json([
            'sucesso'    => true,
            'categoria'  => $category,
            'limite'     => $limit,
            'perfil_kids' => $isKids,
            'resultados' => $data
        ]);
    }
}