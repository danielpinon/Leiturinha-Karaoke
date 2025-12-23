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

    public static function create_transcript($attachment_id, $public_url, $language = 'pt-BR')
    {
        global $wpdb;

        $wpdb->insert(
            self::transcripts_table(),
            [
                'attachment_id' => (int) $attachment_id,
                'public_url' => esc_url_raw($public_url),
                'language' => sanitize_text_field($language),
                'status' => 'pending'
            ],
            ['%d', '%s', '%s', '%s']
        );

        return (int) $wpdb->insert_id;
    }

    public static function get_by_id($transcript_id)
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::transcripts_table() . " WHERE id = %d",
                $transcript_id
            )
        );
    }

    public static function update_transcript($transcript_id, array $data)
    {
        global $wpdb;

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
     */
    public static function insert_words_bulk(int $transcript_id, array $words): void
    {
        global $wpdb;

        foreach ($words as $word) {

            $wpdb->insert(
                self::words_table(),
                [
                    'transcript_id' => (int) $transcript_id,
                    'idx' => (int) ($word['idx'] ?? 0),
                    'word' => sanitize_text_field($word['word']),
                    'type' => $word['type'] ?? 'word',

                    'start_ms' => isset($word['start_ms']) ? (int) $word['start_ms'] : null,
                    'end_ms' => isset($word['end_ms']) ? (int) $word['end_ms'] : null,
                    'group_id' => isset($word['group_id']) ? (int) $word['group_id'] : null,

                    /* ===== ESTILOS ===== */
                    'font_family' => $word['font_family'] ?? null,
                    'font_size' => isset($word['font_size']) ? (int) $word['font_size'] : null,
                    'font_weight' => $word['font_weight'] ?? null,
                    'font_style' => $word['font_style'] ?? null,
                    'underline' => !empty($word['underline']) ? 1 : 0,
                    'color' => $word['color'] ?? null,
                    'background' => $word['background'] ?? null,
                    'letter_spacing' => $word['letter_spacing'] ?? null,
                    'line_height' => $word['line_height'] ?? null,
                ],
                [
                    '%d', // transcript_id
                    '%d', // idx
                    '%s', // word
                    '%s', // type
                    '%d', // start_ms
                    '%d', // end_ms
                    '%d', // group_id

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
                $transcript_id
            )
        );
    }

    /**
     * Atualiza uma palavra específica
     */
    public static function updateWord(int $word_id, array $data): bool
    {
        global $wpdb;

        return (bool) $wpdb->update(
            self::words_table(),
            $data,
            ['id' => $word_id]
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
                    'text' => $word->word,
                    'start_ms' => $word->start_ms,
                    'end_ms' => $word->end_ms,
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
     * Reconstrói palavras a partir de texto puro
     * Preserva quebras de linha como linebreak
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
         * 1️⃣ MONTA TOKENS (SEM TEMPO AINDA)
         * ===================================================== */
        $tokens = [];
        $idx = 0;
        $wordCount = 0;

        foreach ($lines as $line) {

            $line = trim($line);

            if ($line !== '') {
                $words = preg_split('/\s+/', $line);

                foreach ($words as $w) {
                    $tokens[] = [
                        'idx' => $idx++,
                        'word' => $w,
                        'type' => 'word',
                        'start_ms' => null,
                        'end_ms' => null,
                        'group_id' => null,
                    ];
                    $wordCount++;
                }
            }

            // linebreak estrutural (sem tempo)
            $tokens[] = [
                'idx' => $idx++,
                'word' => "\n",
                'type' => 'linebreak',
                'start_ms' => null,
                'end_ms' => null,
                'group_id' => null,
            ];
        }

        /* =====================================================
         * 2️⃣ BUSCA DURAÇÃO DO ÁUDIO
         * ===================================================== */

        // 🔥 AJUSTE AQUI SE SUA DURAÇÃO VIER DE OUTRO LUGAR
        $durationMs = (int) get_post_meta($transcript_id, '_audio_duration_ms', true);

        // fallback de segurança (30s)
        if ($durationMs <= 0) {
            $durationMs = 30000;
        }

        /* =====================================================
         * 3️⃣ DISTRIBUI TEMPO APENAS PARA PALAVRAS
         * ===================================================== */

        if ($wordCount > 0) {
            $step = (int) floor($durationMs / $wordCount);
            $currentTime = 0;

            foreach ($tokens as &$token) {
                if ($token['type'] === 'word') {
                    $token['start_ms'] = $currentTime;
                    $token['end_ms'] = $currentTime + $step;
                    $currentTime += $step;
                }
            }
            unset($token);
        }

        /* =====================================================
         * 4️⃣ SALVA NO BANCO
         * ===================================================== */

        self::insert_words_bulk($transcript_id, $tokens);
    }


}
