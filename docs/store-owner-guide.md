# WPCalibrate Gift Wrap - Store Owner Usage Guide

Welcome to **WPCalibrate Gift Wrap**! Follow this step-by-step workflow to configure and start offering gift wrapping in your WooCommerce store.

---

## 1. Initial Setup

1. Make sure **WooCommerce** is active on your WordPress website.
2. In your WordPress admin dashboard, navigate to **WPCalibrate > Gift Wrap**.
3. Under the **General** tab:
   * Verify the **Master Switch** is enabled.
   * Choose which modes you want to activate:
     * **Per-Item Gift Wrapping**: Allows customers to wrap individual items from product pages or the cart.
     * **Whole-Order Gift Wrapping**: Allows customers to wrap the entire order as a single unit at cart/checkout.
     * *Note*: By default, combining both modes on the same order is prevented to protect shoppers from accidental duplicate wrapping fees.
   * Check where you want wrapping selectors to appear (**Single Product Pages**, **Cart Page**, **Checkout Page**).
   * Enable **Gift Messages** and set a maximum character limit (default: 500).
   * Click **Save General Settings**.

---

## 2. Creating Gift Wrapping Styles

1. Switch to the **Wrap Styles** tab.
2. Click **Add Wrapping Style**.
3. Fill in the style details:
   * **Style Name**: E.g., *Festive Red Ribbon*, *Classic Kraft Paper*, *Luxury Satin*.
   * **Price**: Enter the wrapping cost (e.g., `4.50`) or enter `0.00` for free/complimentary wrapping.
   * **Customer Badge**: Optional short label such as *Free*, *Popular*, or *Eco-Friendly*.
   * **Preview Image**: Click **Select Image** to choose a high-quality sample photo from your WordPress Media Library.
   * **Description**: Brief explanation shown to shoppers.
   * **Status**: Keep checked to enable.
   * **Tax Status**: Check to apply store tax rules to the wrapping fee.
4. (Optional) Set specific category inclusions, category exclusions, or minimum/maximum product prices for which this style is eligible.
5. Click **Save Wrapping Style**.

---

## 3. Product-Level Customization (Overrides)

By default, products inherit your global store settings. To customize an individual product:

1. Go to **Products > All Products** and edit a product.
2. Scroll to the **Product Data** box and click the **Gift Wrapping** tab.
3. You can:
   * Change availability to **Explicitly Enable** or **Explicitly Exclude** this product.
   * Restrict which specific wrapping styles are allowed for this product.
   * Check **Exclude from Whole-Order Wrap** for oversized or fragile items that cannot be bundled into whole-order wrapping.
4. Click **Update** to save the product.

---

## 4. Testing Your Storefront Flow

Before launching to live customers, verify your checkout:

1. Visit a simple product page on your site:
   * Select a wrapping style, enter a sample gift message, and click **Add to Cart**.
2. Visit your Cart page:
   * Notice the selected gift wrapping style and personalized message listed beneath the item with its transparent price.
   * Test changing quantities or adding whole-order wrap if enabled.
3. Proceed to Checkout:
   * Verify totals, fees, and tax calculations.
   * Complete a test order.

---

## 5. Fulfillment & Processing Wrapped Orders

1. In your WordPress admin, go to **WooCommerce > Orders** and open your test order.
2. In the order items list:
   * Wrapped items will display a dedicated green badge showing the **Style Name**, **Units to Wrap**, and the customer's **Gift Message**.
3. In the sidebar:
   * Look for the **Gift Wrapping Fulfillment** meta box for a consolidated packing overview ready for your warehouse or packing staff.
4. Check the order confirmation email and admin notification email to ensure gift wrap and message details appear as configured.

---

## Need Support?
* **Support Email**: support@wpcalibrate.com
* **Phone & WhatsApp**: +447474795976
* **Website**: https://wpcalibrate.com
* **Marketplace**: https://marketplace.wpcalibrate.com/
