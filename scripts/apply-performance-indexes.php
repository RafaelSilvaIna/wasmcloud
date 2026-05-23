<?php

declare(strict_types=1);

define('PIPOCINE_DB_CONFIG_ONLY', true);
require_once __DIR__ . '/../database/db.php';

/**
 * Idempotent performance indexes for the busiest API paths.
 */

$cine = createPDO(DB_CINE['name'], DB_CINE['user'], DB_CINE['pass']);
$pipo = createPDO(DB_PIPO['name'], DB_PIPO['user_primary'], DB_PIPO['pass'])
    ?? createPDO(DB_PIPO['name'], DB_PIPO['user_fallback'], DB_PIPO['pass']);

$jobs = [
    [$cine, DB_CINE['name'], 'conteudo', 'idx_conteudo_tmdb_tipo', 'CREATE INDEX idx_conteudo_tmdb_tipo ON conteudo (id_tmdb, tipo)'],
    [$cine, DB_CINE['name'], 'conteudo', 'idx_conteudo_tipo_lancamento', 'CREATE INDEX idx_conteudo_tipo_lancamento ON conteudo (tipo, data_lancamento)'],
    [$cine, DB_CINE['name'], 'conteudo', 'idx_conteudo_tipo_nota', 'CREATE INDEX idx_conteudo_tipo_nota ON conteudo (tipo, nota)'],
    [$cine, DB_CINE['name'], 'conteudo', 'idx_conteudo_tipo_id', 'CREATE INDEX idx_conteudo_tipo_id ON conteudo (tipo, id)'],
    [$cine, DB_CINE['name'], 'links', 'idx_links_tmdb_episode', 'CREATE INDEX idx_links_tmdb_episode ON links (id_tmdb, temporada, episodio)'],
    [$cine, DB_CINE['name'], 'links_legendados', 'idx_links_leg_tmdb_episode', 'CREATE INDEX idx_links_leg_tmdb_episode ON links_legendados (id_tmdb, temporada, episodio)'],

    [$pipo, DB_PIPO['name'], 'pip_comments', 'idx_comments_content_parent_created', 'CREATE INDEX idx_comments_content_parent_created ON pip_comments (content_id, content_type, parent_id, is_deleted, created_at)'],
    [$pipo, DB_PIPO['name'], 'pip_comments', 'idx_comments_parent_deleted_created', 'CREATE INDEX idx_comments_parent_deleted_created ON pip_comments (parent_id, is_deleted, created_at)'],
    [$pipo, DB_PIPO['name'], 'pip_comment_likes', 'idx_comment_likes_comment_profile', 'CREATE INDEX idx_comment_likes_comment_profile ON pip_comment_likes (comment_id, profile_id)'],
    [$pipo, DB_PIPO['name'], 'pip_user_library', 'idx_library_profile_content', 'CREATE INDEX idx_library_profile_content ON pip_user_library (profile_id, content_id, content_type)'],
    [$pipo, DB_PIPO['name'], 'pip_user_library', 'idx_library_profile_saved', 'CREATE INDEX idx_library_profile_saved ON pip_user_library (profile_id, is_saved, saved_at)'],
    [$pipo, DB_PIPO['name'], 'pip_user_library', 'idx_library_profile_liked', 'CREATE INDEX idx_library_profile_liked ON pip_user_library (profile_id, is_liked, liked_at)'],
    [$pipo, DB_PIPO['name'], 'pip_watch_history', 'idx_watch_history_profile_content', 'CREATE INDEX idx_watch_history_profile_content ON pip_watch_history (profile_id, content_id, content_type, watched_at)'],
    [$pipo, DB_PIPO['name'], 'watch_progress', 'idx_watch_progress_user_content', 'CREATE INDEX idx_watch_progress_user_content ON watch_progress (user_id, content_id, content_type, season, episode)'],
    [$pipo, DB_PIPO['name'], 'watch_progress', 'idx_watch_progress_user_updated', 'CREATE INDEX idx_watch_progress_user_updated ON watch_progress (user_id, updated_at)'],
    [$pipo, DB_PIPO['name'], 'pip_profile_watched_episodes', 'idx_watched_profile_episode', 'CREATE INDEX idx_watched_profile_episode ON pip_profile_watched_episodes (profile_id, serie_id, season, episode)'],
    [$pipo, DB_PIPO['name'], 'pipocine_request_metrics', 'idx_metrics_api_created', 'CREATE INDEX idx_metrics_api_created ON pipocine_request_metrics (is_api, created_at)'],
    [$pipo, DB_PIPO['name'], 'pipocine_request_metrics', 'idx_metrics_route_created', 'CREATE INDEX idx_metrics_route_created ON pipocine_request_metrics (route_group, created_at)'],
    [$pipo, DB_PIPO['name'], 'pipocine_request_metrics', 'idx_metrics_path_created', 'CREATE INDEX idx_metrics_path_created ON pipocine_request_metrics (path, created_at)'],
];

foreach ($jobs as [$pdo, $schema, $table, $index, $sql]) {
    if (!$pdo instanceof PDO) {
        echo "SKIP {$schema}.{$table}.{$index}: database unavailable" . PHP_EOL;
        continue;
    }

    try {
        if (!tableExists($pdo, $schema, $table)) {
            echo "SKIP {$schema}.{$table}.{$index}: table missing" . PHP_EOL;
            continue;
        }

        if (indexExists($pdo, $schema, $table, $index)) {
            echo "OK {$schema}.{$table}.{$index}: already exists" . PHP_EOL;
            continue;
        }

        $pdo->exec($sql);
        echo "CREATED {$schema}.{$table}.{$index}" . PHP_EOL;
    } catch (Throwable $e) {
        echo "FAIL {$schema}.{$table}.{$index}: " . $e->getMessage() . PHP_EOL;
    }
}

function tableExists(PDO $pdo, string $schema, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1 FROM information_schema.tables WHERE table_schema = ? AND table_name = ? LIMIT 1'
    );
    $stmt->execute([$schema, $table]);
    return (bool) $stmt->fetchColumn();
}

function indexExists(PDO $pdo, string $schema, string $table, string $index): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1'
    );
    $stmt->execute([$schema, $table, $index]);
    return (bool) $stmt->fetchColumn();
}
