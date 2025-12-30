<?php
/**
 * Plugin Name: Promesas Mundialistas
 * Description: Registro público de promesas mundialistas con tabla propia y páginas /registro/ y /promesas-realizadas/.
 * Version: 1.2.0
 * Author: StartMotifHost
 * Text Domain: promesas-mundialistas
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'PM_PLUGIN_VERSION', '1.2.0' );
define( 'PM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once PM_PLUGIN_DIR . 'includes/class-pm-activator.php';
require_once PM_PLUGIN_DIR . 'includes/class-pm-db.php';
require_once PM_PLUGIN_DIR . 'includes/class-pm-image-generator.php';
require_once PM_PLUGIN_DIR . 'includes/class-pm-censura.php';
require_once PM_PLUGIN_DIR . 'includes/class-pm-router.php';
require_once PM_PLUGIN_DIR . 'includes/class-pm-rest.php';
require_once PM_PLUGIN_DIR . 'includes/class-pm-assets.php';

if ( is_admin() ) {
	require_once PM_PLUGIN_DIR . 'admin/class-pm-admin.php';
}

register_activation_hook( __FILE__, array( 'PM_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PM_Activator', 'deactivate' ) );

add_action( 'plugins_loaded', function () {
	PM_DB::init();
	PM_Router::init();
	PM_REST::init();
	PM_Assets::init();
	if ( is_admin() ) { PM_Admin::init(); }
} );