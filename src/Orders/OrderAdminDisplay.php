<?php
/**
 * Order Admin Display for WPCalibrate Gift Wrap.
 *
 * Renders gift wrap details and fulfillment info in WooCommerce Admin order view (HPOS compatible).
 *
 * @package WPCalibrate\GiftWrap\Orders
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\Orders;

use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Product;
use WPCalibrate\GiftWrap\GiftWrap\Services\OrderWrapService;
use WPCalibrate\GiftWrap\GiftWrap\Services\PriceService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OrderAdminDisplay
 */
class OrderAdminDisplay {

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
		// Display on line items in admin order page.
		add_action( 'woocommerce_after_order_itemmeta', array( $this, 'render_admin_item_wrap' ), 10, 3 );

		// Display whole-order wrap metabox / section.
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'render_admin_order_wrap' ), 10, 1 );

		// Printable / fulfillment metabox.
		add_action( 'add_meta_boxes', array( $this, 'register_fulfillment_meta_box' ), 20, 2 );
	}

	/**
	 * Render item wrap meta in admin order item table.
	 *
	 * @param int           $item_id Item ID.
	 * @param WC_Order_Item $item Item instance.
	 * @param WC_Product|null $product Product instance.
	 * @return void
	 */
	public function render_admin_item_wrap( int $item_id, WC_Order_Item $item, $product ): void {
		$wrap = $this->order_wrap_service->get_item_wrap( $item );
		if ( ! $wrap ) {
			return;
		}

		?>
		<div class="wpcalibrate-admin-item-wrap" style="margin-top: 6px; padding: 6px 10px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 4px; font-size: 12px; color: #166534;">
			<div style="font-weight: 600; display: flex; align-items: center; gap: 4px;">
				<span class="dashicons dashicons-heart" style="font-size: 16px; width: 16px; height: 16px;"></span>
				<?php esc_html_e( 'Gift Wrap:', 'wpcalibrate-gift-wrap' ); ?>
				<span><?php echo esc_html( $wrap['style_name'] ); ?></span>
				<?php if ( $wrap['cost'] > 0 ) : ?>
					<span style="font-weight: normal; color: #15803d;">
						(+<?php echo wp_kses_post( $this->price_service->format_display_price( (float) $wrap['cost'] ) ); ?>)
					</span>
				<?php else : ?>
					<span style="font-weight: normal; color: #15803d;">(<?php esc_html_e( 'Free', 'wpcalibrate-gift-wrap' ); ?>)</span>
				<?php endif; ?>
			</div>

			<?php if ( $wrap['quantity'] < $item->get_quantity() ) : ?>
				<div style="margin-top: 2px;">
					<?php
					printf(
						/* translators: 1: wrapped qty, 2: total qty */
						esc_html__( 'Units to wrap: %1$d of %2$d', 'wpcalibrate-gift-wrap' ),
						(int) $wrap['quantity'],
						(int) $item->get_quantity()
					);
					?>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $wrap['message'] ) ) : ?>
				<div style="margin-top: 4px; padding-top: 4px; border-top: 1px dashed #bbf7d0;">
					<strong><?php esc_html_e( 'Gift Message:', 'wpcalibrate-gift-wrap' ); ?></strong>
					<div style="white-space: pre-wrap; font-style: italic; margin-top: 2px; color: #14532d;">
						<?php echo esc_html( $wrap['message'] ); ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render whole-order wrap in admin order view.
	 *
	 * @param WC_Order $order Order instance.
	 * @return void
	 */
	public function render_admin_order_wrap( WC_Order $order ): void {
		$order_wrap = $this->order_wrap_service->get_order_wrap( $order );
		if ( ! $order_wrap ) {
			return;
		}

		?>
		<div class="wpcalibrate-admin-order-wrap-box" style="clear: both; margin-top: 15px; padding: 10px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 4px; color: #1e40af;">
			<h4 style="margin: 0 0 6px 0; font-size: 13px; font-weight: 600; color: #1e3a8a;">
				<span class="dashicons dashicons-heart" style="font-size: 16px; width: 16px; height: 16px; vertical-align: middle;"></span>
				<?php esc_html_e( 'Whole-Order Gift Wrapping', 'wpcalibrate-gift-wrap' ); ?>
			</h4>
			<p style="margin: 0 0 4px 0; font-size: 12px;">
				<strong><?php esc_html_e( 'Style:', 'wpcalibrate-gift-wrap' ); ?></strong>
				<?php echo esc_html( $order_wrap['style_name'] ); ?>
				<?php if ( $order_wrap['cost'] > 0 ) : ?>
					(<?php echo wp_kses_post( $this->price_service->format_display_price( (float) $order_wrap['cost'] ) ); ?>)
				<?php else : ?>
					(<?php esc_html_e( 'Free', 'wpcalibrate-gift-wrap' ); ?>)
				<?php endif; ?>
			</p>

			<?php if ( ! empty( $order_wrap['message'] ) ) : ?>
				<div style="margin-top: 6px; padding: 6px; background: #fff; border: 1px solid #dbeafe; border-radius: 4px; font-size: 12px;">
					<strong style="display: block; margin-bottom: 2px;"><?php esc_html_e( 'Order Gift Message:', 'wpcalibrate-gift-wrap' ); ?></strong>
					<div style="white-space: pre-wrap; color: #1e293b;"><?php echo esc_html( $order_wrap['message'] ); ?></div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Register fulfillment packing box on order screen (HPOS compatible).
	 *
	 * @param string   $post_type_or_screen Screen ID or post type.
	 * @param \WP_Post|WC_Order $post_or_order Post or Order object.
	 * @return void
	 */
	public function register_fulfillment_meta_box( $post_type_or_screen, $post_or_order = null ): void {
		$screen = class_exists( '\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' )
			&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()
			? wc_get_page_screen_id( 'shop-order' )
			: 'shop_order';

		add_meta_box(
			'wpcalibrate_gift_wrap_fulfillment',
			__( 'Gift Wrapping Fulfillment', 'wpcalibrate-gift-wrap' ),
			array( $this, 'render_fulfillment_box' ),
			$screen,
			'side',
			'default'
		);
	}

	/**
	 * Render fulfillment meta box.
	 *
	 * @param \WP_Post|WC_Order $post_or_order Post or Order object.
	 * @return void
	 */
	public function render_fulfillment_box( $post_or_order ): void {
		$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID ?? 0 );
		if ( ! $order instanceof WC_Order ) {
			echo '<p>' . esc_html__( 'No order data available.', 'wpcalibrate-gift-wrap' ) . '</p>';
			return;
		}

		if ( ! $this->order_wrap_service->has_any_wrap( $order ) ) {
			echo '<p style="color: #64748b; margin: 0;">' . esc_html__( 'No gift wrapping requested for this order.', 'wpcalibrate-gift-wrap' ) . '</p>';
			return;
		}

		$order_wrap = $this->order_wrap_service->get_order_wrap( $order );
		?>
		<div class="wpcalibrate-fulfillment-summary" style="font-size: 13px;">
			<?php if ( $order_wrap ) : ?>
				<div style="margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid #e2e8f0;">
					<strong style="color: #0f172a;"><?php esc_html_e( 'Whole-Order Wrap:', 'wpcalibrate-gift-wrap' ); ?></strong>
					<div style="margin: 2px 0;"><?php echo esc_html( $order_wrap['style_name'] ); ?></div>
					<?php if ( ! empty( $order_wrap['message'] ) ) : ?>
						<div style="margin-top: 4px; padding: 6px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 4px;">
							<em>&ldquo;<?php echo esc_html( $order_wrap['message'] ); ?>&rdquo;</em>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php
			$has_items = false;
			foreach ( $order->get_items( 'line_item' ) as $item ) {
				$wrap = $this->order_wrap_service->get_item_wrap( $item );
				if ( ! $wrap ) {
					continue;
				}
				$has_items = true;
				?>
				<div style="margin-bottom: 10px; padding: 6px; background: #f8fafc; border-radius: 4px;">
					<strong><?php echo esc_html( $item->get_name() ); ?></strong>
					<div style="color: #475569; font-size: 12px;">
						<?php echo esc_html( $wrap['style_name'] ); ?>
						(<?php printf( esc_html__( 'Qty: %d', 'wpcalibrate-gift-wrap' ), (int) $wrap['quantity'] ); ?>)
					</div>
					<?php if ( ! empty( $wrap['message'] ) ) : ?>
						<div style="margin-top: 4px; font-style: italic; color: #334155;">
							&ldquo;<?php echo esc_html( $wrap['message'] ); ?>&rdquo;
						</div>
					<?php endif; ?>
				</div>
				<?php
			}
			?>
		</div>
		<?php
	}
}
