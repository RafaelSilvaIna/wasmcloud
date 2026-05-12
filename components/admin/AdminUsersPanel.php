<?php
declare(strict_types=1);

require_once __DIR__ . '/users/AdminUsersDashboard.php';

final class AdminUsersPanel
{
    public static function render(): void
    {
        AdminUsersDashboard::render();
    }
}
