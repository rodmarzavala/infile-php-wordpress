<?php

namespace InfilePhp\WordPress\Admin;

/**
 * Admin list page showing all issued DTEs.
 * Accessible under WooCommerce > FEL Invoices.
 * PHP 7.4 compatible.
 */
class InvoiceListPage
{
    public static function register()
    {
        add_action('admin_menu', array(__CLASS__, 'addMenuPage'));
    }

    public static function addMenuPage()
    {
        add_submenu_page(
            'woocommerce',
            'FEL Invoices',
            'FEL Invoices',
            'manage_woocommerce',
            'infile-php-invoices',
            array(__CLASS__, 'renderPage')
        );
    }

    public static function renderPage()
    {
        if (!current_user_can('manage_woocommerce')) {
            return;
        }

        $args = array(
            'limit' => 100,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key'     => '_fel_uuid',
                    'compare' => 'EXISTS',
                ),
            ),
        );
        $orders = wc_get_orders($args);

        ?>
        <div class="wrap">
            <h1>FEL Invoices</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Date</th>
                        <th>UUID</th>
                        <th>Serie / Número</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="5">No FEL invoices found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><a href="<?php echo esc_url($order->get_edit_order_url()); ?>">#<?php echo esc_html($order->get_id()); ?></a></td>
                                <td><?php echo esc_html($order->get_date_created() ? $order->get_date_created()->date('Y-m-d H:i:s') : ''); ?></td>
                                <td><code><?php echo esc_html($order->get_meta('_fel_uuid')); ?></code></td>
                                <td><?php echo esc_html($order->get_meta('_fel_serie') . ' / ' . $order->get_meta('_fel_numero')); ?></td>
                                <td><?php echo esc_html($order->get_meta('_fel_status')); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
