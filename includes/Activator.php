<?php

namespace LeiturinhaKaraoke;

/**
 * Ativador do plugin Leiturinha-Karaoke
 * Responsável por criar e atualizar as tabelas do banco
 */

if (!defined('ABSPATH')) {
    exit;
}

class Activator
{
    /**
     * Executado na ativação do plugin
     */
    public static function activate(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        /**
         * Versão do schema do banco
         * (incrementar sempre que mudar estrutura)
         */
        $db_version = '1.4.0';

        /* =====================================================
         * TABELA: lk_transcripts
         * ===================================================== */
        $table_transcripts = $wpdb->prefix . 'lk_transcripts';

        $sql_transcripts = "
            CREATE TABLE {$table_transcripts} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                attachment_id BIGINT UNSIGNED NOT NULL,
                public_url TEXT NOT NULL,
                aws_job_name VARCHAR(191) NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'pending',
                language VARCHAR(10) NOT NULL DEFAULT 'pt-BR',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY attachment_id (attachment_id),
                KEY status (status)
            ) {$charset_collate};
        ";

        /* =====================================================
         * TABELA: lk_transcript_words
         * ===================================================== */
        $table_words = $wpdb->prefix . 'lk_transcript_words';

        $sql_words = "
            CREATE TABLE {$table_words} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                transcript_id BIGINT UNSIGNED NOT NULL,
                idx INT NOT NULL,
                word VARCHAR(255) NOT NULL,
                type ENUM('word','linebreak') NOT NULL DEFAULT 'word',
                start_ms INT NULL,
                end_ms INT NULL,
                group_id BIGINT UNSIGNED NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY transcript_id (transcript_id),
                KEY idx (idx),
                KEY type (type),
                KEY group_id (group_id)
            ) {$charset_collate};
        ";

        // Criação / atualização segura (dbDelta cuida do ALTER)
        dbDelta($sql_transcripts);
        dbDelta($sql_words);

        // Salva versão do banco
        update_option('lk_db_version', $db_version);
    }
}
