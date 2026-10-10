<?php
/**
 * Support Info for WPCalibrate Gift Wrap.
 *
 * @package WPCalibrate\GiftWrap\Support
 */

declare(strict_types=1);

namespace WPCalibrate\GiftWrap\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class SupportInfo
 */
class SupportInfo {

	public const EMAIL       = 'support@wpcalibrate.com';
	public const WEBSITE     = 'https://wpcalibrate.com';
	public const MARKETPLACE = 'https://marketplace.wpcalibrate.com/';
	public const PHONE       = '+447474795976';
	public const WHATSAPP_URL = 'https://wa.me/447474795976';

	/**
	 * Get formatted support links.
	 *
	 * @return array<string, string>
	 */
	public static function get_links(): array {
		return array(
			'email'       => 'mailto:' . self::EMAIL,
			'website'     => self::WEBSITE,
			'marketplace' => self::MARKETPLACE,
			'phone'       => 'tel:' . self::PHONE,
			'whatsapp'    => self::WHATSAPP_URL,
		);
	}
}
