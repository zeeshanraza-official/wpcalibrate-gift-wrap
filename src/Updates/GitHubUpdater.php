<?php
/**
 * GitHub-based Update Checker for WPCalibrate Gift Wrap.
 *
 * Allows store owners to receive update notifications and perform one-click updates
 * directly from the WordPress dashboard via public GitHub releases.
 *
 * @package WPCalibrate\GiftWrap\Updates
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\Updates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class GitHubUpdater
 */
class GitHubUpdater {

	private const GITHUB_REPO = 'zeeshanraza-official/wpcalibrate-gift-wrap';
	private const TRANSIENT_KEY = 'wpcalibrate_gift_wrap_github_release';
	private const CACHE_HOURS = 6;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_for_update' ) );
		add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3 );
		add_filter( 'upgrader_post_install', array( $this, 'post_install' ), 10, 3 );
	}

	/**
	 * Check GitHub releases for a newer version of the plugin.
	 *
	 * @param object|bool $transient Transient object.
	 * @return object|bool
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) || ! is_object( $transient ) ) {
			return $transient;
		}

		$release = $this->get_latest_release();
		if ( ! $release || empty( $release['tag_name'] ) ) {
			return $transient;
		}

		$remote_version = ltrim( (string) $release['tag_name'], 'v' );
		$current_version = defined( 'WPCALIBRATE_GIFT_WRAP_VERSION' ) ? WPCALIBRATE_GIFT_WRAP_VERSION : '1.0.0';

		if ( version_compare( $remote_version, $current_version, '>' ) ) {
			$plugin_file = defined( 'WPCALIBRATE_GIFT_WRAP_FILE' )
				? plugin_basename( WPCALIBRATE_GIFT_WRAP_FILE )
				: 'wpcalibrate-gift-wrap/wpcalibrate-gift-wrap.php';

			$package_url = $this->get_asset_zip_url( $release );

			$item = (object) array(
				'id'            => 'wpcalibrate-gift-wrap',
				'slug'          => 'wpcalibrate-gift-wrap',
				'plugin'        => $plugin_file,
				'new_version'   => $remote_version,
				'url'           => $release['html_url'] ?? 'https://github.com/' . self::GITHUB_REPO,
				'package'       => $package_url,
				'requires'      => '6.4',
				'requires_php'  => '8.2',
				'tested'        => '11.2.1',
				'icons'         => array(
					'1x' => defined( 'WPCALIBRATE_GIFT_WRAP_URL' ) ? WPCALIBRATE_GIFT_WRAP_URL . 'assets/images/icon-dark.png' : '',
				),
			);

			$transient->response[ $plugin_file ] = $item;
			unset( $transient->no_update[ $plugin_file ] );
		}

		return $transient;
	}

	/**
	 * Provide plugin details for the modal view in WordPress dashboard.
	 *
	 * @param false|object|array $result Result.
	 * @param string             $action Action name.
	 * @param object             $args   Arguments.
	 * @return false|object
	 */
	public function plugin_info( $result, string $action, $args ) {
		if ( 'plugin_information' !== $action || ! isset( $args->slug ) || 'wpcalibrate-gift-wrap' !== $args->slug ) {
			return $result;
		}

		$release = $this->get_latest_release();
		if ( ! $release ) {
			return $result;
		}

		$remote_version = ltrim( (string) ( $release['tag_name'] ?? '1.0.0' ), 'v' );
		$body_markdown  = $release['body'] ?? '';

		$info = (object) array(
			'name'          => 'WPCalibrate Gift Wrap',
			'slug'          => 'wpcalibrate-gift-wrap',
			'version'       => $remote_version,
			'author'        => '<a href="https://wpcalibrate.com">WPCalibrate</a>',
			'homepage'      => 'https://github.com/' . self::GITHUB_REPO,
			'requires'      => '6.4',
			'requires_php'  => '8.2',
			'tested'        => '11.2.1',
			'last_updated'  => $release['published_at'] ?? '',
			'download_link' => $this->get_asset_zip_url( $release ),
			'sections'      => array(
				'description' => 'Professional gift-wrapping extension for WooCommerce products and orders with customizable styles, images, transparent pricing, and gift messages.',
				'changelog'   => nl2br( esc_html( $body_markdown ) ),
			),
			'banners'       => array(
				'low' => defined( 'WPCALIBRATE_GIFT_WRAP_URL' ) ? WPCALIBRATE_GIFT_WRAP_URL . 'assets/images/icon-dark.png' : '',
			),
		);

		return $info;
	}

	/**
	 * Ensure the extracted plugin directory matches the plugin slug.
	 *
	 * @param bool  $true       True.
	 * @param array $hook_extra Extra hook parameters.
	 * @param array $result     Installation result array.
	 * @return array
	 */
	public function post_install( $true, array $hook_extra, array $result ): array {
		global $wp_filesystem;

		$plugin_slug = 'wpcalibrate-gift-wrap';
		$destination = WP_PLUGIN_DIR . '/' . $plugin_slug;

		if ( ! empty( $result['destination'] ) && $result['destination'] !== $destination && isset( $hook_extra['plugin'] ) && str_contains( (string) $hook_extra['plugin'], $plugin_slug ) ) {
			$wp_filesystem->move( $result['destination'], $destination );
			$result['destination'] = $destination;
		}

		return $result;
	}

	/**
	 * Fetch the latest release payload from GitHub.
	 *
	 * @return array<string, mixed>|null
	 */
	private function get_latest_release(): ?array {
		$cached = get_site_transient( self::TRANSIENT_KEY );
		if ( is_array( $cached ) && ! empty( $cached['tag_name'] ) ) {
			return $cached;
		}

		$url = 'https://api.github.com/repos/' . self::GITHUB_REPO . '/releases/latest';
		$args = array(
			'headers' => array(
				'Accept'     => 'application/vnd.github.v3+json',
				'User-Agent' => 'WordPress/' . ( $GLOBALS['wp_version'] ?? '6.5' ) . '; WPCalibrate-Gift-Wrap',
			),
			'timeout' => 10,
		);

		$response = wp_remote_get( $url, $args );
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) || empty( $data['tag_name'] ) ) {
			return null;
		}

		set_site_transient( self::TRANSIENT_KEY, $data, self::CACHE_HOURS * HOUR_IN_SECONDS );
		return $data;
	}

	/**
	 * Extract ZIP package URL from release assets or fallback to zipball.
	 *
	 * @param array<string, mixed> $release Release payload.
	 * @return string
	 */
	private function get_asset_zip_url( array $release ): string {
		if ( ! empty( $release['assets'] ) && is_array( $release['assets'] ) ) {
			foreach ( $release['assets'] as $asset ) {
				if ( ! empty( $asset['browser_download_url'] ) && str_ends_with( strtolower( (string) $asset['browser_download_url'] ), '.zip' ) ) {
					return (string) $asset['browser_download_url'];
				}
			}
		}

		return $release['zipball_url'] ?? '';
	}
}
