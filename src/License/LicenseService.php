<?php
/**
 * Concrete License Service for WPCalibrate Gift Wrap (V1 Implementation).
 *
 * Provides a clean architecture ready for future WPCalibrate Marketplace connection,
 * while accurately maintaining "Not Configured" status in V1 without fake server calls.
 *
 * @package WPCalibrate\GiftWrap\License
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\License;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LicenseService
 */
class LicenseService implements LicenseServiceInterface {

	public const OPTION_KEY = 'wpcalibrate_gift_wrap_license_key';

	/**
	 * Get the current license status.
	 *
	 * @return string
	 */
	public function get_status(): string {
		return self::STATUS_NOT_CONFIGURED;
	}

	/**
	 * Get the human-readable status label.
	 *
	 * @return string
	 */
	public function get_status_label(): string {
		return __( 'Not Configured (V1 Standard Release)', 'wpcalibrate-gift-wrap' );
	}

	/**
	 * Get masked license key.
	 *
	 * @return string
	 */
	public function get_masked_key(): string {
		$key = (string) get_option( self::OPTION_KEY, '' );
		if ( empty( $key ) ) {
			return '';
		}

		$length = strlen( $key );
		if ( $length <= 4 ) {
			return str_repeat( '•', $length );
		}

		return str_repeat( '•', max( 0, $length - 4 ) ) . substr( $key, -4 );
	}

	/**
	 * Check if plugin features are unlocked.
	 * Always returns true in V1 so site owners have full functional access.
	 *
	 * @return bool
	 */
	public function is_feature_unlocked(): bool {
		return true;
	}

	/**
	 * Get expiration date.
	 *
	 * @return string|null
	 */
	public function get_expiration_date(): ?string {
		return null;
	}

	/**
	 * Get renewal URL.
	 *
	 * @return string
	 */
	public function get_renewal_url(): string {
		return 'https://marketplace.wpcalibrate.com/';
	}

	/**
	 * Activate license.
	 * In V1, cleanly informs the admin that remote licensing endpoint is not configured.
	 *
	 * @param string $license_key License key.
	 * @return array{success: bool, message: string}
	 */
	public function activate( string $license_key ): array {
		return array(
			'success' => false,
			'message' => __( 'Remote licensing service is not configured in this release. All plugin features remain active.', 'wpcalibrate-gift-wrap' ),
		);
	}

	/**
	 * Deactivate license.
	 *
	 * @return array{success: bool, message: string}
	 */
	public function deactivate(): array {
		return array(
			'success' => false,
			'message' => __( 'No active remote license to deactivate in this release.', 'wpcalibrate-gift-wrap' ),
		);
	}

	/**
	 * Refresh status.
	 *
	 * @return array{success: bool, message: string}
	 */
	public function refresh_status(): array {
		return array(
			'success' => true,
			'message' => __( 'License validation backend is not configured. Features are active.', 'wpcalibrate-gift-wrap' ),
		);
	}
}
