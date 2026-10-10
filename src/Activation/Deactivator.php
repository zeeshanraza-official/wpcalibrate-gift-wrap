<?php
/**
 * Plugin Deactivator for WPCalibrate Gift Wrap.
 *
 * @package WPCalibrate\GiftWrap\Activation
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\Activation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Deactivator
 */
class Deactivator {

	/**
	 * Perform deactivation cleanups.
	 *
	 * Note: Never deletes settings, wrapping styles, or order metadata during ordinary deactivation.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Clean up any temporary transients.
		delete_transient( 'wpcalibrate_gift_wrap_temp' );
	}
}
