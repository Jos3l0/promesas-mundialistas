<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class PM_Promesas_List_Table extends WP_List_Table {

	private $status = 'published';

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'promesa',
				'plural'   => 'promesas',
				'ajax'     => false,
			)
		);
	}

	private function safe_admin_redirect( $url ) {
		$url = esc_url_raw( $url );

		if ( ! headers_sent() ) {
			wp_safe_redirect( $url );
			exit;
		}

		// Fallback si ya hubo output (warnings/notices/etc.)
		echo '<script>window.location.href=' . wp_json_encode( $url ) . ';</script>';
		echo '<meta http-equiv="refresh" content="0;url=' . esc_attr( $url ) . '">';
		exit;
	}

	public function get_columns() {
		return array(
			'cb'         => '<input type="checkbox" />',
			'nombre'     => 'Nombre',
			'instagram'  => 'Instagram',
			'condicion'  => 'Condición',
			'created_at' => 'Fecha',
		);
	}

	protected function get_sortable_columns() {
		return array();
	}

	public function get_views() {
		$current = $this->status;
		$base = admin_url( 'admin.php?page=pm_promesas' );

		$views = array();

		$views['published'] = sprintf(
			'<a href="%s"%s>Publicadas</a>',
			esc_url( add_query_arg( array( 'status' => 'published' ), $base ) ),
			( 'published' === $current ? ' class="current"' : '' )
		);

		$views['trash'] = sprintf(
			'<a href="%s"%s>Papelera</a>',
			esc_url( add_query_arg( array( 'status' => 'trash' ), $base ) ),
			( 'trash' === $current ? ' class="current"' : '' )
		);

		return $views;
	}

	protected function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="id[]" value="%d" />', (int) $item['id'] );
	}

	public function column_nombre( $item ) {
		$name = trim( (string) $item['first_name'] . ' ' . (string) $item['last_name'] );
		$hash = (string) $item['hash'];

		$edit_url = admin_url( 'admin.php?page=pm_promesa_edit&hash=' . rawurlencode( $hash ) );

		$actions = array();

		$actions['edit'] = sprintf( '<a href="%s">Editar</a>', esc_url( $edit_url ) );

		if ( 'trash' === $this->status ) {
			$restore = wp_nonce_url(
				admin_url( 'admin-post.php?action=pm_promesa_restore&id=' . (int) $item['id'] . '&status=trash' ),
				'pm_promesa_restore'
			);
			$delete = wp_nonce_url(
				admin_url( 'admin-post.php?action=pm_promesa_delete&id=' . (int) $item['id'] . '&status=trash' ),
				'pm_promesa_delete'
			);

			$actions['restore'] = sprintf( '<a href="%s">Restaurar</a>', esc_url( $restore ) );
			$actions['delete']  = sprintf( '<a href="%s" style="color:#b32d2e">Eliminar definitivamente</a>', esc_url( $delete ) );
		} else {
			$trash = wp_nonce_url(
				admin_url( 'admin-post.php?action=pm_promesa_trash&id=' . (int) $item['id'] ),
				'pm_promesa_trash'
			);
			$actions['trash'] = sprintf( '<a href="%s" style="color:#b32d2e">Papelera</a>', esc_url( $trash ) );
		}

		return sprintf(
			'<a class="row-title" href="%s">%s</a> %s',
			esc_url( $edit_url ),
			esc_html( $name ),
			$this->row_actions( $actions )
		);
	}

	public function column_instagram( $item ) {
		$ig = (string) $item['instagram'];
		$ig = ltrim( $ig, '@' );
		return '@' . esc_html( $ig );
	}

	public function column_condicion( $item ) {
		return esc_html( (string) $item['condicion'] );
	}

	public function column_created_at( $item ) {
		return esc_html( (string) $item['created_at'] );
	}

	protected function get_bulk_actions() {
		if ( 'trash' === $this->status ) {
			return array(
				'restore' => 'Restaurar',
				'delete'  => 'Eliminar definitivamente',
			);
		}

		return array(
			'trash' => 'Mover a la papelera',
		);
	}

	public function process_bulk_action() {
		if ( ! current_user_can( 'manage_options' ) ) { return; }

		$action = $this->current_action();
		if ( ! $action ) { return; }

		$ids = isset( $_POST['id'] ) && is_array( $_POST['id'] ) ? array_map( 'intval', (array) $_POST['id'] ) : array();
		if ( empty( $ids ) ) { return; }

		check_admin_referer( 'bulk-' . $this->_args['plural'] );

		if ( 'trash' === $action ) {
			foreach ( $ids as $id ) {
				PM_DB::set_status_by_id( $id, 'trash' );
			}
			$this->safe_admin_redirect( admin_url( 'admin.php?page=pm_promesas&pm_msg=trashed' ) );
		}

		if ( 'restore' === $action ) {
			foreach ( $ids as $id ) {
				PM_DB::set_status_by_id( $id, 'published' );
			}
			$this->safe_admin_redirect( admin_url( 'admin.php?page=pm_promesas&status=trash&pm_msg=restored' ) );
		}

		if ( 'delete' === $action ) {
			foreach ( $ids as $id ) {
				PM_DB::delete_by_id( $id );
			}
			$this->safe_admin_redirect( admin_url( 'admin.php?page=pm_promesas&status=trash&pm_msg=deleted' ) );
		}
	}

	public function prepare_items() {
		$this->status = isset( $_REQUEST['status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) : 'published';
		if ( ! in_array( $this->status, array( 'published', 'trash' ), true ) ) {
			$this->status = 'published';
		}

		$this->process_bulk_action();

		$per_page = 20;
		$page     = $this->get_pagenum();
		$q        = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';

		$res = PM_DB::admin_list( $q, $this->status, $page, $per_page );

		$this->items = $res['rows'];

		$this->set_pagination_args(
			array(
				'total_items' => (int) $res['total'],
				'per_page'    => $per_page,
				'total_pages' => max( 1, (int) ceil( $res['total'] / $per_page ) ),
			)
		);

		$this->_column_headers = array( $this->get_columns(), array(), array() );
	}

	public function no_items() {
		echo 'No hay promesas.';
	}
}