<?php
/**
 * Settings Controller for WPCalibrate Gift Wrap.
 *
 * Manages the settings tabs, routing, rendering, and safe persistence.
 *
 * @package WPCalibrate\GiftWrap\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\Admin;

use WPCalibrate\GiftWrap\License\LicenseServiceInterface;
use WPCalibrate\GiftWrap\GiftWrap\Repositories\SettingsRepository;
use WPCalibrate\GiftWrap\Support\SupportInfo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SettingsController
 */
class SettingsController {

	public const PAGE_SLUG = 'wpcalibrate-gift-wrap';

	private StylesController $styles_controller;
	private LicenseServiceInterface $license_service;

	public function __construct( StylesController $styles_controller, LicenseServiceInterface $license_service ) {
		$this->styles_controller = $styles_controller;
		$this->license_service   = $license_service;
	}

	/**
	 * Get supported settings tabs.
	 *
	 * @return array<string, string>
	 */
	public function get_tabs(): array {
		return array(
			'overview' => __( 'Overview', 'wpcalibrate-gift-wrap' ),
			'general'  => __( 'General', 'wpcalibrate-gift-wrap' ),
			'styles'   => __( 'Wrap Styles', 'wpcalibrate-gift-wrap' ),
			'display'  => __( 'Display', 'wpcalibrate-gift-wrap' ),
			'rules'    => __( 'Rules', 'wpcalibrate-gift-wrap' ),
			'orders'   => __( 'Orders & Emails', 'wpcalibrate-gift-wrap' ),
			'license'  => __( 'License', 'wpcalibrate-gift-wrap' ),
			'support'  => __( 'Support', 'wpcalibrate-gift-wrap' ),
		);
	}

	/**
	 * Handle admin POST/GET actions.
	 *
	 * @return void
	 */
	public function handle_actions(): void {
		// Styles controller actions.
		$this->styles_controller->handle_actions();

		// Settings save action.
		if ( isset( $_POST['wpcalibrate_save_settings'] ) ) {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'Unauthorized user action.', 'wpcalibrate-gift-wrap' ) );
			}

			check_admin_referer( 'wpcalibrate_gift_wrap_save_settings', 'wpcalibrate_settings_nonce' );

			$active_tab = isset( $_POST['current_tab'] ) ? sanitize_key( wp_unslash( $_POST['current_tab'] ) ) : 'general';

			$raw_settings = $_POST['wpcalibrate_settings'] ?? array();
			if ( is_array( $raw_settings ) ) {
				SettingsRepository::update_all( $raw_settings );
			}

			wp_safe_redirect(
				add_query_arg(
					array(
						'page'    => self::PAGE_SLUG,
						'tab'     => $active_tab,
						'message' => 'settings_saved',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}
	}

	/**
	 * Render the complete settings interface.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access these settings.', 'wpcalibrate-gift-wrap' ) );
		}

		$tabs = $this->get_tabs();
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
		if ( ! array_key_exists( $current_tab, $tabs ) ) {
			$current_tab = 'overview';
		}

		$settings = SettingsRepository::get_all();
		?>
		<div class="wrap wpcalibrate-settings-wrap">
			<div class="wpcalibrate-header" style="display:flex;align-items:center;justify-content:space-between;margin-top:10px;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid #cbd5e1;">
				<div style="display:flex;align-items:center;gap:12px;">
					<?php
					$icon_path = WPCALIBRATE_GIFT_WRAP_DIR . 'branding/icon-dark.png';
					if ( file_exists( $icon_path ) ) :
						$icon_url = WPCALIBRATE_GIFT_WRAP_URL . 'branding/icon-dark.png';
					?>
						<img src="<?php echo esc_url( $icon_url ); ?>" alt="WPCalibrate" style="height:32px;width:auto;" />
					<?php endif; ?>
					<h1 style="margin:0;font-size:22px;color:#0f172a;">
						<?php esc_html_e( 'WPCalibrate Gift Wrap', 'wpcalibrate-gift-wrap' ); ?>
						<span style="font-size:12px;font-weight:normal;color:#64748b;margin-left:8px;">v<?php echo esc_html( WPCALIBRATE_GIFT_WRAP_VERSION ); ?></span>
					</h1>
				</div>
				<div>
					<a href="<?php echo esc_url( SupportInfo::MARKETPLACE ); ?>" target="_blank" rel="noopener noreferrer" class="button">
						<?php esc_html_e( 'Marketplace', 'wpcalibrate-gift-wrap' ); ?> &rarr;
					</a>
				</div>
			</div>

			<?php
			if ( isset( $_GET['message'] ) ) {
				$msg = sanitize_key( wp_unslash( $_GET['message'] ) );
				if ( 'settings_saved' === $msg ) {
					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved successfully.', 'wpcalibrate-gift-wrap' ) . '</p></div>';
				} elseif ( 'style_saved' === $msg ) {
					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Gift wrapping style saved.', 'wpcalibrate-gift-wrap' ) . '</p></div>';
				} elseif ( 'style_deleted' === $msg ) {
					echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Gift wrapping style deleted.', 'wpcalibrate-gift-wrap' ) . '</p></div>';
				}
			}
			?>

			<nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e( 'Plugin Settings Tabs', 'wpcalibrate-gift-wrap' ); ?>">
				<?php foreach ( $tabs as $tab_key => $tab_title ) : ?>
					<?php
					$tab_url = add_query_arg(
						array(
							'page' => self::PAGE_SLUG,
							'tab'  => $tab_key,
						),
						admin_url( 'admin.php' )
					);
					$is_active = ( $tab_key === $current_tab );
					?>
					<a
						href="<?php echo esc_url( $tab_url ); ?>"
						class="nav-tab <?php echo $is_active ? 'nav-tab-active' : ''; ?>"
					>
						<?php echo esc_html( $tab_title ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<div class="wpcalibrate-tab-content" style="margin-top:20px;">
				<?php
				switch ( $current_tab ) {
					case 'overview':
						$this->render_overview_tab();
						break;
					case 'styles':
						$this->styles_controller->render();
						break;
					case 'display':
						$this->render_display_tab( $settings );
						break;
					case 'rules':
						$this->render_rules_tab( $settings );
						break;
					case 'orders':
						$this->render_orders_tab( $settings );
						break;
					case 'license':
						$this->render_license_tab();
						break;
					case 'support':
						$this->render_support_tab();
						break;
					case 'general':
						$this->render_general_tab( $settings );
						break;
					default:
						$this->render_overview_tab();
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Overview tab explaining plugin purpose, method, and how to use.
	 *
	 * @return void
	 */
	private function render_overview_tab(): void {
		$styles_url  = add_query_arg( array( 'page' => self::PAGE_SLUG, 'tab' => 'styles' ), admin_url( 'admin.php' ) );
		$general_url = add_query_arg( array( 'page' => self::PAGE_SLUG, 'tab' => 'general' ), admin_url( 'admin.php' ) );
		$rules_url   = add_query_arg( array( 'page' => self::PAGE_SLUG, 'tab' => 'rules' ), admin_url( 'admin.php' ) );
		$orders_url  = add_query_arg( array( 'page' => self::PAGE_SLUG, 'tab' => 'orders' ), admin_url( 'admin.php' ) );
		$display_url = add_query_arg( array( 'page' => self::PAGE_SLUG, 'tab' => 'display' ), admin_url( 'admin.php' ) );
		$support_url = add_query_arg( array( 'page' => self::PAGE_SLUG, 'tab' => 'support' ), admin_url( 'admin.php' ) );
		?>
		<div class="wpcalibrate-overview-container" style="max-width: 960px;">
			<!-- Hero Banner -->
			<div class="wpcalibrate-hero-banner" style="background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%); color: #ffffff; padding: 28px 32px; border-radius: 8px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
				<div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
					<div>
						<span style="display: inline-block; background: rgba(99, 102, 241, 0.35); color: #c7d2fe; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; padding: 4px 10px; border-radius: 20px; margin-bottom: 10px;">
							<?php esc_html_e( 'WooCommerce Gift Wrapping System', 'wpcalibrate-gift-wrap' ); ?>
						</span>
						<h2 style="color: #ffffff; margin: 0 0 8px 0; font-size: 24px; font-weight: 700;">
							<?php esc_html_e( 'Elevate Your Store\'s Gifting Experience', 'wpcalibrate-gift-wrap' ); ?>
						</h2>
						<p style="color: #cbd5e1; margin: 0; font-size: 14px; max-width: 680px; line-height: 1.5;">
							<?php esc_html_e( 'WPCalibrate Gift Wrap provides a professional, production-grade gift-wrapping architecture for WooCommerce stores. Empower shoppers to wrap individual products or complete orders with transparent pricing and personalized messages.', 'wpcalibrate-gift-wrap' ); ?>
						</p>
					</div>
					<div style="display: flex; gap: 8px;">
						<a href="<?php echo esc_url( $styles_url ); ?>" class="button button-primary button-hero" style="background: #4f46e5; border-color: #4338ca; text-shadow: none;">
							<?php esc_html_e( 'Manage Styles', 'wpcalibrate-gift-wrap' ); ?> &rarr;
						</a>
					</div>
				</div>
			</div>

			<!-- Section 1: Purpose -->
			<div style="margin-bottom: 28px;">
				<h3 style="font-size: 18px; color: #0f172a; margin: 0 0 12px 0; display: flex; align-items: center; gap: 8px;">
					<span class="dashicons dashicons-flag" style="color: #4f46e5;"></span>
					<?php esc_html_e( 'Plugin Purpose & Business Value', 'wpcalibrate-gift-wrap' ); ?>
				</h3>
				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
					<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px;">
						<div style="font-size: 24px; margin-bottom: 8px;">🎁</div>
						<h4 style="margin: 0 0 6px 0; font-size: 15px; color: #1e293b;"><?php esc_html_e( 'Upsell Premium Gifting', 'wpcalibrate-gift-wrap' ); ?></h4>
						<p style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.5;">
							<?php esc_html_e( 'Offer custom wrapping styles with images, transparent pricing, or complimentary badges. Boost average order value (AOV) throughout holidays and special occasions.', 'wpcalibrate-gift-wrap' ); ?>
						</p>
					</div>

					<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px;">
						<div style="font-size: 24px; margin-bottom: 8px;">🛍️</div>
						<h4 style="margin: 0 0 6px 0; font-size: 15px; color: #1e293b;"><?php esc_html_e( 'Zero Shopper Friction', 'wpcalibrate-gift-wrap' ); ?></h4>
						<p style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.5;">
							<?php esc_html_e( 'Accessible, intuitive selectors seamlessly integrated into Single Product, Cart, and Checkout pages, supporting both Classic WooCommerce and modern Cart/Checkout Blocks.', 'wpcalibrate-gift-wrap' ); ?>
						</p>
					</div>

					<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px;">
						<div style="font-size: 24px; margin-bottom: 8px;">📋</div>
						<h4 style="margin: 0 0 6px 0; font-size: 15px; color: #1e293b;"><?php esc_html_e( 'Flawless Warehouse Fulfillment', 'wpcalibrate-gift-wrap' ); ?></h4>
						<p style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.5;">
							<?php esc_html_e( 'Immutable order-time snapshots store style names, unit prices, and customer messages. Warehouse staff see clear packing indicators directly in the order dashboard.', 'wpcalibrate-gift-wrap' ); ?>
						</p>
					</div>
				</div>
			</div>

			<!-- Section 2: Method / How to Use -->
			<div style="margin-bottom: 28px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 22px;">
				<h3 style="font-size: 18px; color: #0f172a; margin: 0 0 16px 0; display: flex; align-items: center; gap: 8px;">
					<span class="dashicons dashicons-clipboard" style="color: #4f46e5;"></span>
					<?php esc_html_e( 'Recommended Setup Method (Step-by-Step Workflow)', 'wpcalibrate-gift-wrap' ); ?>
				</h3>

				<div style="display: flex; flex-direction: column; gap: 14px;">
					<!-- Step 1 -->
					<div style="display: flex; gap: 16px; align-items: flex-start; background: #ffffff; padding: 14px 18px; border-radius: 6px; border: 1px solid #e2e8f0;">
						<div style="background: #e0e7ff; color: #4338ca; font-weight: 700; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 14px;">
							1
						</div>
						<div style="flex: 1;">
							<strong style="color: #1e293b; font-size: 14px;"><?php esc_html_e( 'Step 1: Configure Modes & Locations', 'wpcalibrate-gift-wrap' ); ?></strong>
							<p style="margin: 4px 0 8px 0; font-size: 13px; color: #64748b; line-height: 1.4;">
								<?php esc_html_e( 'Choose whether to enable Per-Item wrapping, Whole-Order wrapping, or both. By default, duplicate charges are prevented so shoppers wrapping items individually won\'t be accidentally billed for whole-order wrapping.', 'wpcalibrate-gift-wrap' ); ?>
							</p>
							<a href="<?php echo esc_url( $general_url ); ?>" class="button button-small">
								<?php esc_html_e( 'Configure General Settings', 'wpcalibrate-gift-wrap' ); ?> &rarr;
							</a>
						</div>
					</div>

					<!-- Step 2 -->
					<div style="display: flex; gap: 16px; align-items: flex-start; background: #ffffff; padding: 14px 18px; border-radius: 6px; border: 1px solid #e2e8f0;">
						<div style="background: #e0e7ff; color: #4338ca; font-weight: 700; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 14px;">
							2
						</div>
						<div style="flex: 1;">
							<strong style="color: #1e293b; font-size: 14px;"><?php esc_html_e( 'Step 2: Create Your Wrapping Styles', 'wpcalibrate-gift-wrap' ); ?></strong>
							<p style="margin: 4px 0 8px 0; font-size: 13px; color: #64748b; line-height: 1.4;">
								<?php esc_html_e( 'Add styles with custom names, preview images selected via the WordPress Media Library, prices (or 0.00 for free), custom badges (e.g., Free, Eco-friendly), and store tax rules.', 'wpcalibrate-gift-wrap' ); ?>
							</p>
							<a href="<?php echo esc_url( $styles_url ); ?>" class="button button-small">
								<?php esc_html_e( 'Manage Wrapping Styles', 'wpcalibrate-gift-wrap' ); ?> &rarr;
							</a>
						</div>
					</div>

					<!-- Step 3 -->
					<div style="display: flex; gap: 16px; align-items: flex-start; background: #ffffff; padding: 14px 18px; border-radius: 6px; border: 1px solid #e2e8f0;">
						<div style="background: #e0e7ff; color: #4338ca; font-weight: 700; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 14px;">
							3
						</div>
						<div style="flex: 1;">
							<strong style="color: #1e293b; font-size: 14px;"><?php esc_html_e( 'Step 3: Define Rules & Overrides', 'wpcalibrate-gift-wrap' ); ?></strong>
							<p style="margin: 4px 0 8px 0; font-size: 13px; color: #64748b; line-height: 1.4;">
								<?php esc_html_e( 'Set category exclusions/inclusions, price thresholds, or edit individual products in the WooCommerce editor to override availability or exclude oversized items from whole-order wrap.', 'wpcalibrate-gift-wrap' ); ?>
							</p>
							<a href="<?php echo esc_url( $rules_url ); ?>" class="button button-small">
								<?php esc_html_e( 'Configure Rules', 'wpcalibrate-gift-wrap' ); ?> &rarr;
							</a>
						</div>
					</div>

					<!-- Step 4 -->
					<div style="display: flex; gap: 16px; align-items: flex-start; background: #ffffff; padding: 14px 18px; border-radius: 6px; border: 1px solid #e2e8f0;">
						<div style="background: #e0e7ff; color: #4338ca; font-weight: 700; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 14px;">
							4
						</div>
						<div style="flex: 1;">
							<strong style="color: #1e293b; font-size: 14px;"><?php esc_html_e( 'Step 4: Enable Gift Messages & Emails', 'wpcalibrate-gift-wrap' ); ?></strong>
							<p style="margin: 4px 0 8px 0; font-size: 13px; color: #64748b; line-height: 1.4;">
								<?php esc_html_e( 'Allow shoppers to write personalized gift notes with character limits. Toggle whether messages appear in customer confirmation emails and My Account order views.', 'wpcalibrate-gift-wrap' ); ?>
							</p>
							<a href="<?php echo esc_url( $orders_url ); ?>" class="button button-small">
								<?php esc_html_e( 'Configure Orders & Emails', 'wpcalibrate-gift-wrap' ); ?> &rarr;
							</a>
						</div>
					</div>
				</div>
			</div>

			<!-- Section 3: Key Architecture Highlights -->
			<div style="margin-bottom: 24px;">
				<h3 style="font-size: 18px; color: #0f172a; margin: 0 0 12px 0; display: flex; align-items: center; gap: 8px;">
					<span class="dashicons dashicons-shield" style="color: #4f46e5;"></span>
					<?php esc_html_e( 'Key Architectural Highlights', 'wpcalibrate-gift-wrap' ); ?>
				</h3>
				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
					<div style="padding: 12px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px;">
						<strong style="display: block; margin-bottom: 2px; color: #0f172a;">⚡ <?php esc_html_e( 'HPOS Compatible', 'wpcalibrate-gift-wrap' ); ?></strong>
						<span style="color: #64748b;"><?php esc_html_e( 'Officially declares custom_order_tables compatibility using native WooCommerce CRUD APIs.', 'wpcalibrate-gift-wrap' ); ?></span>
					</div>

					<div style="padding: 12px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px;">
						<strong style="display: block; margin-bottom: 2px; color: #0f172a;">🧱 <?php esc_html_e( 'Blocks Ready', 'wpcalibrate-gift-wrap' ); ?></strong>
						<span style="color: #64748b;"><?php esc_html_e( 'Integrates with Cart and Checkout Blocks via official Store API schema extension points.', 'wpcalibrate-gift-wrap' ); ?></span>
					</div>

					<div style="padding: 12px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px;">
						<strong style="display: block; margin-bottom: 2px; color: #0f172a;">🔒 <?php esc_html_e( 'Authoritative Pricing', 'wpcalibrate-gift-wrap' ); ?></strong>
						<span style="color: #64748b;"><?php esc_html_e( 'Zero trust for browser pricing; all wrapping fees are verified server-side.', 'wpcalibrate-gift-wrap' ); ?></span>
					</div>

					<div style="padding: 12px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px;">
						<strong style="display: block; margin-bottom: 2px; color: #0f172a;">♿ <?php esc_html_e( 'WCAG 2.2 Accessible', 'wpcalibrate-gift-wrap' ); ?></strong>
						<span style="color: #64748b;"><?php esc_html_e( 'Semantic HTML, labeled controls, visible focus rings, and full RTL language support.', 'wpcalibrate-gift-wrap' ); ?></span>
					</div>
				</div>
			</div>

			<!-- Quick Links Bar -->
			<div style="padding: 16px 20px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 6px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
				<div style="font-size: 13px; color: #475569;">
					<strong><?php esc_html_e( 'Need Assistance?', 'wpcalibrate-gift-wrap' ); ?></strong>
					<span style="margin-left: 6px;"><?php esc_html_e( 'Direct engineering support is available via email and WhatsApp.', 'wpcalibrate-gift-wrap' ); ?></span>
				</div>
				<div style="display: flex; gap: 8px;">
					<a href="<?php echo esc_url( $support_url ); ?>" class="button">
						<?php esc_html_e( 'Contact Support', 'wpcalibrate-gift-wrap' ); ?>
					</a>
					<a href="<?php echo esc_url( SupportInfo::MARKETPLACE ); ?>" target="_blank" rel="noopener noreferrer" class="button">
						<?php esc_html_e( 'WPCalibrate Marketplace', 'wpcalibrate-gift-wrap' ); ?> &rarr;
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render General tab.
	 *
	 * @param array<string, mixed> $settings Settings array.
	 * @return void
	 */
	private function render_general_tab( array $settings ): void {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ); ?>">
			<?php wp_nonce_field( 'wpcalibrate_gift_wrap_save_settings', 'wpcalibrate_settings_nonce' ); ?>
			<input type="hidden" name="wpcalibrate_save_settings" value="1" />
			<input type="hidden" name="current_tab" value="general" />

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Master Switch', 'wpcalibrate-gift-wrap' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpcalibrate_settings[enabled]" value="yes" <?php checked( 'yes', $settings['enabled'] ); ?> />
							<strong><?php esc_html_e( 'Enable Gift Wrapping System', 'wpcalibrate-gift-wrap' ); ?></strong>
						</label>
						<p class="description"><?php esc_html_e( 'Turn gift wrapping on or off across your entire store.', 'wpcalibrate-gift-wrap' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Wrapping Modes', 'wpcalibrate-gift-wrap' ); ?></th>
					<td>
						<fieldset>
							<label style="display:block;margin-bottom:6px;">
								<input type="checkbox" name="wpcalibrate_settings[enable_per_item]" value="yes" <?php checked( 'yes', $settings['enable_per_item'] ); ?> />
								<?php esc_html_e( 'Enable Per-Item Gift Wrapping', 'wpcalibrate-gift-wrap' ); ?>
								<span class="description" style="display:block;margin-left:22px;color:#64748b;">
									<?php esc_html_e( 'Customers can wrap specific items individually from product or cart pages.', 'wpcalibrate-gift-wrap' ); ?>
								</span>
							</label>

							<label style="display:block;margin-bottom:6px;">
								<input type="checkbox" name="wpcalibrate_settings[enable_whole_order]" value="yes" <?php checked( 'yes', $settings['enable_whole_order'] ); ?> />
								<?php esc_html_e( 'Enable Whole-Order Gift Wrapping', 'wpcalibrate-gift-wrap' ); ?>
								<span class="description" style="display:block;margin-left:22px;color:#64748b;">
									<?php esc_html_e( 'Customers can wrap the complete order as a single unit at cart or checkout.', 'wpcalibrate-gift-wrap' ); ?>
								</span>
							</label>

							<div style="margin-top:12px;padding:10px;background:#f8fafc;border-left:4px solid #6366f1;border-radius:4px;">
								<label>
									<input type="checkbox" name="wpcalibrate_settings[allow_combine_modes]" value="yes" <?php checked( 'yes', $settings['allow_combine_modes'] ); ?> />
									<strong><?php esc_html_e( 'Allow combining both modes in a single order', 'wpcalibrate-gift-wrap' ); ?></strong>
								</label>
								<p class="description" style="margin-top:4px;">
									<?php esc_html_e( 'Default recommendation: Unchecked. Prevents accidental duplicate wrapping charges by not allowing whole-order wrapping when individual items have already been wrapped.', 'wpcalibrate-gift-wrap' ); ?>
								</p>
							</div>
						</fieldset>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Selector Locations', 'wpcalibrate-gift-wrap' ); ?></th>
					<td>
						<fieldset>
							<label style="display:inline-block;margin-right:20px;">
								<input type="checkbox" name="wpcalibrate_settings[locations_product]" value="yes" <?php checked( 'yes', $settings['locations_product'] ); ?> />
								<?php esc_html_e( 'Single Product Pages', 'wpcalibrate-gift-wrap' ); ?>
							</label>

							<label style="display:inline-block;margin-right:20px;">
								<input type="checkbox" name="wpcalibrate_settings[locations_cart]" value="yes" <?php checked( 'yes', $settings['locations_cart'] ); ?> />
								<?php esc_html_e( 'Cart Page', 'wpcalibrate-gift-wrap' ); ?>
							</label>

							<label style="display:inline-block;margin-right:20px;">
								<input type="checkbox" name="wpcalibrate_settings[locations_checkout]" value="yes" <?php checked( 'yes', $settings['locations_checkout'] ); ?> />
								<?php esc_html_e( 'Checkout Page', 'wpcalibrate-gift-wrap' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Gift Messages', 'wpcalibrate-gift-wrap' ); ?></th>
					<td>
						<label style="display:block;margin-bottom:6px;">
							<input type="checkbox" name="wpcalibrate_settings[enable_gift_messages]" value="yes" <?php checked( 'yes', $settings['enable_gift_messages'] ); ?> />
							<?php esc_html_e( 'Enable Customer Gift Messages', 'wpcalibrate-gift-wrap' ); ?>
						</label>

						<div style="margin-top:10px;">
							<label for="gift_message_max_length">
								<?php esc_html_e( 'Maximum Character Count:', 'wpcalibrate-gift-wrap' ); ?>
							</label>
							<input type="number" min="10" max="5000" name="wpcalibrate_settings[gift_message_max_length]" id="gift_message_max_length" value="<?php echo esc_attr( (string) $settings['gift_message_max_length'] ); ?>" class="small-text" />
							<span class="description"><?php esc_html_e( 'Recommended default: 500 characters.', 'wpcalibrate-gift-wrap' ); ?></span>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Taxation', 'wpcalibrate-gift-wrap' ); ?></th>
					<td>
						<select name="wpcalibrate_settings[tax_status]" id="tax_status">
							<option value="taxable" <?php selected( 'taxable', $settings['tax_status'] ); ?>>
								<?php esc_html_e( 'Taxable (Integrate with WooCommerce store tax rates)', 'wpcalibrate-gift-wrap' ); ?>
							</option>
							<option value="none" <?php selected( 'none', $settings['tax_status'] ); ?>>
								<?php esc_html_e( 'None (Non-taxable fees)', 'wpcalibrate-gift-wrap' ); ?>
							</option>
						</select>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save General Settings', 'wpcalibrate-gift-wrap' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Render Display tab.
	 *
	 * @param array<string, mixed> $settings Settings array.
	 * @return void
	 */
	private function render_display_tab( array $settings ): void {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ); ?>">
			<?php wp_nonce_field( 'wpcalibrate_gift_wrap_save_settings', 'wpcalibrate_settings_nonce' ); ?>
			<input type="hidden" name="wpcalibrate_save_settings" value="1" />
			<input type="hidden" name="current_tab" value="display" />

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Visual Elements', 'wpcalibrate-gift-wrap' ); ?></th>
					<td>
						<fieldset>
							<label style="display:block;margin-bottom:6px;">
								<input type="checkbox" name="wpcalibrate_settings[display_images]" value="yes" <?php checked( 'yes', $settings['display_images'] ); ?> />
								<?php esc_html_e( 'Display Style Preview Images', 'wpcalibrate-gift-wrap' ); ?>
							</label>

							<label style="display:block;margin-bottom:6px;">
								<input type="checkbox" name="wpcalibrate_settings[display_descriptions]" value="yes" <?php checked( 'yes', $settings['display_descriptions'] ); ?> />
								<?php esc_html_e( 'Display Style Descriptions', 'wpcalibrate-gift-wrap' ); ?>
							</label>

							<label style="display:block;margin-bottom:6px;">
								<input type="checkbox" name="wpcalibrate_settings[display_prices]" value="yes" <?php checked( 'yes', $settings['display_prices'] ); ?> />
								<?php esc_html_e( 'Display Additional Price Next to Style Names', 'wpcalibrate-gift-wrap' ); ?>
							</label>
						</fieldset>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="image_size"><?php esc_html_e( 'Image Size', 'wpcalibrate-gift-wrap' ); ?></label>
					</th>
					<td>
						<select name="wpcalibrate_settings[image_size]" id="image_size">
							<option value="thumbnail" <?php selected( 'thumbnail', $settings['image_size'] ); ?>><?php esc_html_e( 'Thumbnail (Standard)', 'wpcalibrate-gift-wrap' ); ?></option>
							<option value="small" <?php selected( 'small', $settings['image_size'] ); ?>><?php esc_html_e( 'Small', 'wpcalibrate-gift-wrap' ); ?></option>
							<option value="medium" <?php selected( 'medium', $settings['image_size'] ); ?>><?php esc_html_e( 'Medium', 'wpcalibrate-gift-wrap' ); ?></option>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Customer Labels', 'wpcalibrate-gift-wrap' ); ?></th>
					<td>
						<p>
							<label for="label_gift_wrap"><?php esc_html_e( 'Section Title:', 'wpcalibrate-gift-wrap' ); ?></label><br />
							<input type="text" name="wpcalibrate_settings[label_gift_wrap]" id="label_gift_wrap" value="<?php echo esc_attr( (string) $settings['label_gift_wrap'] ); ?>" class="regular-text" />
						</p>
						<p>
							<label for="label_choose_style"><?php esc_html_e( 'Prompt / Subheading:', 'wpcalibrate-gift-wrap' ); ?></label><br />
							<input type="text" name="wpcalibrate_settings[label_choose_style]" id="label_choose_style" value="<?php echo esc_attr( (string) $settings['label_choose_style'] ); ?>" class="regular-text" />
						</p>
						<p>
							<label for="label_no_wrap"><?php esc_html_e( 'No Gift Wrap Option:', 'wpcalibrate-gift-wrap' ); ?></label><br />
							<input type="text" name="wpcalibrate_settings[label_no_wrap]" id="label_no_wrap" value="<?php echo esc_attr( (string) $settings['label_no_wrap'] ); ?>" class="regular-text" />
						</p>
						<p>
							<label for="label_gift_message"><?php esc_html_e( 'Gift Message Label:', 'wpcalibrate-gift-wrap' ); ?></label><br />
							<input type="text" name="wpcalibrate_settings[label_gift_message]" id="label_gift_message" value="<?php echo esc_attr( (string) $settings['label_gift_message'] ); ?>" class="regular-text" />
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Display Settings', 'wpcalibrate-gift-wrap' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Render Rules tab.
	 *
	 * @param array<string, mixed> $settings Settings array.
	 * @return void
	 */
	private function render_rules_tab( array $settings ): void {
		$categories = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);
		if ( ! is_array( $categories ) || is_wp_error( $categories ) ) {
			$categories = array();
		}

		$excluded_cats = (array) ( $settings['excluded_categories'] ?? array() );
		$included_cats = (array) ( $settings['included_categories'] ?? array() );
		$rules_mode    = (string) ( $settings['rules_mode'] ?? 'all' );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ); ?>">
			<?php wp_nonce_field( 'wpcalibrate_gift_wrap_save_settings', 'wpcalibrate_settings_nonce' ); ?>
			<input type="hidden" name="wpcalibrate_save_settings" value="1" />
			<input type="hidden" name="current_tab" value="rules" />

			<div style="margin-bottom:15px;padding:12px 16px;background:#f0fdf4;border-left:4px solid #16a34a;border-radius:4px;">
				<h4 style="margin:0 0 6px 0;color:#166534;"><?php esc_html_e( 'Deterministic Rule Precedence', 'wpcalibrate-gift-wrap' ); ?></h4>
				<ol style="margin:0;padding-left:20px;color:#15803d;font-size:13px;">
					<li><strong><?php esc_html_e( 'Product Override', 'wpcalibrate-gift-wrap' ); ?>:</strong> <?php esc_html_e( 'Explicit product exclusion or inclusion in product editor.', 'wpcalibrate-gift-wrap' ); ?></li>
					<li><strong><?php esc_html_e( 'Product Exclusion', 'wpcalibrate-gift-wrap' ); ?>:</strong> <?php esc_html_e( 'Global product excluded list.', 'wpcalibrate-gift-wrap' ); ?></li>
					<li><strong><?php esc_html_e( 'Product Inclusion', 'wpcalibrate-gift-wrap' ); ?>:</strong> <?php esc_html_e( 'Global product included list (when inclusion mode is active).', 'wpcalibrate-gift-wrap' ); ?></li>
					<li><strong><?php esc_html_e( 'Category Exclusion', 'wpcalibrate-gift-wrap' ); ?>:</strong> <?php esc_html_e( 'Excluded product categories take precedence.', 'wpcalibrate-gift-wrap' ); ?></li>
					<li><strong><?php esc_html_e( 'Category Inclusion', 'wpcalibrate-gift-wrap' ); ?>:</strong> <?php esc_html_e( 'Included product categories (when inclusion mode is active).', 'wpcalibrate-gift-wrap' ); ?></li>
					<li><strong><?php esc_html_e( 'Global Default', 'wpcalibrate-gift-wrap' ); ?>:</strong> <?php esc_html_e( 'Applies if no specific rule matches.', 'wpcalibrate-gift-wrap' ); ?></li>
				</ol>
			</div>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'General Rule Strategy', 'wpcalibrate-gift-wrap' ); ?></th>
					<td>
						<label style="display:block;margin-bottom:6px;">
							<input type="radio" name="wpcalibrate_settings[rules_mode]" value="all" <?php checked( 'all', $rules_mode ); ?> />
							<strong><?php esc_html_e( 'Enable for all products (except excluded)', 'wpcalibrate-gift-wrap' ); ?></strong>
						</label>
						<label style="display:block;margin-bottom:6px;">
							<input type="radio" name="wpcalibrate_settings[rules_mode]" value="include" <?php checked( 'include', $rules_mode ); ?> />
							<strong><?php esc_html_e( 'Only enable for specific included categories/products', 'wpcalibrate-gift-wrap' ); ?></strong>
						</label>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Category Inclusions & Exclusions', 'wpcalibrate-gift-wrap' ); ?></th>
					<td>
						<div style="display:flex;gap:30px;max-width:700px;">
							<div style="flex:1;">
								<strong style="display:block;margin-bottom:6px;"><?php esc_html_e( 'Included Categories:', 'wpcalibrate-gift-wrap' ); ?></strong>
								<div style="max-height:160px;overflow-y:auto;border:1px solid #cbd5e1;padding:8px;background:#fff;border-radius:4px;">
									<?php foreach ( $categories as $cat ) : ?>
										<label style="display:block;font-size:12px;margin-bottom:4px;">
											<input type="checkbox" name="wpcalibrate_settings[included_categories][]" value="<?php echo esc_attr( (string) $cat->term_id ); ?>" <?php checked( in_array( (int) $cat->term_id, $included_cats, true ) ); ?> />
											<?php echo esc_html( $cat->name ); ?>
										</label>
									<?php endforeach; ?>
								</div>
							</div>

							<div style="flex:1;">
								<strong style="display:block;margin-bottom:6px;"><?php esc_html_e( 'Excluded Categories:', 'wpcalibrate-gift-wrap' ); ?></strong>
								<div style="max-height:160px;overflow-y:auto;border:1px solid #cbd5e1;padding:8px;background:#fff;border-radius:4px;">
									<?php foreach ( $categories as $cat ) : ?>
										<label style="display:block;font-size:12px;margin-bottom:4px;">
											<input type="checkbox" name="wpcalibrate_settings[excluded_categories][]" value="<?php echo esc_attr( (string) $cat->term_id ); ?>" <?php checked( in_array( (int) $cat->term_id, $excluded_cats, true ) ); ?> />
											<?php echo esc_html( $cat->name ); ?>
										</label>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="excluded_products_input"><?php esc_html_e( 'Excluded Product IDs', 'wpcalibrate-gift-wrap' ); ?></label>
					</th>
					<td>
						<input
							type="text"
							name="wpcalibrate_settings[excluded_products]"
							id="excluded_products_input"
							value="<?php echo esc_attr( implode( ', ', (array) ( $settings['excluded_products'] ?? array() ) ) ); ?>"
							class="regular-text"
							placeholder="101, 102, 105"
						/>
						<p class="description"><?php esc_html_e( 'Comma-separated list of product IDs to exclude from gift wrapping globally.', 'wpcalibrate-gift-wrap' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Rules Settings', 'wpcalibrate-gift-wrap' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Render Orders & Emails tab.
	 *
	 * @param array<string, mixed> $settings Settings array.
	 * @return void
	 */
	private function render_orders_tab( array $settings ): void {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ); ?>">
			<?php wp_nonce_field( 'wpcalibrate_gift_wrap_save_settings', 'wpcalibrate_settings_nonce' ); ?>
			<input type="hidden" name="wpcalibrate_save_settings" value="1" />
			<input type="hidden" name="current_tab" value="orders" />

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Customer Notifications', 'wpcalibrate-gift-wrap' ); ?></th>
					<td>
						<fieldset>
							<label style="display:block;margin-bottom:6px;">
								<input type="checkbox" name="wpcalibrate_settings[orders_customer_emails]" value="yes" <?php checked( 'yes', $settings['orders_customer_emails'] ); ?> />
								<?php esc_html_e( 'Include Gift Wrapping info in Customer Confirmation Emails', 'wpcalibrate-gift-wrap' ); ?>
							</label>

							<label style="display:block;margin-bottom:6px;">
								<input type="checkbox" name="wpcalibrate_settings[orders_customer_message]" value="yes" <?php checked( 'yes', $settings['orders_customer_message'] ); ?> />
								<?php esc_html_e( 'Include Customer Gift Messages in Customer Order Emails', 'wpcalibrate-gift-wrap' ); ?>
							</label>

							<label style="display:block;margin-bottom:6px;">
								<input type="checkbox" name="wpcalibrate_settings[orders_my_account]" value="yes" <?php checked( 'yes', $settings['orders_my_account'] ); ?> />
								<?php esc_html_e( 'Display Gift Wrapping details on My Account order view', 'wpcalibrate-gift-wrap' ); ?>
							</label>
						</fieldset>
						<p class="description"><?php esc_html_e( 'Administrator fulfillment order emails always include complete gift wrapping data.', 'wpcalibrate-gift-wrap' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row"><?php esc_html_e( 'Data Retention on Uninstall', 'wpcalibrate-gift-wrap' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpcalibrate_settings[uninstall_delete_data]" value="yes" <?php checked( 'yes', $settings['uninstall_delete_data'] ); ?> />
							<span style="color:#dc2626;font-weight:600;"><?php esc_html_e( 'Remove plugin settings and style records on uninstall', 'wpcalibrate-gift-wrap' ); ?></span>
						</label>
						<p class="description">
							<?php esc_html_e( 'Default: Unchecked. When unchecked, uninstall preserves settings and style options while removing transients. Historical WooCommerce orders are never altered.', 'wpcalibrate-gift-wrap' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Order Settings', 'wpcalibrate-gift-wrap' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Render License tab.
	 *
	 * @return void
	 */
	private function render_license_tab(): void {
		$status = $this->license_service->get_status();
		$label  = $this->license_service->get_status_label();
		?>
		<div style="max-width:750px;background:#fff;padding:24px;border:1px solid #cbd5e1;border-radius:8px;">
			<h3 style="margin-top:0;display:flex;align-items:center;gap:8px;">
				<span class="dashicons dashicons-admin-network" style="color:#6366f1;"></span>
				<?php esc_html_e( 'Plugin License & Updates', 'wpcalibrate-gift-wrap' ); ?>
			</h3>

			<div style="margin:15px 0;padding:12px 16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;display:flex;align-items:center;justify-content:space-between;">
				<div>
					<div style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;"><?php esc_html_e( 'Current License Status', 'wpcalibrate-gift-wrap' ); ?></div>
					<div style="font-size:16px;font-weight:600;color:#0f172a;margin-top:2px;">
						<span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#3b82f6;margin-right:6px;"></span>
						<?php echo esc_html( $label ); ?>
					</div>
				</div>
				<span class="badge" style="padding:4px 10px;background:#e0f2fe;color:#0369a1;border-radius:20px;font-size:12px;font-weight:500;">
					<?php esc_html_e( 'Features Active', 'wpcalibrate-gift-wrap' ); ?>
				</span>
			</div>

			<p style="color:#475569;line-height:1.6;">
				<?php esc_html_e( 'This release is fully unlocked. Online license validation is currently Not Configured in V1. You have complete access to all gift wrapping capabilities without restriction.', 'wpcalibrate-gift-wrap' ); ?>
			</p>

			<div style="margin-top:20px;padding-top:15px;border-top:1px solid #e2e8f0;display:flex;gap:10px;">
				<a href="<?php echo esc_url( SupportInfo::MARKETPLACE ); ?>" target="_blank" rel="noopener noreferrer" class="button button-primary">
					<?php esc_html_e( 'Visit WPCalibrate Marketplace', 'wpcalibrate-gift-wrap' ); ?> &rarr;
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Support tab.
	 *
	 * @return void
	 */
	private function render_support_tab(): void {
		$links = SupportInfo::get_links();
		?>
		<div style="max-width:750px;background:#fff;padding:24px;border:1px solid #cbd5e1;border-radius:8px;">
			<h3 style="margin-top:0;display:flex;align-items:center;gap:8px;">
				<span class="dashicons dashicons-sos" style="color:#6366f1;"></span>
				<?php esc_html_e( 'WPCalibrate Support & Assistance', 'wpcalibrate-gift-wrap' ); ?>
			</h3>

			<p style="color:#475569;line-height:1.6;">
				<?php esc_html_e( 'Need assistance configuring gift wrapping or have questions regarding customization? Our direct engineering support team is available to assist you.', 'wpcalibrate-gift-wrap' ); ?>
			</p>

			<div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin:20px 0;">
				<div style="padding:15px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc;">
					<strong style="display:block;margin-bottom:4px;color:#0f172a;"><?php esc_html_e( 'Direct Email Support', 'wpcalibrate-gift-wrap' ); ?></strong>
					<a href="<?php echo esc_url( $links['email'] ); ?>" style="text-decoration:none;color:#4338ca;font-weight:500;">
						<?php echo esc_html( SupportInfo::EMAIL ); ?>
					</a>
				</div>

				<div style="padding:15px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc;">
					<strong style="display:block;margin-bottom:4px;color:#0f172a;"><?php esc_html_e( 'Phone & WhatsApp', 'wpcalibrate-gift-wrap' ); ?></strong>
					<a href="<?php echo esc_url( $links['whatsapp'] ); ?>" target="_blank" rel="noopener noreferrer" style="text-decoration:none;color:#4338ca;font-weight:500;">
						<?php echo esc_html( SupportInfo::PHONE ); ?>
					</a>
				</div>

				<div style="padding:15px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc;">
					<strong style="display:block;margin-bottom:4px;color:#0f172a;"><?php esc_html_e( 'Official Website', 'wpcalibrate-gift-wrap' ); ?></strong>
					<a href="<?php echo esc_url( $links['website'] ); ?>" target="_blank" rel="noopener noreferrer" style="text-decoration:none;color:#4338ca;font-weight:500;">
						<?php echo esc_html( SupportInfo::WEBSITE ); ?>
					</a>
				</div>

				<div style="padding:15px;border:1px solid #e2e8f0;border-radius:6px;background:#f8fafc;">
					<strong style="display:block;margin-bottom:4px;color:#0f172a;"><?php esc_html_e( 'Marketplace & Extensions', 'wpcalibrate-gift-wrap' ); ?></strong>
					<a href="<?php echo esc_url( $links['marketplace'] ); ?>" target="_blank" rel="noopener noreferrer" style="text-decoration:none;color:#4338ca;font-weight:500;">
						<?php echo esc_html( SupportInfo::MARKETPLACE ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}
}
