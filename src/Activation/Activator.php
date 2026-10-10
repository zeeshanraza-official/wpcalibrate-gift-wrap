<?php
/**
 * Plugin Activator for WPCalibrate Gift Wrap.
 *
 * @package WPCalibrate\GiftWrap\Activation
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\Activation;

use WPCalibrate\GiftWrap\Dependencies;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Activator
 */
class Activator {

	/**
	 * Perform activation actions.
	 *
	 * @return void
	 */
	public static function activate(): void {
		if ( ! Dependencies::is_php_compatible() ) {
			deactivate_plugins( plugin_basename( WPCALIBRATE_GIFT_WRAP_FILE ) );
			wp_die(
				sprintf(
					/* translators: 1: required PHP version, 2: current PHP version */
					esc_html__( 'WPCalibrate Gift Wrap requires PHP %1$s or higher. Your server is running PHP %2$s.', 'wpcalibrate-gift-wrap' ),
					esc_html( Dependencies::MIN_PHP_VERSION ),
					esc_html( PHP_VERSION )
				)
			);
		}

		Migrations::run();
	}
}
