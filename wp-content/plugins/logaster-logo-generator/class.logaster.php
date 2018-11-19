<?php

class Logaster
{
    private static $logo_filename_template = 'logo-%s.png';
    private static $show_logo_shortcode = 'show_logo';
    private static $text_domain = 'logaster';

    private static $widget_url = 'https://www.logaster.com/api/m.js?t=logo&c=wp_logo&cb=logotypeSaved';
    private static $brandkit_widget_url = 'https://www.logaster.com/api/bk/?c=wp_logo&lid=%s';
    private static $wp_activate_url = 'https://www.logaster.com/account/wp-activation/?%s';

    private static $logo_saving_dir;


    public function __construct()
    {
        // http://stackoverflow.com/questions/18079131/best-workaround-to-create-a-php-class-constant-from-an-expression
        $upload_dir = wp_upload_dir();
        self::$logo_saving_dir = $upload_dir[ 'basedir' ] . '/logos';

        if ( is_admin() ) {
            add_action( 'admin_menu', array( $this, 'register_menu' ) );
        }

        add_action( 'wp_ajax_upload_logo', array( $this, 'upload_logo_callback' ) );
        add_action( 'wp_ajax_delete_logo', array( $this, 'delete_logo_callback' ) );

        // Delete logo info from options when logotype deleted with Media Gallery
        add_action( 'delete_attachment', array( $this, 'delete_logo_from_options' ) );

        // Load shortcode and translations
        add_shortcode( self::$show_logo_shortcode, array( $this, 'show_logo' ) );
        load_plugin_textdomain( self::$text_domain, null, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }


    public static function activate()
    {
        // Create directory for storing logotypes
        if ( !is_dir( self::$logo_saving_dir ) ) {
            mkdir( self::$logo_saving_dir, 0775, true );
        }

        // Add options to db
        $options = array(
            'u'                          => null,
            'v'                          => null,
            'logotypes'                  => array(),
            'last_logotype_order_number' => 0
        );
        add_option( 'logaster', $options, null, 'no' );
    }


    public static function deactivate()
    {
        $options = get_option( 'logaster' );
        $ids = array_keys($options[ 'logotypes' ]);

        // Remove all created logotypes
        foreach ( $ids as $id ) {
            $attachment_id = $options[ 'logotypes' ][ $id ][ 'attachment_id' ];
            wp_delete_attachment( $attachment_id, true );
        }

        // Delete directory with logotypes if empty
        if ( is_dir( self::$logo_saving_dir ) ) {
            rmdir( self::$logo_saving_dir );
        }

        // Delete logaster options
        delete_option( 'logaster' );
    }


    public function register_menu()
    {
        global $wp_version;
        $menu_icon = null;

        // Add svg icon to supported WP versions
        if ( version_compare( $wp_version, '3.8',  '>=' ) ) {
            $menu_icon = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iaXNvLTg4NTktMSI/Pgo8IURPQ1RZUEUgc3ZnIFBVQkxJQyAiLS8vVzNDLy9EVEQgU1ZHIDEuMS8vRU4iICJodHRwOi8vd3d3LnczLm9yZy9HcmFwaGljcy9TVkcvMS4xL0RURC9zdmcxMS5kdGQiPgo8c3ZnIHZlcnNpb249IjEuMSIgaWQ9IkNhcGFfMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgeD0iMHB4IiB5PSIwcHgiCiAgICAgd2lkdGg9Ijk2LjkwM3B4IiBoZWlnaHQ9Ijk2LjkwNHB4IiB2aWV3Qm94PSIwIDAgOTYuOTAzIDk2LjkwNCIgc3R5bGU9ImVuYWJsZS1iYWNrZ3JvdW5kOm5ldyAwIDAgOTYuOTAzIDk2LjkwNDsiCiAgICAgeG1sOnNwYWNlPSJwcmVzZXJ2ZSI+CjxnPgogICAgPHBhdGggZD0iTTk2LjEyMyw1Mi4yNjFsLTIyLjk4NC04LjQxOHYtMjUuNjJjMC0wLjQ5NC0wLjMxMy0wLjkzNC0wLjc4MS0xLjA5Nkw0OC44MjYsOC41MDhjLTAuMjQ0LTAuMDg2LTAuNTE4LTAuMDg0LTAuNzYyLDAKICAgICAgICBsLTIzLjQ4OCw4LjM4MWMtMC4yMzcsMC4wODQtMC40NDEsMC4yMzgtMC41NDIsMC4zOThjLTAuMTcyLDAuMjA3LTAuMjY3LDAuNDcxLTAuMjY3LDAuNzM4djI1LjgwN2wtMjIuOTU4LDguMTkKICAgICAgICBjLTAuMjM3LDAuMDg1LTAuNDQxLDAuMjM5LTAuNTQyLDAuMzk5QzAuMDk1LDUyLjYyOCwwLDUyLjg5MiwwLDUzLjE2djI2LjA0M2MwLDAuNDksMC4zMTMsMC45MzIsMC43NzgsMS4wOTRsMjMuNTAyLDguMQogICAgICAgIGMwLjI0NSwwLjA4NCwwLjUxOCwwLjA4NCwwLjc2MywwbDIzLjQwOS04LjA2MmwyMy4zNiw4LjA2MmMwLjI0NCwwLjA4NCwwLjUxOCwwLjA4NCwwLjc2MywwbDIzLjUwOC04LjExCiAgICAgICAgYzAuNDg5LTAuMTUsMC44Mi0wLjU5NiwwLjgyLTEuMTA5VjUzLjM1N0M5Ni45MDMsNTIuODYzLDk2LjU5Miw1Mi40MjMsOTYuMTIzLDUyLjI2MXogTTY5LjA0Miw0Mi4wODJsLTE4LjA2Myw2LjExM1YyOC44MjIKICAgICAgICBsMTguMDYzLTYuNjI1VjQyLjA4MnogTTQ4LjQ0NCwxMi43MTJsMTYuODAxLDYuMjc3bC0xNi43ODEsNi4yMTVsLTE3LjczLTYuMTE5TDQ4LjQ0NCwxMi43MTJ6IE0yNC42OTgsNjAuMzM5TDYuOTY4LDU0LjIyCiAgICAgICAgbDE3LjcxMS02LjM3M2wxNi44MDEsNi4yNzdMMjQuNjk4LDYwLjMzOXogTTQ1LjI3Niw3Ny4yMTZMMjcuMjEyLDgzLjMzVjYzLjk1N2wxOC4wNjQtNi42MjVWNzcuMjE2eiBNNzIuMjI5LDYwLjMzOUw1NC40OTksNTQuMjIKICAgICAgICBsMTcuNzExLTYuMzczbDE2LjgwMiw2LjI3N0w3Mi4yMjksNjAuMzM5eiBNOTIuODA3LDc3LjIxNkw3NC43NDMsODMuMzNWNjMuOTU3bDE4LjA2My02LjYyNVY3Ny4yMTZ6Ii8+CjwvZz4KPC9zdmc+Cg==';
        }

        $plugin_instance = $this;

        add_menu_page(
            __( 'Logos', self::$text_domain ),
            __( 'Logos', self::$text_domain ),
            'manage_options',
            'logotypes',
            function() use ( $plugin_instance ) { $plugin_instance->create_logo(); },
            $menu_icon
        );

        add_submenu_page(
            'Logo details',
            'Logo details',
            'Logo details',
            'manage_options',
            'logotype-details',
            function () use ( $plugin_instance ) { $plugin_instance->logo_details(); }
        );
    }


    /*
     * Function for displaying menu item and creating logo page
     */
    public function create_logo()
    {
        self::load_styles();

        // Build widget url
        $options = get_option( 'logaster' );
        self::$widget_url = sprintf( self::$widget_url );

        // Add to widget url u and v if present in options
        if ( $options[ 'u' ] != null && $options[ 'v' ] != null ) {
            self::$widget_url .= sprintf( '&u=%s', $options[ 'u' ] );
        }

        $can_create_logotype = false;
        if ( ini_get( 'allow_url_fopen' ) == "1" || extension_loaded( 'curl' ) )
        {
            $can_create_logotype = true;
        }

        // Template args
        $logotypes = $this->get_created_logotypes($options);
        $text_domain = self::$text_domain;
        $widget_url = self::$widget_url;
        require_once( plugin_dir_path( __FILE__ ) . 'views/create_logo.php' );
    }

    /*
     * Function for creating logo details menu and logo page
     */
    public function logo_details()
    {
        self::load_styles();

        $id = null;
        if (isset( $_GET[ 'id' ] ) && array_key_exists( 'id', $_GET ) )
        {
            $id = intval( $_GET[ 'id' ] );
        }

        // Template args
        $options = get_option( 'logaster' );
        $logotype = self::get_logotype_data( $id, $options );
        $text_domain = self::$text_domain;
        $widget_url = self::$widget_url;
        $brandkit_widget_url = self::$brandkit_widget_url;
        $wp_activate_url = self::$wp_activate_url;
        require_once( plugin_dir_path( __FILE__ ) . 'views/logo_details.php' );
    }


    /*
     * Process ajax request http://codex.wordpress.org/AJAX_in_Plugins
     * */
    public function upload_logo_callback()
    {
        $url = $_POST[ 'smallSizeUrl' ];
        $logotype_id = intval( $_POST[ 'id' ] );

        $upload_result = self::save_logo_to_uploads( $url, $logotype_id );

        if ( $upload_result[ 'is_uploaded' ] == true ) {
            // Add additional data about logotype to result
            // Add uploaded logotype data to db
            $options = get_option( 'logaster' );
            $options[ 'last_logotype_order_number' ] += 1;
            $options[ 'logotypes' ][ $logotype_id ] = array(
                'order_number'  => $options[ 'last_logotype_order_number' ],
                'attachment_id' => $upload_result[ 'attachment_id' ],
            );

            // If this logo is first set u, v
            if ( is_null( $options[ 'u' ] ) && is_null( $options[ 'v' ] ) && isset( $_POST[ 'u' ] ) && isset( $_POST[ 'v' ] ) ) {
                $options[ 'u' ] = intval( $_POST[ 'u' ] );
                $options[ 'v' ] = sanitize_text_field( $_POST[ 'v' ] );
            }

            update_option( 'logaster', $options );

            $upload_result[ 'details_url' ] = self::get_logo_details_url( $logotype_id );
        }

        // Setting header and returning json with image upload
        header( 'Content-Type: application/json' );
        echo json_encode( $upload_result );
        die();
    }

    /*
     * Returns an array of urls of already created logos
     * */
    public function get_created_logotypes($options = null)
    {
        if ( is_null( $options ) )
        {
            $options = get_option('logaster');
        }

        $ids = array_keys($options[ 'logotypes' ]);
        // Sort ids in descending order
        usort( $ids, create_function( '$a,$b', '$b - $a;' ) );

        $logotypes = array();

        foreach ( $ids as $id ) {
            $logotype_data = self::get_logotype_data( $id, $options );
            array_push( $logotypes, $logotype_data );
        }
        return $logotypes;
    }


    /*
     * Shortcode to display logotype anywhere
     */
    public function show_logo( $atts ) {
        $defaults = array(
            'id' => null
        );

        extract( shortcode_atts( $defaults, $atts ) );

        if ( !is_null( $id ) ) {
            $url = self::get_logo_image_url( $id );
            return sprintf( '<img src="%s">', $url );
        }
    }


    /*
     * Used for ajax delete logotype
     */
    public function delete_logo_callback()
    {
        $attachment_id = intval( $_POST[ 'attachment_id' ] );
        $status = wp_delete_attachment( $attachment_id, true );

        if ( $status !== false ) {
            $result = array( 'isDeleted' => true );
        } else {
            $result = array( 'isDeleted' => false );
        }

        header( 'Content-Type: application/json' );
        echo json_encode( $result );
        die();
    }


    /*
     * Automatically delete options record for given logo.
     * Hooked to action 'delete_attachment' which is fired when invoked wp_delete_attachment()
     */
    public function delete_logo_from_options( $attachment_id )
    {
        // Return if deleted file is not in logo upload directory
        $attachment_path = get_attached_file( $attachment_id );
        $filename = basename( $attachment_path );

        if ( realpath( $attachment_path ) != realpath(sprintf( '%s/%s', self::$logo_saving_dir, $filename ))) {
            return;
        }

        $options = get_option( 'logaster' );
        $ids = array_keys($options[ 'logotypes' ]);

        foreach ( $ids as $id ) {
            // Find index by attachment id. $id - will be index in $options['logotypes']
            if ( $options[ 'logotypes' ][ $id ][ 'attachment_id' ] == $attachment_id ) {
                unset( $options[ 'logotypes' ][ $id ] );
                break;
            }
        }
        // Update options
        update_option( 'logaster', $options );
    }


    /*
     * Function for saving created logo to upload folder
     * */
    private function save_logo_to_uploads( $url, $logotype_id )
    {
        $filename = sprintf( self::$logo_filename_template, $logotype_id );
        $upload_file_path = sprintf( '%s/%s', self::$logo_saving_dir, $filename );

        $contents = false;

        if ( ini_get( 'allow_url_fopen' ) == "1")
        {
            $contents = file_get_contents( $url );
        }
        elseif ( extension_loaded( 'curl' ) )
        {
            // if content not got and there is curl enabled, try using it to get that file
            $ch = curl_init();
            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            $contents = curl_exec( $ch );
            curl_close($ch);
        }

        // If loading of logo was failed or nor allow_url_fopen and nor curl are enabled
        if ( $contents == false )
        {
            return array( 'is_uploaded' => false );
        }

        $savefile = fopen( $upload_file_path, 'w' );
        fwrite( $savefile, $contents );
        fclose( $savefile );

        $wp_filetype = wp_check_filetype( basename( $filename ), null );

        $attachment = array(
            'post_mime_type' => $wp_filetype[ 'type' ],
            'post_title'     => $filename,
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        $attachment_id = wp_insert_attachment( $attachment, $upload_file_path );

        $imagenew = get_post( $attachment_id );
        $fullsize_path = get_attached_file( $imagenew->ID );
        $attach_data = wp_generate_attachment_metadata( $attachment_id, $fullsize_path );
        wp_update_attachment_metadata( $attachment_id, $attach_data );

        return array(
            'id'            => $logotype_id,
            'is_uploaded'   => true,
            'path'          => $upload_file_path,
            'attachment_id' => $attachment_id,
            'upload_dir'    => self::$logo_saving_dir,
        );
    }


    /*
     * Return absolute url of given path to absolute url. Can receive array of paths
     * */
    private static function convert_path_to_url( $path )
    {
        return str_replace( ABSPATH, get_bloginfo( 'url' ) . '/', $path );
    }


    /*
     * Return shorcode snippet to logo with specified filename.
     * Snippet can be used in pages, posts or in template as php code.
     * Accept path an url as parameter
     */
    private static function get_shortcode( $id, $forPage = true )
    {
        if ( $forPage == true ) {
            return sprintf( '[%s id=%s]', self::$show_logo_shortcode, $id );
        } else {
            return sprintf( '&lt;?php echo do_shortcode( "[%s id=%s]" ); ?&gt;', self::$show_logo_shortcode, $id );
        }
    }

    /*
     * Get logotype data by name or by url
     * URL has higher priority
     */
    private static function get_logotype_data( $id, $options = null )
    {
        if ( is_null( $options ) )
        {
            $options = get_option('logaster');
        }

        if (!isset($options[ 'logotypes' ][ $id ]) || !array_key_exists($id, $options[ 'logotypes' ]))
        {
            return null;
        }

        $logotype_options = $options[ 'logotypes' ][ $id ];

        $logotype = array(
            'id'                    => $id,
            'attachment_id'         => $logotype_options[ 'attachment_id' ],
            'order_number'          => $logotype_options[ 'order_number' ],
            'image_url'             => self::get_logo_image_url( $id ),
            'details_url'           => self::get_logo_details_url( $id ),
            'shortcode_page'        => self::get_shortcode( $id, true ),
            'shortcode_php'         => self::get_shortcode( $id, false ),
            'logaster_logotype_url' => self::get_logaster_logotype_url( $id, $options )
        );

        return $logotype;
    }

    /*
     * Get url to logo details page
     */
    private static function get_logo_details_url( $id )
    {
        return get_admin_url( null, 'admin.php?page=logotype-details&id=' . $id );
    }

    /*
     * Get url to logo preview image
     */
    private static function get_logo_image_url( $id )
    {
        $filename = sprintf( self::$logo_filename_template, $id );
        $path = sprintf( '%s/%s', self::$logo_saving_dir, $filename );
        $url = self::convert_path_to_url( $path );
        return $url;
    }


    /*
     * Load styles
     * http://codex.wordpress.org/Function_Reference/wp_enqueue_style
     */
    private static function load_styles()
    {
        wp_enqueue_style( 'logaster-style', plugins_url( 'css/style.css', __FILE__ ), array(), '1.0' );
    }

    /*
     * Get url to logaster logotype details page
     */
    private static function get_logaster_logotype_url( $id, $options = null )
    {
        if ( is_null( $options ) )
        {
            $options = get_option('logaster');
        }

        if (!isset($options[ 'logotypes' ][ $id ]) || !array_key_exists($id, $options[ 'logotypes' ]))
        {
            return null;
        }

        $data = array(
            'id'    => $id,
            'rt'    => 'logo',
            'vk'    => $options[ 'v' ],
            'uid'   => $options[ 'u' ]
        );

        $params = http_build_query($data);

        return sprintf(self::$wp_activate_url, $params);
    }
}