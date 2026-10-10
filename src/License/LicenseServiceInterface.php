<?php
/**
 * License Service Interface for WPCalibrate plugins.
 *
 * Designed for future WPCalibrate Marketplace licensing integration.
 *
 * @package WPCalibrate\GiftWrap\License
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\License;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface LicenseServiceInterface
 */
interface LicenseServiceInterface {

	public const STATUS_NOT_CONFIGURED = 'not_configured';
	public const STATUS_ACTIVE         = 'active';
	public const STATUS_INACTIVE       = 'inactive';
	public const STATUS_EXPIRED        = 'expired';
	public const STATUS_ERROR          = 'error';

	/**
	 * Get the current license status.
	 *
	 * @return string One of STATUS_* constants.
	 */
	public function get_status(): string;

	/**
	 * Get the human-readable status label.
	 *
	 * @return string
	 */
	public function get_status_label(): string;

	/**
	 * Get masked license key (e.g., '••••••••••••1234').
	 *
	 * @return string
	 */
	public function get_masked_key(): string;

	/**
	 * Check if the feature set is unlocked.
	 * In V1, always returns true to ensure no lockouts when backend is unconfigured.
	 *
	 * @return bool
	 */
	public function is_feature_unlocked(): bool;

	/**
	 * Get expiration date string if known.
	 *
	 * @return string|null
	 */
	public function get_expiration_date(): ?string;

	/**
	 * Get renewal URL on marketplace.
	 *
	 * @return string
	 */
	public function get_renewal_url(): string;

	/**
	 * Attempt license activation.
	 *
	 * @param string $license_key License key.
	 * @return array{success: bool, message: string}
	 */
	public function activate( string $license_key ): array;

	/**
	 * Attempt license deactivation.
	 *
	 * @return array{success: bool, message: string}
	 */
	public function deactivate(): array;

	/**
	 * Refresh / re-check license status from server.
	 *
	 * @return array{success: bool, message: string}
	 */
	public function refresh_status(): array;
}
