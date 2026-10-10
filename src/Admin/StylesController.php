<?php
/**
 * Styles Controller for WPCalibrate Gift Wrap.
 *
 * Handles creation, editing, deletion, reordering, and validation of gift wrap styles.
 *
 * @package WPCalibrate\GiftWrap\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\Admin;

use WPCalibrate\GiftWrap\GiftWrap\Models\Style;
use WPCalibrate\GiftWrap\GiftWrap\Repositories\StyleRepositoryInterface;
use WPCalibrate\GiftWrap\GiftWrap\Services\PriceService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class StylesController
 */
class StylesController {

	private StyleRepositoryInterface $style_repository;
	private PriceService $price_service;

	public function __construct( StyleRepositoryInterface $style_repository, PriceService $price_service ) {
		$this->style_repository = $style_repository;
		$this->price_service    = $price_service;
	}

	/**
	 * Handle admin style actions (save, delete).
	 *
	 * @return void
	 */
	public function handle_actions(): void {
		if ( ! isset( $_POST['wpcalibrate_style_action'] ) && ! isset( $_GET['wpcalibrate_style_action'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Unauthorized user action.', 'wpcalibrate-gift-wrap' ) );
		}

		$action = sanitize_key( wp_unslash( $_POST['wpcalibrate_style_action'] ?? $_GET['wpcalibrate_style_action'] ?? '' ) );

		if ( 'save_style' === $action ) {
			$this->save_style();
		} elseif ( 'delete_style' === $action ) {
			$this->delete_style();
		}
	}

	/**
	 * Save (create or update) a style.
	 *
	 * @return void
	 */
	private function save_style(): void {
		check_admin_referer( 'wpcalibrate_save_style_action', 'wpcalibrate_style_nonce' );

		$id = isset( $_POST['style_id'] ) ? sanitize_key( wp_unslash( $_POST['style_id'] ) ) : '';
		if ( empty( $id ) ) {
			$id = Style::generate_id();
		}

		$name = isset( $_POST['style_name'] ) ? sanitize_text_field( wp_unslash( $_POST['style_name'] ) ) : '';
		if ( empty( $name ) ) {
			add_settings_error( 'wpcalibrate_styles', 'style_name_required', __( 'Style Name is required.', 'wpcalibrate-gift-wrap' ), 'error' );
			return;
		}

		$description = isset( $_POST['style_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['style_description'] ) ) : '';
		$image_id    = isset( $_POST['style_image_id'] ) ? absint( $_POST['style_image_id'] ) : 0;
		$image_url   = isset( $_POST['style_image_url'] ) ? esc_url_raw( wp_unslash( $_POST['style_image_url'] ) ) : '';
		$price       = isset( $_POST['style_price'] ) ? max( 0.0, (float) $_POST['style_price'] ) : 0.0;
		$enabled     = isset( $_POST['style_enabled'] ) && '1' === (string) $_POST['style_enabled'];
		$taxable     = isset( $_POST['style_taxable'] ) && '1' === (string) $_POST['style_taxable'];
		$sort_order  = isset( $_POST['style_sort_order'] ) ? (int) $_POST['style_sort_order'] : 0;
		$badge       = isset( $_POST['style_badge'] ) ? sanitize_text_field( wp_unslash( $_POST['style_badge'] ) ) : '';

		// Rules.
		$rules = array();
		if ( isset( $_POST['rule_included_cats'] ) ) {
			$rules['included_categories'] = array_filter( array_map( 'absint', (array) $_POST['rule_included_cats'] ) );
		}
		if ( isset( $_POST['rule_excluded_cats'] ) ) {
			$rules['excluded_categories'] = array_filter( array_map( 'absint', (array) $_POST['rule_excluded_cats'] ) );
		}
		if ( isset( $_POST['rule_min_price'] ) && '' !== trim( (string) $_POST['rule_min_price'] ) ) {
			$rules['min_price'] = max( 0.0, (float) $_POST['rule_min_price'] );
		}
		if ( isset( $_POST['rule_max_price'] ) && '' !== trim( (string) $_POST['rule_max_price'] ) ) {
			$rules['max_price'] = max( 0.0, (float) $_POST['rule_max_price'] );
		}

		$style = new Style(
			array(
				'id'          => $id,
				'name'        => $name,
				'description' => $description,
				'image_id'    => $image_id,
				'image_url'   => $image_url,
				'price'       => $price,
				'enabled'     => $enabled,
				'sort_order'  => $sort_order,
				'taxable'     => $taxable,
				'badge'       => $badge,
				'rules'       => $rules,
			)
		);

		$this->style_repository->save( $style );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'wpcalibrate-gift-wrap',
					'tab'     => 'styles',
					'message' => 'style_saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Delete a style.
	 *
	 * @return void
	 */
	private function delete_style(): void {
		$id = isset( $_GET['style_id'] ) ? sanitize_key( wp_unslash( $_GET['style_id'] ) ) : '';
		check_admin_referer( 'wpcalibrate_delete_style_' . $id );

		if ( ! empty( $id ) ) {
			$this->style_repository->delete( $id );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'wpcalibrate-gift-wrap',
					'tab'     => 'styles',
					'message' => 'style_deleted',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render Styles tab content.
	 *
	 * @return void
	 */
	public function render(): void {
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : 'list';
		$style_id = isset( $_GET['style_id'] ) ? sanitize_key( wp_unslash( $_GET['style_id'] ) ) : '';

		if ( 'edit' === $action || 'new' === $action ) {
			$style = ! empty( $style_id ) ? $this->style_repository->find( $style_id ) : null;
			$this->render_form( $style );
		} else {
			$this->render_list();
		}
	}

	/**
	 * Render styles table list.
	 *
	 * @return void
	 */
	private function render_list(): void {
		$styles = $this->style_repository->all();
		$new_url = add_query_arg(
			array(
				'page'   => 'wpcalibrate-gift-wrap',
				'tab'    => 'styles',
				'action' => 'new',
			),
			admin_url( 'admin.php' )
		);
		?>
		<div class="wpcalibrate-styles-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
			<p class="description" style="margin:0;">
				<?php esc_html_e( 'Create and manage wrapping styles, pricing, badges, and preview images.', 'wpcalibrate-gift-wrap' ); ?>
			</p>
			<a href="<?php echo esc_url( $new_url ); ?>" class="button button-primary">
				<span class="dashicons dashicons-plus-alt2" style="vertical-align:middle;margin-top:-2px;"></span>
				<?php esc_html_e( 'Add Wrapping Style', 'wpcalibrate-gift-wrap' ); ?>
			</a>
		</div>

		<?php if ( empty( $styles ) ) : ?>
			<div class="wpcalibrate-empty-state" style="padding:40px;text-align:center;background:#fff;border:1px dashed #cbd5e1;border-radius:8px;">
				<span class="dashicons dashicons-art" style="font-size:48px;width:48px;height:48px;color:#94a3b8;"></span>
				<h3 style="color:#334155;margin-top:10px;"><?php esc_html_e( 'No Gift Wrapping Styles Yet', 'wpcalibrate-gift-wrap' ); ?></h3>
				<p style="color:#64748b;max-width:400px;margin:0 auto 15px auto;">
					<?php esc_html_e( 'Add your first wrapping style so customers can start selecting gift wraps in your store.', 'wpcalibrate-gift-wrap' ); ?>
				</p>
				<a href="<?php echo esc_url( $new_url ); ?>" class="button button-primary">
					<?php esc_html_e( 'Create Your First Style', 'wpcalibrate-gift-wrap' ); ?>
				</a>
			</div>
		<?php else : ?>
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width:60px;"><?php esc_html_e( 'Image', 'wpcalibrate-gift-wrap' ); ?></th>
						<th><?php esc_html_e( 'Name', 'wpcalibrate-gift-wrap' ); ?></th>
						<th style="width:110px;"><?php esc_html_e( 'Price', 'wpcalibrate-gift-wrap' ); ?></th>
						<th style="width:90px;"><?php esc_html_e( 'Badge', 'wpcalibrate-gift-wrap' ); ?></th>
						<th style="width:90px;"><?php esc_html_e( 'Taxable', 'wpcalibrate-gift-wrap' ); ?></th>
						<th style="width:90px;"><?php esc_html_e( 'Status', 'wpcalibrate-gift-wrap' ); ?></th>
						<th style="width:140px;text-align:right;"><?php esc_html_e( 'Actions', 'wpcalibrate-gift-wrap' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $styles as $style ) : ?>
						<?php
						$edit_url = add_query_arg(
							array(
								'page'     => 'wpcalibrate-gift-wrap',
								'tab'      => 'styles',
								'action'   => 'edit',
								'style_id' => $style->get_id(),
							),
							admin_url( 'admin.php' )
						);
						$delete_url = wp_nonce_url(
							add_query_arg(
								array(
									'page'                    => 'wpcalibrate-gift-wrap',
									'tab'                     => 'styles',
									'wpcalibrate_style_action' => 'delete_style',
									'style_id'                => $style->get_id(),
								),
								admin_url( 'admin.php' )
							),
							'wpcalibrate_delete_style_' . $style->get_id()
						);
						?>
						<tr>
							<td>
								<?php if ( ! empty( $style->get_image_url() ) ) : ?>
									<img src="<?php echo esc_url( $style->get_image_url() ); ?>" alt="" style="width:40px;height:40px;object-fit:cover;border-radius:4px;border:1px solid #e2e8f0;" />
								<?php else : ?>
									<div style="width:40px;height:40px;background:#f1f5f9;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#94a3b8;">
										<span class="dashicons dashicons-format-image"></span>
									</div>
								<?php endif; ?>
							</td>
							<td>
								<strong>
									<a href="<?php echo esc_url( $edit_url ); ?>">
										<?php echo esc_html( $style->get_name() ); ?>
									</a>
								</strong>
								<?php if ( ! empty( $style->get_description() ) ) : ?>
									<div style="font-size:12px;color:#64748b;"><?php echo esc_html( $style->get_description() ); ?></div>
								<?php endif; ?>
							</td>
							<td>
								<?php echo wp_kses_post( $this->price_service->format_display_price( $style->get_price() ) ); ?>
							</td>
							<td>
								<?php if ( '' !== $style->get_badge() ) : ?>
									<span style="display:inline-block;padding:2px 8px;font-size:11px;font-weight:600;background:#e0e7ff;color:#4338ca;border-radius:4px;">
										<?php echo esc_html( $style->get_badge() ); ?>
									</span>
								<?php else : ?>
									<span style="color:#94a3b8;">&mdash;</span>
								<?php endif; ?>
							</td>
							<td>
								<?php echo $style->is_taxable() ? esc_html__( 'Yes', 'wpcalibrate-gift-wrap' ) : esc_html__( 'No', 'wpcalibrate-gift-wrap' ); ?>
							</td>
							<td>
								<?php if ( $style->is_enabled() ) : ?>
									<span style="color:#16a34a;font-weight:600;">● <?php esc_html_e( 'Enabled', 'wpcalibrate-gift-wrap' ); ?></span>
								<?php else : ?>
									<span style="color:#94a3b8;">● <?php esc_html_e( 'Disabled', 'wpcalibrate-gift-wrap' ); ?></span>
								<?php endif; ?>
							</td>
							<td style="text-align:right;">
								<a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small">
									<?php esc_html_e( 'Edit', 'wpcalibrate-gift-wrap' ); ?>
								</a>
								<a
									href="<?php echo esc_url( $delete_url ); ?>"
									class="button button-small button-link-delete"
									onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this wrapping style? Historical order data will remain preserved.', 'wpcalibrate-gift-wrap' ) ); ?>');"
								>
									<?php esc_html_e( 'Delete', 'wpcalibrate-gift-wrap' ); ?>
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
		<?php
	}

	/**
	 * Render Add / Edit form.
	 *
	 * @param Style|null $style Style instance if editing.
	 * @return void
	 */
	private function render_form( ?Style $style ): void {
		$is_edit = null !== $style;
		$back_url = add_query_arg(
			array(
				'page' => 'wpcalibrate-gift-wrap',
				'tab'  => 'styles',
			),
			admin_url( 'admin.php' )
		);

		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);
		if ( ! is_array( $categories ) || is_wp_error( $categories ) ) {
			$categories = array();
		}

		$rules = $style ? $style->get_rules() : array();
		$included_cats = (array) ( $rules['included_categories'] ?? array() );
		$excluded_cats = (array) ( $rules['excluded_categories'] ?? array() );
		?>
		<div style="margin-bottom:15px;">
			<a href="<?php echo esc_url( $back_url ); ?>">
				&larr; <?php esc_html_e( 'Back to Wrapping Styles', 'wpcalibrate-gift-wrap' ); ?>
			</a>
		</div>

		<h3><?php echo $is_edit ? esc_html__( 'Edit Wrapping Style', 'wpcalibrate-gift-wrap' ) : esc_html__( 'Add New Wrapping Style', 'wpcalibrate-gift-wrap' ); ?></h3>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=wpcalibrate-gift-wrap&tab=styles' ) ); ?>" style="max-width:850px;background:#fff;padding:20px;border:1px solid #cbd5e1;border-radius:8px;">
			<?php wp_nonce_field( 'wpcalibrate_save_style_action', 'wpcalibrate_style_nonce' ); ?>
			<input type="hidden" name="wpcalibrate_style_action" value="save_style" />
			<input type="hidden" name="style_id" value="<?php echo esc_attr( $style ? $style->get_id() : '' ); ?>" />

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="style_name"><?php esc_html_e( 'Style Name', 'wpcalibrate-gift-wrap' ); ?> <span class="required">*</span></label>
					</th>
					<td>
						<input type="text" name="style_name" id="style_name" value="<?php echo esc_attr( $style ? $style->get_name() : '' ); ?>" class="regular-text" required />
						<p class="description"><?php esc_html_e( 'E.g., Elegant Gold Ribbon, Festive Red, Kraft Eco-Wrap.', 'wpcalibrate-gift-wrap' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="style_description"><?php esc_html_e( 'Description', 'wpcalibrate-gift-wrap' ); ?></label>
					</th>
					<td>
						<textarea name="style_description" id="style_description" rows="3" class="large-text"><?php echo esc_textarea( $style ? $style->get_description() : '' ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Brief description visible to customers under the style name.', 'wpcalibrate-gift-wrap' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="style_price"><?php esc_html_e( 'Price', 'wpcalibrate-gift-wrap' ); ?> (<?php echo esc_html( function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$' ); ?>)</label>
					</th>
					<td>
						<input type="number" step="0.01" min="0" name="style_price" id="style_price" value="<?php echo esc_attr( (string) ( $style ? $style->get_price() : '0.00' ) ); ?>" class="small-text" />
						<p class="description"><?php esc_html_e( 'Set to 0.00 for complimentary / free gift wrapping.', 'wpcalibrate-gift-wrap' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="style_badge"><?php esc_html_e( 'Customer Badge', 'wpcalibrate-gift-wrap' ); ?></label>
					</th>
					<td>
						<input type="text" name="style_badge" id="style_badge" value="<?php echo esc_attr( $style ? $style->get_badge() : '' ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Free, Eco, Popular...', 'wpcalibrate-gift-wrap' ); ?>" />
						<p class="description"><?php esc_html_e( 'Optional concise badge highlighted next to the name. Pure text only (no HTML).', 'wpcalibrate-gift-wrap' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Preview Image', 'wpcalibrate-gift-wrap' ); ?></label>
					</th>
					<td>
						<div style="display:flex;align-items:center;gap:15px;">
							<div id="style_image_preview" style="width:64px;height:64px;border:1px dashed #cbd5e1;border-radius:4px;display:flex;align-items:center;justify-content:center;background:#f8fafc;overflow:hidden;">
								<?php if ( $style && ! empty( $style->get_image_url() ) ) : ?>
									<img src="<?php echo esc_url( $style->get_image_url() ); ?>" alt="" style="width:100%;height:100%;object-fit:cover;" />
								<?php else : ?>
									<span class="dashicons dashicons-format-image" style="color:#94a3b8;"></span>
								<?php endif; ?>
							</div>
							<div>
								<input type="hidden" name="style_image_id" id="style_image_id" value="<?php echo esc_attr( (string) ( $style ? $style->get_image_id() : '' ) ); ?>" />
								<input type="hidden" name="style_image_url" id="style_image_url" value="<?php echo esc_attr( $style ? $style->get_image_url() : '' ); ?>" />
								<button type="button" class="button" id="wpcalibrate_media_upload_btn">
									<?php esc_html_e( 'Select Image', 'wpcalibrate-gift-wrap' ); ?>
								</button>
								<button type="button" class="button" id="wpcalibrate_media_remove_btn" style="<?php echo ( $style && ! empty( $style->get_image_url() ) ) ? '' : 'display:none;'; ?>">
									<?php esc_html_e( 'Remove Image', 'wpcalibrate-gift-wrap' ); ?>
								</button>
								<p class="description"><?php esc_html_e( 'Select an image from the WordPress Media Library to visually represent this style.', 'wpcalibrate-gift-wrap' ); ?></p>
							</div>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="style_enabled"><?php esc_html_e( 'Status', 'wpcalibrate-gift-wrap' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" name="style_enabled" id="style_enabled" value="1" <?php checked( $style ? $style->is_enabled() : true ); ?> />
							<?php esc_html_e( 'Enable this wrapping style for customer selection', 'wpcalibrate-gift-wrap' ); ?>
						</label>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="style_taxable"><?php esc_html_e( 'Tax Status', 'wpcalibrate-gift-wrap' ); ?></label>
					</th>
					<td>
						<label>
							<input type="checkbox" name="style_taxable" id="style_taxable" value="1" <?php checked( $style ? $style->is_taxable() : true ); ?> />
							<?php esc_html_e( 'Apply store tax rates to this wrapping style', 'wpcalibrate-gift-wrap' ); ?>
						</label>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="style_sort_order"><?php esc_html_e( 'Sort Priority', 'wpcalibrate-gift-wrap' ); ?></label>
					</th>
					<td>
						<input type="number" name="style_sort_order" id="style_sort_order" value="<?php echo esc_attr( (string) ( $style ? $style->get_sort_order() : 0 ) ); ?>" class="small-text" />
						<p class="description"><?php esc_html_e( 'Lower numbers display first.', 'wpcalibrate-gift-wrap' ); ?></p>
					</td>
				</tr>
			</table>

			<h4 style="margin-top:25px;border-top:1px solid #e2e8f0;padding-top:15px;"><?php esc_html_e( 'Optional Category & Price Restrictions', 'wpcalibrate-gift-wrap' ); ?></h4>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Category Restrictions', 'wpcalibrate-gift-wrap' ); ?></th>
					<td>
						<div style="display:flex;gap:20px;">
							<div>
								<strong><?php esc_html_e( 'Only allow for categories:', 'wpcalibrate-gift-wrap' ); ?></strong>
								<div style="max-height:120px;overflow-y:auto;border:1px solid #cbd5e1;padding:6px;margin-top:4px;border-radius:4px;background:#f8fafc;">
									<?php foreach ( $categories as $cat ) : ?>
										<label style="display:block;font-size:12px;">
											<input type="checkbox" name="rule_included_cats[]" value="<?php echo esc_attr( (string) $cat->term_id ); ?>" <?php checked( in_array( (int) $cat->term_id, $included_cats, true ) ); ?> />
											<?php echo esc_html( $cat->name ); ?>
										</label>
									<?php endforeach; ?>
								</div>
							</div>

							<div>
								<strong><?php esc_html_e( 'Exclude categories:', 'wpcalibrate-gift-wrap' ); ?></strong>
								<div style="max-height:120px;overflow-y:auto;border:1px solid #cbd5e1;padding:6px;margin-top:4px;border-radius:4px;background:#f8fafc;">
									<?php foreach ( $categories as $cat ) : ?>
										<label style="display:block;font-size:12px;">
											<input type="checkbox" name="rule_excluded_cats[]" value="<?php echo esc_attr( (string) $cat->term_id ); ?>" <?php checked( in_array( (int) $cat->term_id, $excluded_cats, true ) ); ?> />
											<?php echo esc_html( $cat->name ); ?>
										</label>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Applicable Product Price Range', 'wpcalibrate-gift-wrap' ); ?></th>
					<td>
						<label>
							<?php esc_html_e( 'Min price:', 'wpcalibrate-gift-wrap' ); ?>
							<input type="number" step="0.01" min="0" name="rule_min_price" value="<?php echo esc_attr( isset( $rules['min_price'] ) ? (string) $rules['min_price'] : '' ); ?>" class="small-text" />
						</label>
						&nbsp;&nbsp;
						<label>
							<?php esc_html_e( 'Max price:', 'wpcalibrate-gift-wrap' ); ?>
							<input type="number" step="0.01" min="0" name="rule_max_price" value="<?php echo esc_attr( isset( $rules['max_price'] ) ? (string) $rules['max_price'] : '' ); ?>" class="small-text" />
						</label>
					</td>
				</tr>
			</table>

			<p class="submit" style="margin-top:20px;">
				<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $is_edit ? esc_attr__( 'Update Wrapping Style', 'wpcalibrate-gift-wrap' ) : esc_attr__( 'Save Wrapping Style', 'wpcalibrate-gift-wrap' ); ?>" />
				<a href="<?php echo esc_url( $back_url ); ?>" class="button" style="margin-left:8px;"><?php esc_html_e( 'Cancel', 'wpcalibrate-gift-wrap' ); ?></a>
			</p>
		</form>
		<?php
	}
}
