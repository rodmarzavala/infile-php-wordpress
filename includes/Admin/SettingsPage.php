<?php

namespace InfilePhp\WordPress\Admin;

/**
 * Settings page under WooCommerce > FEL / Infile.
 * PHP 7.4 compatible — uses wp_options for credential storage and WP nonces for security.
 */
class SettingsPage
{
    const OPTION_GROUP = 'infile_php_settings';

    const OPTION_FIELDS = array(
        'infile_nit'            => 'Emisor NIT',
        'infile_sign_user'      => 'UsuarioFirma (FEL_SIGN_USER)',
        'infile_sign_key'       => 'LlaveFirma (FEL_SIGN_KEY)',
        'infile_api_user'       => 'UsuarioApi (FEL_API_USER)',
        'infile_api_key'        => 'LlaveApi (FEL_API_KEY)',
        'infile_environment'    => 'Environment (sandbox / production)',
        'infile_flow'           => 'Flow (unified / separate)',
        'infile_email_copy'     => 'BCC Email Copy',
        'infile_auto_invoice'   => 'Auto-invoice on order completion',
        'infile_auto_credit_note' => 'Auto credit note on full refund',
    );

    public static function register()
    {
        add_action('admin_menu', array(__CLASS__, 'addMenuPage'));
        add_action('admin_init', array(__CLASS__, 'registerSettings'));
    }

    public static function addMenuPage()
    {
        add_submenu_page(
            'woocommerce',
            'FEL / Infile Settings',
            'FEL / Infile',
            'manage_options',
            'infile-php-settings',
            array(__CLASS__, 'renderPage')
        );
    }

    public static function registerSettings()
    {
        foreach (array_keys(self::OPTION_FIELDS) as $field) {
            register_setting(self::OPTION_GROUP, $field, array('sanitize_callback' => 'sanitize_text_field'));
        }
    }

    public static function renderPage()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'infile_php_settings')) {
            foreach (array_keys(self::OPTION_FIELDS) as $field) {
                if (isset($_POST[$field])) {
                    update_option($field, sanitize_text_field(wp_unslash($_POST[$field])));
                }
            }
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }

        ?>
        <div class="wrap">
            <h1>FEL / Infile Settings</h1>
            <form method="post" action="">
                <?php wp_nonce_field('infile_php_settings'); ?>
                <table class="form-table">
                    <?php foreach (self::OPTION_FIELDS as $field => $label): ?>
                    <tr>
                        <th><label for="<?php echo esc_attr($field); ?>"><?php echo esc_html($label); ?></label></th>
                        <td>
                            <input type="text"
                                   name="<?php echo esc_attr($field); ?>"
                                   id="<?php echo esc_attr($field); ?>"
                                   value="<?php echo esc_attr(get_option($field, '')); ?>"
                                   class="regular-text"/>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }
}
