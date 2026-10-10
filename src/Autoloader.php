<?php
/**
 * PSR-4 compatible autoloader for WPCalibrate Gift Wrap.
 *
 * @package WPCalibrate\GiftWrap
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Autoloader class.
 */
class Autoloader {

	/**
	 * Prefix namespace to autoload.
	 *
	 * @var string
	 */
	private const NAMESPACE_PREFIX = 'WPCalibrate\\GiftWrap\\';

	/**
	 * Base directory for the class namespace.
	 *
	 * @var string
	 */
	private string $base_dir;

	/**
	 * Constructor.
	 *
	 * @param string $base_dir Base directory path for `src/`.
	 */
	public function __construct( string $base_dir ) {
		$this->base_dir = rtrim( $base_dir, '/\\' ) . DIRECTORY_SEPARATOR;
	}

	/**
	 * Register the autoloader.
	 *
	 * @return void
	 */
	public function register(): void {
		spl_autoload_register( array( $this, 'autoload' ) );
	}

	/**
	 * Autoload callback.
	 *
	 * @param string $class Fully qualified class name.
	 * @return void
	 */
	public function autoload( string $class ): void {
		$prefix_length = strlen( self::NAMESPACE_PREFIX );
		if ( strncmp( self::NAMESPACE_PREFIX, $class, $prefix_length ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $prefix_length );
		$file = $this->base_dir . str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
}
