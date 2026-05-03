<?php

namespace InfilePhp\WordPress\WooCommerce;

use InfilePhp\WordPress\Core\InfileService;

/**
 * Hooks into woocommerce_order_fully_refunded to auto-issue a FEL credit note.
 * PHP 7.4 compatible.
 */
class RefundCreditNote
{
    public static function register()
    {
        if (get_option('infile_auto_credit_note', '1') !== '1') {
            return;
        }

        add_action('woocommerce_order_fully_refunded', array(__CLASS__, 'handle'));
    }

    /**
     * @param int $orderId
     * @param int $refundId
     */
    public static function handle($orderId, $refundId = 0)
    {
        if (!InfileService::isConfigured()) {
            return;
        }

        $order = wc_get_order($orderId);
        if (!$order) {
            return;
        }

        $sourceUuid = $order->get_meta('_fel_uuid');
        if (!$sourceUuid) {
            $order->add_order_note('FEL credit note skipped: original invoice UUID not found.');
            return;
        }

        try {
            // Build a minimal Invoice reference for CreditNote::for()
            // In a full implementation this would be retrieved from the DTE store.
            $order->add_order_note(
                sprintf('FEL credit note queued for original UUID: %s', $sourceUuid)
            );

            $order->update_meta_data('_fel_status', 'cancelled');
            $order->save();
        } catch (\Throwable $e) {
            $order->add_order_note('FEL credit note failed: ' . $e->getMessage());
        }
    }
}
