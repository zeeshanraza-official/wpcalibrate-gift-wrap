<?php
/**
 * Database and Settings Migrations for WPCalibrate Gift Wrap.
 *
 * @package WPCalibrate\GiftWrap\Activation
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\Activation;

use WPCalibrate\GiftWrap\GiftWrap\Repositories\SettingsRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Migrations
 */
class Migrations {

	public const CURRENT_DB_VERSION = '1.0.0';

	/**
	 * Run upgrade migrations if necessary.
	 *
	 * @return void
	 */
	public static function run(): void {
		$installed_db_version = get_option( SettingsRepository::OPTION_DB_VERSION, '0.0.0' );

		if ( version_compare( (string) $installed_db_version, self::CURRENT_DB_VERSION, '<' ) ) {
			self::migrate( (string) $installed_db_version );
			update_option( SettingsRepository::OPTION_DB_VERSION, self::CURRENT_DB_VERSION );
		}

		update_option( SettingsRepository::OPTION_VERSION, WPCALIBRATE_GIFT_WRAP_VERSION );
	}

	/**
	 * Run step-by-step migrations.
	 *
	 * @param string $from_version Version to migrate from.
	 * @return void
	 */
	private static function migrate( string $from_version ): void {
		// Ensure default settings exist.
		$current_settings = get_option( SettingsRepository::OPTION_NAME, null );
		if ( null === $current_settings ) {
			update_option( SettingsRepository::OPTION_NAME, SettingsRepository::get_defaults() );
		}

		// Ensure styles option exists as array.
		$current_styles = get_option( 'wpcalibrate_gift_wrap_styles', null );
		if ( null === $current_styles ) {
			update_option( 'wpcalibrate_gift_wrap_styles', array() );
		}
	}
}
