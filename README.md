# WPCalibrate Gift Wrap

[![WordPress](https://img.shields.io/badge/WordPress-6.4%20to%207.1.3-blue.svg)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-8.0%20to%2011.2.1-96588a.svg)](https://woocommerce.com/)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%20%7C%208.3-777bb4.svg)](https://php.net/)
[![HPOS Ready](https://img.shields.io/badge/WooCommerce-HPOS%20Compatible-green.svg)](https://woocommerce.com/document/high-performance-order-storage/)
[![Blocks Ready](https://img.shields.io/badge/WooCommerce-Blocks%20Ready-purple.svg)](https://woocommerce.com/document/cart-checkout-blocks-status/)
[![License](https://img.shields.io/badge/License-GPLv2%20or%20later-orange.svg)](LICENSE.txt)

A modern, high-performance, and accessible gift-wrapping extension for **WooCommerce**. Empower your customers to personalize their purchases with elegant per-item wrapping, whole-order gift packaging, and custom personal messages — all backed by authoritative server-side pricing, HPOS compatibility, and frictionless WordPress dashboard updates.

---

## Table of Contents
- [About](#about)
- [Features](#features)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [How to Use & Documentation](#how-to-use--documentation)
  - [1. Wrap Styles Configuration](#1-wrap-styles-configuration)
  - [2. Wrapping Modes](#2-wrapping-modes)
  - [3. Eligibility & Rules Engine](#3-eligibility--rules-engine)
  - [4. Order Fulfillment & Emails](#4-order-fulfillment--emails)
  - [5. Developer Hooks & API](#5-developer-hooks--api)
  - [6. Automatic Dashboard Updates](#6-automatic-dashboard-updates)
- [Changelog](#changelog)
- [Support & Inquiries](#support--inquiries)
- [License](#license)

---

## About

**WPCalibrate Gift Wrap** turns standard e-commerce purchases into memorable gifting experiences. Designed specifically for modern WooCommerce stores, it helps merchants significantly increase Average Order Value (AOV) during holidays, birthdays, anniversaries, and year-round celebrations.

Unlike cumbersome third-party add-ons that bloat page loads or break during WooCommerce updates, WPCalibrate Gift Wrap is engineered with an object-oriented, domain-driven architecture that is:
* **Lightweight**: Zero third-party runtime dependencies; pure vanilla CSS and JavaScript with instant DOM hydration.
* **Standards-Compliant**: Fully declared and verified for **WooCommerce High-Performance Order Storage (HPOS)** and modern **Cart & Checkout Blocks**.
* **Authoritative**: Strict server-side price validation prevents client tampering, race conditions, or accidental duplicate wrapping charges.
* **Accessible**: WCAG 2.2 AA compliant with full keyboard navigation, screen-reader descriptions, and RTL (Right-to-Left) language support.

---

## Features

### 🎁 Dual Wrapping Modes
* **Per-Item Gift Wrapping**: Customers can wrap individual line items directly on single product pages or within the cart. Each wrapped item can have its own quantity and personalized gift message.
* **Whole-Order Gift Wrapping**: Customers can wrap the entire order as a collective gift package at cart or checkout.
* **Duplicate Charge Guard**: Intelligent exclusivity prevents customers from being double-charged for whole-order wrapping when individual items are already wrapped (customizable via store settings).

### 🎨 Visual Wrap Style Studio
* Configure unlimited gift wrap options (e.g., *Kraft Eco Paper*, *Luxury Velvet Ribbon*, *Festive Holiday Foil*, *Reusable Canvas Pouch*).
* Support for free wrap tiers or premium fixed pricing per unit.
* High-resolution image selection integrated directly with the WordPress Media Library.
* Optional promotional badges (e.g., *Popular*, *Eco-Friendly*, *Festive*).
* Minimum and maximum product price threshold guards per style.

### ✉️ Customer Gift Messages
* Clean, plain-text gift messaging with customizable maximum character length (up to 500 characters).
* Real-time character counter and live message preview.
* Multi-layer security sanitization prevents HTML and script injection while preserving formatting, emojis, and punctuation.

### ⚙️ Multi-Tier Rules Engine (Deterministic Precedence)
Fine-grained control over which products are eligible for gift wrapping:
1. **Product Meta Override**: Force-enable or force-disable gift wrapping on specific products (Simple & Variable).
2. **Global Product Exclusions**: Block oversized, fragile, or heavy items from wrapping.
3. **Global Product Inclusions**: Restrict wrapping exclusively to an approved product list.
4. **Category Inclusions / Exclusions**: Enable or disable wrapping based on product categories.
5. **Global Store Default**: Clean fallback for all remaining catalog items.

### 📦 Fulfillment & Packing Operations
* **WooCommerce Admin Order Screen**: Dedicated Gift Wrap packing slip card displaying style name, thumbnail, wrapped quantity, unit price, and printed gift message.
* **HPOS Immutable Snapshots**: Historical orders preserve the style name, price charged, and message at the exact moment of checkout — immune to future style edits or deletions.
* **Transactional Emails**: Included in customer receipts and store admin new-order notifications (both HTML and Plain Text).
* **Customer My Account**: Customers can review their chosen wrap styles and gift messages under their order history.

### 🔄 One-Click WordPress Dashboard Updates
* Direct integration with GitHub releases allows store owners to receive update notifications in **WordPress > Plugins** and perform one-click updates without leaving the WordPress dashboard.

---

## System Requirements

| Requirement | Minimum | Recommended |
|---|---|---|
| **WordPress** | 6.4 | 6.5+ (Tested up to 7.1.3) |
| **WooCommerce** | 8.0 | 9.0+ (Tested up to 11.2.1) |
| **PHP** | 8.2 | 8.3 |
| **Database** | MySQL 5.7+ / MariaDB 10.4+ | MySQL 8.0+ |
| **Order Storage** | HPOS or Legacy Posts | HPOS enabled |

---

## Installation

### Method 1: WordPress Admin Dashboard (ZIP Upload)
1. Download the latest `wpcalibrate-gift-wrap-1.0.0.zip` from the [Releases](https://github.com/zeeshanraza-official/wpcalibrate-gift-wrap/releases) page.
2. In your WordPress admin dashboard, navigate to **Plugins > Add New Plugin**.
3. Click **Upload Plugin** at the top of the page.
4. Select the downloaded `.zip` file and click **Install Now**.
5. Once uploaded, click **Activate Plugin**.

### Method 2: Manual Installation via FTP / SFTP
1. Extract the downloaded ZIP file on your computer.
2. Upload the `wpcalibrate-gift-wrap/` folder to your server's `/wp-content/plugins/` directory.
3. Go to **Plugins > Installed Plugins** in WordPress and activate **WPCalibrate Gift Wrap**.

---

## How to Use & Documentation

Once activated, access the plugin settings by navigating to **WPCalibrate > Gift Wrap** in your WordPress admin menu.

### 1. Wrap Styles Configuration
Navigate to the **Wrap Styles** tab:
* Click **Add New Style**.
* Enter a title (e.g., *Midnight Elegance Ribbon*), an optional description, and a price (enter `0` for free wrapping).
* Click **Select Image** to choose an image from your WordPress Media Library.
* Set eligibility constraints (optional minimum/maximum product price).
* Click **Save Style**.

### 2. Wrapping Modes
Navigate to the **General** tab:
* **Master Switch**: Toggle the entire gift-wrapping service on or off.
* **Per-Item Wrapping**: Allow customers to wrap individual items.
* **Whole-Order Wrapping**: Allow customers to wrap the entire order as one package.
* **Allow Combining Modes**: Check to permit both per-item and whole-order wrapping in the same order (unchecked by default to avoid duplicate customer charges).
* **Gift Messages**: Enable or disable customer gift messages and set maximum character limits.
* **Tax Status**: Choose whether gift wrapping is taxable based on your WooCommerce tax settings.

### 3. Eligibility & Rules Engine
Navigate to the **Rules** tab:
* Choose **All Products Eligible (Except Excluded)** or **Only Included Products/Categories**.
* Select specific products or entire categories to exclude (e.g., *Bulky Furniture*, *Digital Downloads*).
* Individual products can also be fine-tuned via the **Product Data > Gift Wrapping** tab on the WooCommerce product editor.

### 4. Order Fulfillment & Emails
* Open any order under **WooCommerce > Orders**.
* A distinct **Gift Wrapping & Fulfillment** box displays:
  * Packaging style selected with visual thumbnail.
  * Number of wrapped items.
  * Total gift-wrapping fee.
  * Formatted gift message ready for your fulfillment staff to print or hand-write on cards.
* Customers automatically receive the wrapping summary in their order confirmation email.

### 5. Developer Hooks & API

#### PHP Filters & Actions
```php
// Filter eligible styles for a specific product
add_filter( 'wpcalibrate_gift_wrap_available_styles', function( $styles, $product ) {
    // Custom filtering logic
    return $styles;
}, 10, 2 );

// Modify whole-order gift wrap fee programmatically
add_filter( 'wpcalibrate_gift_wrap_order_cost', function( $cost, $style, $cart ) {
    return $cost;
}, 10, 3 );

// Action triggered when gift wrap is attached to an order
add_action( 'wpcalibrate_gift_wrap_order_processed', function( $order_id, $wrap_data ) {
    // Custom warehouse/ERP integration
}, 10, 2 );
```

### 6. Automatic Dashboard Updates
This plugin includes a built-in updater connected to this GitHub repository. When a new release tag is published on GitHub:
1. WordPress detects the update automatically on the **Plugins** screen and **Dashboard > Updates**.
2. Click **Update Now** to download and install the new release directly.
3. No manual file transfers or re-installation required!

---

## Changelog

### [1.0.1] - 2026-10-10
* **New Feature**: Added an interactive **Overview** tab in the plugin settings providing setup guidance, workflow methods, business value metrics, and quick jump links.
* **Bug Fix**: Fixed WordPress admin sidebar logo sizing (constrained to standard 20x20 px) and adjusted icon colors to match native WordPress dashboard states (`#a7aaad` default, `#72aee6` on hover, `#ffffff` when active).
* **Update Engine**: Integrated native GitHub-based update checker (`GitHubUpdater`) supporting seamless one-click dashboard updates directly from GitHub releases.
* **Tests**: Expanded automated test suite to 53 assertions covering settings tabs and update interfaces.

### [1.0.0] - 2026-10-10
* Initial release of WPCalibrate Gift Wrap.
* Dual wrapping modes: Per-Item and Whole-Order wrapping.
* Visual Wrap Style management with WordPress Media Library integration.
* Deterministic 6-tier eligibility precedence rules engine.
* Full WooCommerce High-Performance Order Storage (HPOS) and Cart/Checkout Blocks support.
* Authoritative server-side pricing and tax calculation.
* Transactional emails and order fulfillment packing slip cards.
* Complete WCAG 2.2 AA accessibility and RTL stylesheet support.

---

## Support & Inquiries

* **Website**: [wpcalibrate.com](https://wpcalibrate.com)
* **Marketplace**: [marketplace.wpcalibrate.com](https://marketplace.wpcalibrate.com/)
* **Email Support**: [support@wpcalibrate.com](mailto:support@wpcalibrate.com)
* **Phone / WhatsApp**: [+1 (332) 238-6622](tel:+13322386622)

---

## License

This project is licensed under the GNU General Public License v2.0 or later (GPL-2.0-or-later) - see the [LICENSE.txt](LICENSE.txt) file for details.
