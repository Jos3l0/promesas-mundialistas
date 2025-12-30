<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PM_Router {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( __CLASS__, 'register_query_vars' ) );
		add_filter( 'template_include', array( __CLASS__, 'template_include' ) );
	}

	public static function add_rewrite_rules() {
		add_rewrite_rule( '^promesa/([A-Za-z0-9]{8,32})/?$', 'index.php?pm_promesa_hash=$matches[1]', 'top' );
	}

	public static function register_query_vars( $vars ) {
		$vars[] = 'pm_promesa_hash';
		return $vars;
	}

	public static function template_include( $template ) {
		$hash = get_query_var( 'pm_promesa_hash' );
		if ( is_string( $hash ) && '' !== $hash ) {
			$t = PM_PLUGIN_DIR . 'templates/single-promesa.php';
			if ( file_exists( $t ) ) { return $t; }
		}

		if ( is_page( 'registro' ) ) {
			$t = PM_PLUGIN_DIR . 'templates/page-registro.php';
			if ( file_exists( $t ) ) { return $t; }
		}

		if ( is_page( 'promesas-realizadas' ) ) {
			$t = PM_PLUGIN_DIR . 'templates/page-promesas-realizadas.php';
			if ( file_exists( $t ) ) { return $t; }
		}

		return $template;
	}
}
