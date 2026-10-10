<?php
/**
 * Customer Order Display for WPCalibrate Gift Wrap.
 *
 * Renders customer-safe gift wrapping details in My Account order details.
 *
 * @package WPCalibrate\GiftWrap\Orders
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\Orders;

use WC_Order;
use WC_Order_Item;
use WPCalibrate\GiftWrap\GiftWrap\Repositories\SettingsRepository;
use WPCalibrate\GiftWrap\GiftWrap\Services\OrderWrapService;
use WPCalibrate\GiftWrap\GiftWrap\Services\PriceService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OrderCustomerDisplay
 */
class OrderCustomerDisplay {

	private OrderWrapService $order_wrap_service;
	private PriceService $price_service;

	public function __construct( OrderWrapService $order_wrap_service, PriceService $price_service ) {
		$this->order_wrap_service = $order_wrap_service;
		$this->price_service      = $price_service;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		if ( 'yes' !== SettingsRepository::get( 'orders_my_account', 'yes' ) ) {
			return;
		}

		add_action( 'woocommerce_order_item_meta_end', array( $this, 'render_customer_item_wrap' ), 10, 3 );
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'render_customer_order_wrap' ), 15, 1 );
	}

	/**
	 * Render per-item wrap in customer order details.
	 *
	 * @param int           $item_id Line item ID.
	 * @param WC_Order_Item $item Line item.
	 * @param WC_Order      $order Order object.
	 * @return void
	 */
	public function render_customer_item_wrap( int $item_id, WC_Order_Item $item, WC_Order $order ): void {
		$wrap = $this->order_wrap_service->get_item_wrap( $item );
		if ( ! $wrap ) {
			return;
		}

		$show_message = 'yes' === SettingsRepository::get( 'orders_customer_message', 'yes' );
		?>
		<div class="wpcalibrate-customer-item-wrap" style="margin-top: 6px; font-size: 0.9em; color: #475569;">
			<div>
				<strong><?php echo esc_html( SettingsRepository::get( 'label_gift_wrap', __( 'Gift Wrapping', 'wpcalibrate-gift-wrap' ) ) ); ?>:</strong>
				<span><?php echo esc_html( $wrap['style_name'] ); ?></span>
				<?php if ( $wrap['quantity'] < $item->get_quantity() ) : ?>
					<span>(<?php printf( esc_html__( '%1$d of %2$d wrapped', 'wpcalibrate-gift-wrap' ), (int) $wrap['quantity'], (int) $item->get_quantity() ); ?>)</span>
				<?php endif; ?>
			</div>

			<?php if ( $show_message && ! empty( $wrap['message'] ) ) : ?>
				<div style="margin-top: 2px;">
					<strong><?php echo esc_html( SettingsRepository::get( 'label_gift_message', __( 'Gift Message', 'wpcalibrate-gift-wrap' ) ) ); ?>:</strong>
					<span style="font-style: italic;">&ldquo;<?php echo esc_html( $wrap['message'] ); ?>&rdquo;</span>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render whole-order wrap in customer order details table footer.
	 *
	 * @param WC_Order $order Order object.
	 * @return void
	 */
	public function render_customer_order_wrap( WC_Order $order ): void {
		$order_wrap = $this->order_wrap_service->get_order_wrap( $order );
		if ( ! $order_wrap ) {
			return;
		}

		$show_message = 'yes' === SettingsRepository::get( 'orders_customer_message', 'yes' );
		?>
		<section class="wpcalibrate-customer-order-wrap" style="margin-top: 1.5em; padding: 1em; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px;">
			<h3 style="margin-top: 0; font-size: 1.1em; color: #0f172a;">
				<?php echo esc_html( SettingsRepository::get( 'label_gift_wrap', __( 'Gift Wrapping', 'wpcalibrate-gift-wrap' ) ) ); ?>
			</h3>
			<p style="margin: 0.25em 0;">
				<strong><?php esc_html_e( 'Selected Style:', 'wpcalibrate-gift-wrap' ); ?></strong>
				<?php echo esc_html( $order_wrap['style_name'] ); ?>
			</p>
			<?php if ( $show_message && ! empty( $order_wrap['message'] ) ) : ?>
				<p style="margin: 0.25em 0;">
					<strong><?php echo esc_html( SettingsRepository::get( 'label_gift_message', __( 'Gift Message', 'wpcalibrate-gift-wrap' ) ) ); ?>:</strong>
					<em>&ldquo;<?php echo esc_html( $order_wrap['message'] ); ?>&rdquo;</em>
				</p>
			<?php endif; ?>
		</section>
		<?php
	}
}
