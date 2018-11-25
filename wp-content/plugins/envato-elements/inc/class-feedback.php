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
 * Feedback registration and management.
 *
 * @since 0.0.2
 */
class Feedback extends Base {

	/**
	 * Feedback constructor.
	 */
	public function __construct() {
		parent::__construct();
		add_action( 'admin_action_envato_elements_feedback', [ $this, 'envato_elements_feedback' ] );
	}


	/**
	 * Send page view track (only if they agree to terms with valid license)
	 *
	 * @param $page
	 * @param string $data
	 */
	public function page_view( $page, $data = '' ) {
		if ( License::get_instance()->is_activated() ) {
			API::get_instance()->api_call(
				'v1/statistics/page_view', [
					'page' => $page,
					'data' => $data,
				]
			);
		}
	}


	public function envato_elements_feedback() {
		check_admin_referer( 'feedback' );

		switch ( $_GET['answer'] ) {
			case 'yes':
				update_option( 'envato_elements_feedback_photos', 'yes' );
				API::get_instance()->api_call(
					'v1/statistics/feedback', [
						'feedback' => 'photos',
						'answer'   => 'yes',
					]
				);
				break;
			case 'no':
				update_option( 'envato_elements_feedback_photos', 'no' );
				API::get_instance()->api_call(
					'v1/statistics/feedback', [
						'feedback' => 'photos',
						'answer'   => 'no',
					]
				);
				break;
		}
		wp_safe_redirect( admin_url( 'admin.php?page=envato-elements&category=photos' ) );

	}

	public function generate_form( $type = '' ) {

		$url_yes = wp_nonce_url(
			add_query_arg(
				[
					'action'   => 'envato_elements_feedback',
					'feedback' => 'photos',
					'answer'   => 'yes',
				], admin_url( 'admin.php' )
			), 'feedback'
		);

		$url_no = wp_nonce_url(
			add_query_arg(
				[
					'action'   => 'envato_elements_feedback',
					'feedback' => 'photos',
					'answer'   => 'no',
				], admin_url( 'admin.php' )
			), 'feedback'
		);

		ob_start();
		?>
		<section class="envato-elements__modal">
			<div class="envato-elements__modal-inner">
				<div class="envato-elements__modal-inner-bg">
					<?php if ( get_option( 'envato_elements_feedback_photos' ) ) { ?>
						<h3 class="envato-elements__feedback-question">Thank you for your feedback.</h3>
						<h3 class="envato-elements__feedback-question">We will let you know when Photos become available.</h3>
					<?php } else { ?>
						<h3
							class="envato-elements__feedback-question">Would having access to 500,000 Envato Elements Photos from within
							WordPress be useful to you?</h3>
						<div class="envato-elements__feedback-answers-wrap">
							<a href="<?php echo esc_url( $url_yes ); ?>"><span><img
										src="<?php echo ENVATO_ELEMENTS_URI . 'assets/images/thumbs-up.svg'; ?>"> </span><br>Yes</a>
							<a href="<?php echo esc_url( $url_no ); ?>"><span><img
										src="<?php echo ENVATO_ELEMENTS_URI . 'assets/images/thumbs-down.svg'; ?>"></span><br>No</a>
						</div>
					<?php } ?>
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

}
