<?php
/**
 * Uninstall routine for WPCalibrate Gift Wrap.
 *
 * Triggered only when the plugin is deleted via the WordPress admin plugins screen.
 *
 * @package WPCalibrate\GiftWrap
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Always clean temporary transients.
delete_transient( 'wpcalibrate_gift_wrap_temp' );

// Check if administrator chose to delete data on uninstall.
$settings = get_option( 'wpcalibrate_gift_wrap_settings', array() );
$delete_data = is_array( $settings ) && isset( $settings['uninstall_delete_data'] ) && 'yes' === $settings['uninstall_delete_data'];

if ( $delete_data ) {
	// Delete plugin options.
	delete_option( 'wpcalibrate_gift_wrap_settings' );
	delete_option( 'wpcalibrate_gift_wrap_styles' );
	delete_option( 'wpcalibrate_gift_wrap_version' );
	delete_option( 'wpcalibrate_gift_wrap_db_version' );
	delete_option( 'wpcalibrate_gift_wrap_license_key' );

	// Clean up product-level overrides across the site.
	global $wpdb;
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->postmeta} WHERE meta_key IN (%s, %s, %s)",
			'_wpcalibrate_gift_wrap_enabled',
			'_wpcalibrate_gift_wrap_styles',
			'_wpcalibrate_exclude_whole_order'
		)
	);

	// Note: We deliberately preserve WooCommerce order items and order snapshots
	// so historical order accounting and customer receipts remain accurate.
}
