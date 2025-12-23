<?php
/**
 * Template Front-end – Leiturinha-Karaoke
 *
 * Pode ser usado como:
 * - Page Template
 * - Template carregado dinamicamente
 */

if (!defined('ABSPATH')) {
    exit;
}

/*
Template Name: Leiturinha Karaoke
*/

get_header();

/**
 * ======================================================
 * RESOLUÇÃO DO transcript_id
 *
 * Prioridade:
 * 1) Query string ?transcript_id=
 * 2) Post Meta (transcript_id)
 * ======================================================
 */
$transcript_id = 0;

// Via query string
if (isset($_GET['transcript_id'])) {
    $transcript_id = (int) $_GET['transcript_id'];
}

// Via post meta (fallback)
if (!$transcript_id) {
    $meta_id = get_post_meta(get_the_ID(), 'transcript_id', true);
    $transcript_id = $meta_id ? (int) $meta_id : 0;
}
?>

<div class="lk-template-container" style="max-width:900px;margin:0 auto;padding:40px 20px;">

    <?php if (!$transcript_id): ?>

        <div class="lk-warning">
            <p>
                <strong>Leiturinha-Karaoke:</strong><br>
                Nenhuma transcrição foi configurada para esta página.
            </p>
        </div>

    <?php else: ?>

        <?php
        /**
         * Renderização principal
         * Toda a lógica fica no shortcode
         */
        echo do_shortcode(
            sprintf(
                '[leiturinha_karaoke transcript_id="%d"]',
                $transcript_id
            )
        );
        ?>

    <?php endif; ?>

</div>

<?php
get_footer();
