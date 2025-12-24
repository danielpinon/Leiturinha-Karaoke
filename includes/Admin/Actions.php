<?php

namespace LeiturinhaKaraoke\Admin;

use LeiturinhaKaraoke\Repository\TranscriptRepository;

if (!defined("ABSPATH")) {
    exit();
}

class Actions
{
    public static function init(): void
    {
        add_action("admin_post_lk_rename_transcript", function () {
            if (!current_user_can("manage_options")) {
                wp_die("Sem permissão.");
            }

            check_admin_referer("lk_rename_transcript");

            $id = (int) ($_POST["transcript_id"] ?? 0);

            $code = sanitize_key($_POST["code"] ?? "");

            if (!$id || !$code) {
                wp_die("Dados inválidos.");
            }

            if (TranscriptRepository::exists_by_code($code)) {
                wp_die("Este código já está em uso.");
            }

            TranscriptRepository::update_transcript($id, ["code" => $code]);

            wp_redirect(wp_get_referer());

            exit();
        });

        add_action("admin_post_lk_delete_transcript", function () {
            if (!current_user_can("manage_options")) {
                wp_die("Sem permissão.");
            }

            check_admin_referer("lk_delete_transcript");

            $id = (int) ($_POST["transcript_id"] ?? 0);

            if (!$id) {
                wp_die("ID inválido.");
            }

            global $wpdb;

            $wpdb->delete($wpdb->prefix . "lk_transcript_words", [
                "transcript_id" => $id,
            ]);

            $wpdb->delete($wpdb->prefix . "lk_transcripts", ["id" => $id]);

            wp_redirect(wp_get_referer());

            exit();
        });
    }
}