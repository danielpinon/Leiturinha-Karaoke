<?php

namespace LeiturinhaKaraoke\Admin;

use Exception;
use LeiturinhaKaraoke\Repository\TranscriptRepository;
use LeiturinhaKaraoke\AWS\TranscribeService;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Página administrativa: Upload de Áudio
 * Plugin: Leiturinha-Karaoke
 */
class UploadPage
{
    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Você não tem permissão para acessar esta página.');
        }

        global $wpdb;

        /* =====================================================
         * PROCESSAMENTO DO FORMULÁRIO
         * ===================================================== */
        if (isset($_POST['lk_upload_audio']) && check_admin_referer('lk_upload_audio_nonce')) {

            try {

                if (empty($_FILES['audio_file']['name'])) {
                    throw new Exception('Selecione um arquivo de áudio.');
                }

                // Media Library
                require_once ABSPATH . 'wp-admin/includes/media.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/image.php';

                $attachment_id = media_handle_upload('audio_file', 0);

                if (is_wp_error($attachment_id)) {
                    throw new Exception($attachment_id->get_error_message());
                }

                $file_path  = get_attached_file($attachment_id);
                $public_url = wp_get_attachment_url($attachment_id);

                if (!$file_path || !file_exists($file_path)) {
                    throw new Exception('Arquivo não encontrado no servidor.');
                }

                /* =====================================================
                 * REGISTRA TRANSCRIÇÃO
                 * ===================================================== */
                $transcript_id = TranscriptRepository::create_transcript(
                    $attachment_id,
                    $public_url,
                    'pt-BR'
                );

                TranscriptRepository::update_transcript($transcript_id, [
                    'status' => 'uploading'
                ]);

                /* =====================================================
                 * AWS TRANSCRIBE
                 * ===================================================== */
                $service = new TranscribeService(
                    get_option('lk_aws_access_key'),
                    get_option('lk_aws_secret_key'),
                    get_option('lk_aws_region', 'us-east-1'),
                    get_option('lk_aws_bucket')
                );

                $result = $service->transcribe($file_path);

                TranscriptRepository::update_transcript($transcript_id, [
                    'aws_job_name' => $result['jobName'] ?? null,
                    'status'       => 'completed'
                ]);

                /* =====================================================
                 * PROCESSA PALAVRAS
                 * ===================================================== */
                $items = $result['json']['results']['items'] ?? [];

                // Remove palavras antigas (segurança)
                TranscriptRepository::delete_words($transcript_id);

                $words = [];
                $idx   = 0;

                foreach ($items as $item) {
                    if ($item['type'] === 'pronunciation') {
                        $words[] = [
                            'idx'      => $idx,
                            'word'     => $item['alternatives'][0]['content'],
                            'start_ms' => isset($item['start_time'])
                                ? (int) round($item['start_time'] * 1000)
                                : null,
                            'end_ms'   => isset($item['end_time'])
                                ? (int) round($item['end_time'] * 1000)
                                : null,
                            'group_id' => null,
                        ];
                        $idx++;
                    } elseif ($item['type'] === 'punctuation' && !empty($words)) {
                        $words[$idx - 1]['word'] .= $item['alternatives'][0]['content'];
                    }
                }

                TranscriptRepository::insert_words_bulk($transcript_id, $words);

                echo '<div class="notice notice-success"><p>Áudio enviado e transcrito com sucesso!</p></div>';

            } catch (Exception $e) {
                echo '<div class="notice notice-error"><p>Erro: ' . esc_html($e->getMessage()) . '</p></div>';
            }
        }

        /* =====================================================
         * LISTAGEM DE ÁUDIOS
         * ===================================================== */
        $transcripts = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}lk_transcripts ORDER BY created_at DESC"
        );
        ?>

        <div class="wrap">
            <h1>Leiturinha – Karaoke | Áudios</h1>

            <h2>Enviar novo áudio</h2>

            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('lk_upload_audio_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">Arquivo de áudio</th>
                        <td>
                            <input type="file" name="audio_file" accept="audio/*" required />
                        </td>
                    </tr>
                </table>

                <p>
                    <button type="submit" name="lk_upload_audio" class="button button-primary">
                        Enviar e Transcrever
                    </button>
                </p>
            </form>

            <hr>

            <h2>Áudios enviados</h2>

            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Áudio</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($transcripts): ?>
                        <?php foreach ($transcripts as $t): ?>
                            <tr>
                                <td><?php echo (int) $t->id; ?></td>
                                <td>
                                    <a href="<?php echo esc_url($t->public_url); ?>" target="_blank">
                                        Ouvir áudio
                                    </a>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($t->status); ?></strong>
                                </td>
                                <td>
                                    <a
                                        href="<?php echo admin_url(
                                            'admin.php?page=leiturinha-karaoke-editor&transcript_id=' . (int) $t->id
                                        ); ?>"
                                        class="button"
                                    >
                                        Editar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">Nenhum áudio enviado ainda.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
