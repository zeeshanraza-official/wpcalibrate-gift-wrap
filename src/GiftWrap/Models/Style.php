<?php
/**
 * Gift Wrap Style domain model.
 *
 * @package WPCalibrate\GiftWrap\Models
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\GiftWrap\Models;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Style model class.
 */
class Style {

	private string $id;
	private string $name;
	private string $description;
	private int $image_id;
	private string $image_url;
	private float $price;
	private bool $enabled;
	private int $sort_order;
	private bool $taxable;
	private string $badge;

	/**
	 * Restriction rules for this style.
	 *
	 * @var array<string, mixed>
	 */
	private array $rules;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $data Raw style attributes.
	 */
	public function __construct( array $data = array() ) {
		$this->id          = isset( $data['id'] ) ? sanitize_key( (string) $data['id'] ) : self::generate_id();
		$this->name        = isset( $data['name'] ) ? sanitize_text_field( (string) $data['name'] ) : '';
		$this->description = isset( $data['description'] ) ? sanitize_textarea_field( (string) $data['description'] ) : '';
		$this->image_id    = isset( $data['image_id'] ) ? absint( $data['image_id'] ) : 0;
		$this->image_url   = isset( $data['image_url'] ) ? esc_url_raw( (string) $data['image_url'] ) : '';
		$this->price       = isset( $data['price'] ) ? max( 0.0, (float) $data['price'] ) : 0.0;
		$this->enabled     = isset( $data['enabled'] ) ? (bool) $data['enabled'] : true;
		$this->sort_order  = isset( $data['sort_order'] ) ? (int) $data['sort_order'] : 0;
		$this->taxable     = isset( $data['taxable'] ) ? (bool) $data['taxable'] : true;
		$this->badge       = isset( $data['badge'] ) ? sanitize_text_field( (string) $data['badge'] ) : '';

		// If image_id is set and image_url is empty, fetch attachment url.
		if ( $this->image_id > 0 && empty( $this->image_url ) && function_exists( 'wp_get_attachment_image_url' ) ) {
			$url = wp_get_attachment_image_url( $this->image_id, 'medium' );
			if ( $url ) {
				$this->image_url = $url;
			}
		}

		$raw_rules = isset( $data['rules'] ) && is_array( $data['rules'] ) ? $data['rules'] : array();
		$this->rules = array(
			'included_products'   => isset( $raw_rules['included_products'] ) ? array_values( array_filter( array_map( 'absint', (array) $raw_rules['included_products'] ) ) ) : array(),
			'excluded_products'   => isset( $raw_rules['excluded_products'] ) ? array_values( array_filter( array_map( 'absint', (array) $raw_rules['excluded_products'] ) ) ) : array(),
			'included_categories' => isset( $raw_rules['included_categories'] ) ? array_values( array_filter( array_map( 'absint', (array) $raw_rules['included_categories'] ) ) ) : array(),
			'excluded_categories' => isset( $raw_rules['excluded_categories'] ) ? array_values( array_filter( array_map( 'absint', (array) $raw_rules['excluded_categories'] ) ) ) : array(),
			'min_price'           => ( isset( $raw_rules['min_price'] ) && '' !== $raw_rules['min_price'] && null !== $raw_rules['min_price'] ) ? max( 0.0, (float) $raw_rules['min_price'] ) : null,
			'max_price'           => ( isset( $raw_rules['max_price'] ) && '' !== $raw_rules['max_price'] && null !== $raw_rules['max_price'] ) ? max( 0.0, (float) $raw_rules['max_price'] ) : null,
		);
	}

	/**
	 * Generate a unique ID for a style.
	 *
	 * @return string
	 */
	public static function generate_id(): string {
		return 'style_' . substr( md5( uniqid( (string) wp_rand(), true ) ), 0, 10 );
	}

	public function get_id(): string {
		return $this->id;
	}

	public function get_name(): string {
		return $this->name;
	}

	public function get_description(): string {
		return $this->description;
	}

	public function get_image_id(): int {
		return $this->image_id;
	}

	public function get_image_url(): string {
		return $this->image_url;
	}

	public function get_price(): float {
		return $this->price;
	}

	public function is_free(): bool {
		return $this->price <= 0.0;
	}

	public function is_enabled(): bool {
		return $this->enabled;
	}

	public function get_sort_order(): int {
		return $this->sort_order;
	}

	public function is_taxable(): bool {
		return $this->taxable;
	}

	public function get_badge(): string {
		return $this->badge;
	}

	/**
	 * Get restriction rules.
	 *
	 * @return array<string, mixed>
	 */
	public function get_rules(): array {
		return $this->rules;
	}

	/**
	 * Check if style is eligible for a specific product.
	 *
	 * @param int        $product_id Product ID.
	 * @param float|null $product_price Product price.
	 * @param int[]      $category_ids Category IDs.
	 * @return bool
	 */
	public function is_eligible_for_product( int $product_id, ?float $product_price = null, array $category_ids = array() ): bool {
		if ( ! $this->enabled ) {
			return false;
		}

		$rules = $this->rules;

		// 1. Explicit style product exclusion.
		if ( ! empty( $rules['excluded_products'] ) && in_array( $product_id, $rules['excluded_products'], true ) ) {
			return false;
		}

		// 2. Explicit style product inclusion.
		if ( ! empty( $rules['included_products'] ) && ! in_array( $product_id, $rules['included_products'], true ) ) {
			return false;
		}

		// 3. Category exclusions.
		if ( ! empty( $rules['excluded_categories'] ) && ! empty( $category_ids ) ) {
			$matched_excluded = array_intersect( $category_ids, $rules['excluded_categories'] );
			if ( ! empty( $matched_excluded ) ) {
				return false;
			}
		}

		// 4. Category inclusions.
		if ( ! empty( $rules['included_categories'] ) ) {
			if ( empty( $category_ids ) ) {
				return false;
			}
			$matched_included = array_intersect( $category_ids, $rules['included_categories'] );
			if ( empty( $matched_included ) ) {
				return false;
			}
		}

		// 5. Price bounds.
		if ( null !== $product_price ) {
			if ( null !== $rules['min_price'] && $product_price < $rules['min_price'] ) {
				return false;
			}
			if ( null !== $rules['max_price'] && $product_price > $rules['max_price'] ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Convert to array for storage and API responses.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'          => $this->id,
			'name'        => $this->name,
			'description' => $this->description,
			'image_id'    => $this->image_id,
			'image_url'   => $this->image_url,
			'price'       => $this->price,
			'enabled'     => $this->enabled,
			'sort_order'  => $this->sort_order,
			'taxable'     => $this->taxable,
			'badge'       => $this->badge,
			'rules'       => $this->rules,
		);
	}
}
