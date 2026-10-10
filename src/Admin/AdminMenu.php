<?php
/**
 * Admin Menu Coordinator for WPCalibrate.
 *
 * Implements non-conflicting top-level WPCalibrate parent menu registration
 * and registers the isolated Gift Wrap submenu router.
 *
 * @package WPCalibrate\GiftWrap\Admin
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AdminMenu
 */
class AdminMenu {

	public const PARENT_MENU_SLUG = 'wpcalibrate';

	private SettingsController $settings_controller;

	public function __construct( SettingsController $settings_controller ) {
		$this->settings_controller = $settings_controller;
	}

	/**
	 * Register admin menu hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );
		add_action( 'admin_head', array( $this, 'output_menu_icon_styles' ) );
		add_action( 'admin_init', array( $this->settings_controller, 'handle_actions' ) );
	}

	/**
	 * Register WPCalibrate parent menu (if not registered) and Gift Wrap submenu.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$parent_exists = $this->parent_menu_exists();

		if ( ! $parent_exists ) {
			$icon_path = WPCALIBRATE_GIFT_WRAP_DIR . 'assets/images/menu-icon.png';
			$icon_url  = file_exists( $icon_path ) ? WPCALIBRATE_GIFT_WRAP_URL . 'assets/images/menu-icon.png' : 'dashicons-art';

			add_menu_page(
				'WPCalibrate',
				'WPCalibrate',
				'manage_woocommerce',
				self::PARENT_MENU_SLUG,
				array( $this, 'render_parent_dashboard' ),
				$icon_url,
				58
			);
		}

		// Register isolated Gift Wrap submenu.
		add_submenu_page(
			self::PARENT_MENU_SLUG,
			__( 'WPCalibrate Gift Wrap', 'wpcalibrate-gift-wrap' ),
			__( 'Gift Wrap', 'wpcalibrate-gift-wrap' ),
			'manage_woocommerce',
			SettingsController::PAGE_SLUG,
			array( $this->settings_controller, 'render' )
		);
	}

	/**
	 * Check if top-level WPCalibrate menu is already registered in WordPress.
	 *
	 * @return bool
	 */
	private function parent_menu_exists(): bool {
		global $menu;
		if ( ! is_array( $menu ) ) {
			return false;
		}

		foreach ( $menu as $item ) {
			if ( isset( $item[2] ) && self::PARENT_MENU_SLUG === $item[2] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Default fallback dashboard for the parent menu if opened directly.
	 *
	 * @return void
	 */
	public function render_parent_dashboard(): void {
		// Redirect to gift wrap settings page.
		wp_safe_redirect( admin_url( 'admin.php?page=' . SettingsController::PAGE_SLUG ) );
		exit;
	}

	/**
	 * Output scoped styles for WPCalibrate admin menu icon in admin_head.
	 * Ensures the menu icon is strictly 20x20 px and matches standard WordPress dashboard icon colors.
	 *
	 * @return void
	 */
	public function output_menu_icon_styles(): void {
		?>
		<style id="wpcalibrate-menu-icon-style">
			#adminmenu #toplevel_page_wpcalibrate div.wp-menu-image {
				display: flex;
				align-items: center;
				justify-content: center;
			}
			#adminmenu #toplevel_page_wpcalibrate div.wp-menu-image img {
				width: 20px !important;
				height: 20px !important;
				max-width: 20px !important;
				max-height: 20px !important;
				padding: 7px 0 0 0 !important;
				box-sizing: content-box !important;
				opacity: 0.72;
				filter: invert(74%) sepia(4%) saturate(246%) hue-rotate(173deg) brightness(89%) contrast(85%); /* matches standard WordPress dashboard icon #a7aaad */
				transition: opacity 0.15s ease-in-out, filter 0.15s ease-in-out;
			}
			#adminmenu #toplevel_page_wpcalibrate:hover div.wp-menu-image img,
			#adminmenu #toplevel_page_wpcalibrate.opensub div.wp-menu-image img {
				opacity: 1;
				filter: invert(67%) sepia(26%) saturate(1067%) hue-rotate(174deg) brightness(97%) contrast(93%); /* matches WordPress hover #72aee6 */
			}
			#adminmenu #toplevel_page_wpcalibrate.wp-has-current-submenu div.wp-menu-image img,
			#adminmenu #toplevel_page_wpcalibrate.current div.wp-menu-image img {
				opacity: 1;
				filter: brightness(0) invert(1); /* matches WordPress active #ffffff */
			}
		</style>
		<?php
	}
}
