<?php

namespace LeiturinhaKaraoke;

use LeiturinhaKaraoke\Admin\Menu;
use LeiturinhaKaraoke\Admin\Actions;
use LeiturinhaKaraoke\Admin\RestApi;
use LeiturinhaKaraoke\Frontend\Shortcode;

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
    }
}

Init::run();
