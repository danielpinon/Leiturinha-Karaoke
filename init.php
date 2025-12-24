<?php

namespace LeiturinhaKaraoke;

use LeiturinhaKaraoke\Admin\Menu;
use LeiturinhaKaraoke\Admin\Actions;
use LeiturinhaKaraoke\Admin\RestApi;
use LeiturinhaKaraoke\Frontend\Shortcode;
use LeiturinhaKaraoke\ACF\Fields;

if (!defined('ABSPATH')) {
    exit;
}

class Init
{
    public static function run(): void
    {
        Menu::init();
        Actions::init();
        RestApi::init();
        Shortcode::init();

        // 🔥 ACF
        Fields::init();
    }
}

Init::run();
