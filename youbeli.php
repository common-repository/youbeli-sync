<?php
/*
Plugin Name: Youbeli Sync for WooCommerce
Plugin URI:
Description: Sync WooCommerce products to Youbeli.com !
Author: Youbeli.com
Version: 2.3
Author URI: https://www.youbeli.com/
Developer: youbeli.com, yiingxp
WC requires at least: 4.1
WC tested up to: 5.3.2
Copyright: © 2017 Youbuy Online Sdn Bhd.
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

if ( ! defined( 'YOUBELI__PLUGIN_DIR' ) ) {
	define( 'YOUBELI__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
define( 'YOUBELI_VERSION', '2.2' );
define( 'YOUBELI__MINIMUM_WP_VERSION', '4.1' );
define( 'YOUBELI_DELIVERY_DAYS', 7 );

register_activation_hook( __FILE__, array( 'Youbeli', 'plugin_activation' ) );
register_deactivation_hook( __FILE__, array( 'Youbeli', 'plugin_deactivation' ) );

if ( is_admin() ) {
	require_once YOUBELI__PLUGIN_DIR . 'class.youbeli.php';
	require_once YOUBELI__PLUGIN_DIR . 'class.youbeli-admin.php';
	require_once YOUBELI__PLUGIN_DIR . '/helper/log.php';
	add_action( 'init', array( 'Youbeli', 'init' ) );
}

