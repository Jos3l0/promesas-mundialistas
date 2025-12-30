<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PM_REST {

	const NS = 'promesas/v1';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes() {
		register_rest_route(
			self::NS,
			'/submit',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'submit' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NS,
			'/search',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'search' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'q'        => array( 'type' => 'string',  'required' => false ),
					'page'     => array( 'type' => 'integer', 'required' => false, 'default' => 1 ),
					'per_page' => array( 'type' => 'integer', 'required' => false, 'default' => 12 ),
				),
			)
		);
	}

	private static function errors_response( $errors, $status = 400 ) {
		return new WP_REST_Response(
			array(
				'ok'     => false,
				'errors' => $errors,
			),
			(int) $status
		);
	}

	public static function submit( WP_REST_Request $req ) {
		$first_name    = (string) $req->get_param( 'first_name' );
		$last_name     = (string) $req->get_param( 'last_name' );
		$alias         = (string) $req->get_param( 'alias' );
		$instagram     = (string) $req->get_param( 'instagram' );
		$condicion     = (string) $req->get_param( 'condicion' );
		$promesa_texto = (string) $req->get_param( 'promesa_texto' );

		$first_name    = trim( wp_strip_all_tags( $first_name ) );
		$last_name     = trim( wp_strip_all_tags( $last_name ) );
		$alias         = trim( wp_strip_all_tags( $alias ) );
		$instagram     = trim( wp_strip_all_tags( $instagram ) );
		$condicion     = trim( wp_strip_all_tags( $condicion ) );
		$promesa_texto = trim( wp_strip_all_tags( $promesa_texto ) );

		$errors = array();

		if ( '' === $first_name ) {
			$errors['first_name'] = 'El nombre es obligatorio';
		}
		if ( '' === $last_name ) {
			$errors['last_name'] = 'El apellido es obligatorio';
		}
		if ( '' === $alias ) {
			$errors['alias'] = 'El alias es obligatorio';
		}
		if ( '' === $condicion ) {
			$errors['condicion'] = 'La condición es obligatoria';
		}
		if ( '' === $promesa_texto ) {
			$errors['promesa_texto'] = 'La promesa es obligatoria';
		}
		if ( strlen( $promesa_texto ) > 500 ) {
			$errors['promesa_texto'] = 'La promesa no puede superar 500 caracteres';
		}

		$all = $first_name . ' ' . $last_name . ' ' . $alias . ' ' . $instagram . ' ' . $condicion . ' ' . $promesa_texto;
		$matched = '';
		if ( class_exists( 'PM_Censura' ) && PM_Censura::contains_bad_words( $all, $matched ) ) {
			$errors['general'] = 'Insultos/vulgaridades/discriminación no están permitidos';
		}

		if ( ! empty( $errors ) ) {
			return self::errors_response( $errors, 400 );
		}

		$hash = strtolower( wp_generate_password( 18, false, false ) );
		$hash = preg_replace( '/[^a-z0-9]/', '', $hash );
		$hash = substr( $hash, 0, 24 );

		$data = array(
			'hash'          => $hash,
			'first_name'    => $first_name,
			'last_name'     => $last_name,
			'alias'         => $alias,
			'instagram'     => $instagram,
			'condicion'     => $condicion,
			'promesa_texto' => $promesa_texto,
			'created_at'    => current_time( 'mysql' ),
		);

		// Generar imagen oficial (si hay fondos en /imagenes/). Si falla, se guarda igual sin imagen.
		if ( class_exists( 'PM_Image_Generator' ) ) {
			$data['image_url'] = PM_Image_Generator::generate( $data );
		}

		$id = PM_DB::insert_promesa( $data );
		if ( $id <= 0 ) {
			return self::errors_response(
				array( 'general' => 'No se pudo guardar la promesa. Probá de nuevo.' ),
				500
			);
		}

		$url = home_url( '/promesa/' . $hash . '/' );

		return new WP_REST_Response(
			array(
				'ok'        => true,
				'url'       => $url,
				'hash'      => $hash,
				'image_url' => isset( $data['image_url'] ) ? (string) $data['image_url'] : '',
			),
			200
		);
	}

	public static function search( WP_REST_Request $req ) {
		$q        = (string) $req->get_param( 'q' );
		$page     = (int) $req->get_param( 'page' );
		$per_page = (int) $req->get_param( 'per_page' );

		$res = PM_DB::search( $q, $page, $per_page );

		$items = array();
		foreach ( $res['rows'] as $r ) {
			$items[] = array(
				'hash'       => (string) $r['hash'],
				'nombre'     => (string) $r['first_name'],
				'apellido'   => (string) $r['last_name'],
				'image_url'  => isset( $r['image_url'] ) ? (string) $r['image_url'] : '',
				'condicion'  => (string) $r['condicion'],
				'created_at' => (string) $r['created_at'],
				'url'        => home_url( '/promesa/' . $r['hash'] . '/' ),
			);
		}

		return new WP_REST_Response(
			array(
				'ok'    => true,
				'items' => $items,
				'total' => (int) $res['total'],
			),
			200
		);
	}
}