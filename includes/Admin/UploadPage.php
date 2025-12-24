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

                $code = sanitize_key($_POST['audio_code'] ?? '');
                if (empty($code)) {
                    throw new Exception('Informe um código para o áudio.');
                }

                if (TranscriptRepository::exists_by_code($code)) {
                    throw new Exception('Este código já está em uso.');
                }

                require_once ABSPATH . 'wp-admin/includes/media.php';
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/image.php';

                $attachment_id = media_handle_upload('audio_file', 0);
                if (is_wp_error($attachment_id)) {
                    throw new Exception($attachment_id->get_error_message());
                }

                $file_path = get_attached_file($attachment_id);
                $public_url = wp_get_attachment_url($attachment_id);

                if (!$file_path || !file_exists($file_path)) {
                    throw new Exception('Arquivo não encontrado no servidor.');
                }

                $transcript_id = TranscriptRepository::create_transcript(
                    $attachment_id,
                    $public_url,
                    'pt-BR',
                    $code
                );

                TranscriptRepository::update_transcript($transcript_id, [
                    'status' => 'uploading'
                ]);

                $service = new TranscribeService(
                    get_option('lk_aws_access_key'),
                    get_option('lk_aws_secret_key'),
                    get_option('lk_aws_region', 'us-east-1'),
                    get_option('lk_aws_bucket')
                );

                $result = $service->transcribe($file_path);


                TranscriptRepository::update_transcript($transcript_id, [
                    'aws_job_name' => $result['jobName'] ?? null,
                    'status' => 'completed'
                ]);

                $items = $result['json']['results']['items'] ?? [];

                TranscriptRepository::delete_words($transcript_id);

                $words = [];
                $idx = 0;

                foreach ($items as $item) {
                    if ($item['type'] === 'pronunciation') {
                        $words[] = [
                            'idx' => $idx++,
                            'word' => $item['alternatives'][0]['content'],
                            'start_ms' => isset($item['start_time'])
                                ? (int) round($item['start_time'] * 1000)
                                : null,
                            'end_ms' => isset($item['end_time'])
                                ? (int) round($item['end_time'] * 1000)
                                : null,
                            'group_id' => null,
                        ];
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
                        <th>Código do áudio</th>
                        <td>
                            <input type="text" name="audio_code" required pattern="[a-zA-Z0-9_-]+">
                            <p class="description">Identificador único (sem espaços)</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Arquivo de áudio</th>
                        <td><input type="file" name="audio_file" accept="audio/*" required></td>
                    </tr>
                </table>

                <button class="button button-primary" name="lk_upload_audio">
                    Enviar e Transcrever
                </button>
            </form>

            <hr>

            <h2>Áudios enviados</h2>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Código</th>
                        <th>Áudio</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transcripts as $t): ?>
                        <tr>
                            <td><?php echo (int) $t->id; ?></td>
                            <td><code><?php echo esc_html($t->code); ?></code></td>
                            <td><a href="<?php echo esc_url($t->public_url); ?>" target="_blank">Ouvir</a></td>
                            <td><strong><?php echo esc_html($t->status); ?></strong></td>
                            <td class="lk-actions">
                                <a href="#" class="button lk-rename" data-id="<?php echo (int) $t->id; ?>"
                                    data-code="<?php echo esc_attr($t->code); ?>">
                                    Renomear
                                </a>

                                <a class="button button-primary"
                                    href="<?php echo admin_url('admin.php?page=leiturinha-karaoke-editor&transcript_id=' . $t->id); ?>">
                                    Editar texto
                                </a>

                                <a href="#" class="button button-link-delete lk-delete" data-id="<?php echo (int) $t->id; ?>">
                                    Apagar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- MODAL RENOMEAR -->
        <div id="lk-rename-modal" class="lk-modal" aria-hidden="true">
            <div class="lk-modal-backdrop"></div>
            <div class="lk-modal-content">
                <h2>Renomear áudio</h2>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('lk_rename_transcript'); ?>
                    <input type="hidden" name="action" value="lk_rename_transcript">
                    <input type="hidden" name="transcript_id" id="lk-rename-id">

                    <label>Novo código</label>
                    <input type="text" name="code" id="lk-rename-code" required>

                    <div class="lk-modal-actions">
                        <button type="button" class="button lk-cancel">Cancelar</button>
                        <button class="button button-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL APAGAR -->
        <div id="lk-delete-modal" class="lk-modal" aria-hidden="true">
            <div class="lk-modal-backdrop"></div>

            <div class="lk-modal-content">
                <h2>Apagar áudio</h2>

                <p style="margin-bottom:16px">
                    Tem certeza que deseja apagar este áudio?<br>
                    <strong>Essa ação não pode ser desfeita.</strong>
                </p>

                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('lk_delete_transcript'); ?>
                    <input type="hidden" name="action" value="lk_delete_transcript">
                    <input type="hidden" name="transcript_id" id="lk-delete-id">

                    <div class="lk-modal-actions">
                        <button type="button" class="button lk-cancel">Cancelar</button>
                        <button type="submit" class="button button-danger">
                            Apagar
                        </button>
                    </div>
                </form>
            </div>
        </div>


        <style>
            .lk-actions {
                display: flex;
                gap: 8px;
                flex-wrap: wrap
            }

            .lk-modal {
                position: fixed;
                inset: 0;
                display: none;
                z-index: 99999
            }

            .lk-modal[aria-hidden="false"] {
                display: block
            }

            .lk-modal-backdrop {
                position: absolute;
                inset: 0;
                background: rgba(0, 0, 0, .5)
            }

            .lk-modal-content {
                background: #fff;
                max-width: 420px;
                margin: 10% auto;
                padding: 24px;
                border-radius: 8px;
            }

            .lk-modal-actions {
                display: flex;
                justify-content: flex-end;
                gap: 8px
            }

            .lk-modal {
                position: fixed;
                inset: 0;
                display: none;
                z-index: 999999;
                /* 🔥 acima do admin WP */
            }

            .lk-modal[aria-hidden="false"] {
                display: block;
            }

            /* backdrop */
            .lk-modal-backdrop {
                position: absolute;
                inset: 0;
                background: rgba(0, 0, 0, 0.55);
                z-index: 1;
            }

            /* conteúdo do modal */
            .lk-modal-content {
                position: relative;
                z-index: 2;
                /* 🔥 SEMPRE acima do backdrop */
                background: #fff;
                max-width: 480px;
                margin: 10% auto;
                padding: 24px;
                border-radius: 10px;
                box-shadow: 0 15px 40px rgba(0, 0, 0, .25);
            }
        </style>

        <script>
            document.addEventListener('click', function (e) {

                /* ===== RENOMEAR ===== */
                if (e.target.classList.contains('lk-rename')) {
                    e.preventDefault();

                    document.getElementById('lk-rename-id').value =
                        e.target.dataset.id;

                    document.getElementById('lk-rename-code').value =
                        e.target.dataset.code || '';

                    document.getElementById('lk-rename-modal')
                        .setAttribute('aria-hidden', 'false');
                }

                /* ===== APAGAR ===== */
                if (e.target.classList.contains('lk-delete')) {
                    e.preventDefault();

                    document.getElementById('lk-delete-id').value =
                        e.target.dataset.id;

                    document.getElementById('lk-delete-modal')
                        .setAttribute('aria-hidden', 'false');
                }

                /* ===== FECHAR MODAIS ===== */
                if (
                    e.target.classList.contains('lk-cancel') ||
                    e.target.classList.contains('lk-modal-backdrop')
                ) {
                    document.querySelectorAll('.lk-modal')
                        .forEach(m => m.setAttribute('aria-hidden', 'true'));
                }
            });
        </script>
        <?php
    }
}
