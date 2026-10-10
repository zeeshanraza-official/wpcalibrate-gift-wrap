<?php
/**
 * Cart Handler for WPCalibrate Gift Wrap.
 *
 * Handles cart item data, session persistence, totals calculation,
 * quantity clamping, fee handling, and cart UI.
 *
 * @package WPCalibrate\GiftWrap\Cart
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\Cart;

use WC_Cart;
use WC_Product;
use WPCalibrate\GiftWrap\GiftWrap\Models\Style;
use WPCalibrate\GiftWrap\GiftWrap\Repositories\SettingsRepository;
use WPCalibrate\GiftWrap\GiftWrap\Repositories\StyleRepositoryInterface;
use WPCalibrate\GiftWrap\GiftWrap\Services\EligibilityService;
use WPCalibrate\GiftWrap\GiftWrap\Services\PriceService;
use WPCalibrate\GiftWrap\GiftWrap\Services\OrderWrapService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class CartHandler
 */
class CartHandler {

	public const CART_ITEM_KEY = 'wpcalibrate_gift_wrap';
	public const SESSION_WHOLE_ORDER_KEY = 'wpcalibrate_whole_order_wrap';

	private StyleRepositoryInterface $style_repository;
	private EligibilityService $eligibility_service;
	private PriceService $price_service;
	private OrderWrapService $order_wrap_service;

	public function __construct(
		StyleRepositoryInterface $style_repository,
		EligibilityService $eligibility_service,
		PriceService $price_service,
		OrderWrapService $order_wrap_service
	) {
		$this->style_repository    = $style_repository;
		$this->eligibility_service = $eligibility_service;
		$this->price_service       = $price_service;
		$this->order_wrap_service  = $order_wrap_service;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		// Add to cart data.
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 3 );
		add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 10, 2 );

		// Cart item display.
		add_filter( 'woocommerce_get_item_data', array( $this, 'get_item_data' ), 10, 2 );

		// Price & totals adjustments.
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'calculate_item_totals' ), 20, 1 );
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'calculate_order_fees' ), 20, 1 );

		// Quantity adjustments & validation.
		add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'on_cart_item_quantity_update' ), 10, 4 );
		add_action( 'woocommerce_check_cart_items', array( $this, 'validate_cart_items' ) );

		// Cart actions (remove/update wrap via GET/POST or AJAX).
		add_action( 'wp_loaded', array( $this, 'handle_cart_actions' ) );
		add_action( 'wp_ajax_wpcalibrate_set_whole_order_wrap', array( $this, 'ajax_set_whole_order_wrap' ) );
		add_action( 'wp_ajax_nopriv_wpcalibrate_set_whole_order_wrap', array( $this, 'ajax_set_whole_order_wrap' ) );
		add_action( 'wp_ajax_wpcalibrate_remove_item_wrap', array( $this, 'ajax_remove_item_wrap' ) );
		add_action( 'wp_ajax_nopriv_wpcalibrate_remove_item_wrap', array( $this, 'ajax_remove_item_wrap' ) );

		// Cart page whole-order wrap selector output.
		if ( 'yes' === SettingsRepository::get( 'locations_cart', 'yes' ) ) {
			add_action( 'woocommerce_cart_totals_before_order_total', array( $this, 'render_cart_whole_order_wrap' ) );
		}
	}

	/**
	 * Capture gift wrap selections on Add to Cart.
	 *
	 * @param array<string, mixed> $cart_item_data Existing item data.
	 * @param int                  $product_id Product ID.
	 * @param int                  $variation_id Variation ID.
	 * @return array<string, mixed>
	 */
	public function add_cart_item_data( array $cart_item_data, int $product_id, int $variation_id ): array {
		if ( ! $this->eligibility_service->is_per_item_enabled() ) {
			return $cart_item_data;
		}

		$effective_id = $variation_id > 0 ? $variation_id : $product_id;
		$product = wc_get_product( $effective_id );
		if ( ! $product || ! $this->eligibility_service->is_product_eligible( $product ) ) {
			return $cart_item_data;
		}

		$style_id = isset( $_POST['wpcalibrate_gift_wrap_style'] ) ? sanitize_key( wp_unslash( $_POST['wpcalibrate_gift_wrap_style'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( empty( $style_id ) || 'none' === $style_id ) {
			return $cart_item_data;
		}

		$style = $this->eligibility_service->validate_style_for_product( $style_id, $product );
		if ( ! $style ) {
			return $cart_item_data;
		}

		$requested_qty = isset( $_POST['wpcalibrate_gift_wrap_quantity'] ) ? absint( $_POST['wpcalibrate_gift_wrap_quantity'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$add_to_cart_qty = isset( $_POST['quantity'] ) ? absint( $_POST['quantity'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( $requested_qty <= 0 ) {
			$requested_qty = $add_to_cart_qty;
		}
		$wrapped_qty = min( $requested_qty, $add_to_cart_qty );

		$message = '';
		if ( $this->eligibility_service->are_gift_messages_enabled() && isset( $_POST['wpcalibrate_gift_wrap_message'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$raw_msg = sanitize_textarea_field( wp_unslash( $_POST['wpcalibrate_gift_wrap_message'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$max_len = absint( SettingsRepository::get( 'gift_message_max_length', 500 ) );
			$message = mb_substr( $raw_msg, 0, $max_len );
		}

		$cart_item_data[ self::CART_ITEM_KEY ] = array(
			'style_id'     => $style->get_id(),
			'style_name'   => $style->get_name(),
			'quantity'     => $wrapped_qty,
			'message'      => $message,
			'unique_token' => wp_generate_uuid4(), // Ensures differently wrapped identical items create distinct cart items.
		);

		return $cart_item_data;
	}

	/**
	 * Restore cart item data from session.
	 *
	 * @param array<string, mixed> $cart_item Item data.
	 * @param array<string, mixed> $values Session values.
	 * @return array<string, mixed>
	 */
	public function get_cart_item_from_session( array $cart_item, array $values ): array {
		if ( isset( $values[ self::CART_ITEM_KEY ] ) && is_array( $values[ self::CART_ITEM_KEY ] ) ) {
			$cart_item[ self::CART_ITEM_KEY ] = $values[ self::CART_ITEM_KEY ];
		}
		return $cart_item;
	}

	/**
	 * Display gift wrap info under cart item in cart/checkout.
	 *
	 * @param array<int, array<string, string>> $item_data Existing item display data.
	 * @param array<string, mixed>              $cart_item Cart item array.
	 * @return array<int, array<string, string>>
	 */
	public function get_item_data( array $item_data, array $cart_item ): array {
		if ( empty( $cart_item[ self::CART_ITEM_KEY ] ) || ! is_array( $cart_item[ self::CART_ITEM_KEY ] ) ) {
			return $item_data;
		}

		$wrap_data = $cart_item[ self::CART_ITEM_KEY ];
		$style = $this->style_repository->find( (string) $wrap_data['style_id'] );
		$style_name = $style ? $style->get_name() : ( $wrap_data['style_name'] ?? __( 'Gift Wrapped', 'wpcalibrate-gift-wrap' ) );
		$wrapped_qty = (int) ( $wrap_data['quantity'] ?? 1 );
		$line_qty = (int) ( $cart_item['quantity'] ?? 1 );

		// Price display.
		$display_price_setting = 'yes' === SettingsRepository::get( 'display_prices', 'yes' );
		$price_text = '';
		if ( $style && $display_price_setting ) {
			$cost = $this->price_service->calculate_item_wrap_cost( $style, $wrapped_qty );
			$price_text = ' (' . $this->price_service->format_display_price( $cost ) . ')';
		}

		$qty_detail = '';
		if ( $wrapped_qty < $line_qty ) {
			/* translators: 1: wrapped quantity, 2: total line quantity */
			$qty_detail = sprintf( __( ' [%1$d of %2$d items]', 'wpcalibrate-gift-wrap' ), $wrapped_qty, $line_qty );
		}

		$item_data[] = array(
			'key'   => SettingsRepository::get( 'label_gift_wrap', __( 'Gift Wrapping', 'wpcalibrate-gift-wrap' ) ),
			'value' => esc_html( $style_name . $qty_detail . $price_text ),
		);

		if ( ! empty( $wrap_data['message'] ) ) {
			$item_data[] = array(
				'key'   => SettingsRepository::get( 'label_gift_message', __( 'Gift Message', 'wpcalibrate-gift-wrap' ) ),
				'value' => esc_html( (string) $wrap_data['message'] ),
			);
		}

		return $item_data;
	}

	/**
	 * Adjust cart item prices server-side based on authoritative wrap styles.
	 *
	 * @param WC_Cart $cart Cart instance.
	 * @return void
	 */
	public function calculate_item_totals( WC_Cart $cart ): void {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		if ( ! $this->eligibility_service->is_per_item_enabled() ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( empty( $cart_item[ self::CART_ITEM_KEY ] ) || ! is_array( $cart_item[ self::CART_ITEM_KEY ] ) ) {
				continue;
			}

			$wrap_data = $cart_item[ self::CART_ITEM_KEY ];
			$style_id = (string) ( $wrap_data['style_id'] ?? '' );
			$style = $this->style_repository->find( $style_id );

			// If style is invalid or disabled, we don't charge.
			if ( ! $style || ! $style->is_enabled() ) {
				continue;
			}

			/** @var WC_Product $product */
			$product = $cart_item['data'];
			$line_qty = max( 1, (int) $cart_item['quantity'] );
			$wrapped_qty = min( max( 1, (int) ( $wrap_data['quantity'] ?? $line_qty ) ), $line_qty );

			// Authoritative cost calculation.
			$total_wrap_cost = $this->price_service->calculate_item_wrap_cost( $style, $wrapped_qty );
			if ( $total_wrap_cost <= 0.0 ) {
				continue;
			}

			// Store original product price once to avoid compounding calculations on multiple totals recalculations.
			if ( ! isset( $cart_item['_wpcalibrate_original_price'] ) ) {
				$cart_item['_wpcalibrate_original_price'] = (float) $product->get_price();
				$cart->cart_contents[ $cart_item_key ]['_wpcalibrate_original_price'] = $cart_item['_wpcalibrate_original_price'];
			}

			$original_price = (float) $cart_item['_wpcalibrate_original_price'];
			$unit_wrap_addition = $total_wrap_cost / $line_qty;

			$product->set_price( $original_price + $unit_wrap_addition );
		}
	}

	/**
	 * Add whole-order gift wrap fee if selected.
	 *
	 * @param WC_Cart $cart Cart instance.
	 * @return void
	 */
	public function calculate_order_fees( WC_Cart $cart ): void {
		if ( ! $this->eligibility_service->is_whole_order_enabled() ) {
			return;
		}

		$whole_order_data = WC()->session ? WC()->session->get( self::SESSION_WHOLE_ORDER_KEY ) : null;
		if ( empty( $whole_order_data ) || ! is_array( $whole_order_data ) ) {
			return;
		}

		$style_id = (string) ( $whole_order_data['style_id'] ?? '' );
		if ( empty( $style_id ) ) {
			return;
		}

		// Enforce exclusivity rule: If individual items are wrapped and combine is disallowed, skip whole-order fee.
		if ( ! $this->eligibility_service->can_combine_modes() && $this->cart_has_per_item_wrap( $cart ) ) {
			return;
		}

		$style = $this->eligibility_service->validate_style_for_order( $style_id, $cart );
		if ( ! $style ) {
			return;
		}

		$cost = $this->price_service->calculate_order_wrap_cost( $style );
		$is_taxable = $this->price_service->is_style_taxable( $style );
		$tax_class = $this->price_service->get_tax_class();

		$fee_label = sprintf(
			/* translators: %s: style name */
			__( 'Gift Wrapping (%s)', 'wpcalibrate-gift-wrap' ),
			$style->get_name()
		);

		$cart->add_fee(
			$fee_label,
			$cost,
			$is_taxable,
			$tax_class
		);
	}

	/**
	 * Clamp wrapped quantity if product quantity decreased.
	 *
	 * @param string $cart_item_key Cart item key.
	 * @param int    $quantity New quantity.
	 * @param int    $old_quantity Old quantity.
	 * @param WC_Cart $cart Cart instance.
	 * @return void
	 */
	public function on_cart_item_quantity_update( string $cart_item_key, int $quantity, int $old_quantity, WC_Cart $cart ): void {
		if ( empty( $cart->cart_contents[ $cart_item_key ][ self::CART_ITEM_KEY ] ) ) {
			return;
		}

		$wrap_data = &$cart->cart_contents[ $cart_item_key ][ self::CART_ITEM_KEY ];
		$current_wrapped = (int) ( $wrap_data['quantity'] ?? 1 );

		if ( $current_wrapped > $quantity ) {
			$wrap_data['quantity'] = $quantity;
		}
	}

	/**
	 * Validate cart items on checkout/cart view.
	 *
	 * @return void
	 */
	public function validate_cart_items(): void {
		if ( ! WC()->cart ) {
			return;
		}

		$cart = WC()->cart;

		// 1. Validate per-item wraps.
		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( empty( $cart_item[ self::CART_ITEM_KEY ] ) ) {
				continue;
			}

			$wrap_data = $cart_item[ self::CART_ITEM_KEY ];
			$style_id = (string) ( $wrap_data['style_id'] ?? '' );
			/** @var WC_Product $product */
			$product = $cart_item['data'];

			$style = $this->eligibility_service->validate_style_for_product( $style_id, $product );
			if ( ! $style ) {
				// Style no longer valid or eligible for this product. Remove wrap.
				unset( $cart->cart_contents[ $cart_item_key ][ self::CART_ITEM_KEY ] );
				if ( isset( $cart->cart_contents[ $cart_item_key ]['_wpcalibrate_original_price'] ) ) {
					$cart_item['data']->set_price( (float) $cart->cart_contents[ $cart_item_key ]['_wpcalibrate_original_price'] );
					unset( $cart->cart_contents[ $cart_item_key ]['_wpcalibrate_original_price'] );
				}
				wc_add_notice(
					sprintf(
						/* translators: %s: product title */
						__( 'Gift wrapping for "%s" is no longer available and has been removed.', 'wpcalibrate-gift-wrap' ),
						$product->get_name()
					),
					'notice'
				);
			}
		}

		// 2. Validate whole-order wrap.
		if ( WC()->session ) {
			$whole_order_data = WC()->session->get( self::SESSION_WHOLE_ORDER_KEY );
			if ( ! empty( $whole_order_data['style_id'] ) ) {
				$style = $this->eligibility_service->validate_style_for_order( (string) $whole_order_data['style_id'], $cart );
				if ( ! $style ) {
					WC()->session->set( self::SESSION_WHOLE_ORDER_KEY, null );
					wc_add_notice( __( 'The selected whole-order gift wrap style is no longer available.', 'wpcalibrate-gift-wrap' ), 'notice' );
				} elseif ( ! $this->eligibility_service->can_combine_modes() && $this->cart_has_per_item_wrap( $cart ) ) {
					// Duplicate wrap conflict.
					WC()->session->set( self::SESSION_WHOLE_ORDER_KEY, null );
					wc_add_notice( __( 'Whole-order gift wrap was removed because individual items are already gift wrapped.', 'wpcalibrate-gift-wrap' ), 'notice' );
				}
			}
		}
	}

	/**
	 * Check if cart contains any per-item wrapped products.
	 *
	 * @param WC_Cart|null $cart Cart instance.
	 * @return bool
	 */
	public function cart_has_per_item_wrap( ?WC_Cart $cart = null ): bool {
		$cart_obj = $cart ?: ( WC()->cart ?? null );
		if ( ! $cart_obj ) {
			return false;
		}

		foreach ( $cart_obj->get_cart() as $cart_item ) {
			if ( ! empty( $cart_item[ self::CART_ITEM_KEY ]['style_id'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Handle cart actions from GET/POST requests.
	 *
	 * @return void
	 */
	public function handle_cart_actions(): void {
		if ( ! isset( $_REQUEST['wpcalibrate_action'] ) ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_REQUEST['wpcalibrate_action'] ) );

		// Remove per-item wrap.
		if ( 'remove_item_wrap' === $action ) {
			$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
			$cart_item_key = isset( $_REQUEST['cart_item_key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['cart_item_key'] ) ) : '';

			if ( wp_verify_nonce( $nonce, 'wpcalibrate_remove_wrap_' . $cart_item_key ) && WC()->cart ) {
				if ( isset( WC()->cart->cart_contents[ $cart_item_key ][ self::CART_ITEM_KEY ] ) ) {
					unset( WC()->cart->cart_contents[ $cart_item_key ][ self::CART_ITEM_KEY ] );
					if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['_wpcalibrate_original_price'] ) ) {
						WC()->cart->cart_contents[ $cart_item_key ]['data']->set_price( (float) WC()->cart->cart_contents[ $cart_item_key ]['_wpcalibrate_original_price'] );
						unset( WC()->cart->cart_contents[ $cart_item_key ]['_wpcalibrate_original_price'] );
					}
					WC()->cart->calculate_totals();
					wc_add_notice( __( 'Gift wrap removed for this item.', 'wpcalibrate-gift-wrap' ), 'success' );
				}
				wp_safe_redirect( wc_get_cart_url() );
				exit;
			}
		}

		// Set whole-order wrap.
		if ( 'set_whole_order_wrap' === $action ) {
			$nonce = isset( $_POST['wpcalibrate_whole_order_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wpcalibrate_whole_order_nonce'] ) ) : '';
			if ( wp_verify_nonce( $nonce, 'wpcalibrate_set_whole_order_wrap' ) && WC()->session ) {
				$style_id = isset( $_POST['wpcalibrate_order_style'] ) ? sanitize_key( wp_unslash( $_POST['wpcalibrate_order_style'] ) ) : '';
				if ( empty( $style_id ) || 'none' === $style_id ) {
					WC()->session->set( self::SESSION_WHOLE_ORDER_KEY, null );
					wc_add_notice( __( 'Whole-order gift wrap removed.', 'wpcalibrate-gift-wrap' ), 'success' );
				} else {
					$style = $this->eligibility_service->validate_style_for_order( $style_id, WC()->cart );
					if ( $style ) {
						$message = '';
						if ( $this->eligibility_service->are_gift_messages_enabled() && isset( $_POST['wpcalibrate_order_message'] ) ) {
							$raw_msg = sanitize_textarea_field( wp_unslash( $_POST['wpcalibrate_order_message'] ) );
							$max_len = absint( SettingsRepository::get( 'gift_message_max_length', 500 ) );
							$message = mb_substr( $raw_msg, 0, $max_len );
						}
						WC()->session->set(
							self::SESSION_WHOLE_ORDER_KEY,
							array(
								'style_id' => $style->get_id(),
								'message'  => $message,
							)
						);
						wc_add_notice( __( 'Whole-order gift wrap updated.', 'wpcalibrate-gift-wrap' ), 'success' );
					}
				}
				if ( WC()->cart ) {
					WC()->cart->calculate_totals();
				}
				wp_safe_redirect( wc_get_cart_url() );
				exit;
			}
		}
	}

	/**
	 * AJAX endpoint to set whole-order wrap.
	 *
	 * @return void
	 */
	public function ajax_set_whole_order_wrap(): void {
		check_ajax_referer( 'wpcalibrate_whole_order_nonce', 'security' );

		if ( ! WC()->session || ! WC()->cart ) {
			wp_send_json_error( array( 'message' => __( 'Session expired.', 'wpcalibrate-gift-wrap' ) ) );
		}

		$style_id = isset( $_POST['style_id'] ) ? sanitize_key( wp_unslash( $_POST['style_id'] ) ) : '';
		if ( empty( $style_id ) || 'none' === $style_id ) {
			WC()->session->set( self::SESSION_WHOLE_ORDER_KEY, null );
			WC()->cart->calculate_totals();
			wp_send_json_success( array( 'message' => __( 'Gift wrap removed.', 'wpcalibrate-gift-wrap' ) ) );
		}

		// Exclusivity check.
		if ( ! $this->eligibility_service->can_combine_modes() && $this->cart_has_per_item_wrap( WC()->cart ) ) {
			wp_send_json_error( array( 'message' => __( 'Cannot combine whole-order wrap with individual item wrap.', 'wpcalibrate-gift-wrap' ) ) );
		}

		$style = $this->eligibility_service->validate_style_for_order( $style_id, WC()->cart );
		if ( ! $style ) {
			wp_send_json_error( array( 'message' => __( 'Selected gift wrap style is not available.', 'wpcalibrate-gift-wrap' ) ) );
		}

		$message = '';
		if ( $this->eligibility_service->are_gift_messages_enabled() && isset( $_POST['message'] ) ) {
			$raw_msg = sanitize_textarea_field( wp_unslash( $_POST['message'] ) );
			$max_len = absint( SettingsRepository::get( 'gift_message_max_length', 500 ) );
			$message = mb_substr( $raw_msg, 0, $max_len );
		}

		WC()->session->set(
			self::SESSION_WHOLE_ORDER_KEY,
			array(
				'style_id' => $style->get_id(),
				'message'  => $message,
			)
		);

		WC()->cart->calculate_totals();

		wp_send_json_success(
			array(
				'message'    => __( 'Gift wrap applied.', 'wpcalibrate-gift-wrap' ),
				'style_name' => $style->get_name(),
				'cost'       => $this->price_service->calculate_order_wrap_cost( $style ),
			)
		);
	}

	/**
	 * AJAX endpoint to remove item wrap.
	 *
	 * @return void
	 */
	public function ajax_remove_item_wrap(): void {
		$cart_item_key = isset( $_POST['cart_item_key'] ) ? sanitize_text_field( wp_unslash( $_POST['cart_item_key'] ) ) : '';
		check_ajax_referer( 'wpcalibrate_remove_wrap_' . $cart_item_key, 'security' );

		if ( ! WC()->cart || empty( WC()->cart->cart_contents[ $cart_item_key ][ self::CART_ITEM_KEY ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Item not found.', 'wpcalibrate-gift-wrap' ) ) );
		}

		unset( WC()->cart->cart_contents[ $cart_item_key ][ self::CART_ITEM_KEY ] );
		if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['_wpcalibrate_original_price'] ) ) {
			WC()->cart->cart_contents[ $cart_item_key ]['data']->set_price( (float) WC()->cart->cart_contents[ $cart_item_key ]['_wpcalibrate_original_price'] );
			unset( WC()->cart->cart_contents[ $cart_item_key ]['_wpcalibrate_original_price'] );
		}
		WC()->cart->calculate_totals();

		wp_send_json_success( array( 'message' => __( 'Gift wrap removed.', 'wpcalibrate-gift-wrap' ) ) );
	}

	/**
	 * Render whole-order wrap selector on Cart totals.
	 *
	 * @return void
	 */
	public function render_cart_whole_order_wrap(): void {
		if ( ! $this->eligibility_service->is_whole_order_enabled() || ! WC()->cart ) {
			return;
		}

		if ( ! $this->eligibility_service->can_combine_modes() && $this->cart_has_per_item_wrap( WC()->cart ) ) {
			return;
		}

		$styles = $this->eligibility_service->get_eligible_styles_for_order( WC()->cart );
		if ( empty( $styles ) ) {
			return;
		}

		$current = WC()->session ? WC()->session->get( self::SESSION_WHOLE_ORDER_KEY ) : null;
		$selected_id = $current['style_id'] ?? '';
		$selected_msg = $current['message'] ?? '';
		$price_service = $this->price_service;
		$show_messages = $this->eligibility_service->are_gift_messages_enabled() && 'yes' === SettingsRepository::get( 'enable_whole_order_message', 'yes' );
		$max_len = absint( SettingsRepository::get( 'gift_message_max_length', 500 ) );

		?>
		<tr class="wpcalibrate-cart-whole-order-wrap">
			<th><?php echo esc_html( SettingsRepository::get( 'label_gift_wrap', __( 'Gift Wrapping', 'wpcalibrate-gift-wrap' ) ) ); ?></th>
			<td>
				<form method="post" action="<?php echo esc_url( wc_get_cart_url() ); ?>" class="wpcalibrate-order-wrap-form">
					<?php wp_nonce_field( 'wpcalibrate_set_whole_order_wrap', 'wpcalibrate_whole_order_nonce' ); ?>
					<input type="hidden" name="wpcalibrate_action" value="set_whole_order_wrap" />

					<label for="wpcalibrate_order_style" class="screen-reader-text">
						<?php echo esc_html( SettingsRepository::get( 'label_choose_style', __( 'Choose a gift wrap style', 'wpcalibrate-gift-wrap' ) ) ); ?>
					</label>
					<select name="wpcalibrate_order_style" id="wpcalibrate_order_style" class="wpcalibrate-order-style-select" onchange="this.form.submit()">
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

					<?php if ( $show_messages && ! empty( $selected_id ) ) : ?>
						<div class="wpcalibrate-message-field" style="margin-top: 8px;">
							<label for="wpcalibrate_order_message" style="display:block;font-size:0.9em;margin-bottom:3px;">
								<?php echo esc_html( SettingsRepository::get( 'label_gift_message', __( 'Gift Message', 'wpcalibrate-gift-wrap' ) ) ); ?>
							</label>
							<textarea
								name="wpcalibrate_order_message"
								id="wpcalibrate_order_message"
								maxlength="<?php echo esc_attr( (string) $max_len ); ?>"
								rows="2"
								style="width: 100%; font-size: 0.9em;"
								placeholder="<?php esc_attr_e( 'Enter your personal message here...', 'wpcalibrate-gift-wrap' ); ?>"
							><?php echo esc_textarea( $selected_msg ); ?></textarea>
							<button type="submit" class="button button-small" style="margin-top: 4px;">
								<?php esc_html_e( 'Update Message', 'wpcalibrate-gift-wrap' ); ?>
							</button>
						</div>
					<?php endif; ?>
				</form>
			</td>
		</tr>
		<?php
	}
}
