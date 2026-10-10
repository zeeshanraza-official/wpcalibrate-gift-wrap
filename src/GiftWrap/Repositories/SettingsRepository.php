<?php
/**
 * Settings repository for WPCalibrate Gift Wrap.
 *
 * @package WPCalibrate\GiftWrap
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\GiftWrap\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SettingsRepository class.
 */
class SettingsRepository {

	public const OPTION_NAME = 'wpcalibrate_gift_wrap_settings';
	public const OPTION_VERSION = 'wpcalibrate_gift_wrap_version';
	public const OPTION_DB_VERSION = 'wpcalibrate_gift_wrap_db_version';

	/**
	 * Default settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_defaults(): array {
		return array(
			// General.
			'enabled'                  => 'yes',
			'enable_per_item'          => 'yes',
			'enable_whole_order'       => 'yes',
			'allow_combine_modes'      => 'no', // Default recommended: prevent accidental duplicate charges.
			'enable_gift_messages'     => 'yes',
			'gift_message_max_length'  => 500,
			'enable_per_item_messages' => 'yes',
			'enable_whole_order_message' => 'yes',
			'locations_product'        => 'yes',
			'locations_cart'           => 'yes',
			'locations_checkout'       => 'yes',
			'tax_status'               => 'taxable',
			'tax_class'                => '',

			// Display.
			'display_images'           => 'yes',
			'image_size'               => 'thumbnail',
			'display_descriptions'     => 'yes',
			'display_prices'           => 'yes',
			'label_gift_wrap'          => __( 'Gift Wrapping', 'wpcalibrate-gift-wrap' ),
			'label_choose_style'       => __( 'Choose a gift wrap style', 'wpcalibrate-gift-wrap' ),
			'label_gift_message'       => __( 'Gift Message', 'wpcalibrate-gift-wrap' ),
			'label_no_wrap'            => __( 'No gift wrap', 'wpcalibrate-gift-wrap' ),
			'label_wrap_this_item'     => __( 'Add Gift Wrap', 'wpcalibrate-gift-wrap' ),
			'label_wrapped'            => __( 'Gift Wrapped', 'wpcalibrate-gift-wrap' ),

			// Rules.
			'rules_mode'               => 'all', // 'all', 'include', 'exclude'
			'included_categories'      => array(),
			'excluded_categories'      => array(),
			'included_products'        => array(),
			'excluded_products'        => array(),

			// Orders & Emails.
			'orders_customer_emails'   => 'yes',
			'orders_customer_message'  => 'yes',
			'orders_my_account'        => 'yes',

			// Uninstall & Data Retention.
			'uninstall_delete_data'    => 'no',
		);
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_all(): array {
		$saved = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		return array_merge( self::get_defaults(), $saved );
	}

	/**
	 * Get a specific setting value.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Optional default if not found.
	 * @return mixed
	 */
	public static function get( string $key, mixed $default = null ): mixed {
		$all = self::get_all();
		if ( array_key_exists( $key, $all ) ) {
			return $all[ $key ];
		}
		return $default;
	}

	/**
	 * Update settings.
	 *
	 * @param array<string, mixed> $settings Raw input settings.
	 * @return bool
	 */
	public static function update_all( array $settings ): bool {
		$sanitized = self::sanitize_settings( $settings );
		return update_option( self::OPTION_NAME, $sanitized );
	}

	/**
	 * Sanitize all settings safely.
	 *
	 * @param array<string, mixed> $input Raw settings.
	 * @return array<string, mixed> Sanitized settings.
	 */
	public static function sanitize_settings( array $input ): array {
		$current = self::get_all();
		$output = $current;

		// General switches.
		$boolean_keys = array(
			'enabled',
			'enable_per_item',
			'enable_whole_order',
			'allow_combine_modes',
			'enable_gift_messages',
			'enable_per_item_messages',
			'enable_whole_order_message',
			'locations_product',
			'locations_cart',
			'locations_checkout',
			'display_images',
			'display_descriptions',
			'display_prices',
			'orders_customer_emails',
			'orders_customer_message',
			'orders_my_account',
			'uninstall_delete_data',
		);

		foreach ( $boolean_keys as $b_key ) {
			if ( isset( $input[ $b_key ] ) ) {
				$output[ $b_key ] = 'yes' === $input[ $b_key ] || true === $input[ $b_key ] || '1' === (string) $input[ $b_key ] ? 'yes' : 'no';
			}
		}

		// Numbers.
		if ( isset( $input['gift_message_max_length'] ) ) {
			$length = absint( $input['gift_message_max_length'] );
			$output['gift_message_max_length'] = ( $length > 0 && $length <= 5000 ) ? $length : 500;
		}

		// Tax status.
		if ( isset( $input['tax_status'] ) ) {
			$tax_status = sanitize_key( (string) $input['tax_status'] );
			$output['tax_status'] = in_array( $tax_status, array( 'taxable', 'none' ), true ) ? $tax_status : 'taxable';
		}

		// Tax class.
		if ( isset( $input['tax_class'] ) ) {
			$output['tax_class'] = sanitize_text_field( (string) $input['tax_class'] );
		}

		// Image size.
		if ( isset( $input['image_size'] ) ) {
			$image_size = sanitize_key( (string) $input['image_size'] );
			$allowed_sizes = array( 'thumbnail', 'medium', 'small' );
			$output['image_size'] = in_array( $image_size, $allowed_sizes, true ) ? $image_size : 'thumbnail';
		}

		// Labels.
		$label_keys = array(
			'label_gift_wrap',
			'label_choose_style',
			'label_gift_message',
			'label_no_wrap',
			'label_wrap_this_item',
			'label_wrapped',
		);
		foreach ( $label_keys as $l_key ) {
			if ( isset( $input[ $l_key ] ) ) {
				$output[ $l_key ] = sanitize_text_field( (string) $input[ $l_key ] );
			}
		}

		// Rules mode.
		if ( isset( $input['rules_mode'] ) ) {
			$mode = sanitize_key( (string) $input['rules_mode'] );
			$output['rules_mode'] = in_array( $mode, array( 'all', 'include', 'exclude' ), true ) ? $mode : 'all';
		}

		// Included/Excluded categories and products.
		$id_array_keys = array(
			'included_categories',
			'excluded_categories',
			'included_products',
			'excluded_products',
		);
		foreach ( $id_array_keys as $id_key ) {
			if ( isset( $input[ $id_key ] ) ) {
				$val = $input[ $id_key ];
				if ( is_string( $val ) ) {
					$val = array_values( array_filter( array_map( 'absint', explode( ',', $val ) ) ) );
				} elseif ( is_array( $val ) ) {
					$val = array_values( array_unique( array_filter( array_map( 'absint', $val ) ) ) );
				} else {
					$val = array();
				}
				$output[ $id_key ] = $val;
			}
		}

		return $output;
	}
}
