<?php
/**
 * Dependency checker for WPCalibrate Gift Wrap.
 *
 * @package WPCalibrate\GiftWrap
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dependencies class.
 */
class Dependencies {

	/**
	 * Minimum PHP version required.
	 *
	 * @var string
	 */
	public const MIN_PHP_VERSION = '8.2.0';

	/**
	 * Check if WooCommerce is installed and active.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_active(): bool {
		if ( class_exists( 'WooCommerce' ) ) {
			return true;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			if ( defined( 'ABSPATH' ) && file_exists( ABSPATH . 'wp-admin/includes/plugin.php' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
		}

		$is_active = function_exists( 'is_plugin_active' ) ? is_plugin_active( 'woocommerce/woocommerce.php' ) : false;
		if ( ! $is_active && function_exists( 'is_multisite' ) && is_multisite() && function_exists( 'is_plugin_active_for_network' ) ) {
			$is_active = is_plugin_active_for_network( 'woocommerce/woocommerce.php' );
		}

		return (bool) $is_active;
	}

	/**
	 * Check if the current PHP version meets the minimum requirement.
	 *
	 * @return bool
	 */
	public static function is_php_compatible(): bool {
		return version_compare( PHP_VERSION, self::MIN_PHP_VERSION, '>=' );
	}

	/**
	 * Render WooCommerce missing admin notice.
	 *
	 * @return void
	 */
	public static function render_woocommerce_missing_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'WPCalibrate Gift Wrap', 'wpcalibrate-gift-wrap' ); ?></strong>
				<?php esc_html_e( 'requires WooCommerce to be installed and active. Please activate WooCommerce to use gift-wrapping features.', 'wpcalibrate-gift-wrap' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render PHP incompatible admin notice.
	 *
	 * @return void
	 */
	public static function render_php_incompatible_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'WPCalibrate Gift Wrap', 'wpcalibrate-gift-wrap' ); ?></strong>
				<?php
				printf(
					/* translators: 1: Required PHP version, 2: Current PHP version */
					esc_html__( 'requires PHP %1$s or higher. Your server is currently running PHP %2$s. Please update PHP to use this plugin.', 'wpcalibrate-gift-wrap' ),
					esc_html( self::MIN_PHP_VERSION ),
					esc_html( PHP_VERSION )
				);
				?>
			</p>
		</div>
		<?php
	}
}
