<?php
/**
 * Concrete Style Repository using WordPress Options API.
 *
 * @package WPCalibrate\GiftWrap\Repositories
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\GiftWrap\Repositories;

use WPCalibrate\GiftWrap\GiftWrap\Models\Style;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class StyleRepository
 */
class StyleRepository implements StyleRepositoryInterface {

	public const OPTION_NAME = 'wpcalibrate_gift_wrap_styles';

	/**
	 * In-memory cache of styles.
	 *
	 * @var array<string, Style>|null
	 */
	private ?array $cache = null;

	/**
	 * Clear in-memory cache.
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		$this->cache = null;
	}

	/**
	 * Load all raw styles from WordPress options.
	 *
	 * @return array<string, Style>
	 */
	private function load_styles(): array {
		if ( null !== $this->cache ) {
			return $this->cache;
		}

		$raw = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}

		$styles = array();
		foreach ( $raw as $id => $data ) {
			if ( is_array( $data ) ) {
				$style = new Style( $data );
				$styles[ $style->get_id() ] = $style;
			}
		}

		$this->cache = $styles;
		return $this->cache;
	}

	/**
	 * Retrieve all styles, sorted by sort_order ascending.
	 *
	 * @return Style[]
	 */
	public function all(): array {
		$styles = array_values( $this->load_styles() );

		usort(
			$styles,
			static function ( Style $a, Style $b ): int {
				if ( $a->get_sort_order() === $b->get_sort_order() ) {
					return strcmp( $a->get_name(), $b->get_name() );
				}
				return $a->get_sort_order() <=> $b->get_sort_order();
			}
		);

		return $styles;
	}

	/**
	 * Retrieve only enabled styles, sorted by sort_order ascending.
	 *
	 * @return Style[]
	 */
	public function all_active(): array {
		$all = $this->all();
		return array_values(
			array_filter(
				$all,
				static fn( Style $s ) => $s->is_enabled()
			)
		);
	}

	/**
	 * Find a style by internal ID.
	 *
	 * @param string $id Style ID.
	 * @return Style|null
	 */
	public function find( string $id ): ?Style {
		$styles = $this->load_styles();
		return $styles[ $id ] ?? null;
	}

	/**
	 * Save a style (create or update).
	 *
	 * @param Style $style Style instance.
	 * @return bool
	 */
	public function save( Style $style ): bool {
		$styles = $this->load_styles();
		$styles[ $style->get_id() ] = $style;

		$raw_to_save = array();
		foreach ( $styles as $id => $item ) {
			$raw_to_save[ $id ] = $item->to_array();
		}

		$updated = update_option( self::OPTION_NAME, $raw_to_save );
		$this->clear_cache();
		return $updated;
	}

	/**
	 * Delete a style by ID.
	 *
	 * @param string $id Style ID.
	 * @return bool
	 */
	public function delete( string $id ): bool {
		$styles = $this->load_styles();
		if ( ! isset( $styles[ $id ] ) ) {
			return false;
		}

		unset( $styles[ $id ] );

		$raw_to_save = array();
		foreach ( $styles as $key => $item ) {
			$raw_to_save[ $key ] = $item->to_array();
		}

		$updated = update_option( self::OPTION_NAME, $raw_to_save );
		$this->clear_cache();
		return $updated;
	}

	/**
	 * Update sort ordering of styles.
	 *
	 * @param string[] $ordered_ids Array of style IDs in preferred order.
	 * @return bool
	 */
	public function reorder( array $ordered_ids ): bool {
		$styles = $this->load_styles();
		$order = 0;

		foreach ( $ordered_ids as $id ) {
			$id = sanitize_key( (string) $id );
			if ( isset( $styles[ $id ] ) ) {
				$data = $styles[ $id ]->to_array();
				$data['sort_order'] = $order++;
				$styles[ $id ] = new Style( $data );
			}
		}

		$raw_to_save = array();
		foreach ( $styles as $id => $item ) {
			$raw_to_save[ $id ] = $item->to_array();
		}

		$updated = update_option( self::OPTION_NAME, $raw_to_save );
		$this->clear_cache();
		return $updated;
	}
}
