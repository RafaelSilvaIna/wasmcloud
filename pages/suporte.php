<?php
declare(strict_types=1);

/**
 * ARQUIVO: pages/suporte.php
 * DESCRIÇÃO: Página pública do sistema de suporte ao usuário do Pipocine.
 *
 * Acessível tanto para usuários autenticados quanto visitantes.
 * Inclui db.php para iniciar a sessão e obter dados de autenticação.
 */

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../components/suporte/SupportPage.php';
