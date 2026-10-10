<?php
/**
 * Classic Checkout Handler for WPCalibrate Gift Wrap.
 *
 * Handles checkout form fields, validation, and snapshot persistence on order creation.
 *
 * @package WPCalibrate\GiftWrap\Checkout
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\Checkout;

use WC_Order;
use WC_Order_Item_Product;
use WPCalibrate\GiftWrap\Cart\CartHandler;
use WPCalibrate\GiftWrap\GiftWrap\Models\Style;
use WPCalibrate\GiftWrap\GiftWrap\Repositories\SettingsRepository;
use WPCalibrate\GiftWrap\GiftWrap\Repositories\StyleRepositoryInterface;
use WPCalibrate\GiftWrap\GiftWrap\Services\EligibilityService;
use WPCalibrate\GiftWrap\GiftWrap\Services\OrderWrapService;
use WPCalibrate\GiftWrap\GiftWrap\Services\PriceService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ClassicCheckoutHandler
 */
class ClassicCheckoutHandler {

	private StyleRepositoryInterface $style_repository;
	private EligibilityService $eligibility_service;
	private PriceService $price_service;
	private OrderWrapService $order_wrap_service;
	private CartHandler $cart_handler;

	public function __construct(
		StyleRepositoryInterface $style_repository,
		EligibilityService $eligibility_service,
		PriceService $price_service,
		OrderWrapService $order_wrap_service,
		CartHandler $cart_handler
	) {
		$this->style_repository    = $style_repository;
		$this->eligibility_service = $eligibility_service;
		$this->price_service       = $price_service;
		$this->order_wrap_service  = $order_wrap_service;
		$this->cart_handler        = $cart_handler;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		if ( 'yes' === SettingsRepository::get( 'locations_checkout', 'yes' ) ) {
			add_action( 'woocommerce_review_order_before_submit', array( $this, 'render_checkout_wrap_field' ), 10 );
		}

		add_action( 'woocommerce_checkout_process', array( $this, 'validate_checkout' ) );
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'handle_checkout_update' ) );

		// Persist immutable snapshots on order creation.
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'create_order_line_item' ), 10, 4 );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'create_order' ), 10, 2 );
	}

	/**
	 * Render whole-order wrap field at checkout.
	 *
	 * @return void
	 */
	public function render_checkout_wrap_field(): void {
		if ( ! $this->eligibility_service->is_whole_order_enabled() || ! WC()->cart ) {
			return;
		}

		// Prevent duplicate wrapping charge if combination is disabled.
		if ( ! $this->eligibility_service->can_combine_modes() && $this->cart_handler->cart_has_per_item_wrap( WC()->cart ) ) {
			return;
		}

		$styles = $this->eligibility_service->get_eligible_styles_for_order( WC()->cart );
		if ( empty( $styles ) ) {
			return;
		}

		$current = WC()->session ? WC()->session->get( CartHandler::SESSION_WHOLE_ORDER_KEY ) : null;
		$selected_id = $current['style_id'] ?? '';
		$selected_msg = $current['message'] ?? '';
		$price_service = $this->price_service;
		$show_messages = $this->eligibility_service->are_gift_messages_enabled() && 'yes' === SettingsRepository::get( 'enable_whole_order_message', 'yes' );
		$max_len = absint( SettingsRepository::get( 'gift_message_max_length', 500 ) );

		?>
		<div class="wpcalibrate-checkout-wrap-section" style="margin: 1.5em 0; padding: 1em; border: 1px solid #e2e8f0; border-radius: 6px;">
			<h4 style="margin-top: 0; margin-bottom: 0.5em; font-size: 1.1em;">
				<?php echo esc_html( SettingsRepository::get( 'label_gift_wrap', __( 'Gift Wrapping', 'wpcalibrate-gift-wrap' ) ) ); ?>
			</h4>

			<p class="form-row form-row-wide">
				<label for="wpcalibrate_checkout_order_style" style="display:block;margin-bottom:4px;">
					<?php echo esc_html( SettingsRepository::get( 'label_choose_style', __( 'Choose a gift wrap style', 'wpcalibrate-gift-wrap' ) ) ); ?>
				</label>
				<select name="wpcalibrate_checkout_order_style" id="wpcalibrate_checkout_order_style" class="select" style="width:100%;">
					<option value="none"><?php echo esc_html( SettingsRepository::get( 'label_no_wrap', __( 'No gift wrap', 'wpcalibrate-gift-wrap' ) ) ); ?></option>
					<?php foreach ( $styles as $style ) : ?>
						<?php
						$cost = $price_service->calculate_order_wrap_cost( $style );
						$price_text = 'yes' === SettingsRepository::get( 'display_prices', 'yes' ) ? ' - ' . $price_service->format_display_price( $cost ) : '';
						?>
						<option value="<?php echo esc_attr( $style->get_id() ); ?>" <?php selected( $selected_id, $style->get_id() ); ?>>
							<?php echo esc_html( $style->get_name() . $price_text ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>

			<?php if ( $show_messages ) : ?>
				<p class="form-row form-row-wide" id="wpcalibrate_checkout_message_container" style="<?php echo empty( $selected_id ) ? 'display:none;' : ''; ?>">
					<label for="wpcalibrate_checkout_order_message" style="display:block;margin-bottom:4px;">
						<?php echo esc_html( SettingsRepository::get( 'label_gift_message', __( 'Gift Message', 'wpcalibrate-gift-wrap' ) ) ); ?>
						<span style="font-size:0.85em;color:#666;">(<?php printf( esc_html__( 'Max %d characters', 'wpcalibrate-gift-wrap' ), $max_len ); ?>)</span>
					</label>
					<textarea
						name="wpcalibrate_checkout_order_message"
						id="wpcalibrate_checkout_order_message"
						class="input-text"
						rows="2"
						maxlength="<?php echo esc_attr( (string) $max_len ); ?>"
						style="width:100%;"
						placeholder="<?php esc_attr_e( 'Enter your personal gift message...', 'wpcalibrate-gift-wrap' ); ?>"
					><?php echo esc_textarea( $selected_msg ); ?></textarea>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Handle checkout AJAX update (when customer changes form fields).
	 *
	 * @param string $post_data Serialized checkout form data.
	 * @return void
	 */
	public function handle_checkout_update( string $post_data ): void {
		parse_str( $post_data, $data );

		if ( ! isset( $data['wpcalibrate_checkout_order_style'] ) || ! WC()->session ) {
			return;
		}

		$style_id = sanitize_key( (string) $data['wpcalibrate_checkout_order_style'] );
		if ( empty( $style_id ) || 'none' === $style_id ) {
			WC()->session->set( CartHandler::SESSION_WHOLE_ORDER_KEY, null );
			return;
		}

		// Prevent duplicate wrap.
		if ( ! $this->eligibility_service->can_combine_modes() && $this->cart_handler->cart_has_per_item_wrap( WC()->cart ) ) {
			WC()->session->set( CartHandler::SESSION_WHOLE_ORDER_KEY, null );
			return;
		}

		$style = $this->eligibility_service->validate_style_for_order( $style_id, WC()->cart );
		if ( ! $style ) {
			WC()->session->set( CartHandler::SESSION_WHOLE_ORDER_KEY, null );
			return;
		}

		$message = '';
		if ( isset( $data['wpcalibrate_checkout_order_message'] ) ) {
			$raw_msg = sanitize_textarea_field( (string) $data['wpcalibrate_checkout_order_message'] );
			$max_len = absint( SettingsRepository::get( 'gift_message_max_length', 500 ) );
			$message = mb_substr( $raw_msg, 0, $max_len );
		}

		WC()->session->set(
			CartHandler::SESSION_WHOLE_ORDER_KEY,
			array(
				'style_id' => $style->get_id(),
				'message'  => $message,
			)
		);
	}

	/**
	 * Server-side checkout validation before order placement.
	 *
	 * @return void
	 */
	public function validate_checkout(): void {
		// Validate whole-order wrap if submitted.
		if ( isset( $_POST['wpcalibrate_checkout_order_style'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$style_id = sanitize_key( wp_unslash( $_POST['wpcalibrate_checkout_order_style'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! empty( $style_id ) && 'none' !== $style_id ) {
				$style = $this->eligibility_service->validate_style_for_order( $style_id, WC()->cart );
				if ( ! $style ) {
					wc_add_notice( __( 'The selected gift wrap style is invalid or no longer available.', 'wpcalibrate-gift-wrap' ), 'error' );
					return;
				}

				if ( ! $this->eligibility_service->can_combine_modes() && $this->cart_handler->cart_has_per_item_wrap( WC()->cart ) ) {
					wc_add_notice( __( 'Cannot combine whole-order gift wrap with individually wrapped items.', 'wpcalibrate-gift-wrap' ), 'error' );
					return;
				}

				if ( isset( $_POST['wpcalibrate_checkout_order_message'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$message = sanitize_textarea_field( wp_unslash( $_POST['wpcalibrate_checkout_order_message'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$max_len = absint( SettingsRepository::get( 'gift_message_max_length', 500 ) );
					if ( mb_strlen( $message ) > $max_len ) {
						wc_add_notice(
							sprintf(
								/* translators: %d: maximum characters allowed */
								__( 'Gift message cannot exceed %d characters.', 'wpcalibrate-gift-wrap' ),
								$max_len
							),
							'error'
						);
					}
				}
			}
		}

		// Re-validate per-item wraps in cart.
		if ( WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				if ( empty( $cart_item[ CartHandler::CART_ITEM_KEY ]['style_id'] ) ) {
					continue;
				}

				$style_id = (string) $cart_item[ CartHandler::CART_ITEM_KEY ]['style_id'];
				$product  = $cart_item['data'];
				$style    = $this->eligibility_service->validate_style_for_product( $style_id, $product );

				if ( ! $style ) {
					wc_add_notice(
						sprintf(
							/* translators: %s: product title */
							__( 'Gift wrapping for "%s" is no longer available. Please update your cart.', 'wpcalibrate-gift-wrap' ),
							$product->get_name()
						),
						'error'
					);
				}
			}
		}
	}

	/**
	 * Create line item gift wrap snapshot.
	 *
	 * @param WC_Order_Item_Product $item Line item.
	 * @param string                $cart_item_key Cart key.
	 * @param array<string, mixed>  $values Cart item values.
	 * @param WC_Order              $order Order instance.
	 * @return void
	 */
	public function create_order_line_item( WC_Order_Item_Product $item, string $cart_item_key, array $values, WC_Order $order ): void {
		if ( empty( $values[ CartHandler::CART_ITEM_KEY ] ) || ! is_array( $values[ CartHandler::CART_ITEM_KEY ] ) ) {
			return;
		}

		$wrap_data = $values[ CartHandler::CART_ITEM_KEY ];
		$style_id = (string) ( $wrap_data['style_id'] ?? '' );
		$style = $this->style_repository->find( $style_id );
		if ( ! $style ) {
			return;
		}

		$line_qty = (int) $item->get_quantity();
		$wrapped_qty = min( max( 1, (int) ( $wrap_data['quantity'] ?? $line_qty ) ), $line_qty );
		$cost = $this->price_service->calculate_item_wrap_cost( $style, $wrapped_qty );
		$message = (string) ( $wrap_data['message'] ?? '' );

		$this->order_wrap_service->attach_item_wrap( $item, $style, $wrapped_qty, $cost, $message );
	}

	/**
	 * Create order-level gift wrap snapshot.
	 *
	 * @param WC_Order             $order Order instance.
	 * @param array<string, mixed> $data Checkout posted data.
	 * @return void
	 */
	public function create_order( WC_Order $order, array $data ): void {
		$whole_order = WC()->session ? WC()->session->get( CartHandler::SESSION_WHOLE_ORDER_KEY ) : null;

		// Fallback to posted data if session was not updated.
		if ( empty( $whole_order['style_id'] ) && isset( $_POST['wpcalibrate_checkout_order_style'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$post_style = sanitize_key( wp_unslash( $_POST['wpcalibrate_checkout_order_style'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! empty( $post_style ) && 'none' !== $post_style ) {
				$post_msg = isset( $_POST['wpcalibrate_checkout_order_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wpcalibrate_checkout_order_message'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$whole_order = array(
					'style_id' => $post_style,
					'message'  => $post_msg,
				);
			}
		}

		if ( empty( $whole_order['style_id'] ) ) {
			return;
		}

		$style_id = (string) $whole_order['style_id'];
		$style = $this->style_repository->find( $style_id );
		if ( ! $style ) {
			return;
		}

		$cost = $this->price_service->calculate_order_wrap_cost( $style );
		$message = (string) ( $whole_order['message'] ?? '' );

		$this->order_wrap_service->attach_order_wrap( $order, $style, $cost, $message );
	}
}
