<?php
/**
 * Email Display for WPCalibrate Gift Wrap.
 *
 * Renders gift wrap and message details in WooCommerce transactional emails.
 *
 * @package WPCalibrate\GiftWrap\Emails
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\Emails;

use WC_Order;
use WC_Order_Item;
use WPCalibrate\GiftWrap\GiftWrap\Repositories\SettingsRepository;
use WPCalibrate\GiftWrap\GiftWrap\Services\OrderWrapService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class EmailDisplay
 */
class EmailDisplay {

	private OrderWrapService $order_wrap_service;

	public function __construct( OrderWrapService $order_wrap_service ) {
		$this->order_wrap_service = $order_wrap_service;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'woocommerce_email_order_meta', array( $this, 'render_email_order_wrap' ), 20, 3 );
	}

	/**
	 * Render gift wrap details in email order meta.
	 *
	 * @param WC_Order $order Order instance.
	 * @param bool     $sent_to_admin Whether sent to store administrator.
	 * @param bool     $plain_text Whether plain text email.
	 * @return void
	 */
	public function render_email_order_wrap( WC_Order $order, bool $sent_to_admin, bool $plain_text = false ): void {
		if ( ! $this->order_wrap_service->has_any_wrap( $order ) ) {
			return;
		}

		// If customer email and setting is disabled, skip.
		if ( ! $sent_to_admin && 'yes' !== SettingsRepository::get( 'orders_customer_emails', 'yes' ) ) {
			return;
		}

		$show_customer_msg = 'yes' === SettingsRepository::get( 'orders_customer_message', 'yes' );
		$allow_message = $sent_to_admin || $show_customer_msg;

		$order_wrap = $this->order_wrap_service->get_order_wrap( $order );

		if ( $plain_text ) {
			echo "\n========================================\n";
			echo esc_html( strtoupper( SettingsRepository::get( 'label_gift_wrap', __( 'Gift Wrapping Details', 'wpcalibrate-gift-wrap' ) ) ) ) . "\n";
			echo "========================================\n";

			if ( $order_wrap ) {
				echo esc_html__( 'Whole-Order Gift Wrap:', 'wpcalibrate-gift-wrap' ) . ' ' . esc_html( $order_wrap['style_name'] ) . "\n";
				if ( $allow_message && ! empty( $order_wrap['message'] ) ) {
					echo esc_html__( 'Gift Message:', 'wpcalibrate-gift-wrap' ) . ' "' . esc_html( $order_wrap['message'] ) . "\"\n";
				}
				echo "\n";
			}

			foreach ( $order->get_items( 'line_item' ) as $item ) {
				$item_wrap = $this->order_wrap_service->get_item_wrap( $item );
				if ( ! $item_wrap ) {
					continue;
				}
				echo esc_html( $item->get_name() ) . ': ' . esc_html( $item_wrap['style_name'] ) . ' (' . (int) $item_wrap['quantity'] . ' ' . esc_html__( 'wrapped', 'wpcalibrate-gift-wrap' ) . ")\n";
				if ( $allow_message && ! empty( $item_wrap['message'] ) ) {
					echo '  ' . esc_html__( 'Message:', 'wpcalibrate-gift-wrap' ) . ' "' . esc_html( $item_wrap['message'] ) . "\"\n";
				}
			}
			echo "========================================\n\n";
			return;
		}

		// HTML Email formatting.
		?>
		<div style="margin: 20px 0; padding: 15px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-family: sans-serif; font-size: 14px; line-height: 1.5;">
			<h3 style="margin-top: 0; margin-bottom: 10px; color: #0f172a; font-size: 16px; border-bottom: 1px solid #cbd5e1; padding-bottom: 5px;">
				🎁 <?php echo esc_html( SettingsRepository::get( 'label_gift_wrap', __( 'Gift Wrapping Details', 'wpcalibrate-gift-wrap' ) ) ); ?>
			</h3>

			<?php if ( $order_wrap ) : ?>
				<div style="margin-bottom: 12px;">
					<strong><?php esc_html_e( 'Whole-Order Wrapping:', 'wpcalibrate-gift-wrap' ); ?></strong>
					<span><?php echo esc_html( $order_wrap['style_name'] ); ?></span>
					<?php if ( $allow_message && ! empty( $order_wrap['message'] ) ) : ?>
						<div style="margin-top: 4px; padding: 6px 10px; background: #ffffff; border-left: 3px solid #6366f1; font-style: italic;">
							&ldquo;<?php echo esc_html( $order_wrap['message'] ); ?>&rdquo;
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php
			$rendered_item = false;
			foreach ( $order->get_items( 'line_item' ) as $item ) {
				$item_wrap = $this->order_wrap_service->get_item_wrap( $item );
				if ( ! $item_wrap ) {
					continue;
				}
				$rendered_item = true;
				?>
				<div style="margin-top: 8px; padding-top: 8px; border-top: 1px dashed #e2e8f0;">
					<strong><?php echo esc_html( $item->get_name() ); ?>:</strong>
					<span><?php echo esc_html( $item_wrap['style_name'] ); ?></span>
					<?php if ( $item_wrap['quantity'] < $item->get_quantity() ) : ?>
						<span style="color: #64748b;">(<?php printf( esc_html__( '%1$d of %2$d wrapped', 'wpcalibrate-gift-wrap' ), (int) $item_wrap['quantity'], (int) $item->get_quantity() ); ?>)</span>
					<?php endif; ?>

					<?php if ( $allow_message && ! empty( $item_wrap['message'] ) ) : ?>
						<div style="margin-top: 4px; padding: 6px 10px; background: #ffffff; border-left: 3px solid #6366f1; font-style: italic;">
							&ldquo;<?php echo esc_html( $item_wrap['message'] ); ?>&rdquo;
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
