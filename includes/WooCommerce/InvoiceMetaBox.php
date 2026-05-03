<?php

namespace InfilePhp\WordPress\WooCommerce;

/**
 * Displays FEL invoice data (UUID, serie, número) in the WooCommerce order detail meta box.
 * PHP 7.4 compatible.
 */
class InvoiceMetaBox
{
    public static function register()
    {
        add_action('add_meta_boxes', array(__CLASS__, 'addMetaBox'));
    }

    public static function addMetaBox()
    {
        add_meta_box(
            'infile_php_invoice',
            'FEL Invoice',
            array(__CLASS__, 'render'),
            'shop_order',
            'side',
            'high'
        );
    }

    /**
     * @param \WP_Post $post
     */
    public static function render($post)
    {
        $orderId = $post->ID;
        $uuid    = get_post_meta($orderId, '_fel_uuid', true);
        $serie   = get_post_meta($orderId, '_fel_serie', true);
        $numero  = get_post_meta($orderId, '_fel_numero', true);
        $status  = get_post_meta($orderId, '_fel_status', true);
        $issuedAt = get_post_meta($orderId, '_fel_issued_at', true);

        include INFILE_PHP_PLUGIN_DIR . 'templates/invoice-meta-box.php';
    }
}
