<?php

namespace LeiturinhaKaraoke\Admin;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Página de Configurações – Leiturinha-Karaoke
 */
class SettingsPage
{
    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Você não tem permissão para acessar esta página.');
        }

        // Salvar configurações
        if (isset($_POST['lk_save_settings']) && check_admin_referer('lk_settings_nonce')) {
            update_option('lk_aws_access_key', sanitize_text_field($_POST['lk_aws_access_key']));
            update_option('lk_aws_secret_key', sanitize_text_field($_POST['lk_aws_secret_key']));
            update_option('lk_aws_region', sanitize_text_field($_POST['lk_aws_region']));
            update_option('lk_aws_bucket', sanitize_text_field($_POST['lk_aws_bucket']));

            echo '<div class="notice notice-success"><p>Configurações salvas com sucesso.</p></div>';
        }

        // Valores atuais
        $access_key = get_option('lk_aws_access_key', '');
        $secret_key = get_option('lk_aws_secret_key', '');
        $region     = get_option('lk_aws_region', 'us-east-1');
        $bucket     = get_option('lk_aws_bucket', '');
        ?>

        <div class="wrap">
            <h1>Configurações – Leiturinha-Karaoke</h1>

            <form method="post">
                <?php wp_nonce_field('lk_settings_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">AWS Access Key</th>
                        <td>
                            <input type="text" name="lk_aws_access_key" value="<?php echo esc_attr($access_key); ?>" class="regular-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">AWS Secret Key</th>
                        <td>
                            <input type="password" name="lk_aws_secret_key" value="<?php echo esc_attr($secret_key); ?>" class="regular-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">AWS Region</th>
                        <td>
                            <input type="text" name="lk_aws_region" value="<?php echo esc_attr($region); ?>" class="regular-text">
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">AWS Bucket</th>
                        <td>
                            <input type="text" name="lk_aws_bucket" value="<?php echo esc_attr($bucket); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>

                <p>
                    <button type="submit" name="lk_save_settings" class="button button-primary">
                        Salvar configurações
                    </button>
                </p>
            </form>
        </div>
        <?php
    }
}
