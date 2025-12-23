<?php

namespace LeiturinhaKaraoke\Admin;

use LeiturinhaKaraoke\Repository\TranscriptRepository;

if (!defined('ABSPATH')) {
    exit;
}

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

        wp_enqueue_style(
            'lk-material-icons',
            'https://fonts.googleapis.com/icon?family=Material+Icons',
            [],
            null
        );
        wp_enqueue_style(
            'lk-fontawesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
            [],
            '6.5.1'
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
            <audio id="lk-audio" controls preload="metadata">
                <source src="<?php echo esc_url($transcript->public_url); ?>">
            </audio>

            <!-- TOOLBAR PRINCIPAL -->
            <div class="editor-toolbar">
                <button data-cmd="bold"><i class="fa-solid fa-bold"></i></button>
                <button data-cmd="italic"><i class="fa-solid fa-italic"></i></button>
                <button data-cmd="underline"><i class="fa-solid fa-underline"></i></button>

                <button data-cmd="justifyLeft"><i class="fa-solid fa-align-left"></i></button>
                <button data-cmd="justifyCenter"><i class="fa-solid fa-align-center"></i></button>
                <button data-cmd="justifyRight"><i class="fa-solid fa-align-right"></i></button>

                <button data-cmd="insertUnorderedList"><i class="fa-solid fa-list-ul"></i></button>
                <button data-cmd="insertOrderedList"><i class="fa-solid fa-list-ol"></i></button>
            </div>

            <!-- PAINEL DE ESTILO POR PALAVRA -->
            <div class="editor-style-panel">
                <div class="style-group">
                    <label>Fonte</label>
                    <select id="font-family">
                        <option value="">Padrão</option>
                        <option value="Arial">Arial</option>
                        <option value="Georgia">Georgia</option>
                        <option value="Times New Roman">Times</option>
                        <option value="Comic Sans MS">Comic Sans</option>
                    </select>
                </div>

                <div class="style-group">
                    <label>Tamanho</label>
                    <select id="font-size">
                        <option value="">Padrão</option>
                        <option value="14">14 px</option>
                        <option value="16">16 px</option>
                        <option value="18">18 px</option>
                        <option value="22">22 px</option>
                        <option value="28">28 px</option>
                    </select>
                </div>

                <div class="style-group">
                    <label>Estilo</label>
                    <div class="style-buttons">
                        <button data-style="bold">B</button>
                        <button data-style="italic">I</button>
                        <button data-style="underline">U</button>
                    </div>
                </div>

                <div class="style-group" style="width: 80px;">
                    <label>Cor texto</label>
                    <input type="color" id="style-color">
                </div>

                <div class="style-group" style="width: 80px;">
                    <label>Cor fundo</label>
                    <input type="color" id="style-background">
                </div>

                <div class="style-group">
                    <label>Espaçamento</label>
                    <input type="number" step="0.1" id="letter-spacing" placeholder="ex: 0.05em">
                </div>

                <div class="style-group">
                    <label>Altura linha</label>
                    <input type="number" step="0.1" id="line-height" placeholder="ex: 1.8">
                </div>

            </div>

            <!-- EDITOR -->
            <div id="editor-content" contenteditable="true">

                <?php foreach ($words as $w): ?>

                    <?php if ($w->type === 'linebreak'): ?>

                        <br />

                    <?php else:

                        $style = [];

                        if ($w->font_family)
                            $style[] = "font-family: {$w->font_family}";
                        if ($w->font_size)
                            $style[] = "font-size: {$w->font_size}px";
                        if ($w->font_weight)
                            $style[] = "font-weight: {$w->font_weight}";
                        if ($w->font_style)
                            $style[] = "font-style: {$w->font_style}";
                        if ($w->underline)
                            $style[] = "text-decoration: underline";
                        if ($w->color)
                            $style[] = "color: {$w->color}";
                        if ($w->background)
                            $style[] = "background-color: {$w->background}";
                        if ($w->letter_spacing)
                            $style[] = "letter-spacing: {$w->letter_spacing}";
                        if ($w->line_height)
                            $style[] = "line-height: {$w->line_height}";
                        ?>

                        <span class="word" style="<?php echo esc_attr(implode('; ', $style)); ?>"
                            data-id="<?php echo esc_attr($w->id); ?>" data-start="<?php echo esc_attr($w->start_ms); ?>"
                            data-end="<?php echo esc_attr($w->end_ms); ?>" data-font-family="<?php echo esc_attr($w->font_family); ?>"
                            data-font-size="<?php echo esc_attr($w->font_size); ?>"
                            data-font-weight="<?php echo esc_attr($w->font_weight); ?>"
                            data-font-style="<?php echo esc_attr($w->font_style); ?>"
                            data-underline="<?php echo esc_attr($w->underline); ?>" data-color="<?php echo esc_attr($w->color); ?>"
                            data-background="<?php echo esc_attr($w->background); ?>"
                            data-letter-spacing="<?php echo esc_attr($w->letter_spacing); ?>"
                            data-line-height="<?php echo esc_attr($w->line_height); ?>">
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

            <!-- MODAL DE TEMPO -->
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
