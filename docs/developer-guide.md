# WPCalibrate Gift Wrap - Developer & Architecture Guide

## Overview
**WPCalibrate Gift Wrap** (`wpcalibrate-gift-wrap`) is a modular, high-performance gift-wrapping extension for WooCommerce built with object-oriented PHP 8.2+, strictly adhering to WordPress and WooCommerce coding conventions and official APIs.

---

## 1. Architecture & Design Principles

The plugin is structured with clean separation of concerns without heavyweight external frameworks:

* **Autoloading**: Built-in PSR-4 autoloader (`src/Autoloader.php`) mapping `WPCalibrate\GiftWrap\` to `src/`. Zero runtime Composer dependency.
* **Domain Models**: Immutable/structured domain models (`src/GiftWrap/Models/Style.php`).
* **Repositories**: Abstracted data access layer (`StyleRepositoryInterface` and `StyleRepository`), storing data natively in WordPress options without polluting the database with unnecessary custom tables.
* **Services**:
  * `EligibilityService`: Deterministic rule evaluation for categories, products, price thresholds, and overrides.
  * `PriceService`: Authoritative server-side pricing, quantity mathematics, and tax calculations.
  * `OrderWrapService`: Creation and retrieval of immutable order snapshots using WooCommerce CRUD APIs.
  * `LicenseService`: Interface-based licensing abstraction ready for future WPCalibrate Marketplace integration (`LicenseServiceInterface`).
* **UI & Integration**:
  * `CartHandler`: Session persistence, cart item data differentiation, and totals recalculation.
  * `ClassicCheckoutHandler`: Classic checkout fields, form validation, and order metadata attachment.
  * `BlocksIntegration`: WooCommerce Cart and Checkout Blocks extensibility and Store API schema data integration.
  * `ProductDisplay`: Accessible single-product wrapping selection UI.
  * `OrderAdminDisplay`: Admin order screen fulfillment cards and packing overview (HPOS compatible).
  * `OrderCustomerDisplay`: Customer-facing My Account order details.
  * `EmailDisplay`: Transactional HTML and plain text email templates.

---

## 2. Storage & Metadata Schema

### WordPress Options
* `wpcalibrate_gift_wrap_settings`: Plugin configuration array (defaults, toggles, labels, taxonomy rules).
* `wpcalibrate_gift_wrap_styles`: Associative array of wrapping styles keyed by unique internal style ID.
* `wpcalibrate_gift_wrap_version`: Plugin code version (`1.0.0`).
* `wpcalibrate_gift_wrap_db_version`: Plugin database/options schema version (`1.0.0`).

### Product Metadata
* `_wpcalibrate_gift_wrap_enabled`: `'default'`, `'yes'`, or `'no'` product-level availability override.
* `_wpcalibrate_gift_wrap_styles`: Array of allowed style IDs specifically for this product.
* `_wpcalibrate_exclude_whole_order`: `'yes'` or `'no'` flag excluding the product from whole-order wrapping.

### WooCommerce Order Item Metadata (Line Items)
* `_wpcalibrate_gift_wrap_style_id`: Style internal ID.
* `_wpcalibrate_gift_wrap_style_name`: Immutable snapshot of style name at order time.
* `_wpcalibrate_gift_wrap_unit_price`: Immutable snapshot of unit wrap price.
* `_wpcalibrate_gift_wrap_quantity`: Wrapped physical units count.
* `_wpcalibrate_gift_wrap_cost`: Total gift wrap cost charged for this item.
* `_wpcalibrate_gift_wrap_message`: Customer gift message (plain text).

### WooCommerce Order Metadata (Whole-Order Wrap)
* `_wpcalibrate_order_gift_wrap_style_id`: Style internal ID.
* `_wpcalibrate_order_gift_wrap_style_name`: Immutable snapshot of style name at order time.
* `_wpcalibrate_order_gift_wrap_cost`: Total fee charged for whole order wrapping.
* `_wpcalibrate_order_gift_wrap_message`: Whole-order gift message.

---

## 3. Deterministic Eligibility Rule Precedence

When determining if a wrapping style or product is eligible for gift wrapping, the engine evaluates rules in the following strict hierarchy:

1. **Explicit Product Override**: Product edit meta `_wpcalibrate_gift_wrap_enabled` (`'no'` forces ineligible; `'yes'` forces eligible).
2. **Global Product Exclusion**: If product ID is in `excluded_products`, it is excluded.
3. **Global Product Inclusion**: If `rules_mode` is `'include'` and product ID is in `included_products`, it is eligible.
4. **Category Exclusion**: If any product category matches `excluded_categories`, it is excluded.
5. **Category Inclusion**: If `rules_mode` is `'include'` and any product category matches `included_categories`, it is eligible.
6. **Global Default**: Master switch (`enabled` setting) and general rules mode.

---

## 4. Public Hooks & Filters

Developers can extend or customize gift wrapping via official hooks:

* `apply_filters( 'wpcalibrate_gift_wrap_exclude_virtual_products', $is_virtual, $product )`: Filter whether virtual products are excluded.
* `apply_filters( 'wpcalibrate_gift_wrap_product_eligible_styles', $styles, $product )`: Filter the list of eligible styles for a specific product.
* `apply_filters( 'wpcalibrate_gift_wrap_order_eligible_styles', $styles, $cart )`: Filter the eligible styles for whole-order wrapping.
* `apply_filters( 'wpcalibrate_gift_wrap_allow_order_wrap_with_excluded_item', false, $product, $cart )`: Override whole-order wrap blocking when an excluded item is in the cart.

---

## 5. HPOS & Blocks Compatibility

* **HPOS (Custom Order Tables)**: Declared via `FeaturesUtil::declare_compatibility( 'custom_order_tables', WPCALIBRATE_GIFT_WRAP_FILE, true )`. All order reading and updating uses WooCommerce standard CRUD APIs (`$order->get_meta()`, `$order->update_meta_data()`, `$item->update_meta_data()`).
* **Cart & Checkout Blocks**: Declared via `FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', WPCALIBRATE_GIFT_WRAP_FILE, true )`. Registers Store API checkout schema extension with `Automattic\WooCommerce\StoreApi\StoreApi`.

---

## 6. Future Licensing Adapter Implementation

In V1, `LicenseService` implements `LicenseServiceInterface` returning `STATUS_NOT_CONFIGURED` without calling fake endpoints or blocking features.

To connect with the future WPCalibrate Marketplace API:
1. Implement `LicenseServiceInterface` or extend `LicenseService`.
2. Use `wp_remote_post()` with appropriate timeouts to validate keys against `https://marketplace.wpcalibrate.com/api/v1/license/validate`.
3. Never store or expose raw license keys in HTML or logs.

---

## 7. Testing & Quality Assurance

To execute the automated test suite:
```bash
php tests/run-tests.php
```
To run PHP syntax validation across all files:
```powershell
Get-ChildItem -Path "." -Recurse -Filter "*.php" | ForEach-Object { php -l $_.FullName }
```
