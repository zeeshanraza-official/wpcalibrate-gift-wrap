=== WPCalibrate Gift Wrap ===
Contributors: wpcalibrate
Tags: woocommerce, gift wrap, gift wrapping, gift message, checkout
Requires at least: 6.4
Tested up to: 7.1.3
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional gift-wrapping system for WooCommerce products and whole orders with custom styles, pricing, badges, and gift messages.

== Description ==

WPCalibrate Gift Wrap provides WooCommerce store owners with an elegant, extensible gift-wrapping solution. Give your shoppers the option to add premium gift wrapping to individual products or wrap an entire order, complete with personalized gift messages.

### Key Capabilities

* **Per-Item & Whole-Order Modes**: Support wrapping individual products, whole orders, or both with clear combination controls.
* **Custom Wrapping Styles**: Manage multiple wrapping options with names, descriptions, images, prices, badges, and tax status.
* **Deterministic Eligibility Rules**: Control wrapping availability by product category, product exclusions, and price thresholds.
* **Product-Level Overrides**: Enable, disable, or restrict specific wrapping styles directly from the WooCommerce product editor.
* **Multi-Quantity Flexibility**: Accurately wrap multi-quantity line items with transparent server-side pricing.
* **Personalized Gift Messages**: Support plain text customer gift messages with configurable character limits.
* **High-Performance Order Storage (HPOS)**: Fully compatible with WooCommerce HPOS and custom order tables.
* **Cart & Checkout Blocks**: Integrates with modern WooCommerce Blocks and classic templates.
* **Seamless Fulfillment**: View wrapping styles, quantities, and gift messages on the order admin screen and packing summaries.
* **Transactional Emails**: Present gift wrapping and messages on order receipts and admin fulfillment emails.

== Installation ==

1. Upload the `wpcalibrate-gift-wrap` folder to your `/wp-content/plugins/` directory, or install via the WordPress Plugins menu.
2. Activate the plugin through the **Plugins** screen in WordPress. Ensure WooCommerce is active.
3. Navigate to **WPCalibrate > Gift Wrap** to configure general options and create your wrapping styles.
4. Test a product on your storefront to verify wrapping selectors and checkout totals.

== Frequently Asked Questions ==

= Does this plugin work with High-Performance Order Storage (HPOS)? =
Yes. WPCalibrate Gift Wrap declares full HPOS compatibility and interacts with order data strictly through official WooCommerce CRUD APIs.

= Can customers wrap only some units of a multi-quantity item? =
Yes. When per-item wrapping is enabled, customers can specify the quantity of physical units to be wrapped.

= How are wrapping prices calculated? =
All pricing calculations are performed strictly server-side based on the authoritative wrapping style configuration. Client-side price tampering is prevented.

= Does this plugin support WooCommerce Checkout Blocks? =
Yes. WPCalibrate Gift Wrap includes integration with WooCommerce Cart & Checkout Blocks via official Store API extension hooks.

= Can I prevent whole-order wrapping when individual items are already wrapped? =
Yes. By default, whole-order wrapping is exclusive to prevent accidental duplicate wrapping charges, but administrators can allow combining both modes in settings if desired.

== Screenshots ==

1. WPCalibrate Gift Wrap settings and style manager.
2. Single product page gift wrap selector with preview images and badge.
3. Cart totals with whole-order wrapping fee and gift message.
4. WooCommerce order admin screen showing fulfillment gift details.

== Changelog ==

= 1.0.0 =
* Initial production release.
* Added Per-Item and Whole-Order gift wrapping modes.
* Added Gift Wrap Styles manager with Media Library image support.
* Added deterministic category and product eligibility rules engine.
* Added HPOS and Cart/Checkout Blocks compatibility.
* Added fulfillment summaries in WooCommerce Order admin.
* Added support for plain text gift messages and email integration.
