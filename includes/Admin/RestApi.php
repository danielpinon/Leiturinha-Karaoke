<?php

namespace LeiturinhaKaraoke\Admin;

use LeiturinhaKaraoke\Repository\TranscriptRepository;
use WP_Error;
use WP_REST_Request;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * REST API do Editor – Leiturinha-Karaoke
 */
class RestApi
{
    /**
     * Inicializa as rotas
     */
    public static function init(): void
    {
        add_action('rest_api_init', [self::class, 'registerRoutes']);
    }

    /**
     * Registra as rotas REST
     */
    public static function registerRoutes(): void
    {
        register_rest_route('leiturinha-karaoke/v1', '/transcript/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [self::class, 'getTranscript'],
            'permission_callback' => [self::class, 'permissionCheck'],
        ]);

        register_rest_route('leiturinha-karaoke/v1', '/transcript/(?P<id>\d+)/words', [
            'methods' => 'PUT',
            'callback' => [self::class, 'updateWords'],
            'permission_callback' => [self::class, 'permissionCheck'],
        ]);

        register_rest_route('leiturinha-karaoke/v1', '/transcript/(?P<id>\d+)/merge', [
            'methods' => 'POST',
            'callback' => [self::class, 'mergeWords'],
            'permission_callback' => [self::class, 'permissionCheck'],
        ]);

        register_rest_route('leiturinha-karaoke/v1', '/transcript/(?P<id>\d+)/unmerge', [
            'methods' => 'POST',
            'callback' => [self::class, 'unmergeWords'],
            'permission_callback' => [self::class, 'permissionCheck'],
        ]);

        register_rest_route('leiturinha-karaoke/v1', '/transcript/(?P<id>\d+)/rebuild', [
            'methods' => 'POST',
            'callback' => [self::class, 'rebuildTranscript'],
            'permission_callback' => [self::class, 'permissionCheck'],
        ]);

    }

    /* =====================================================
     * PERMISSÃO
     * ===================================================== */
    public static function permissionCheck(): bool
    {
        return current_user_can('manage_options');
    }

    /* =====================================================
     * ENDPOINTS
     * ===================================================== */

    /**
     * Retorna transcript + palavras
     */
    public static function getTranscript(WP_REST_Request $request)
    {
        $transcript_id = (int) $request['id'];

        $transcript = TranscriptRepository::getById($transcript_id);

        if (!$transcript) {
            return new WP_Error(
                'not_found',
                'Transcrição não encontrada',
                ['status' => 404]
            );
        }

        return [
            'transcript' => $transcript,
            'words' => TranscriptRepository::getWords($transcript_id),
        ];
    }

    /**
     * Atualiza palavras (lote)
     */
    public static function updateWords(WP_REST_Request $request)
    {
        $params = $request->get_json_params();

        if (empty($params['words']) || !is_array($params['words'])) {
            return new WP_Error(
                'invalid_data',
                'Nenhuma palavra enviada',
                ['status' => 400]
            );
        }

        foreach ($params['words'] as $word) {
            if (empty($word['id'])) {
                continue;
            }

            TranscriptRepository::updateWord((int) $word['id'], [
                'word' => sanitize_text_field($word['word'] ?? ''),
                'start_ms' => isset($word['start_ms']) && is_numeric($word['start_ms'])
                    ? (int) $word['start_ms']
                    : null,
                'end_ms' => isset($word['end_ms']) && is_numeric($word['end_ms'])
                    ? (int) $word['end_ms']
                    : null,
            ]);
        }

        return ['status' => 'success'];
    }

    /**
     * Une palavras
     */
    public static function mergeWords(WP_REST_Request $request)
    {
        $params = $request->get_json_params();

        if (empty($params['word_ids']) || !is_array($params['word_ids'])) {
            return new WP_Error(
                'invalid_data',
                'IDs inválidos',
                ['status' => 400]
            );
        }

        $group_id = TranscriptRepository::mergeWords(
            array_map('intval', $params['word_ids'])
        );

        return [
            'status' => 'success',
            'group_id' => $group_id
        ];
    }

    /**
     * Desfaz união
     */
    public static function unmergeWords(WP_REST_Request $request)
    {
        $params = $request->get_json_params();

        if (empty($params['group_id'])) {
            return new WP_Error(
                'invalid_data',
                'Grupo inválido',
                ['status' => 400]
            );
        }

        TranscriptRepository::unmergeGroup((int) $params['group_id']);

        return ['status' => 'success'];
    }

    public static function rebuildTranscript(WP_REST_Request $request)
    {
        $id = (int) $request['id'];
        $params = $request->get_json_params();

        if (empty($params['text'])) {
            return new WP_Error('invalid', 'Texto vazio', ['status' => 400]);
        }

        // ⚠️ NÃO sanitize texto multilinha com strip/sanitize_text_field
        $text = wp_unslash($params['text']);
        $text = trim($text);

        // 🔥 DELEGA TOTALMENTE PARA O REPOSITORY
        TranscriptRepository::rebuild_from_text($id, $text);

        return [
            'status' => 'rebuilt',
            'transcript_id' => $id
        ];
    }


}
