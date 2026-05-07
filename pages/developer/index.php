<?php
/**
 * PipoCine Developer Portal - Entry Point
 * Roteamento interno para páginas do portal de desenvolvedores
 */

// Definir constante de segurança
define('PIPOCINE_DEV', true);

// Obter a ação da URL
$action = $_GET['action'] ?? 'main';

// Sanitizar ação
$action = preg_replace('/[^a-zA-Z0-9_-]/', '', $action);

// Mapeamento de páginas permitidas
$allowedPages = [
    'main' => 'main.php',
    'docs' => 'docs.php',
    'pricing' => 'pricing.php',
    'dashboard' => 'dashboard.php',
];

// Verificar se a página existe e permitir
if (isset($allowedPages[$action]) && file_exists(__DIR__ . '/' . $allowedPages[$action])) {
    require_once __DIR__ . '/' . $allowedPages[$action];
} else {
    // Página padrão
    require_once __DIR__ . '/main.php';
}
