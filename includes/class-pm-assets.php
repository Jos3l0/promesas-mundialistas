<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PM_Assets {

	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	public static function enqueue() {
		if ( is_page( 'registro' ) ) {
			wp_enqueue_style( 'pm-public', PM_PLUGIN_URL . 'public/css/pm-public.css', array(), PM_PLUGIN_VERSION );
			wp_enqueue_script( 'pm-registro', PM_PLUGIN_URL . 'public/js/pm-registro.js', array(), PM_PLUGIN_VERSION, true );
			wp_localize_script(
				'pm-registro',
				'PM_REG',
				array(
					'endpoint' => esc_url_raw( rest_url( 'promesas/v1/submit' ) ),
				)
			);
		}

		if ( is_page( 'promesas-realizadas' ) ) {
			wp_enqueue_style( 'pm-public', PM_PLUGIN_URL . 'public/css/pm-public.css', array(), PM_PLUGIN_VERSION );
			wp_enqueue_script( 'pm-listado', PM_PLUGIN_URL . 'public/js/pm-listado.js', array(), PM_PLUGIN_VERSION, true );
			wp_localize_script(
				'pm-listado',
				'PM_LIST',
				array(
					'endpoint'  => esc_url_raw( rest_url( 'promesas/v1/search' ) ),
					'per_page'  => 12,
				)
			);
		}

		if ( get_query_var( 'pm_promesa_hash' ) ) {
			wp_enqueue_style( 'pm-public', PM_PLUGIN_URL . 'public/css/pm-public.css', array(), PM_PLUGIN_VERSION );
		}
	}
}
