<?php

namespace LeiturinhaKaraoke\Frontend;

use LeiturinhaKaraoke\Repository\TranscriptRepository;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode [leiturinha_karaoke]
 */
class Shortcode
{
    /**
     * Inicializa o shortcode
     */
    public static function init(): void
    {
        add_shortcode('leiturinha_karaoke', [self::class, 'render']);
    }

    /**
     * Renderiza o player + texto sincronizado
     */
    public static function render(array $atts): string
    {
        $atts = shortcode_atts([
            'transcript_id' => 0,
        ], $atts);

        $transcript_id = (int) $atts['transcript_id'];

        if (!$transcript_id) {
            return '<p><strong>Leiturinha-Karaoke:</strong> Transcrição inválida.</p>';
        }

        $transcript = TranscriptRepository::getById($transcript_id);

        if (!$transcript) {
            return '<p><strong>Leiturinha-Karaoke:</strong> Transcrição não encontrada.</p>';
        }

        $words = TranscriptRepository::getWords($transcript_id);

        if (!$words) {
            return '<p><strong>Leiturinha-Karaoke:</strong> Nenhuma palavra encontrada.</p>';
        }

        // Enfileira assets do player
        wp_enqueue_style(
            'leiturinha-karaoke-player',
            LK_PLUGIN_URL . 'assets/public/player.css',
            [],
            LK_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'leiturinha-karaoke-player',
            LK_PLUGIN_URL . 'assets/public/player.js',
            [],
            LK_PLUGIN_VERSION,
            true
        );

        // Passa dados para JS
        wp_localize_script('leiturinha-karaoke-player', 'LK_PLAYER_DATA', [
            'words' => $words,
        ]);

        ob_start();
        ?>
        <div class="lk-player">

            <audio id="lk-audio" controls preload="metadata">
                <source src="<?php echo esc_url($transcript->public_url); ?>">
            </audio>

            <div id="lk-text" class="lk-text">
                <?php foreach ($words as $w): ?>
                    <span
                        class="lk-word"
                        data-start="<?php echo (int) $w->start_ms; ?>"
                        data-end="<?php echo (int) $w->end_ms; ?>"
                    >
                        <?php echo esc_html($w->word); ?>
                    </span>
                <?php endforeach; ?>
            </div>

        </div>
        <?php
        return ob_get_clean();
    }
}
