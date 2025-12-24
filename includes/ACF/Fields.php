<?php

namespace LeiturinhaKaraoke\ACF;

if (!defined('ABSPATH')) {
    exit;
}

class Fields
{
    public static function init(): void
    {
        add_action('plugins_loaded', [self::class, 'loadFields']);
    }

    public static function loadFields(): void
    {
        // Garante que o ACF está ativo
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        require_once __DIR__ . '/leitura-guiada-fields.php';
    }
}
