<?php
/**
 * Plugin Name: Yellow Duck Gallery
 * Plugin URI: http://yellowduck.me/
 * Description: Plugin to enhance functionality
 * Version: 1.1
 * Requires at least: WP 4.5.2
 * Author: Vitaly Kukin
 * Author URI: http://yellowduck.me/
 */

if ( !defined('YDG_VERSION') ) define( 'YDG_VERSION', '1.1' );
if ( !defined('YDG_PATH') ) define( 'YDG_PATH', plugin_dir_path( __FILE__ ) );
if ( !defined('YDG_URL') ) define( 'YDG_URL', plugins_url('yd-gallery') );

require( YDG_PATH . 'core.php');

if( is_admin() ){
	require( YDG_PATH . 'setup.php');
}