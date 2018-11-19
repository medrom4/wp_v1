<?php
    // Make sure we don't expose any info if called directly
    if ( !function_exists( 'add_action' ) ) {
        die( 'Hi there!  I\'m just a plugin, not much I can do when called directly.' );
    }
?>
<script type="text/javascript">
    function createLogo() {
        var url = "<?php echo $widget_url; ?>";
        var s = document.createElement("script");
        s.type = "application/javascript";
        s.src = url;
        var e = document.getElementById("logaster-ph");
        e.appendChild(s);
    }

    function logotypeSaved(logotype) {
        // Send request for logo uploading to site storage
        logotype.action = 'upload_logo';

        jQuery.post(ajaxurl, logotype, function (upload_result) {
            if (upload_result.is_uploaded == true) {
                window.location = upload_result.details_url;
            } else {
                // Show notice
                jQuery('#message').removeClass('hidden');
            }
        });
    }
</script>
<div class="wrap create-logo">
    <h2><?php _e( 'Create your logo for website using Logaster\'s plugin', $text_domain ); ?></h2>
    <?php if ( $can_create_logotype ): ?>
        <div id="message" class="hidden error">
            <p><?php _e( 'Logotype uploading failed. Please, try to create logo again.', $text_domain ); ?></p>
        </div>

        <?php if ( empty( $logotypes ) ): ?>
            <script type="text/javascript">jQuery(document).ready(createLogo);</script>
        <?php else: ?>
            <p><?php _e( 'Creating process is so easy! Just enter your website name and follow the instructions below.', $text_domain ); ?></p>
            <p class="submit">
                <a href="#" id="create-logo" class="button button-primary" onclick="createLogo(); return false;"><?php _e( 'Create logo', $text_domain ); ?></a>
            </p>
        <?php endif; ?>

		<div id="logaster-ph"></div>
		<div id="logaster"></div>

    <?php else: ?>
        <div id="message" class="update-nag">
            <?php _e( 'To create logo please enable cURL extension or "allow_url_fopen" setting in php.ini.', $text_domain ); ?>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $logotypes ) ): ?>
        <hr>
        <h2><?php _e( 'Already created logos', $text_domain ); ?></h2>
        <p><?php _e( 'Click on logo preview to see how you can use it on your site.', $text_domain ); ?></p>
        <div id="created-logotypes">
            <?php foreach ( $logotypes as $logotype ): ?>
                <a href="<?php echo $logotype['details_url']; ?>"><img src="<?php echo $logotype['image_url']; ?>"></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>