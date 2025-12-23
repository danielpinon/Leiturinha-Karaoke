<?php

namespace LeiturinhaKaraoke\Admin;

use LeiturinhaKaraoke\Admin\EditorPage;
use LeiturinhaKaraoke\Admin\UploadPage;
use LeiturinhaKaraoke\Admin\SettingsPage;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Menu administrativo do plugin Leiturinha-Karaoke
 */
class Menu
{
    /**
     * Inicializa os menus
     */
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerMenus']);
    }

    /**
     * Registra menus e submenus
     */
    public static function registerMenus(): void
    {
        // Menu principal
        add_menu_page(
            'Leiturinha – Karaoke',
            'Leiturinha-Karaoke',
            'manage_options',
            'leiturinha-karaoke',
            [UploadPage::class, 'render'],
            'dashicons-format-audio',
            26
        );

        // Submenu: Áudios
        add_submenu_page(
            'leiturinha-karaoke',
            'Áudios',
            'Áudios',
            'manage_options',
            'leiturinha-karaoke',
            [UploadPage::class, 'render']
        );

        // Submenu: Editor
        add_submenu_page(
            null,
            'Editor de Transcrição',
            'Editor',
            'manage_options',
            'leiturinha-karaoke-editor',
            [EditorPage::class, 'render']
        );

        // Submenu: Configurações
        add_submenu_page(
            'leiturinha-karaoke',
            'Configurações',
            'Configurações',
            'manage_options',
            'leiturinha-karaoke-settings',
            [SettingsPage::class, 'render']
        );
    }
}
