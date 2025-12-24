<?php
/**
 * Campos ACF – Página Leitura Guiada Item
 * Plugin: Leiturinha-Karaoke
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ============================================================
 * REGISTRO DO GRUPO DE CAMPOS (ACF)
 * ============================================================
 */
add_action('acf/init', function () {

    // Garante que o ACF está ativo
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key'   => 'group_leitura_guiada_item',
        'title' => 'Página – Leitura Guiada Item',

        'fields' => [

            // ------------------------------------------------
            // ABA: LEITURINHA – KARAOKE (NOVO)
            // ------------------------------------------------
            [
                'key'       => 'tab_leiturinha_karaoke_novo',
                'label'     => 'Leiturinha – Karaoke (Novo)',
                'type'      => 'tab',
                'placement' => 'top',
            ],

            // ------------------------------------------------
            // CÓDIGO DO ÁUDIO (DIGITÁVEL / LINKAGEM)
            // ------------------------------------------------
            [
                'key'           => 'field_karaoke_audio_code',
                'label'         => 'Código do Áudio (Karaoke)',
                'name'          => 'karaoke_audio_code',
                'type'          => 'text',
                'required'      => 1,
                'instructions' => 'Informe o código do áudio que será utilizado pelo Karaoke. Este campo apenas vincula a página ao plugin.',
                'placeholder'  => 'Ex: LT-KAR-001',
            ],

            // ------------------------------------------------
            // AWS JOB NAME (INTERNO / OCULTO – USO DO PLUGIN)
            // ------------------------------------------------
            [
                'key'   => 'field_karaoke_aws_job_name',
                'label' => 'AWS Job Name (Karaoke)',
                'name'  => 'karaoke_aws_job_name',
                'type'  => 'text',
                'wrapper' => [
                    'class' => 'hidden',
                ],
            ],
        ],

        // ------------------------------------------------
        // LOCALIZAÇÃO: APENAS PARA O TEMPLATE CORRETO
        // ------------------------------------------------
        'location' => [
            [
                [
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'page',
                ],
                [
                    'param'    => 'page_template',
                    'operator' => '==',
                    'value'    => 'page-leitura-guiada-item.php',
                ],
            ],
        ],
    ]);
});
