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
    public static function insert_words_bulk($transcript_id, array $words)
    {
        global $wpdb;

        foreach ($words as $word) {
            $wpdb->insert(
                self::words_table(),
                [
                    'transcript_id' => (int) $transcript_id,
                    'idx' => (int) $word['idx'],
                    'word' => sanitize_text_field($word['word']),
                    'type' => $word['type'] ?? 'word',
                    'start_ms' => $word['start_ms'] ?? null,
                    'end_ms' => $word['end_ms'] ?? null,
                    'group_id' => $word['group_id'] ?? null,
                ],
                ['%d', '%d', '%s', '%s', '%d', '%d', '%d']
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

        $text = wp_unslash($text);
        $text = trim($text);

        // Divide mantendo linhas vazias
        $lines = preg_split("/\r\n|\n|\r/", $text);
        $idx = 0;
        $tokens = [];

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
                }
            }

            // 🔥 SEMPRE adiciona linebreak (inclusive para linhas vazias)
            $tokens[] = [
                'idx' => $idx++,
                'word' => "\n",
                'type' => 'linebreak',
                'start_ms' => null,
                'end_ms' => null,
                'group_id' => null,
            ];
        }

        self::insert_words_bulk($transcript_id, $tokens);
    }

}
