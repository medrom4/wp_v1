<?php
// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
    die('Hi there!  I\'m just a plugin, not much I can do when called directly.');
}
?>
<script type="text/javascript">
    function deleteLogotype(event)
    {
        event.preventDefault();
        // Check that user really want delete this logo
        var removeLogo = confirm('<?php _e( 'Do you really want to delete this logo?', $text_domain ); ?>');
        if (!removeLogo) {
            return;
        }

        // Prepare data and send request for logo uploading to site storage
        data = {
            'action': 'delete_logo',
            'attachment_id': <?php echo $logotype[ 'attachment_id' ]; ?>
        };

        jQuery.post(ajaxurl, data, function (logotype) {
            if (logotype.isDeleted) {
                window.location.href = '<?php echo get_admin_url( null, 'admin.php?page=logotypes' ); ?>';
            }
            else {
                alert('<?php _e( 'Error occurred. Please, reload page and try again.', $text_domain ); ?>');
            }
        });
    }
</script>
<div class="wrap logotype-details">
    <?php if ( is_null ( $logotype ) ): ?>
        <h2><?php _e( 'Logo not found', $text_domain ); ?></h2>
        <p><?php _e( sprintf('Please go to <a href="%s">all logos</a> page and select another one.', get_admin_url( null, 'admin.php?page=logotypes' ) ), $text_domain ); ?></p>
    <?php else: ?>
        <h2><?php printf( __( '#%s logo details', $text_domain ), $logotype[ 'order_number' ] ); ?></h2>

            <p><img src="<?php echo $logotype[ 'image_url' ]; ?>"></p>
            <h3><?php _e( 'How you can use this logo:', $text_domain ); ?></h3>
            <ol>
                <li><?php printf( __( '<a href="%s">Download</a> the logo to your computer.', $text_domain ), $logotype[ 'image_url' ]); ?></li>
                <li><?php _e( 'Use media library to insert logo into posts and pages.', $text_domain ); ?></li>
                <li><?php _e( 'Use the URL below as image source attribute in markup of your page.', $text_domain ); ?>
                    <p>
                        <code>
                            <?php echo $logotype[ 'image_url' ]; ?>
                        </code>
                    </p>
                    <p><?php _e( 'Example of HTML markup:', $text_domain ); ?> </p>
                    <code>
                        &lt;img src="<?php echo $logotype[ 'image_url' ]; ?>" alt="<?php bloginfo( 'name' ); ?>"&gt;
                    </code>
                </li>
                <li>
                    <p><?php _e( 'Use the shortcode to insert the logo into the post or page on your website. Shortcode will generate the appropriate markup for the logo.', $text_domain ); ?></p>
                    <code>
                        <?php echo $logotype[ 'shortcode_page' ] ?>
                    </code>
                </li>
                <li>
                    <p><?php _e( 'Use the code below to insert the logo to any place in your theme template.', $text_domain  ); ?></p>
                    <code>
                        <?php echo $logotype[ 'shortcode_php' ] ?>
                    </code>
                </li>
                <li>
                    <p><?php _e( sprintf('Download your high-res logo files without the watermark <a href="%s">here</a>.', $logotype[ 'logaster_logotype_url' ] ), $text_domain ); ?></p>
                </li>
                <li>
                    <p><?php _e( 'Download BrandKit. It is a complete package which includes your logo and business card, letterhead, envelope, favicon designs created based on your logo. Click the button below to download BrandKit and/or change the design aspects.', $text_domain ); ?></p>
                </li>
            </ol>
        <div>
            <script type="application/javascript" src="<?php printf( $brandkit_widget_url, $logotype[ 'id' ] ); ?>"></script>
        </div>
        <hr>
        <a href="#" onclick="deleteLogotype(event)"><span class="dashicons dashicons-trash"></span><?php _e( 'Delete this logo', $text_domain ); ?></a>
    <?php endif; ?>
</div>