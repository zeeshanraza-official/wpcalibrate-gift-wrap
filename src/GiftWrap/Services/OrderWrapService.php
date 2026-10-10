<?php
/**
 * Order Wrap Service for WPCalibrate Gift Wrap.
 *
 * Handles order-time snapshot persistence and reading via WooCommerce CRUD APIs.
 *
 * @package WPCalibrate\GiftWrap\Services
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\GiftWrap\Services;

use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Product;
use WPCalibrate\GiftWrap\GiftWrap\Models\Style;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class OrderWrapService
 */
class OrderWrapService {

	// Item metadata keys.
	public const ITEM_META_STYLE_ID   = '_wpcalibrate_gift_wrap_style_id';
	public const ITEM_META_STYLE_NAME = '_wpcalibrate_gift_wrap_style_name';
	public const ITEM_META_UNIT_PRICE = '_wpcalibrate_gift_wrap_unit_price';
	public const ITEM_META_QUANTITY   = '_wpcalibrate_gift_wrap_quantity';
	public const ITEM_META_COST       = '_wpcalibrate_gift_wrap_cost';
	public const ITEM_META_MESSAGE    = '_wpcalibrate_gift_wrap_message';

	// Order metadata keys.
	public const ORDER_META_STYLE_ID   = '_wpcalibrate_order_gift_wrap_style_id';
	public const ORDER_META_STYLE_NAME = '_wpcalibrate_order_gift_wrap_style_name';
	public const ORDER_META_COST       = '_wpcalibrate_order_gift_wrap_cost';
	public const ORDER_META_MESSAGE    = '_wpcalibrate_order_gift_wrap_message';

	// Order fee identifier.
	public const FEE_WHOLE_ORDER_KEY   = 'wpcalibrate_whole_order_gift_wrap';

	/**
	 * Attach gift wrap snapshot to a WC_Order_Item_Product.
	 *
	 * @param WC_Order_Item_Product $item Order line item.
	 * @param Style                 $style Authoritative Style instance.
	 * @param int                   $wrapped_quantity Quantity wrapped.
	 * @param float                 $cost Total wrap cost.
	 * @param string                $message Gift message.
	 * @return void
	 */
	public function attach_item_wrap(
		WC_Order_Item_Product $item,
		Style $style,
		int $wrapped_quantity,
		float $cost,
		string $message = ''
	): void {
		$item->update_meta_data( self::ITEM_META_STYLE_ID, $style->get_id() );
		$item->update_meta_data( self::ITEM_META_STYLE_NAME, $style->get_name() );
		$item->update_meta_data( self::ITEM_META_UNIT_PRICE, $style->get_price() );
		$item->update_meta_data( self::ITEM_META_QUANTITY, $wrapped_quantity );
		$item->update_meta_data( self::ITEM_META_COST, $cost );

		if ( '' !== trim( $message ) ) {
			$item->update_meta_data( self::ITEM_META_MESSAGE, sanitize_textarea_field( $message ) );
		}
	}

	/**
	 * Attach whole-order gift wrap snapshot to WC_Order.
	 *
	 * @param WC_Order $order Order instance.
	 * @param Style    $style Authoritative Style instance.
	 * @param float    $cost Whole-order wrap cost.
	 * @param string   $message Whole-order gift message.
	 * @return void
	 */
	public function attach_order_wrap(
		WC_Order $order,
		Style $style,
		float $cost,
		string $message = ''
	): void {
		$order->update_meta_data( self::ORDER_META_STYLE_ID, $style->get_id() );
		$order->update_meta_data( self::ORDER_META_STYLE_NAME, $style->get_name() );
		$order->update_meta_data( self::ORDER_META_COST, $cost );

		if ( '' !== trim( $message ) ) {
			$order->update_meta_data( self::ORDER_META_MESSAGE, sanitize_textarea_field( $message ) );
		}
	}

	/**
	 * Get item wrap snapshot data.
	 *
	 * @param WC_Order_Item $item Order item.
	 * @return array<string, mixed>|null
	 */
	public function get_item_wrap( WC_Order_Item $item ): ?array {
		$style_id = $item->get_meta( self::ITEM_META_STYLE_ID );
		if ( empty( $style_id ) ) {
			return null;
		}

		return array(
			'style_id'   => (string) $style_id,
			'style_name' => (string) $item->get_meta( self::ITEM_META_STYLE_NAME ),
			'unit_price' => (float) $item->get_meta( self::ITEM_META_UNIT_PRICE ),
			'quantity'   => (int) $item->get_meta( self::ITEM_META_QUANTITY ),
			'cost'       => (float) $item->get_meta( self::ITEM_META_COST ),
			'message'    => (string) $item->get_meta( self::ITEM_META_MESSAGE ),
		);
	}

	/**
	 * Get whole-order wrap snapshot data.
	 *
	 * @param WC_Order $order Order instance.
	 * @return array<string, mixed>|null
	 */
	public function get_order_wrap( WC_Order $order ): ?array {
		$style_id = $order->get_meta( self::ORDER_META_STYLE_ID );
		if ( empty( $style_id ) ) {
			return null;
		}

		return array(
			'style_id'   => (string) $style_id,
			'style_name' => (string) $order->get_meta( self::ORDER_META_STYLE_NAME ),
			'cost'       => (float) $order->get_meta( self::ORDER_META_COST ),
			'message'    => (string) $order->get_meta( self::ORDER_META_MESSAGE ),
		);
	}

	/**
	 * Check if order has any wrapped items or whole-order wrap.
	 *
	 * @param WC_Order $order Order instance.
	 * @return bool
	 */
	public function has_any_wrap( WC_Order $order ): bool {
		if ( null !== $this->get_order_wrap( $order ) ) {
			return true;
		}

		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( null !== $this->get_item_wrap( $item ) ) {
				return true;
			}
		}

		return false;
	}
}
