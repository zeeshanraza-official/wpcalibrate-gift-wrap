<?php
/**
 * Style Repository Interface.
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
 * Interface StyleRepositoryInterface
 */
interface StyleRepositoryInterface {

	/**
	 * Retrieve all styles, sorted by sort_order ascending.
	 *
	 * @return Style[]
	 */
	public function all(): array;

	/**
	 * Retrieve only enabled styles, sorted by sort_order ascending.
	 *
	 * @return Style[]
	 */
	public function all_active(): array;

	/**
	 * Find a style by internal ID.
	 *
	 * @param string $id Style ID.
	 * @return Style|null
	 */
	public function find( string $id ): ?Style;

	/**
	 * Save a style (create or update).
	 *
	 * @param Style $style Style instance.
	 * @return bool
	 */
	public function save( Style $style ): bool;

	/**
	 * Delete a style by ID.
	 *
	 * @param string $id Style ID.
	 * @return bool
	 */
	public function delete( string $id ): bool;

	/**
	 * Update sort ordering of styles.
	 *
	 * @param string[] $ordered_ids Array of style IDs in preferred order.
	 * @return bool
	 */
	public function reorder( array $ordered_ids ): bool;
}
