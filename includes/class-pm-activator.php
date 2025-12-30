<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PM_Activator {

	public static function activate() {
		PM_DB::create_table();
		PM_Router::add_rewrite_rules();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}
}
