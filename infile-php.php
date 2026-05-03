<?php
/**
 * Plugin Name:       infile-php — FEL for WooCommerce
 * Plugin URI:        https://github.com/rodmarzavala/infile-php
 * Description:       Guatemala FEL (Factura Electrónica en Línea) integration via Infile S.A. Automatically issues certified invoices on WooCommerce order completion.
 * Version:           1.0.0
 * Author:            Rodmar Zavala
 * Author URI:        https://github.com/rodmarzavala
 * License:           MIT
 * Text Domain:       infile-php
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * WC requires at least: 6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('INFILE_PHP_VERSION', '1.0.0');
define('INFILE_PHP_PLUGIN_FILE', __FILE__);
define('INFILE_PHP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('INFILE_PHP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Verify PHP >= 8.2 for core SDK (core requires 8.2, plugin supports 7.4 for WP compat)
if (version_compare(PHP_VERSION, '8.2.0', '<')) {
    add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>infile-php:</strong> The core SDK requires PHP 8.2 or higher. ';
        echo 'Current version: ' . esc_html(PHP_VERSION) . '. Please upgrade PHP to use this plugin.';
        echo '</p></div>';
    });
    return;
}

// Autoloader
if (file_exists(INFILE_PHP_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once INFILE_PHP_PLUGIN_DIR . 'vendor/autoload.php';
}

// Load includes
require_once INFILE_PHP_PLUGIN_DIR . 'includes/Core/InfileService.php';
require_once INFILE_PHP_PLUGIN_DIR . 'includes/Admin/SettingsPage.php';
require_once INFILE_PHP_PLUGIN_DIR . 'includes/Admin/InvoiceListPage.php';
require_once INFILE_PHP_PLUGIN_DIR . 'includes/WooCommerce/OrderInvoiceHook.php';
require_once INFILE_PHP_PLUGIN_DIR . 'includes/WooCommerce/InvoiceMetaBox.php';
require_once INFILE_PHP_PLUGIN_DIR . 'includes/WooCommerce/RefundCreditNote.php';

// Bootstrap
add_action('plugins_loaded', function () {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-warning"><p>';
            echo '<strong>infile-php:</strong> WooCommerce is required for automatic invoicing.';
            echo '</p></div>';
        });
    }

    InfilePhp\WordPress\Core\InfileService::init();
    InfilePhp\WordPress\Admin\SettingsPage::register();
    InfilePhp\WordPress\Admin\InvoiceListPage::register();
    InfilePhp\WordPress\WooCommerce\OrderInvoiceHook::register();
    InfilePhp\WordPress\WooCommerce\InvoiceMetaBox::register();
    InfilePhp\WordPress\WooCommerce\RefundCreditNote::register();
});

register_activation_hook(__FILE__, function () {
    // Future: create custom DB table for DTE log if needed
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});
