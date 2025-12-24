<?php
/**
 * Plugin Name: Leiturinha – Karaoke
 * Description: Leitura sincronizada com áudio, destaque palavra por palavra e transcrição via AWS.
 * Version: 1.0.0
 * Author: Mentores
 * Text Domain: leiturinha-karaoke
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ======================================================
 * CONSTANTES DO PLUGIN
 * ======================================================
 */
define('LK_PLUGIN_FILE', __FILE__);
define('LK_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('LK_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LK_PLUGIN_VERSION', '1.0.0');

/**
 * ======================================================
 * AUTOLOAD (Composer)
 * ======================================================
 */
if (file_exists(LK_PLUGIN_PATH . 'vendor/autoload.php')) {
    require_once LK_PLUGIN_PATH . 'vendor/autoload.php';
}

/**
 * ======================================================
 * BOOTSTRAP DO PLUGIN
 * ======================================================
 */
require_once LK_PLUGIN_PATH . 'init.php';

/**
 * ======================================================
 * ATIVAÇÃO
 * ======================================================
 */
register_activation_hook(
    __FILE__,
    [\LeiturinhaKaraoke\Activator::class, 'activate']
);
