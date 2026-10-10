<?php
/**
 * Eligibility Service for WPCalibrate Gift Wrap.
 *
 * Implements deterministic rule precedence:
 * 1. Product-level explicit override (disabled -> false, enabled -> true)
 * 2. Explicit global product exclusion (in excluded_products -> false)
 * 3. Explicit global product inclusion (if inclusion mode active and not in included_products -> false)
 * 4. Category exclusion (if any product category in excluded_categories -> false)
 * 5. Category inclusion (if category inclusion mode active and no category in included_categories -> false)
 * 6. Global default (rules_mode or master switch)
 *
 * @package WPCalibrate\GiftWrap\Services
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\GiftWrap\Services;

use WC_Product;
use WPCalibrate\GiftWrap\GiftWrap\Models\Style;
use WPCalibrate\GiftWrap\GiftWrap\Repositories\SettingsRepository;
use WPCalibrate\GiftWrap\GiftWrap\Repositories\StyleRepositoryInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class EligibilityService
 */
class EligibilityService {

	public const META_PRODUCT_ENABLE = '_wpcalibrate_gift_wrap_enabled'; // 'default', 'yes', 'no'
	public const META_PRODUCT_STYLES = '_wpcalibrate_gift_wrap_styles';  // array of style IDs or empty for all
	public const META_PRODUCT_EXCLUDE_WHOLE_ORDER = '_wpcalibrate_exclude_whole_order'; // 'yes', 'no'

	private StyleRepositoryInterface $style_repository;

	public function __construct( StyleRepositoryInterface $style_repository ) {
		$this->style_repository = $style_repository;
	}

	/**
	 * Is gift wrapping enabled globally?
	 *
	 * @return bool
	 */
	public function is_globally_enabled(): bool {
		return 'yes' === SettingsRepository::get( 'enabled', 'yes' );
	}

	/**
	 * Is per-item wrapping enabled?
	 *
	 * @return bool
	 */
	public function is_per_item_enabled(): bool {
		return $this->is_globally_enabled() && 'yes' === SettingsRepository::get( 'enable_per_item', 'yes' );
	}

	/**
	 * Is whole-order wrapping enabled?
	 *
	 * @return bool
	 */
	public function is_whole_order_enabled(): bool {
		return $this->is_globally_enabled() && 'yes' === SettingsRepository::get( 'enable_whole_order', 'yes' );
	}

	/**
	 * Can both per-item and whole-order wrapping be combined?
	 *
	 * @return bool
	 */
	public function can_combine_modes(): bool {
		return 'yes' === SettingsRepository::get( 'allow_combine_modes', 'no' );
	}

	/**
	 * Are gift messages enabled?
	 *
	 * @return bool
	 */
	public function are_gift_messages_enabled(): bool {
		return 'yes' === SettingsRepository::get( 'enable_gift_messages', 'yes' );
	}

	/**
	 * Check if a product is eligible for gift wrapping.
	 *
	 * @param int|WC_Product $product Product ID or instance.
	 * @return bool
	 */
	public function is_product_eligible( int|WC_Product $product ): bool {
		if ( ! $this->is_per_item_enabled() ) {
			return false;
		}

		$product_obj = is_numeric( $product ) ? wc_get_product( $product ) : $product;
		if ( ! $product_obj instanceof WC_Product ) {
			return false;
		}

		// Virtual or downloadable without physical shipping usually excluded by store logic,
		// but allow filterable hook for custom behaviors.
		$is_virtual = $product_obj->is_virtual();
		if ( apply_filters( 'wpcalibrate_gift_wrap_exclude_virtual_products', $is_virtual, $product_obj ) ) {
			return false;
		}

		$product_id = $product_obj->is_type( 'variation' ) ? $product_obj->get_parent_id() : $product_obj->get_id();

		// Precedence Rule 1: Product-level explicit override.
		$override = get_post_meta( $product_id, self::META_PRODUCT_ENABLE, true );
		if ( 'no' === $override ) {
			return false;
		}
		if ( 'yes' === $override ) {
			return true;
		}

		// Global Rules Precedence.
		$settings = SettingsRepository::get_all();
		$rules_mode = $settings['rules_mode'] ?? 'all';
		$excluded_products = (array) ( $settings['excluded_products'] ?? array() );
		$included_products = (array) ( $settings['included_products'] ?? array() );
		$excluded_cats     = (array) ( $settings['excluded_categories'] ?? array() );
		$included_cats     = (array) ( $settings['included_categories'] ?? array() );

		// Precedence Rule 2: Explicit global product exclusion.
		if ( in_array( $product_id, $excluded_products, true ) ) {
			return false;
		}

		// Precedence Rule 3: Explicit global product inclusion (if inclusion mode active).
		if ( 'include' === $rules_mode && in_array( $product_id, $included_products, true ) ) {
			return true;
		}

		// Fetch product categories.
		$product_cat_ids = $this->get_product_category_ids( $product_id );

		// Precedence Rule 4: Category exclusion.
		if ( ! empty( $excluded_cats ) && ! empty( $product_cat_ids ) ) {
			$intersection = array_intersect( $product_cat_ids, $excluded_cats );
			if ( ! empty( $intersection ) ) {
				return false;
			}
		}

		// Precedence Rule 5: Category inclusion.
		if ( 'include' === $rules_mode ) {
			if ( ! empty( $included_cats ) && ! empty( $product_cat_ids ) ) {
				$intersection = array_intersect( $product_cat_ids, $included_cats );
				if ( ! empty( $intersection ) ) {
					return true;
				}
			}
			// If include mode is active and neither product nor category matched, product is ineligible.
			return false;
		}

		// Precedence Rule 6: Global default.
		return true;
	}

	/**
	 * Check if product is excluded from whole-order wrapping.
	 *
	 * @param int|WC_Product $product Product ID or instance.
	 * @return bool
	 */
	public function is_product_excluded_from_whole_order( int|WC_Product $product ): bool {
		$product_obj = is_numeric( $product ) ? wc_get_product( $product ) : $product;
		if ( ! $product_obj instanceof WC_Product ) {
			return false;
		}

		$product_id = $product_obj->is_type( 'variation' ) ? $product_obj->get_parent_id() : $product_obj->get_id();
		return 'yes' === get_post_meta( $product_id, self::META_PRODUCT_EXCLUDE_WHOLE_ORDER, true );
	}

	/**
	 * Get eligible styles for a given product.
	 *
	 * @param int|WC_Product $product Product instance or ID.
	 * @return Style[]
	 */
	public function get_eligible_styles_for_product( int|WC_Product $product ): array {
		if ( ! $this->is_product_eligible( $product ) ) {
			return array();
		}

		$product_obj = is_numeric( $product ) ? wc_get_product( $product ) : $product;
		if ( ! $product_obj instanceof WC_Product ) {
			return array();
		}

		$parent_id = $product_obj->is_type( 'variation' ) ? $product_obj->get_parent_id() : $product_obj->get_id();
		$product_price = (float) $product_obj->get_price();
		$cat_ids = $this->get_product_category_ids( $parent_id );

		// Check if product limits styles to specific ones.
		$product_styles = get_post_meta( $parent_id, self::META_PRODUCT_STYLES, true );
		$allowed_style_ids = is_array( $product_styles ) ? array_filter( array_map( 'sanitize_key', $product_styles ) ) : array();

		$all_active = $this->style_repository->all_active();
		$eligible = array();

		foreach ( $all_active as $style ) {
			if ( ! empty( $allowed_style_ids ) && ! in_array( $style->get_id(), $allowed_style_ids, true ) ) {
				continue;
			}

			if ( $style->is_eligible_for_product( $parent_id, $product_price, $cat_ids ) ) {
				$eligible[] = $style;
			}
		}

		return apply_filters( 'wpcalibrate_gift_wrap_product_eligible_styles', $eligible, $product_obj );
	}

	/**
	 * Get eligible styles for whole order wrapping.
	 *
	 * @param \WC_Cart|null $cart Cart instance.
	 * @return Style[]
	 */
	public function get_eligible_styles_for_order( ?\WC_Cart $cart = null ): array {
		if ( ! $this->is_whole_order_enabled() ) {
			return array();
		}

		// If cart contains products excluded from whole-order wrapping, check setting/filter.
		if ( $cart ) {
			foreach ( $cart->get_cart() as $cart_item ) {
				$product = $cart_item['data'] ?? null;
				if ( $product instanceof WC_Product && $this->is_product_excluded_from_whole_order( $product ) ) {
					// Store policy: If any item in cart is explicitly excluded from whole-order wrapping,
					// whole order wrap may be blocked or filtered.
					$allow_order_wrap = apply_filters( 'wpcalibrate_gift_wrap_allow_order_wrap_with_excluded_item', false, $product, $cart );
					if ( ! $allow_order_wrap ) {
						return array();
					}
				}
			}
		}

		$all_active = $this->style_repository->all_active();
		return apply_filters( 'wpcalibrate_gift_wrap_order_eligible_styles', $all_active, $cart );
	}

	/**
	 * Validate a style selection for a product.
	 *
	 * @param string         $style_id Style ID.
	 * @param int|WC_Product $product Product instance or ID.
	 * @return Style|null Returns Style if valid, null otherwise.
	 */
	public function validate_style_for_product( string $style_id, int|WC_Product $product ): ?Style {
		$style = $this->style_repository->find( $style_id );
		if ( ! $style || ! $style->is_enabled() ) {
			return null;
		}

		$eligible_styles = $this->get_eligible_styles_for_product( $product );
		foreach ( $eligible_styles as $eligible ) {
			if ( $eligible->get_id() === $style_id ) {
				return $style;
			}
		}

		return null;
	}

	/**
	 * Validate a style selection for whole-order.
	 *
	 * @param string         $style_id Style ID.
	 * @param \WC_Cart|null  $cart Cart instance.
	 * @return Style|null
	 */
	public function validate_style_for_order( string $style_id, ?\WC_Cart $cart = null ): ?Style {
		$style = $this->style_repository->find( $style_id );
		if ( ! $style || ! $style->is_enabled() ) {
			return null;
		}

		$eligible_styles = $this->get_eligible_styles_for_order( $cart );
		foreach ( $eligible_styles as $eligible ) {
			if ( $eligible->get_id() === $style_id ) {
				return $style;
			}
		}

		return null;
	}

	/**
	 * Helper: get product category term IDs.
	 *
	 * @param int $product_id Product ID.
	 * @return int[]
	 */
	private function get_product_category_ids( int $product_id ): array {
		if ( ! function_exists( 'get_the_terms' ) ) {
			return array();
		}
		$terms = get_the_terms( $product_id, 'product_cat' );
		if ( ! is_array( $terms ) || ( function_exists( 'is_wp_error' ) && is_wp_error( $terms ) ) ) {
			return array();
		}
		return array_values( array_map( static fn( $term ) => (int) $term->term_id, $terms ) );
	}
}
