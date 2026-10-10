<?php
/**
 * HPOS Compatibility Handler for WPCalibrate Gift Wrap.
 *
 * Declares WooCommerce High-Performance Order Storage (HPOS) and
 * Cart/Checkout Blocks compatibility.
 *
 * @package WPCalibrate\GiftWrap\Compatibility\HPOS
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\Compatibility\HPOS;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class HPOSCompatibility
 */
class HPOSCompatibility {

	/**
	 * Register compatibility declaration on before_woocommerce_init.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'before_woocommerce_init', array( self::class, 'declare_compatibility' ) );
	}

	/**
	 * Declare HPOS and Blocks feature compatibility.
	 *
	 * @return void
	 */
	public static function declare_compatibility(): void {
		if ( ! class_exists( FeaturesUtil::class ) ) {
			return;
		}

		// Declare HPOS compatibility.
		FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			WPCALIBRATE_GIFT_WRAP_FILE,
			true
		);

		// Declare Cart & Checkout Blocks compatibility.
		FeaturesUtil::declare_compatibility(
			'cart_checkout_blocks',
			WPCALIBRATE_GIFT_WRAP_FILE,
			true
		);
	}
}
