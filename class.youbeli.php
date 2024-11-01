<?php

class Youbeli {
	private static $initiated = false;

	public static function init() {

		if ( ! self::$initiated ) {
			self::init_hooks();
			self::init_wc_hooks();
		}
	}

	private static function init_hooks() {
		self::$initiated = true;

		add_action( 'admin_menu', array( 'Youbeli', 'admin_menu' ), 10 );
		add_action( 'admin_menu', array( 'Youbeli_Admin', 'init' ) );
		add_action( 'admin_head', array( 'Youbeli', 'remove_submenu' ) );
		add_filter( 'plugin_action_links', array( 'Youbeli', 'plugin_action_links' ), 10, 2 );
		add_filter( 'plugin_action_links_' . plugin_basename( plugin_dir_path( __FILE__ ) . 'youbeli.php' ), array( 'Youbeli', 'admin_plugin_settings_link' ) );
		add_action( 'admin_enqueue_scripts', array( 'Youbeli', 'enqueue_scripts_and_styles' ), 9, 1 );
		add_action( 'wp_ajax_select', array( 'Youbeli_Admin', 'select' ) );
		add_action( 'wp_ajax_match_category', array( 'Youbeli_Admin', 'match_category' ) );
		add_action( 'wp_ajax_sync_product', array( 'Youbeli_Admin', 'sync_product' ) );
	}

	private static function init_wc_hooks() {
		add_action( 'woocommerce_product_bulk_edit_save', array( 'Youbeli_Admin', 'bulk_quick_edit_save' ) );
		add_action( 'woocommerce_product_quick_edit_save', array( 'Youbeli_Admin', 'bulk_quick_edit_save' ) );
		add_action( 'woocommerce_product_write_panel_tabs', array( 'Youbeli', 'render_custom_product_tabs' ) );
		add_action( 'woocommerce_product_data_panels', array( 'Youbeli_Admin', 'product_page_youbeli_custom_tabs_panel' ) );
		add_action( 'woocommerce_process_product_meta', array( 'Youbeli_Admin', 'product_save_data' ), 10, 2 );
		add_action( 'save_post', array( 'Youbeli_Admin', 'edit_product' ), 10, 2 );
		add_action( 'delete_post', array( 'Youbeli_Admin', 'delete_product' ) );
		add_action( 'wp_trash_post', array( 'Youbeli_Admin', 'delete_product' ) );
		add_action( 'wpmu_new_blog', array( 'Youbeli', 'on_create_blog' ), 10, 6 );
		add_filter( 'wpmu_drop_tables', array( 'Youbeli', 'on_delete_blog' ) );
	}

	public static function admin_menu() {
		self::load_menu();
	}

	public static function load_menu() {
		add_menu_page( 'Youbeli Sync', 'Youbeli Sync', 'manage_woocommerce', 'youbeli', null, 'dashicons-admin-links', '55.7' );
		add_submenu_page( 'youbeli', 'Settings', 'Settings', 'manage_woocommerce', 'youbeli_setting', array( 'Youbeli_Admin', 'setting' ) );

		if ( get_option( 'youbeli_store_id' ) && get_option( 'youbeli_api_key' ) ) {
			add_submenu_page( 'youbeli', 'Category Setting', 'Category Setting', 'manage_woocommerce', 'youbeli_category_setting', array( 'Youbeli_Admin', 'category_setting' ) );
			add_submenu_page( 'youbeli', 'Product Sync', 'Product Sync', 'manage_woocommerce', 'youbeli_product_sync', array( 'Youbeli_Admin', 'product_sync' ) );
		}
		add_submenu_page( 'youbeli', 'Log', 'Log', 'manage_woocommerce', 'youbeli_log', array( 'Youbeli_Admin', 'log' ) );
	}

	public static function remove_submenu() {
		global $submenu;
		if ( isset( $submenu['youbeli'] ) ) {
			unset( $submenu['youbeli'][0] );
		}
	}

	public static function render_custom_product_tabs() {
		echo '<li class="youbeli_sync_tab"><a href="#youbeli_sync"><span>Youbeli Sync</span></a></li>';
	}

	public static function enqueue_scripts_and_styles( $hook ) {
		global $post;
		if ( 'post-new.php' === $hook || 'post.php' === $hook ) {
			if ( 'product' === $post->post_type ) {
				wp_enqueue_style( 'youbeli-tab', plugins_url( 'assets/css/tab.css', __FILE__ ) );
			}
		}
	}

	public static function view( $name ) {
		$file = YOUBELI__PLUGIN_DIR . 'views/' . $name . '.php';
		include $file;
	}

	public static function get_page_url( $page , $args = array()) {

		if ( $page == 'youbeli_setting' ) {
			$args['page'] = 'youbeli_setting';
		} elseif ( $page == 'youbeli_category_setting' ) {
			$args['page'] = 'youbeli_category_setting';
		} elseif ( $page == 'youbeli_product_sync' ) {
			$args['page'] = 'youbeli_product_sync';
		} elseif ( $page == 'get_youbeli_category' ) {
			$args['page'] = 'youbeli_category_setting';
			$args['action'] = 'get_youbeli_category';
		} elseif ( $page == 'log' ) {
			$args['page'] = 'youbeli_log';
		} elseif ( $page == 'download_log' ) {
			$args['page'] = 'youbeli_log';
			$args['action'] = 'download';
		} elseif ( $page == 'clear_log' ) {
			$args['page'] = 'youbeli_log';
			$args['action'] = 'clear';
		}

		$url = add_query_arg( $args, admin_url( 'admin.php' ) );

		return $url;
	}

	public static function get_last_category_sync() {
		return ( get_option( 'youbeli_config_last_sync_cat' ) ) ? get_option( 'youbeli_config_last_sync_cat' ) : '-';
	}

	public static function get_last_product_sync() {
		return ( get_option( 'youbeli_config_last_sync_product' ) ) ? get_option( 'youbeli_config_last_sync_product' ) : '-';
	}

	public static function is_api_set() {
		return ( get_option( 'youbeli_store_id' ) && get_option( 'youbeli_api_key' ) );
	}

	public static function plugin_action_links( $links, $file ) {
		if ( $file == plugin_basename( plugin_dir_url( __FILE__ ) . '/youbeli.php' ) ) {
			$links[] = '<a href="' . esc_url( self::get_page_url( 'youbeli_setting' ) ) . '">' . esc_html__( 'Settings', 'youbeli' ) . '</a>';
		}

		return $links;
	}

	public static function admin_plugin_settings_link( $links ) {
		  $settings_link = '<a href="' . esc_url( self::get_page_url( 'youbeli_setting' ) ) . '">' . __( 'Settings', 'youbeli' ) . '</a>';
		  array_unshift( $links, $settings_link );
		  return $links;
	}

	public static function plugin_activation() {
		global $wpdb;

		if ( version_compare( $GLOBALS['wp_version'], YOUBELI__MINIMUM_WP_VERSION, '<' ) ) {

			$message = 'Youbeli Sync requires WordPress 4.1+ to run.';

			Youbeli::bail_on_activation( $message );
		}

		if (is_multisite()) {
			if (!is_plugin_active_for_network('woocommerce/woocommerce.php')) {
				Youbeli::bail_on_activation( 'Youbeli Sync requires WooCommerce.' );
			}
		} else {
			if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
				Youbeli::bail_on_activation( 'Youbeli Sync requires WooCommerce.' );
			}
		}

		if (is_multisite()) {
			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );
				self::create_tables();
				restore_current_blog();
			}
		} else {
			self::create_tables();
		}
	}

	public static function plugin_deactivation() {
		// self::delete_tables();
	}

	public static function on_create_blog($blog_id, $user_id, $domain, $path, $site_id, $meta) {
		if ( is_plugin_active_for_network( 'youbeli-sync/youbeli.php' ) ) {
			switch_to_blog( $blog_id );
			self::create_tables();
			restore_current_blog();
		}
	}

	public static function on_delete_blog( $tables ) {
		global $wpdb;
		$tables[] = $wpdb->prefix . 'youbeli_category';
		$tables[] = $wpdb->prefix . 'youbeli_category_path';
		$tables[] = $wpdb->prefix . 'youbeli_sync_product';
		return $tables;
	}

	private static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$tables = "
CREATE TABLE {$wpdb->prefix}youbeli_category (
id int(11) NOT NULL auto_increment,
parent_id int(11) default NULL,
level int(11) default NULL,
name varchar(255) default NULL,
PRIMARY KEY  (id)
) $collate;
CREATE TABLE {$wpdb->prefix}youbeli_category_path (
term_id int(11),
youbeli_id int(11),
youbeli_name varchar(255) default NULL,
PRIMARY KEY  (term_id,youbeli_id)
) $collate;
CREATE TABLE {$wpdb->prefix}youbeli_sync_product (
product_id int(11) NOT NULL,
action tinyint(1) default 0,
in_youbeli tinyint(1) default 0,
do_sync tinyint(1) default 0,
error varchar(255) default NULL,
date_sync datetime default NULL,
PRIMARY KEY  (product_id)
) $collate;
		";
		
		dbDelta( $tables );
	}

	private static function delete_tables() {
		global $wpdb;

		$sql = "DROP TABLE IF EXISTS {$wpdb->prefix}youbeli_category";
		$wpdb->query( $sql );

		$sql = "DROP TABLE IF EXISTS {$wpdb->prefix}youbeli_category_path";
		$wpdb->query( $sql );

		$sql = "DROP TABLE IF EXISTS {$wpdb->prefix}youbeli_sync_product";
		$wpdb->query( $sql );

	}


	private static function bail_on_activation( $message, $deactivate = true ) {
?>
<!doctype html>
<html>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<style>
* {
	text-align: center;
	margin: 0;
	padding: 0;
	font-family: "Lucida Grande",Verdana,Arial,"Bitstream Vera Sans",sans-serif;
}
p {
	margin-top: 1em;
	font-size: 18px;
}
</style>
<body>
<p><?php echo esc_html( $message ); ?></p>
</body>
</html>
<?php
if ( $deactivate ) {
	$plugins = get_option( 'active_plugins' );
	$youbeli = plugin_basename( YOUBELI__PLUGIN_DIR . 'youbeli.php' );
	$update  = false;
	foreach ( $plugins as $i => $plugin ) {
		if ( $plugin === $youbeli ) {
			$plugins[ $i ] = false;
			$update        = true;
		}
	}

	if ( $update ) {
		update_option( 'active_plugins', array_filter( $plugins ) );
	}
}
		exit;
	}



}
?>
