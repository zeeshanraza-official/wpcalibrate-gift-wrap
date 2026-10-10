<?php
/**
 * Main Plugin Container for WPCalibrate Gift Wrap.
 *
 * Coordinates dependency injection, service instantiation, and lifecycle hooks.
 *
 * @package WPCalibrate\GiftWrap
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap;

use WPCalibrate\GiftWrap\Admin\AdminMenu;
use WPCalibrate\GiftWrap\Admin\Product\ProductConfig;
use WPCalibrate\GiftWrap\Admin\SettingsController;
use WPCalibrate\GiftWrap\Admin\StylesController;
use WPCalibrate\GiftWrap\Cart\CartHandler;
use WPCalibrate\GiftWrap\Checkout\ClassicCheckoutHandler;
use WPCalibrate\GiftWrap\Compatibility\Blocks\BlocksIntegration;
use WPCalibrate\GiftWrap\Compatibility\HPOS\HPOSCompatibility;
use WPCalibrate\GiftWrap\Emails\EmailDisplay;
use WPCalibrate\GiftWrap\Frontend\ProductDisplay;
use WPCalibrate\GiftWrap\GiftWrap\Repositories\StyleRepository;
use WPCalibrate\GiftWrap\GiftWrap\Services\EligibilityService;
use WPCalibrate\GiftWrap\GiftWrap\Services\OrderWrapService;
use WPCalibrate\GiftWrap\GiftWrap\Services\PriceService;
use WPCalibrate\GiftWrap\License\LicenseService;
use WPCalibrate\GiftWrap\Orders\OrderAdminDisplay;
use WPCalibrate\GiftWrap\Orders\OrderCustomerDisplay;
use WPCalibrate\GiftWrap\Updates\GitHubUpdater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Plugin
 */
class Plugin {

	private static ?Plugin $instance = null;

	public StyleRepository $style_repository;
	public PriceService $price_service;
	public EligibilityService $eligibility_service;
	public OrderWrapService $order_wrap_service;
	public LicenseService $license_service;

	public CartHandler $cart_handler;
	public ClassicCheckoutHandler $classic_checkout_handler;
	public ProductDisplay $product_display;
	public OrderAdminDisplay $order_admin_display;
	public OrderCustomerDisplay $order_customer_display;
	public EmailDisplay $email_display;
	public ProductConfig $product_config;
	public StylesController $styles_controller;
	public SettingsController $settings_controller;
	public AdminMenu $admin_menu;
	public BlocksIntegration $blocks_integration;
	public GitHubUpdater $github_updater;

	/**
	 * Get singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {
		// Domain Repositories & Services.
		$this->style_repository    = new StyleRepository();
		$this->price_service       = new PriceService();
		$this->eligibility_service = new EligibilityService( $this->style_repository );
		$this->order_wrap_service  = new OrderWrapService();
		$this->license_service     = new LicenseService();

		// Cart & Checkout.
		$this->cart_handler = new CartHandler(
			$this->style_repository,
			$this->eligibility_service,
			$this->price_service,
			$this->order_wrap_service
		);

		$this->classic_checkout_handler = new ClassicCheckoutHandler(
			$this->style_repository,
			$this->eligibility_service,
			$this->price_service,
			$this->order_wrap_service,
			$this->cart_handler
		);

		// Frontend & Displays.
		$this->product_display        = new ProductDisplay( $this->style_repository, $this->eligibility_service, $this->price_service );
		$this->order_admin_display    = new OrderAdminDisplay( $this->order_wrap_service, $this->price_service );
		$this->order_customer_display = new OrderCustomerDisplay( $this->order_wrap_service, $this->price_service );
		$this->email_display          = new EmailDisplay( $this->order_wrap_service );

		// Admin & Config.
		$this->product_config    = new ProductConfig( $this->style_repository );
		$this->styles_controller  = new StylesController( $this->style_repository, $this->price_service );
		$this->settings_controller = new SettingsController( $this->styles_controller, $this->license_service );
		$this->admin_menu        = new AdminMenu( $this->settings_controller );

		// Blocks & Integrations.
		$this->blocks_integration = new BlocksIntegration(
			$this->style_repository,
			$this->eligibility_service,
			$this->price_service,
			$this->order_wrap_service
		);

		// GitHub Updater.
		$this->github_updater = new GitHubUpdater();
	}

	/**
	 * Boot the plugin.
	 *
	 * @return void
	 */
	public function run(): void {
		// Declare HPOS compatibility early.
		HPOSCompatibility::register();

		// Load plugin text domain.
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Enqueue scripts and styles.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Check runtime dependencies.
		if ( ! Dependencies::is_php_compatible() ) {
			add_action( 'admin_notices', array( Dependencies::class, 'render_php_incompatible_notice' ) );
			return;
		}

		if ( ! Dependencies::is_woocommerce_active() ) {
			add_action( 'admin_notices', array( Dependencies::class, 'render_woocommerce_missing_notice' ) );
			// Still register admin menu so administrator can review settings and support information.
			if ( is_admin() ) {
				$this->admin_menu->init();
				$this->github_updater->init();
			}
			return;
		}

		// Initialize WooCommerce-dependent features.
		$this->cart_handler->init();
		$this->classic_checkout_handler->init();
		$this->product_display->init();
		$this->order_admin_display->init();
		$this->order_customer_display->init();
		$this->email_display->init();
		$this->product_config->init();
		$this->blocks_integration->init();

		if ( is_admin() ) {
			$this->admin_menu->init();
			$this->github_updater->init();
		}
	}

	/**
	 * Load text domain for internationalization.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'wpcalibrate-gift-wrap',
			false,
			dirname( plugin_basename( WPCALIBRATE_GIFT_WRAP_FILE ) ) . '/languages'
		);
	}

	/**
	 * Enqueue frontend CSS and JavaScript only on relevant shop pages.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets(): void {
		// Only load where gift wrapping could appear (product, cart, checkout).
		if ( ! is_product() && ! is_cart() && ! is_checkout() ) {
			return;
		}

		$is_rtl = is_rtl();
		$css_file = $is_rtl ? 'assets/css/frontend-rtl.css' : 'assets/css/frontend.css';

		if ( file_exists( WPCALIBRATE_GIFT_WRAP_DIR . $css_file ) ) {
			wp_enqueue_style(
				'wpcalibrate-gift-wrap-frontend',
				WPCALIBRATE_GIFT_WRAP_URL . $css_file,
				array(),
				WPCALIBRATE_GIFT_WRAP_VERSION
			);
		}

		$js_file = 'assets/js/frontend.js';
		if ( file_exists( WPCALIBRATE_GIFT_WRAP_DIR . $js_file ) ) {
			wp_enqueue_script(
				'wpcalibrate-gift-wrap-frontend',
				WPCALIBRATE_GIFT_WRAP_URL . $js_file,
				array( 'jquery' ),
				WPCALIBRATE_GIFT_WRAP_VERSION,
				true
			);

			wp_localize_script(
				'wpcalibrate-gift-wrap-frontend',
				'wpcalibrateGiftWrap',
				array(
					'ajaxUrl'               => admin_url( 'admin-ajax.php' ),
					'wholeOrderNonce'       => wp_create_nonce( 'wpcalibrate_whole_order_nonce' ),
					'i18nApplying'          => __( 'Updating gift wrap...', 'wpcalibrate-gift-wrap' ),
					'i18nConfirmRemove'     => __( 'Remove gift wrap for this item?', 'wpcalibrate-gift-wrap' ),
					'combineAllowed'        => $this->eligibility_service->can_combine_modes(),
					'hasPerItemWrap'        => $this->cart_handler->cart_has_per_item_wrap(),
				)
			);
		}
	}

	/**
	 * Enqueue admin CSS and JavaScript only on relevant plugin or product screens.
	 *
	 * @param string $hook_suffix Admin page hook suffix.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		$screen = get_current_screen();
		$is_plugin_screen = str_contains( $hook_suffix, SettingsController::PAGE_SLUG );
		$is_product_screen = $screen && 'product' === $screen->id;

		if ( ! $is_plugin_screen && ! $is_product_screen ) {
			return;
		}

		// Enqueue WordPress media scripts if on the plugin settings screen.
		if ( $is_plugin_screen ) {
			wp_enqueue_media();
		}

		$is_rtl = is_rtl();
		$css_file = $is_rtl ? 'assets/css/admin-rtl.css' : 'assets/css/admin.css';

		if ( file_exists( WPCALIBRATE_GIFT_WRAP_DIR . $css_file ) ) {
			wp_enqueue_style(
				'wpcalibrate-gift-wrap-admin',
				WPCALIBRATE_GIFT_WRAP_URL . $css_file,
				array(),
				WPCALIBRATE_GIFT_WRAP_VERSION
			);
		}

		$js_file = 'assets/js/admin.js';
		if ( file_exists( WPCALIBRATE_GIFT_WRAP_DIR . $js_file ) ) {
			wp_enqueue_script(
				'wpcalibrate-gift-wrap-admin',
				WPCALIBRATE_GIFT_WRAP_URL . $js_file,
				array( 'jquery' ),
				WPCALIBRATE_GIFT_WRAP_VERSION,
				true
			);

			wp_localize_script(
				'wpcalibrate-gift-wrap-admin',
				'wpcalibrateAdmin',
				array(
					'mediaTitle'       => __( 'Select Gift Wrap Preview Image', 'wpcalibrate-gift-wrap' ),
					'mediaButton'      => __( 'Use This Image', 'wpcalibrate-gift-wrap' ),
					'confirmDelete'    => __( 'Are you sure you want to delete this wrapping style?', 'wpcalibrate-gift-wrap' ),
				)
			);
		}
	}
}
