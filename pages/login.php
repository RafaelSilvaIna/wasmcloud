<?php
require_once __DIR__ . '/../database/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /home');
    exit;
}

require __DIR__ . '/login/plataforma/index.php';
