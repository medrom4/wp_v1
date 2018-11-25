<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<div class="envato-elements__wrapper envato-elements__wrapper--fixed">
	<div class="envato-elements__header">
		<?php echo $this->header; ?>
	</div>

	<div class="envato-elements__content">
		<?php Envato_Elements\Notices::get_instance()->print_global_notices();
		//echo $this->render_template( 'notices/advertisement.php' );
		?>
		<div class="envato-elements__modal-holder"></div>
		<div class="envato-elements__content-dynamic">
			<?php echo $this->content; ?>
		</div>
	</div>

	<div class="envato-elements__support">
		<p>
			<strong>Feedback &amp; Support: </strong> If you have any questions or feedback for the team please send an email to
			<a href="mailto:extensions@envato.com">extensions@envato.com</a>.</p>
	</div>


</div>
