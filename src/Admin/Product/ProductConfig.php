<?php
/**
 * Product Admin Configuration for WPCalibrate Gift Wrap.
 *
 * Adds Gift Wrapping settings panel to the WooCommerce product editor.
 *
 * @package WPCalibrate\GiftWrap\Admin\Product
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\Admin\Product;

use WC_Product;
use WPCalibrate\GiftWrap\GiftWrap\Repositories\StyleRepositoryInterface;
use WPCalibrate\GiftWrap\GiftWrap\Services\EligibilityService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ProductConfig
 */
class ProductConfig {

	private StyleRepositoryInterface $style_repository;

	public function __construct( StyleRepositoryInterface $style_repository ) {
		$this->style_repository = $style_repository;
	}

	/**
	 * Register product hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_product_data_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'render_product_data_panel' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_product_meta' ), 10, 1 );
	}

	/**
	 * Add Gift Wrapping tab to product edit screen.
	 *
	 * @param array<string, mixed> $tabs Product tabs.
	 * @return array<string, mixed>
	 */
	public function add_product_data_tab( array $tabs ): array {
		$tabs['wpcalibrate_gift_wrap'] = array(
			'label'    => __( 'Gift Wrapping', 'wpcalibrate-gift-wrap' ),
			'target'   => 'wpcalibrate_gift_wrap_data',
			'class'    => array( 'show_if_simple', 'show_if_variable' ),
			'priority' => 70,
		);
		return $tabs;
	}

	/**
	 * Render Gift Wrapping panel fields.
	 *
	 * @return void
	 */
	public function render_product_data_panel(): void {
		global $post;
		$product_id = $post->ID;

		$enabled_meta = get_post_meta( $product_id, EligibilityService::META_PRODUCT_ENABLE, true );
		if ( empty( $enabled_meta ) ) {
			$enabled_meta = 'default';
		}

		$allowed_styles = get_post_meta( $product_id, EligibilityService::META_PRODUCT_STYLES, true );
		if ( ! is_array( $allowed_styles ) ) {
			$allowed_styles = array();
		}

		$exclude_whole = get_post_meta( $product_id, EligibilityService::META_PRODUCT_EXCLUDE_WHOLE_ORDER, true );
		$all_styles    = $this->style_repository->all();
		?>
		<div id="wpcalibrate_gift_wrap_data" class="panel woocommerce_options_panel hidden">
			<div class="options_group">
				<p class="form-field">
					<label for="_wpcalibrate_gift_wrap_enabled">
						<?php esc_html_e( 'Gift Wrap Availability', 'wpcalibrate-gift-wrap' ); ?>
					</label>
					<select id="_wpcalibrate_gift_wrap_enabled" name="_wpcalibrate_gift_wrap_enabled">
						<option value="default" <?php selected( $enabled_meta, 'default' ); ?>>
							<?php esc_html_e( 'Default (Inherit global rules)', 'wpcalibrate-gift-wrap' ); ?>
						</option>
						<option value="yes" <?php selected( $enabled_meta, 'yes' ); ?>>
							<?php esc_html_e( 'Enabled (Explicitly enable for this product)', 'wpcalibrate-gift-wrap' ); ?>
						</option>
						<option value="no" <?php selected( $enabled_meta, 'no' ); ?>>
							<?php esc_html_e( 'Disabled (Explicitly exclude this product)', 'wpcalibrate-gift-wrap' ); ?>
						</option>
					</select>
				</p>

				<?php if ( ! empty( $all_styles ) ) : ?>
					<p class="form-field">
						<label><?php esc_html_e( 'Allowed Wrap Styles', 'wpcalibrate-gift-wrap' ); ?></label>
						<span class="description" style="display:block;margin-bottom:6px;">
							<?php esc_html_e( 'Select specific styles allowed for this product. Leave unchecked to allow all eligible styles.', 'wpcalibrate-gift-wrap' ); ?>
						</span>
						<?php foreach ( $all_styles as $style ) : ?>
							<label style="display:inline-block;margin-right:15px;font-weight:normal;">
								<input
									type="checkbox"
									name="_wpcalibrate_gift_wrap_styles[]"
									value="<?php echo esc_attr( $style->get_id() ); ?>"
									<?php checked( in_array( $style->get_id(), $allowed_styles, true ) ); ?>
								/>
								<?php echo esc_html( $style->get_name() ); ?>
							</label>
						<?php endforeach; ?>
					</p>
				<?php endif; ?>

				<p class="form-field">
					<label for="_wpcalibrate_exclude_whole_order">
						<?php esc_html_e( 'Exclude from Whole-Order Wrap', 'wpcalibrate-gift-wrap' ); ?>
					</label>
					<input
						type="checkbox"
						id="_wpcalibrate_exclude_whole_order"
						name="_wpcalibrate_exclude_whole_order"
						value="yes"
						<?php checked( 'yes', $exclude_whole ); ?>
					/>
					<span class="description">
						<?php esc_html_e( 'When checked, orders containing this product cannot use whole-order gift wrapping.', 'wpcalibrate-gift-wrap' ); ?>
					</span>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Save product metadata.
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public function save_product_meta( int $product_id ): void {
		if ( ! current_user_can( 'edit_product', $product_id ) ) {
			return;
		}

		// Save enabled override.
		if ( isset( $_POST['_wpcalibrate_gift_wrap_enabled'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$val = sanitize_key( wp_unslash( $_POST['_wpcalibrate_gift_wrap_enabled'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( in_array( $val, array( 'default', 'yes', 'no' ), true ) ) {
				update_post_meta( $product_id, EligibilityService::META_PRODUCT_ENABLE, $val );
			}
		}

		// Save allowed styles.
		if ( isset( $_POST['_wpcalibrate_gift_wrap_styles'] ) && is_array( $_POST['_wpcalibrate_gift_wrap_styles'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$styles = array_map( 'sanitize_key', wp_unslash( $_POST['_wpcalibrate_gift_wrap_styles'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			update_post_meta( $product_id, EligibilityService::META_PRODUCT_STYLES, $styles );
		} else {
			delete_post_meta( $product_id, EligibilityService::META_PRODUCT_STYLES );
		}

		// Save exclude whole order.
		if ( isset( $_POST['_wpcalibrate_exclude_whole_order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			update_post_meta( $product_id, EligibilityService::META_PRODUCT_EXCLUDE_WHOLE_ORDER, 'yes' );
		} else {
			delete_post_meta( $product_id, EligibilityService::META_PRODUCT_EXCLUDE_WHOLE_ORDER );
		}
	}
}
