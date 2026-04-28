<?php
ini_set('display_errors', 0); 
error_reporting(E_ALL);

require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../utils/v2/ResponseUtil.php';
require_once __DIR__ . '/../../hooks/v2/ApiHook.php';
require_once __DIR__ . '/../../helpers/v2/TMDBHelper.php';

// --- Classes da API de Conteúdo (Home/Trilhos) ---
require_once __DIR__ . '/../../models/v2/ContentModel.php';
require_once __DIR__ . '/../../services/v2/ContentService.php';
require_once __DIR__ . '/../../controllers/v2/ContentController.php';

// --- Classes da API de Exibição (Player) ---
require_once __DIR__ . '/../../models/v2/ExhibitionModel.php';
require_once __DIR__ . '/../../services/v2/ExhibitionService.php';
require_once __DIR__ . '/../../controllers/v2/ExhibitionController.php';

ApiHook::init();

try {
    $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Rota 1: Trilhos e Conteúdo Geral
    if (strpos($requestUri, '/api/v2/conteudo') === 0) {
        $model = new ContentModel($pdoCineveo);
        $service = new ContentService($model);
        $controller = new ContentController($service);
        $controller->handleRequest();

    // Rota 2: Obtenção de Links e Metadados do Player
    } elseif (strpos($requestUri, '/api/v2/exhibition') === 0) {
        $model = new ExhibitionModel($pdoCineveo);
        $tmdbHelper = new TMDBHelper(); // Usado para buscar metadados do ep. em tempo real
        $service = new ExhibitionService($model, $tmdbHelper);
        $controller = new ExhibitionController($service);
        $controller->getExhibitionData(); // Método do ExhibitionController

    // Rota não encontrada
    } else {
        ResponseUtil::json(['sucesso' => false, 'erro' => 'Rota nao encontrada'], 404);
    }

} catch (Throwable $e) {
    ResponseUtil::json([
        'sucesso' => false, 
        'erro' => 'Erro Interno do Servidor', 
        'detalhes' => $e->getMessage(),
        'arquivo' => basename($e->getFile()),
        'linha' => $e->getLine()
    ], 500);
}