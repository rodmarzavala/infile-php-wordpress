<?php

namespace InfilePhp\WordPress\WooCommerce;

use InfilePhp\WordPress\Core\InfileService;

/**
 * Hooks into woocommerce_order_status_completed to auto-issue a FEL invoice.
 * PHP 7.4 compatible — no enums, no readonly, no named arguments.
 */
class OrderInvoiceHook
{
    public static function register()
    {
        if (get_option('infile_auto_invoice', '1') !== '1') {
            return;
        }

        add_action('woocommerce_order_status_completed', array(__CLASS__, 'handle'));
        add_action('woocommerce_order_actions', array(__CLASS__, 'addManualAction'));
        add_action('woocommerce_order_action_infile_issue_invoice', array(__CLASS__, 'handleManual'));
    }

    /**
     * Auto-issue invoice when order is marked completed.
     *
     * @param int $orderId
     */
    public static function handle($orderId)
    {
        if (!InfileService::isConfigured()) {
            return;
        }

        $order = wc_get_order($orderId);
        if (!$order) {
            return;
        }

        // Skip if already issued
        if ($order->get_meta('_fel_uuid')) {
            return;
        }

        self::issueInvoice($order);
    }

    /**
     * Add "Issue FEL Invoice" to the WooCommerce order actions dropdown.
     *
     * @param array $actions
     * @return array
     */
    public static function addManualAction($actions)
    {
        $actions['infile_issue_invoice'] = 'Issue FEL Invoice';
        return $actions;
    }

    /**
     * Handle the manual "Issue FEL Invoice" action.
     *
     * @param \WC_Order $order
     */
    public static function handleManual($order)
    {
        if (!InfileService::isConfigured()) {
            return;
        }

        self::issueInvoice($order);
    }

    /**
     * Issue the invoice for a WooCommerce order and store meta.
     *
     * @param \WC_Order $order
     */
    private static function issueInvoice($order)
    {
        try {
            $nit           = $order->get_meta('_billing_nit') ?: 'CF';
            $recipientName = $order->get_formatted_billing_full_name() ?: 'Consumidor Final';
            $address       = trim($order->get_billing_address_1() . ' ' . $order->get_billing_city());

            if ($nit === 'CF' || empty($nit)) {
                $recipient = \InfilePhp\Core\Dte\Recipient::finalConsumer();
            } else {
                $recipient = \InfilePhp\Core\Dte\Recipient::withTaxId($nit)
                    ->name($recipientName)
                    ->address($address ?: 'Guatemala');
            }

            $invoice = \InfilePhp\Core\Dte\Invoice::create()->for($recipient);

            foreach ($order->get_items() as $item) {
                $invoice->add(
                    \InfilePhp\Core\Dte\Item::product($item->get_name())
                        ->quantity((int) $item->get_quantity())
                        ->unitPrice((float) ($item->get_total() / max(1, $item->get_quantity())))
                );
            }

            $response = $invoice->issue();

            $order->update_meta_data('_fel_uuid', $response->uuid);
            $order->update_meta_data('_fel_serie', $response->serie);
            $order->update_meta_data('_fel_numero', $response->numero);
            $order->update_meta_data('_fel_issued_at', current_time('mysql'));
            $order->update_meta_data('_fel_status', 'issued');
            $order->save();

            $order->add_order_note(
                sprintf('FEL invoice issued. UUID: %s | Serie: %s | Número: %s', $response->uuid, $response->serie, $response->numero)
            );
        } catch (\Throwable $e) {
            $order->update_meta_data('_fel_status', 'failed');
            $order->save();
            $order->add_order_note('FEL invoice failed: ' . $e->getMessage());
        }
    }
}
