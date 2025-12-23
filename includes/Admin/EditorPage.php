<?php
namespace LeiturinhaKaraoke\Admin;

use LeiturinhaKaraoke\Repository\TranscriptRepository;

if (!defined('ABSPATH'))
    exit;

class EditorPage
{
    public static function render(): void
    {
        $transcript_id = isset($_GET['transcript_id']) ? (int) $_GET['transcript_id'] : 0;
        if (!$transcript_id) {
            echo '<div class="notice notice-error"><p>Transcrição não informada.</p></div>';
            return;
        }

        $transcript = TranscriptRepository::get_by_id($transcript_id);
        $words = TranscriptRepository::get_words($transcript_id);

        wp_enqueue_style(
            'lk-editor-css',
            LK_PLUGIN_URL . 'assets/admin/editor.css',
            [],
            LK_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'lk-editor-js',
            LK_PLUGIN_URL . 'assets/admin/editor.js',
            [],
            LK_PLUGIN_VERSION,
            true
        );

        wp_localize_script('lk-editor-js', 'LK_EDITOR', [
            'rest_url' => rest_url('leiturinha-karaoke/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
            'transcript_id' => $transcript_id,
        ]);
        ?>

        <div class="lk-editor-wrapper">
            <h2>Editor de Transcrição</h2>

            <!-- PLAYER -->
            <audio id="lk-audio" controls>
                <source src="<?php echo esc_url($transcript->public_url); ?>">
            </audio>

            <!-- TOOLBAR COMPLETA -->
            <div class="editor-toolbar">
                <button data-cmd="bold"><b>B</b></button>
                <button data-cmd="italic"><i>I</i></button>
                <button data-cmd="underline"><u>U</u></button>

                <button data-cmd="justifyLeft">⯇</button>
                <button data-cmd="justifyCenter">≡</button>
                <button data-cmd="justifyRight">⯈</button>

                <button data-cmd="insertUnorderedList">• Lista</button>
                <button data-cmd="insertOrderedList">1. Lista</button>
            </div>

            <!-- EDITOR -->
            <div id="editor-content" contenteditable="true">
                <?php foreach ($words as $w): ?>

                    <?php if ($w->type === 'linebreak'): ?>
                        <div class="line-break" data-type="linebreak"></div>
                    <?php else: ?>
                        <span class="word" data-id="<?php echo esc_attr($w->id); ?>" data-start="<?php echo esc_attr($w->start_ms); ?>"
                            data-end="<?php echo esc_attr($w->end_ms); ?>">
                            <?php echo esc_html($w->word); ?>
                        </span>
                    <?php endif; ?>

                <?php endforeach; ?>
            </div>


            <button id="save-editor">Salvar alterações</button>

            <!-- MENU CONTEXTUAL -->
            <div id="context-menu">
                <div data-action="edit-time">Editar tempo</div>
                <div data-action="group">Agrupar palavras</div>
            </div>

            <!-- MODAL -->
            <div id="time-modal">
                <div class="modal-box">
                    <h3>Editar tempo</h3>
                    <label>Início (ms)</label>
                    <input type="number" id="start-time">
                    <label>Fim (ms)</label>
                    <input type="number" id="end-time">
                    <button id="save-time">Salvar</button>
                </div>
            </div>
        </div>
        <?php
    }
}
