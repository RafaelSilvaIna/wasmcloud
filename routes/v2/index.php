<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../utils/v2/ResponseUtil.php';
require_once __DIR__ . '/../../hooks/v2/ApiHook.php';
require_once __DIR__ . '/../../helpers/v2/TMDBHelper.php';
require_once __DIR__ . '/../../models/v2/ContentModel.php';
require_once __DIR__ . '/../../services/v2/ContentService.php';
require_once __DIR__ . '/../../controllers/v2/ContentController.php';

ApiHook::init();

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (strpos($requestUri, '/api/v2/conteudo') === 0) {
    $model = new ContentModel($pdo);
    $service = new ContentService($model);
    $controller = new ContentController($service);
    $controller->handleRequest();
} else {
    ResponseUtil::json(['sucesso' => false, 'erro' => 'Rota nao encontrada'], 404);
}