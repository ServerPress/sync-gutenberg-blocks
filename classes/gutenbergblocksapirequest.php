<?php

class SyncGutenbergBlocksApiRequest
{
	private static $_instance = NULL;

	const NOTICE_GB_GRAVITYFORM = 1200;
	const NOTICE_GB_CONTACTFORM7 = 1201;

	/**
	 * Retrieve singleton class instance
	 * @return SyncMenusApiRequest instance reference API request class
	 */
	public static function get_instance()
	{
		if (NULL === self::$_instance)
			self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 * Filters the notices list, adding SyncMenus specific code-to-string values
	 * @param string $message The notice string message to be returned
	 * @param int $code The notice code being evaluated
	 * @return string The modified $message string, with Pull specific notices added to it
	 */
	public function filter_notice_codes($message, $code)
	{
		switch ($code) {
		case self::NOTICE_GB_GRAVITYFORM:		$message = __('This content contains a reference to a Gravity Form. This content cannot be synchronized.', 'wpsitesync-gutenberg-blocks');			break;
		case self::NOTICE_GB_CONTACTFORM7:		$message = __('This content contains a reference to a Contact Form 7 form. This content cannot be synchronized.', 'wpsitesync-gutenberg-blocks');	break;
		}
		return $message;
	}
}

// EOF
