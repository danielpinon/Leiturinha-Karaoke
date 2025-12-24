<?php

namespace LeiturinhaKaraoke\Repository;

/**
 * Repository de Transcrições e Palavras
 * Plugin: Leiturinha-Karaoke
 */

if (!defined('ABSPATH')) {
    exit;
}

class TranscriptRepository
{
    /* =====================================================
     * TABELAS
     * ===================================================== */

    private static function transcripts_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'lk_transcripts';
    }

    private static function words_table()
    {
        global $wpdb;
        return $wpdb->prefix . 'lk_transcript_words';
    }

    /* =====================================================
     * TRANSCRIPTS
     * ===================================================== */

    /**
     * Cria uma transcrição.
     * ✅ Agora suporta "code" (opcional) sem quebrar chamadas antigas.
     */
    public static function create_transcript($attachment_id, $public_url, $language = 'pt-BR', $code = null)
    {
        global $wpdb;

        $data = [
            'attachment_id' => (int) $attachment_id,
            'public_url'    => esc_url_raw($public_url),
            'language'      => sanitize_text_field($language),
            'status'        => 'pending',
        ];

        // code (opcional)
        if (!empty($code)) {
            // sanitize_key é ótimo pra slug/código: só [a-z0-9_-]
            $data['code'] = sanitize_key($code);
        }

        $wpdb->insert(
            self::transcripts_table(),
            $data,
            !empty($code)
                ? ['%d', '%s', '%s', '%s', '%s']
                : ['%d', '%s', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    public static function get_by_id($transcript_id)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::transcripts_table() . " WHERE id = %d",
                (int) $transcript_id
            )
        );
    }

    /**
     * Busca transcript pelo code (novo).
     */
    public static function get_by_code(string $code)
    {
        global $wpdb;

        $code = sanitize_key($code);

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::transcripts_table() . " WHERE code = %s",
                $code
            )
        );
    }

    /**
     * Verifica se já existe transcript com esse code (novo).
     */
    public static function exists_by_code(string $code): bool
    {
        global $wpdb;

        $code = sanitize_key($code);

        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM " . self::transcripts_table() . " WHERE code = %s",
                $code
            )
        );

        return $count > 0;
    }

    public static function update_transcript($transcript_id, array $data)
    {
        global $wpdb;

        // sanitizações básicas (sem ser agressivo)
        if (isset($data['public_url'])) {
            $data['public_url'] = esc_url_raw($data['public_url']);
        }
        if (isset($data['language'])) {
            $data['language'] = sanitize_text_field($data['language']);
        }
        if (isset($data['status'])) {
            $data['status'] = sanitize_text_field($data['status']);
        }
        if (isset($data['code'])) {
            $data['code'] = sanitize_key($data['code']);
        }

        return $wpdb->update(
            self::transcripts_table(),
            $data,
            ['id' => (int) $transcript_id]
        );
    }

    /* =====================================================
     * WORDS
     * ===================================================== */

    /**
     * Remove todas as palavras de uma transcrição
     */
    public static function delete_words($transcript_id)
    {
        global $wpdb;

        return $wpdb->delete(
            self::words_table(),
            ['transcript_id' => (int) $transcript_id],
            ['%d']
        );
    }

    /**
     * Insere palavras em lote (word / linebreak)
     * ✅ Corrigido: linebreak NÃO some (sanitize_text_field removia "\n")
     */
    public static function insert_words_bulk(int $transcript_id, array $words): void
    {
        global $wpdb;

        foreach ($words as $word) {

            $type = $word['type'] ?? 'word';

            // ✅ linebreak precisa preservar \n (sanitize_text_field quebra isso)
            $wordValue = ($type === 'linebreak')
                ? "\n"
                : sanitize_text_field($word['word'] ?? '');

            $wpdb->insert(
                self::words_table(),
                [
                    'transcript_id' => (int) $transcript_id,
                    'idx'           => (int) ($word['idx'] ?? 0),
                    'word'          => $wordValue,
                    'type'          => $type,

                    'start_ms'      => array_key_exists('start_ms', $word) ? (is_null($word['start_ms']) ? null : (int) $word['start_ms']) : null,
                    'end_ms'        => array_key_exists('end_ms', $word) ? (is_null($word['end_ms']) ? null : (int) $word['end_ms']) : null,
                    'group_id'      => array_key_exists('group_id', $word) ? (is_null($word['group_id']) ? null : (int) $word['group_id']) : null,

                    /* ===== ESTILOS ===== */
                    'font_family'    => $word['font_family'] ?? null,
                    'font_size'      => isset($word['font_size']) ? (int) $word['font_size'] : null,
                    'font_weight'    => $word['font_weight'] ?? null,
                    'font_style'     => $word['font_style'] ?? null,
                    'underline'      => !empty($word['underline']) ? 1 : 0,
                    'color'          => $word['color'] ?? null,
                    'background'     => $word['background'] ?? null,
                    'letter_spacing' => $word['letter_spacing'] ?? null,
                    'line_height'    => $word['line_height'] ?? null,
                ],
                [
                    '%d', // transcript_id
                    '%d', // idx
                    '%s', // word
                    '%s', // type

                    '%d', // start_ms (nullable)
                    '%d', // end_ms (nullable)
                    '%d', // group_id (nullable)

                    '%s', // font_family
                    '%d', // font_size
                    '%s', // font_weight
                    '%s', // font_style
                    '%d', // underline
                    '%s', // color
                    '%s', // background
                    '%s', // letter_spacing
                    '%s', // line_height
                ]
            );
        }
    }

    /**
     * Retorna todas as palavras da transcrição
     */
    public static function get_words($transcript_id)
    {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM " . self::words_table() . "
                 WHERE transcript_id = %d
                 ORDER BY idx ASC",
                (int) $transcript_id
            )
        );
    }

    /**
     * Atualiza uma palavra específica
     */
    public static function updateWord(int $word_id, array $data): bool
    {
        global $wpdb;

        // sanitizações seguras
        if (isset($data['word'])) {
            $data['word'] = sanitize_text_field($data['word']);
        }
        if (isset($data['type'])) {
            $data['type'] = sanitize_text_field($data['type']);
        }

        return (bool) $wpdb->update(
            self::words_table(),
            $data,
            ['id' => (int) $word_id]
        );
    }

    /* =====================================================
     * MERGE / UNMERGE
     * ===================================================== */

    public static function merge_words(array $word_ids)
    {
        global $wpdb;

        if (count($word_ids) < 2) {
            return false;
        }

        sort($word_ids);
        $group_id = (int) $word_ids[0];

        foreach ($word_ids as $word_id) {
            $wpdb->update(
                self::words_table(),
                ['group_id' => $group_id],
                ['id' => (int) $word_id]
            );
        }

        return $group_id;
    }

    public static function unmerge_group($group_id)
    {
        global $wpdb;

        return $wpdb->update(
            self::words_table(),
            ['group_id' => null],
            ['group_id' => (int) $group_id]
        );
    }

    /* =====================================================
     * UTILITÁRIOS
     * ===================================================== */

    /**
     * Retorna palavras agrupadas (karaoke)
     * Ignora linebreak automaticamente
     */
    public static function get_words_grouped($transcript_id)
    {
        $words = self::get_words($transcript_id);
        $grouped = [];

        foreach ($words as $word) {

            if ($word->type === 'linebreak') {
                continue;
            }

            $key = $word->group_id ?: $word->id;

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'text'     => $word->word,
                    'start_ms' => $word->start_ms,
                    'end_ms'   => $word->end_ms,
                ];
            } else {
                $grouped[$key]['text'] .= ' ' . $word->word;
                $grouped[$key]['end_ms'] = $word->end_ms;
            }
        }

        return array_values($grouped);
    }

    /* =====================================================
     * REBUILD (TEXTO → TOKENS)
     * ===================================================== */

    /**
     * Tokeniza respeitando pontuação:
     * - separa palavras e símbolos
     * - anexa pontuação ao token anterior (.,!? etc), estilo Transcribe
     */
    private static function tokenize_line(string $line): array
    {
        $line = trim($line);
        if ($line === '') {
            return [];
        }

        // pega "palavras" (com acentos) ou "qualquer caractere não-espaço" (pontuação)
        preg_match_all('/[\p{L}\p{N}]+(?:[\'’\-][\p{L}\p{N}]+)*|[^\s]/u', $line, $m);
        $raw = $m[0] ?? [];

        $tokens = [];
        foreach ($raw as $t) {
            // se for apenas pontuação/símbolo, anexa no último token se existir
            if (preg_match('/^[^\p{L}\p{N}]+$/u', $t)) {
                if (!empty($tokens)) {
                    $tokens[count($tokens) - 1] .= $t;
                } else {
                    // pontuação "solta" no começo vira token próprio
                    $tokens[] = $t;
                }
            } else {
                $tokens[] = $t;
            }
        }

        return $tokens;
    }

    /**
     * Reconstrói palavras a partir de texto puro
     * Preserva quebras de linha como linebreak
     *
     * ✅ Correções:
     * - linebreak não some
     * - tokenização respeita pontuação
     * - duração pega do attachment (não do transcript_id)
     */
    public static function rebuild_from_text(int $transcript_id, string $text): void
    {
        self::delete_words($transcript_id);

        // ✔ remove slashes
        $text = wp_unslash($text);

        // ✔ normaliza quebras de linha
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // ✔ no máximo 1 linha em branco
        $text = preg_replace("/\n{3,}/", "\n\n", $text);

        $lines = explode("\n", $text);

        /* =====================================================
         * 1) MONTA TOKENS (SEM TEMPO AINDA)
         * ===================================================== */
        $tokens = [];
        $idx = 0;
        $wordCount = 0;

        foreach ($lines as $line) {

            $lineTokens = self::tokenize_line($line);

            foreach ($lineTokens as $w) {
                $tokens[] = [
                    'idx'      => $idx++,
                    'word'     => $w,
                    'type'     => 'word',
                    'start_ms' => null,
                    'end_ms'   => null,
                    'group_id' => null,
                ];
                $wordCount++;
            }

            // linebreak estrutural (sem tempo)
            $tokens[] = [
                'idx'      => $idx++,
                'word'     => "\n",
                'type'     => 'linebreak',
                'start_ms' => null,
                'end_ms'   => null,
                'group_id' => null,
            ];
        }

        /* =====================================================
         * 2) BUSCA DURAÇÃO DO ÁUDIO (CORRETO)
         * ===================================================== */

        $t = self::get_by_id($transcript_id);

        // ✅ duração deve vir do attachment (post da Media Library)
        $durationMs = 0;
        if ($t && !empty($t->attachment_id)) {
            $durationMs = (int) get_post_meta((int) $t->attachment_id, '_audio_duration_ms', true);
        }

        // fallback de segurança (30s)
        if ($durationMs <= 0) {
            $durationMs = 30000;
        }

        /* =====================================================
         * 3) DISTRIBUI TEMPO APENAS PARA PALAVRAS
         * ===================================================== */
        if ($wordCount > 0) {
            $step = (int) floor($durationMs / $wordCount);
            if ($step <= 0) {
                $step = 1;
            }

            $currentTime = 0;

            foreach ($tokens as &$token) {
                if (($token['type'] ?? 'word') === 'word') {
                    $token['start_ms'] = $currentTime;
                    $token['end_ms']   = $currentTime + $step;
                    $currentTime += $step;
                }
            }
            unset($token);

            // ✅ garante que a última palavra termine exatamente na duração (fica mais “bonito”)
            for ($i = count($tokens) - 1; $i >= 0; $i--) {
                if (($tokens[$i]['type'] ?? 'word') === 'word') {
                    $tokens[$i]['end_ms'] = $durationMs;
                    break;
                }
            }
        }

        /* =====================================================
         * 4) SALVA NO BANCO
         * ===================================================== */
        self::insert_words_bulk($transcript_id, $tokens);
    }
}
