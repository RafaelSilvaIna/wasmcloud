-- PipoCine V2 recommendations - optional performance indexes.
-- Execute this file once in MySQL/phpMyAdmin. It is safe to run again.

DELIMITER $$

DROP PROCEDURE IF EXISTS pip_add_index_if_missing $$
CREATE PROCEDURE pip_add_index_if_missing(
    IN p_schema VARCHAR(64),
    IN p_table VARCHAR(64),
    IN p_index VARCHAR(64),
    IN p_columns TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.STATISTICS
        WHERE TABLE_SCHEMA = p_schema
          AND TABLE_NAME = p_table
          AND INDEX_NAME = p_index
        LIMIT 1
    ) THEN
        SET @ddl = CONCAT(
            'ALTER TABLE `', REPLACE(p_schema, '`', '``'), '`.`', REPLACE(p_table, '`', '``'),
            '` ADD INDEX `', REPLACE(p_index, '`', '``'), '` (', p_columns, ')'
        );
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END $$

DELIMITER ;

CALL pip_add_index_if_missing('pipcine', 'pip_user_library', 'idx_pul_profile_engagement', '`profile_id`, `is_liked`, `is_saved`, `liked_at`, `saved_at`');
CALL pip_add_index_if_missing('pipcine', 'pip_user_library', 'idx_pul_profile_content', '`profile_id`, `content_id`, `content_type`');
CALL pip_add_index_if_missing('pipcine', 'pip_watch_history', 'idx_pwh_profile_content_recent', '`profile_id`, `content_id`, `content_type`, `watched_at`');
CALL pip_add_index_if_missing('pipcine', 'watch_progress', 'idx_wp_user_content_recent', '`user_id`, `content_id`, `content_type`, `updated_at`');
CALL pip_add_index_if_missing('pipcine', 'pip_profile_watched_episodes', 'idx_ppwe_profile_serie_recent', '`profile_id`, `serie_id`, `watched_at`');
CALL pip_add_index_if_missing('cineveo', 'conteudo', 'idx_conteudo_tmdb_tipo', '`id_tmdb`, `tipo`');
CALL pip_add_index_if_missing('cineveo', 'conteudo', 'idx_conteudo_tipo_nota_data', '`tipo`, `nota`, `data_lancamento`');

DROP PROCEDURE IF EXISTS pip_add_index_if_missing;
