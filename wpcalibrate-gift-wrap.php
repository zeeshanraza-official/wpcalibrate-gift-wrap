<?php
/**
 * Plugin Name:       WPCalibrate Gift Wrap
 * Plugin URI:        https://marketplace.wpcalibrate.com/
 * Description:       Professional gift-wrapping extension for WooCommerce products and orders with customizable styles, images, transparent pricing, and gift messages.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.2
 * Requires Plugins:  woocommerce
 * Author:            WPCalibrate
 * Author URI:        https://wpcalibrate.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpcalibrate-gift-wrap
 * Domain Path:       /languages
 * WC requires at least: 8.0
 * WC tested up to:   11.2.1
 * Update URI:        https://github.com/zeeshanraza-official/wpcalibrate-gift-wrap
 * GitHub Plugin URI: https://github.com/zeeshanraza-official/wpcalibrate-gift-wrap
 * Primary Branch:    main
 * Release Asset:     true
 *
 * @package WPCalibrate\GiftWrap
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define core constants.
if ( ! defined( 'WPCALIBRATE_GIFT_WRAP_VERSION' ) ) {
	define( 'WPCALIBRATE_GIFT_WRAP_VERSION', '1.0.0' );
}
if ( ! defined( 'WPCALIBRATE_GIFT_WRAP_FILE' ) ) {
	define( 'WPCALIBRATE_GIFT_WRAP_FILE', __FILE__ );
}
if ( ! defined( 'WPCALIBRATE_GIFT_WRAP_DIR' ) ) {
	define( 'WPCALIBRATE_GIFT_WRAP_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WPCALIBRATE_GIFT_WRAP_URL' ) ) {
	define( 'WPCALIBRATE_GIFT_WRAP_URL', plugin_dir_url( __FILE__ ) );
}

// Require autoloader.
require_once WPCALIBRATE_GIFT_WRAP_DIR . 'src/Autoloader.php';
$wpcalibrate_autoloader = new Autoloader( WPCALIBRATE_GIFT_WRAP_DIR . 'src' );
$wpcalibrate_autoloader->register();

// Register activation and deactivation hooks.
register_activation_hook( __FILE__, array( Activation\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Activation\Deactivator::class, 'deactivate' ) );

/**
 * Bootstrap function for WPCalibrate Gift Wrap.
 *
 * @return Plugin
 */
function wpcalibrate_gift_wrap(): Plugin {
	return Plugin::instance();
}

// Launch plugin.
wpcalibrate_gift_wrap()->run();
