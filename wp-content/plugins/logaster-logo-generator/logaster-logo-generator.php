<?php

/*
Plugin Name: Logaster Logo Generator
Description: Convenient tool for creating free logos. Logos are saved to media library and fully integrated to WordPress site.
Version: 1.3
Author: Logaster
Text Domain: logaster
Author URI: http://www.logaster.com/
License: GPLv2
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
    die('Hi there!  I\'m just a plugin, not much I can do when called directly.');
}

// Include plugin class
require_once( plugin_dir_path( __FILE__ ) . 'class.logaster.php' );

// Installing the activation and deactivation hooks
register_activation_hook( __FILE__, array( 'Logaster', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Logaster', 'deactivate' ) );

// Instantiate the plugin class
$logaster_plugin = new Logaster();