<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PM_DB {

	public static function table() {
		global $wpdb;
		return $wpdb->prefix . 'pm_promesas';
	}

	public static function init() {
		// Auto-migración: asegura columnas requeridas.
		if ( ! self::has_column( 'image_url' ) || ! self::has_column( 'status' ) ) {
			self::create_table();
		}
	}

	private static function has_column( $column ) {
		global $wpdb;
		$table  = self::table();
		$column = is_string( $column ) ? trim( $column ) : '';
		if ( '' === $column ) { return false; }

		$exists = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", $column ) );
		return ! empty( $exists );
	}

	public static function create_table() {
		global $wpdb;

		$table = self::table();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			hash VARCHAR(64) NOT NULL,
			first_name VARCHAR(120) NOT NULL,
			last_name VARCHAR(120) NOT NULL,
			alias VARCHAR(120) NULL,
			instagram VARCHAR(120) NOT NULL,
			condicion VARCHAR(80) NOT NULL,
			promesa_texto TEXT NOT NULL,
			image_url TEXT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'published',
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY hash (hash),
			KEY status (status),
			KEY created_at (created_at),
			KEY last_name (last_name),
			KEY first_name (first_name)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		// Backfill status for older rows.
		if ( self::has_column( 'status' ) ) {
			$wpdb->query( "UPDATE {$table} SET status = 'published' WHERE status = '' OR status IS NULL" );
		}
	}

	public static function insert_promesa( $data ) {
		global $wpdb;
		$table = self::table();

		if ( ! is_array( $data ) ) { return 0; }

		$fields = array(
			'hash'          => isset( $data['hash'] ) ? (string) $data['hash'] : '',
			'first_name'    => isset( $data['first_name'] ) ? (string) $data['first_name'] : '',
			'last_name'     => isset( $data['last_name'] ) ? (string) $data['last_name'] : '',
			'alias'         => isset( $data['alias'] ) ? (string) $data['alias'] : '',
			'instagram'     => isset( $data['instagram'] ) ? (string) $data['instagram'] : '',
			'condicion'     => isset( $data['condicion'] ) ? (string) $data['condicion'] : '',
			'promesa_texto' => isset( $data['promesa_texto'] ) ? (string) $data['promesa_texto'] : '',
			'image_url'     => isset( $data['image_url'] ) ? (string) $data['image_url'] : '',
			'status'        => 'published',
			'created_at'    => isset( $data['created_at'] ) ? (string) $data['created_at'] : current_time( 'mysql' ),
		);

		$formats = array( '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s' );

		$ok = $wpdb->insert( $table, $fields, $formats );
		if ( ! $ok ) { return 0; }

		return (int) $wpdb->insert_id;
	}

	public static function get_by_hash( $hash ) {
		global $wpdb;
		$table = self::table();
		$hash = is_string( $hash ) ? trim( $hash ) : '';
		if ( '' === $hash ) { return null; }

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE hash = %s AND status = 'published' LIMIT 1",
				$hash
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	public static function get_by_hash_admin( $hash ) {
		global $wpdb;
		$table = self::table();
		$hash = is_string( $hash ) ? trim( $hash ) : '';
		if ( '' === $hash ) { return null; }

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE hash = %s LIMIT 1",
				$hash
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	public static function search( $q, $page, $per_page ) {
		global $wpdb;
		$table = self::table();

		$page     = max( 1, (int) $page );
		$per_page = min( 50, max( 1, (int) $per_page ) );
		$offset   = ( $page - 1 ) * $per_page;

		$where  = "status = 'published'";
		$params = array();

		$q = is_string( $q ) ? trim( $q ) : '';
		if ( '' !== $q ) {
			$like = '%' . $wpdb->esc_like( $q ) . '%';
			$where .= ' AND (first_name LIKE %s OR last_name LIKE %s)';
			$params[] = $like;
			$params[] = $like;
		}

		$sql_count = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
		$total = (int) ( $params ? $wpdb->get_var( $wpdb->prepare( $sql_count, $params ) ) : $wpdb->get_var( $sql_count ) );

		$sql_items = "SELECT id, hash, first_name, last_name, alias, instagram, condicion, promesa_texto, image_url, created_at
			FROM {$table}
			WHERE {$where}
			ORDER BY id DESC
			LIMIT %d OFFSET %d";

		$params_items = $params;
		$params_items[] = $per_page;
		$params_items[] = $offset;

		$rows = $wpdb->get_results( $wpdb->prepare( $sql_items, $params_items ), ARRAY_A );
		if ( ! is_array( $rows ) ) { $rows = array(); }

		return array(
			'total' => $total,
			'rows'  => $rows,
		);
	}

	public static function admin_list( $q, $status, $page, $per_page ) {
		global $wpdb;
		$table = self::table();

		$page     = max( 1, (int) $page );
		$per_page = min( 100, max( 1, (int) $per_page ) );
		$offset   = ( $page - 1 ) * $per_page;

		$status = is_string( $status ) ? trim( $status ) : 'published';
		if ( ! in_array( $status, array( 'published', 'trash' ), true ) ) {
			$status = 'published';
		}

		$where  = 'status = %s';
		$params = array( $status );

		$q = is_string( $q ) ? trim( $q ) : '';
		if ( '' !== $q ) {
			$like = '%' . $wpdb->esc_like( $q ) . '%';
			$where .= ' AND (first_name LIKE %s OR last_name LIKE %s)';
			$params[] = $like;
			$params[] = $like;
		}

		$sql_count = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
		$total = (int) $wpdb->get_var( $wpdb->prepare( $sql_count, $params ) );

		$sql_items = "SELECT * FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT %d OFFSET %d";
		$params_items = $params;
		$params_items[] = $per_page;
		$params_items[] = $offset;

		$rows = $wpdb->get_results( $wpdb->prepare( $sql_items, $params_items ), ARRAY_A );
		if ( ! is_array( $rows ) ) { $rows = array(); }

		return array(
			'total' => $total,
			'rows'  => $rows,
		);
	}

	public static function update_promesa_by_id( $id, $data ) {
		global $wpdb;
		$table = self::table();
		$id = (int) $id;
		if ( $id <= 0 ) { return false; }
		if ( ! is_array( $data ) ) { return false; }

		$fields = array();
		$formats = array();

		$map = array(
			'first_name'    => '%s',
			'last_name'     => '%s',
			'alias'         => '%s',
			'instagram'     => '%s',
			'condicion'     => '%s',
			'promesa_texto' => '%s',
			'image_url'     => '%s',
		);

		foreach ( $map as $k => $fmt ) {
			if ( array_key_exists( $k, $data ) ) {
				$fields[ $k ] = (string) $data[ $k ];
				$formats[] = $fmt;
			}
		}

		if ( empty( $fields ) ) { return false; }

		return ( false !== $wpdb->update( $table, $fields, array( 'id' => $id ), $formats, array( '%d' ) ) );
	}

	public static function set_status_by_id( $id, $status ) {
		global $wpdb;
		$table = self::table();

		$id = (int) $id;
		$status = is_string( $status ) ? trim( $status ) : '';
		if ( $id <= 0 ) { return false; }
		if ( ! in_array( $status, array( 'published', 'trash' ), true ) ) { return false; }

		return ( false !== $wpdb->update( $table, array( 'status' => $status ), array( 'id' => $id ), array( '%s' ), array( '%d' ) ) );
	}

	public static function delete_by_id( $id ) {
		global $wpdb;
		$table = self::table();

		$id = (int) $id;
		if ( $id <= 0 ) { return false; }

		return ( false !== $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) ) );
	}
}
