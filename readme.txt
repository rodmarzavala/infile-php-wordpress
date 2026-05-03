=== infile-php — FEL for WooCommerce ===
Contributors: rodmarzavala
Tags: woocommerce, factura electronica, FEL, SAT, Guatemala, Infile
Requires at least: 5.9
Tested up to: 6.5
Stable tag: 1.0.0
Requires PHP: 7.4
License: MIT
License URI: https://opensource.org/licenses/MIT

Guatemala FEL (Factura Electrónica en Línea) integration via Infile S.A. for WooCommerce.

== Description ==

Automatically issue certified FEL invoices when a WooCommerce order is completed.
Supports credit notes on full refunds and manual invoice issuance from the order detail page.

Features:
* Auto-invoice on order status = completed
* Auto credit note on full refund
* Manual "Issue FEL Invoice" button in order detail
* Admin settings page under WooCommerce > FEL / Infile
* All issued DTEs listed under WooCommerce > FEL Invoices
* UUID, serie, and número stored as order meta

== Installation ==

1. Upload the plugin folder to /wp-content/plugins/
2. Activate the plugin via Plugins > Installed Plugins
3. Go to WooCommerce > FEL / Infile and enter your Infile credentials
4. Set your NIT and choose your certification flow

== Frequently Asked Questions ==

= Does this support the sandbox environment? =
Yes. Set Environment to "sandbox" in the plugin settings.

= What PHP version is required? =
PHP 7.4 or higher for the plugin, but the Infile SDK core requires PHP 8.2+.

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release.
