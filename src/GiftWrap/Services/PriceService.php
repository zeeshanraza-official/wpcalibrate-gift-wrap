<?php
/**
 * Price Service for WPCalibrate Gift Wrap.
 *
 * Provides authoritative server-side price calculation and formatting.
 *
 * @package WPCalibrate\GiftWrap\Services
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\GiftWrap\Services;

use WPCalibrate\GiftWrap\GiftWrap\Models\Style;
use WPCalibrate\GiftWrap\GiftWrap\Repositories\SettingsRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class PriceService
 */
class PriceService {

	/**
	 * Calculate wrapped item total cost.
	 *
	 * @param Style $style Style instance.
	 * @param int   $wrapped_quantity Quantity of physical items wrapped.
	 * @return float
	 */
	public function calculate_item_wrap_cost( Style $style, int $wrapped_quantity ): float {
		if ( $wrapped_quantity <= 0 || $style->is_free() ) {
			return 0.0;
		}

		$unit_price = $style->get_price();
		$total = $unit_price * $wrapped_quantity;

		$decimals = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2;
		if ( function_exists( 'wc_format_decimal' ) ) {
			return (float) wc_format_decimal( $total, $decimals );
		}

		return round( $total, $decimals );
	}

	/**
	 * Calculate whole-order wrap cost.
	 *
	 * @param Style $style Style instance.
	 * @return float
	 */
	public function calculate_order_wrap_cost( Style $style ): float {
		if ( $style->is_free() ) {
			return 0.0;
		}

		$price = $style->get_price();
		$decimals = function_exists( 'wc_get_price_decimals' ) ? wc_get_price_decimals() : 2;

		if ( function_exists( 'wc_format_decimal' ) ) {
			return (float) wc_format_decimal( $price, $decimals );
		}

		return round( $price, $decimals );
	}

	/**
	 * Format price for customer display.
	 *
	 * @param float $price Amount.
	 * @return string
	 */
	public function format_display_price( float $price ): string {
		if ( $price <= 0.0 ) {
			return __( 'Free', 'wpcalibrate-gift-wrap' );
		}

		if ( function_exists( 'wc_price' ) ) {
			return (string) wc_price( $price );
		}

		return sprintf( '%.2f', $price );
	}

	/**
	 * Determine if the style charge is taxable based on style & global settings.
	 *
	 * @param Style $style Style instance.
	 * @return bool
	 */
	public function is_style_taxable( Style $style ): bool {
		$global_tax_status = SettingsRepository::get( 'tax_status', 'taxable' );
		if ( 'none' === $global_tax_status ) {
			return false;
		}
		return $style->is_taxable();
	}

	/**
	 * Get the configured tax class.
	 *
	 * @return string
	 */
	public function get_tax_class(): string {
		return (string) SettingsRepository::get( 'tax_class', '' );
	}
}
