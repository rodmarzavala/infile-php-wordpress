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

        global $wpdb;

        $orders = $wpdb->get_results(
            "SELECT p.ID, p.post_date,
                    pm_uuid.meta_value AS fel_uuid,
                    pm_serie.meta_value AS fel_serie,
                    pm_numero.meta_value AS fel_numero,
                    pm_status.meta_value AS fel_status
             FROM {$wpdb->posts} p
             LEFT JOIN {$wpdb->postmeta} pm_uuid   ON pm_uuid.post_id   = p.ID AND pm_uuid.meta_key   = '_fel_uuid'
             LEFT JOIN {$wpdb->postmeta} pm_serie  ON pm_serie.post_id  = p.ID AND pm_serie.meta_key  = '_fel_serie'
             LEFT JOIN {$wpdb->postmeta} pm_numero ON pm_numero.post_id = p.ID AND pm_numero.meta_key = '_fel_numero'
             LEFT JOIN {$wpdb->postmeta} pm_status ON pm_status.post_id = p.ID AND pm_status.meta_key = '_fel_status'
             WHERE p.post_type = 'shop_order'
             AND pm_uuid.meta_value IS NOT NULL
             ORDER BY p.post_date DESC
             LIMIT 100"
        );

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
                        <?php foreach ($orders as $row): ?>
                            <tr>
                                <td><a href="<?php echo esc_url(get_edit_post_link($row->ID)); ?>">#<?php echo esc_html($row->ID); ?></a></td>
                                <td><?php echo esc_html($row->post_date); ?></td>
                                <td><code><?php echo esc_html($row->fel_uuid); ?></code></td>
                                <td><?php echo esc_html($row->fel_serie . ' / ' . $row->fel_numero); ?></td>
                                <td><?php echo esc_html($row->fel_status); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
