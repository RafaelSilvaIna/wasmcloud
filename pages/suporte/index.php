<?php
declare(strict_types=1);

require_once __DIR__ . '/../../database/db.php';

session_start();

// Parse ?view= param; default to home
$view = $_GET['view'] ?? 'home';
$allowed = ['home', 'novo', 'chat'];
if (!in_array($view, $allowed, true)) {
    $view = 'home';
}

require_once __DIR__ . "/{$view}.php";
