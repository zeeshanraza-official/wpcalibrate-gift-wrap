<?php
/**
 * WooCommerce Blocks & Store API Integration for WPCalibrate Gift Wrap.
 *
 * Implements Store API data integration and checkout block compatibility.
 *
 * @package WPCalibrate\GiftWrap\Compatibility\Blocks
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\Compatibility\Blocks;

use Automattic\WooCommerce\StoreApi\StoreApi;
use Automattic\WooCommerce\StoreApi\Schemas\ExtendSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;
use WC_Order;
use WP_REST_Request;
use WPCalibrate\GiftWrap\Cart\CartHandler;
use WPCalibrate\GiftWrap\GiftWrap\Repositories\SettingsRepository;
use WPCalibrate\GiftWrap\GiftWrap\Repositories\StyleRepositoryInterface;
use WPCalibrate\GiftWrap\GiftWrap\Services\EligibilityService;
use WPCalibrate\GiftWrap\GiftWrap\Services\OrderWrapService;
use WPCalibrate\GiftWrap\GiftWrap\Services\PriceService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class BlocksIntegration
 */
class BlocksIntegration {

	public const EXTENSION_IDENTIFIER = 'wpcalibrate_gift_wrap';

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
	 * Register Blocks and Store API hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		// Hook into Store API Checkout order completion.
		add_action( 'woocommerce_store_api_checkout_update_order_meta', array( $this, 'update_order_from_store_api' ), 10, 2 );

		// Register schema extension with Store API if class exists.
		add_action( 'woocommerce_blocks_loaded', array( $this, 'register_store_api_extensions' ) );

		// Enqueue blocks integration scripts.
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_block_assets' ) );
	}

	/**
	 * Register extension data with WooCommerce Store API.
	 *
	 * @return void
	 */
	public function register_store_api_extensions(): void {
		if ( ! class_exists( StoreApi::class ) || ! class_exists( ExtendSchema::class ) ) {
			return;
		}

		try {
			/** @var ExtendSchema $extend */
			$extend = StoreApi::container()->get( ExtendSchema::class );

			$extend->register_endpoint_data(
				array(
					'endpoint'        => CheckoutSchema::IDENTIFIER,
					'namespace'       => self::EXTENSION_IDENTIFIER,
					'data_callback'   => array( $this, 'get_store_api_checkout_data' ),
					'schema_callback' => array( $this, 'get_store_api_checkout_schema' ),
				)
			);
		} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Gracefully ignore if Store API container is not initialized in this request.
		}
	}

	/**
	 * Provide current gift wrap data to Store API.
	 *
	 * @return array<string, mixed>
	 */
	public function get_store_api_checkout_data(): array {
		$styles = $this->eligibility_service->get_eligible_styles_for_order( WC()->cart );
		$available_styles = array();

		foreach ( $styles as $style ) {
			$cost = $this->price_service->calculate_order_wrap_cost( $style );
			$available_styles[] = array(
				'id'          => $style->get_id(),
				'name'        => $style->get_name(),
				'price'       => $cost,
				'price_html'  => $this->price_service->format_display_price( $cost ),
				'description' => $style->get_description(),
				'image_url'   => $style->get_image_url(),
				'badge'       => $style->get_badge(),
			);
		}

		$current = WC()->session ? WC()->session->get( CartHandler::SESSION_WHOLE_ORDER_KEY ) : null;

		return array(
			'enabled'          => $this->eligibility_service->is_whole_order_enabled(),
			'selected_style'   => $current['style_id'] ?? '',
			'message'          => $current['message'] ?? '',
			'max_message_len'  => absint( SettingsRepository::get( 'gift_message_max_length', 500 ) ),
			'available_styles' => $available_styles,
		);
	}

	/**
	 * Store API schema definition for gift wrap checkout extension.
	 *
	 * @return array<string, mixed>
	 */
	public function get_store_api_checkout_schema(): array {
		return array(
			'enabled'          => array(
				'description' => __( 'Whether whole-order gift wrapping is available.', 'wpcalibrate-gift-wrap' ),
				'type'        => 'boolean',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
			),
			'selected_style'   => array(
				'description' => __( 'Selected gift wrap style ID.', 'wpcalibrate-gift-wrap' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'message'          => array(
				'description' => __( 'Customer gift message.', 'wpcalibrate-gift-wrap' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
			'available_styles' => array(
				'description' => __( 'Available gift wrap styles.', 'wpcalibrate-gift-wrap' ),
				'type'        => 'array',
				'context'     => array( 'view' ),
				'readonly'    => true,
			),
		);
	}

	/**
	 * Handle order update from Store API checkout request.
	 *
	 * @param WC_Order        $order Order instance.
	 * @param WP_REST_Request $request REST request.
	 * @return void
	 */
	public function update_order_from_store_api( WC_Order $order, WP_REST_Request $request ): void {
		// Check session first.
		$session_wrap = WC()->session ? WC()->session->get( CartHandler::SESSION_WHOLE_ORDER_KEY ) : null;
		if ( ! empty( $session_wrap['style_id'] ) ) {
			$style = $this->style_repository->find( (string) $session_wrap['style_id'] );
			if ( $style ) {
				$cost = $this->price_service->calculate_order_wrap_cost( $style );
				$msg  = (string) ( $session_wrap['message'] ?? '' );
				$this->order_wrap_service->attach_order_wrap( $order, $style, $cost, $msg );
				return;
			}
		}

		// Check request extension data.
		$extensions = $request->get_param( 'extensions' );
		if ( ! is_array( $extensions ) || empty( $extensions[ self::EXTENSION_IDENTIFIER ] ) ) {
			return;
		}

		$data = $extensions[ self::EXTENSION_IDENTIFIER ];
		$style_id = isset( $data['selected_style'] ) ? sanitize_key( (string) $data['selected_style'] ) : '';
		if ( empty( $style_id ) || 'none' === $style_id ) {
			return;
		}

		$style = $this->eligibility_service->validate_style_for_order( $style_id, WC()->cart );
		if ( ! $style ) {
			return;
		}

		$cost = $this->price_service->calculate_order_wrap_cost( $style );
		$msg  = '';
		if ( ! empty( $data['message'] ) ) {
			$raw_msg = sanitize_textarea_field( (string) $data['message'] );
			$max_len = absint( SettingsRepository::get( 'gift_message_max_length', 500 ) );
			$msg     = mb_substr( $raw_msg, 0, $max_len );
		}

		$this->order_wrap_service->attach_order_wrap( $order, $style, $cost, $msg );
	}

	/**
	 * Enqueue checkout block integration script if checkout block is present.
	 *
	 * @return void
	 */
	public function enqueue_block_assets(): void {
		if ( ! function_exists( 'has_block' ) || ! has_block( 'woocommerce/checkout' ) ) {
			return;
		}

		$script_path = WPCALIBRATE_GIFT_WRAP_DIR . 'assets/js/blocks-checkout.js';
		if ( file_exists( $script_path ) ) {
			wp_enqueue_script(
				'wpcalibrate-blocks-checkout',
				WPCALIBRATE_GIFT_WRAP_URL . 'assets/js/blocks-checkout.js',
				array( 'wc-blocks-checkout', 'wp-element', 'wp-i18n' ),
				WPCALIBRATE_GIFT_WRAP_VERSION,
				true
			);
		}
	}
}
