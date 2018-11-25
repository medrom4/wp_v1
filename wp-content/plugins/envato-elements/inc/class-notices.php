<?php
/**
 * Envato Elements:
 *
 * This starts things up. Registers the SPL and starts up some classes.
 *
 * @package Envato/Envato_Elements
 * @since 0.0.2
 */

namespace Envato_Elements;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


/**
 * Notices registration and management.
 *
 * @since 0.0.2
 */
class Notices extends Base {

	const NOTICE_TRANSIENT = 'envato-elements-notices';

	/**
	 * Notices constructor.
	 */
	public function __construct() {

	}

	/**
	 *
	 * We look for any messages or notifications from our API response.
	 * These messages are stored in a global transient and then displayed to the user on next page load.
	 * Messages must be dismissed by the user.
	 *
	 * @param $api_response array
	 * @param $api_endpoint string
	 */
	public function sniff_api_response_for_messages( $api_response, $api_endpoint ) {

		if ( $api_response && ! empty( $api_response['global_message'] ) ) {
			$messages = get_transient( self::NOTICE_TRANSIENT );
			if ( ! is_array( $messages ) ) {
				$messages = [];
			}
			$messages[ md5( $api_response['global_message'] ) ] = $api_response['global_message'];
			set_transient( self::NOTICE_TRANSIENT, $messages, 3600 );
		}

	}

	public function print_global_notices() {

		$messages = get_transient( self::NOTICE_TRANSIENT );
		if ( ! is_array( $messages ) ) {
			$messages = [];
		}
		if ( count( $messages ) ) {
			echo $this->render_template(
				'notices/global.php', [
					'messages' => $messages,
				]
			);
		}

	}

	/**
	 * When the plugin upgrades we clear any transient notices.
	 *
	 * @since 0.0.9
	 */
	public function activation() {
		delete_transient( self::NOTICE_TRANSIENT );
	}

}
