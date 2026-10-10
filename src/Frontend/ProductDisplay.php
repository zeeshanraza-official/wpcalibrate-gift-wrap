<?php
/**
 * Product Display for WPCalibrate Gift Wrap.
 *
 * Renders the gift wrap selector, options, and gift message fields on single product pages.
 *
 * @package WPCalibrate\GiftWrap\Frontend
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\Frontend;

use WC_Product;
use WPCalibrate\GiftWrap\GiftWrap\Repositories\SettingsRepository;
use WPCalibrate\GiftWrap\GiftWrap\Repositories\StyleRepositoryInterface;
use WPCalibrate\GiftWrap\GiftWrap\Services\EligibilityService;
use WPCalibrate\GiftWrap\GiftWrap\Services\PriceService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ProductDisplay
 */
class ProductDisplay {

	private StyleRepositoryInterface $style_repository;
	private EligibilityService $eligibility_service;
	private PriceService $price_service;

	public function __construct(
		StyleRepositoryInterface $style_repository,
		EligibilityService $eligibility_service,
		PriceService $price_service
	) {
		$this->style_repository    = $style_repository;
		$this->eligibility_service = $eligibility_service;
		$this->price_service       = $price_service;
	}

	/**
	 * Register frontend hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		if ( 'yes' !== SettingsRepository::get( 'locations_product', 'yes' ) ) {
			return;
		}

		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'render_product_wrap_selector' ), 25 );
	}

	/**
	 * Render the gift wrap selector.
	 *
	 * @return void
	 */
	public function render_product_wrap_selector(): void {
		global $product;

		if ( ! $product instanceof WC_Product || ! $this->eligibility_service->is_product_eligible( $product ) ) {
			return;
		}

		$styles = $this->eligibility_service->get_eligible_styles_for_product( $product );
		if ( empty( $styles ) ) {
			return;
		}

		$show_images = 'yes' === SettingsRepository::get( 'display_images', 'yes' );
		$image_size = (string) SettingsRepository::get( 'image_size', 'thumbnail' );
		$show_descriptions = 'yes' === SettingsRepository::get( 'display_descriptions', 'yes' );
		$show_prices = 'yes' === SettingsRepository::get( 'display_prices', 'yes' );
		$show_messages = $this->eligibility_service->are_gift_messages_enabled() && 'yes' === SettingsRepository::get( 'enable_per_item_messages', 'yes' );
		$max_len = absint( SettingsRepository::get( 'gift_message_max_length', 500 ) );

		?>
		<div class="wpcalibrate-product-wrap-wrapper" role="region" aria-labelledby="wpcalibrate-wrap-title" style="margin: 1.25em 0; padding: 1em; border: 1px solid #e2e8f0; border-radius: 8px; background: #fafafa;">
			<h4 id="wpcalibrate-wrap-title" style="margin-top: 0; margin-bottom: 0.75em; font-size: 1.05em; display: flex; align-items: center; gap: 8px;">
				<span class="dashicons dashicons-heart" aria-hidden="true" style="color: #6366f1;"></span>
				<?php echo esc_html( SettingsRepository::get( 'label_gift_wrap', __( 'Gift Wrapping', 'wpcalibrate-gift-wrap' ) ) ); ?>
			</h4>

			<div class="wpcalibrate-styles-list" role="radiogroup" aria-label="<?php echo esc_attr( SettingsRepository::get( 'label_choose_style', __( 'Choose a gift wrap style', 'wpcalibrate-gift-wrap' ) ) ); ?>">
				<!-- Option: None -->
				<label class="wpcalibrate-style-card wpcalibrate-style-none" style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px; padding: 8px 12px; background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer;">
					<input type="radio" name="wpcalibrate_gift_wrap_style" value="none" checked="checked" class="wpcalibrate-style-radio" />
					<div class="wpcalibrate-style-details">
						<span class="wpcalibrate-style-name" style="font-weight: 500;"><?php echo esc_html( SettingsRepository::get( 'label_no_wrap', __( 'No gift wrap', 'wpcalibrate-gift-wrap' ) ) ); ?></span>
					</div>
				</label>

				<!-- Option: Each style -->
				<?php foreach ( $styles as $style ) : ?>
					<?php
					$price = $this->price_service->calculate_item_wrap_cost( $style, 1 );
					$price_formatted = $this->price_service->format_display_price( $price );
					$img_url = $style->get_image_url();
					?>
					<label class="wpcalibrate-style-card" style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px; padding: 8px 12px; background: #fff; border: 1px solid #cbd5e1; border-radius: 6px; cursor: pointer;">
						<input type="radio" name="wpcalibrate_gift_wrap_style" value="<?php echo esc_attr( $style->get_id() ); ?>" class="wpcalibrate-style-radio" />

						<?php if ( $show_images && ! empty( $img_url ) ) : ?>
							<img
								src="<?php echo esc_url( $img_url ); ?>"
								alt="<?php echo esc_attr( $style->get_name() ); ?>"
								class="wpcalibrate-style-img"
								style="width: 44px; height: 44px; object-fit: cover; border-radius: 4px; border: 1px solid #e2e8f0;"
							/>
						<?php endif; ?>

						<div class="wpcalibrate-style-details" style="flex: 1;">
							<div style="display: flex; justify-content: space-between; align-items: baseline;">
								<span class="wpcalibrate-style-name" style="font-weight: 600;">
									<?php echo esc_html( $style->get_name() ); ?>
									<?php if ( '' !== $style->get_badge() ) : ?>
										<span class="wpcalibrate-badge" style="font-size: 0.75em; padding: 2px 6px; border-radius: 4px; background: #e0e7ff; color: #4338ca; font-weight: normal; margin-left: 6px;">
											<?php echo esc_html( $style->get_badge() ); ?>
										</span>
									<?php endif; ?>
								</span>

								<?php if ( $show_prices ) : ?>
									<span class="wpcalibrate-style-price" style="font-weight: 600; color: #1e293b;">
										+<?php echo wp_kses_post( $price_formatted ); ?>
									</span>
								<?php endif; ?>
							</div>

							<?php if ( $show_descriptions && '' !== $style->get_description() ) : ?>
								<div class="wpcalibrate-style-desc" style="font-size: 0.85em; color: #64748b; margin-top: 2px;">
									<?php echo esc_html( $style->get_description() ); ?>
								</div>
							<?php endif; ?>
						</div>
					</label>
				<?php endforeach; ?>
			</div>

			<?php if ( $show_messages ) : ?>
				<div class="wpcalibrate-product-message-container" id="wpcalibrate-product-message-container" style="display: none; margin-top: 12px; padding-top: 10px; border-top: 1px dashed #cbd5e1;">
					<label for="wpcalibrate_gift_wrap_message" style="display: block; font-weight: 500; font-size: 0.9em; margin-bottom: 4px;">
						<?php echo esc_html( SettingsRepository::get( 'label_gift_message', __( 'Gift Message', 'wpcalibrate-gift-wrap' ) ) ); ?>
						<span style="font-weight: normal; font-size: 0.85em; color: #64748b;">(<?php printf( esc_html__( 'Max %d characters', 'wpcalibrate-gift-wrap' ), $max_len ); ?>)</span>
					</label>
					<textarea
						name="wpcalibrate_gift_wrap_message"
						id="wpcalibrate_gift_wrap_message"
						rows="3"
						maxlength="<?php echo esc_attr( (string) $max_len ); ?>"
						style="width: 100%; border: 1px solid #cbd5e1; border-radius: 4px; padding: 6px; font-size: 0.9em;"
						placeholder="<?php esc_attr_e( 'Write your personal message here...', 'wpcalibrate-gift-wrap' ); ?>"
					></textarea>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}
