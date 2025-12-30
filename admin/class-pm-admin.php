<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PM_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_pm_promesa_trash', array( __CLASS__, 'handle_trash' ) );
		add_action( 'admin_post_pm_promesa_restore', array( __CLASS__, 'handle_restore' ) );
		add_action( 'admin_post_pm_promesa_delete', array( __CLASS__, 'handle_delete' ) );
		add_action( 'admin_post_pm_promesa_save', array( __CLASS__, 'handle_save' ) );
	}

	public static function menu() {
		add_menu_page(
			'Promesas',
			'Promesas',
			'manage_options',
			'pm_promesas',
			array( __CLASS__, 'render_list' ),
			'dashicons-list-view',
			26
		);

		add_submenu_page(
			'pm_promesas',
			'Todas las promesas',
			'Todas las promesas',
			'manage_options',
			'pm_promesas',
			array( __CLASS__, 'render_list' )
		);

		add_submenu_page(
			'pm_promesas',
			'Editar promesa',
			'Editar promesa',
			'manage_options',
			'pm_promesa_edit',
			array( __CLASS__, 'render_edit' )
		);
	}

	public static function render_list() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'No autorizado.' ); }

		require_once PM_PLUGIN_DIR . 'admin/class-pm-list-table.php';

		$list_table = new PM_Promesas_List_Table();
		$list_table->prepare_items();

		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">Promesas</h1>';
		echo '<hr class="wp-header-end">';

		if ( isset( $_GET['pm_msg'] ) ) {
			$msg = sanitize_text_field( wp_unslash( $_GET['pm_msg'] ) );
			if ( 'updated' === $msg ) {
				echo '<div class="notice notice-success is-dismissible"><p>Promesa actualizada.</p></div>';
			} elseif ( 'trashed' === $msg ) {
				echo '<div class="notice notice-success is-dismissible"><p>Promesa movida a la papelera.</p></div>';
			} elseif ( 'restored' === $msg ) {
				echo '<div class="notice notice-success is-dismissible"><p>Promesa restaurada.</p></div>';
			} elseif ( 'deleted' === $msg ) {
				echo '<div class="notice notice-success is-dismissible"><p>Promesa eliminada definitivamente.</p></div>';
			}
		}

		$list_table->views();

		echo '<form method="post">';
		echo '<input type="hidden" name="page" value="pm_promesas" />';
		if ( isset( $_REQUEST['status'] ) ) {
			$status = sanitize_text_field( wp_unslash( $_REQUEST['status'] ) );
			if ( in_array( $status, array( 'published', 'trash' ), true ) ) {
				echo '<input type="hidden" name="status" value="' . esc_attr( $status ) . '" />';
			}
		}
		$list_table->search_box( 'Buscar', 'pm_search' );
		$list_table->display();
		echo '</form>';

		echo '</div>';
	}

	public static function render_edit() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'No autorizado.' ); }

		$hash = isset( $_GET['hash'] ) ? sanitize_text_field( wp_unslash( $_GET['hash'] ) ) : '';
		$row = PM_DB::get_by_hash_admin( $hash );

		if ( ! $row ) {
			echo '<div class="wrap"><h1>Editar promesa</h1><p>No encontrada.</p></div>';
			return;
		}

		require PM_PLUGIN_DIR . 'admin/page-edit.php';
	}

	private static function redirect_list( $msg, $status = '' ) {
		$args = array(
			'page'   => 'pm_promesas',
			'pm_msg' => $msg,
		);
		if ( in_array( $status, array( 'published', 'trash' ), true ) ) {
			$args['status'] = $status;
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function handle_trash() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'No autorizado.' ); }
		check_admin_referer( 'pm_promesa_trash' );

		$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		if ( $id > 0 ) {
			PM_DB::set_status_by_id( $id, 'trash' );
		}
		self::redirect_list( 'trashed', 'published' );
	}

	public static function handle_restore() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'No autorizado.' ); }
		check_admin_referer( 'pm_promesa_restore' );

		$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		if ( $id > 0 ) {
			PM_DB::set_status_by_id( $id, 'published' );
		}
		$st = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
		self::redirect_list( 'restored', $st ? $st : 'trash' );
	}

	public static function handle_delete() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'No autorizado.' ); }
		check_admin_referer( 'pm_promesa_delete' );

		$id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;
		if ( $id > 0 ) {
			PM_DB::delete_by_id( $id );
		}
		$st = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
		self::redirect_list( 'deleted', $st ? $st : 'trash' );
	}

	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'No autorizado.' ); }
		check_admin_referer( 'pm_promesa_save' );

		$id = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$hash = isset( $_POST['hash'] ) ? sanitize_text_field( wp_unslash( $_POST['hash'] ) ) : '';

		$data = array(
			'first_name'    => isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '',
			'last_name'     => isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '',
			'alias'         => isset( $_POST['alias'] ) ? sanitize_text_field( wp_unslash( $_POST['alias'] ) ) : '',
			'instagram'     => isset( $_POST['instagram'] ) ? sanitize_text_field( wp_unslash( $_POST['instagram'] ) ) : '',
			'condicion'     => isset( $_POST['condicion'] ) ? sanitize_text_field( wp_unslash( $_POST['condicion'] ) ) : '',
			'promesa_texto' => isset( $_POST['promesa_texto'] ) ? wp_strip_all_tags( (string) wp_unslash( $_POST['promesa_texto'] ) ) : '',
		);

		$allowed = array( 'Argentina Campeón', 'Argentina Finalista', 'Argentina Semifinalista' );
		if ( ! in_array( $data['condicion'], $allowed, true ) ) {
			$data['condicion'] = 'Argentina Campeón';
		}

		PM_DB::update_promesa_by_id( $id, $data );

		$status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'published';
		if ( in_array( $status, array( 'published', 'trash' ), true ) ) {
			PM_DB::set_status_by_id( $id, $status );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=pm_promesa_edit&hash=' . rawurlencode( $hash ) . '&pm_msg=updated' ) );
		exit;
	}
}