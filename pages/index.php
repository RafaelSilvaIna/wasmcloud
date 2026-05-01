<?php
/**
 * ARQUIVO: pages/index.php
 * DESCRIÇÃO: Roteador principal das páginas do frontend do Pipocine.
 */

require_once __DIR__ . '/../routes/index.php';

$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// ROTA: Home / Raiz
if ($requestUri === '/' || $requestUri === '/home') {
    require_once __DIR__ . '/home.php';
    exit;
}

// ROTA: Login
if ($requestUri === '/login') {
    require_once __DIR__ . '/login.php';
    exit;
}

// ROTA: Seleção de Perfil
if ($requestUri === '/select-profile') {
    require_once __DIR__ . '/select-profile.php';
    exit;
}

// ROTA: Gerenciamento de Perfis
if ($requestUri === '/manage-profiles') {
    require_once __DIR__ . '/manage-profiles.php';
    exit;
}

// NOVA ROTA: Página de Exibição (Player e Detalhes)
// Esta rota permite que o link gerado pelo ContentCard (/view?id=...) funcione corretamente.
if ($requestUri === '/view') {
    require_once __DIR__ . '/view.php';
    exit;
}

// ROTA: Página de Informações /info=<tmdb_id>
if (preg_match('/^\/info=(\d+)$/', $requestUri, $matches)) {
    $_GET['tmdb_id'] = (int) $matches[1];
    require_once __DIR__ . '/info.php';
    exit;
}

// ROTA: Página de Informações sem ID (fallback)
if ($requestUri === '/info') {
    require_once __DIR__ . '/info.php';
    exit;
}

// FALLBACK: Página não encontrada
http_response_code(404);
echo "<div style='background:#0a0a0a; color:white; height:100vh; display:flex; align-items:center; justify-content:center; font-family:sans-serif;'>";
echo "  <div style='text-align:center;'>";
echo "      <h1 style='font-size:4rem; margin:0;'>404</h1>";
echo "      <p style='color:#888;'>Oops! A página que você procura não existe.</p>";
echo "      <a href='/home' style='color:#3498db; text-decoration:none; font-weight:bold;'>Voltar para a Home</a>";
echo "  </div>";
echo "</div>";
exit;
