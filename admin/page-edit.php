<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$allowed = array( 'Argentina Campeón', 'Argentina Finalista', 'Argentina Semifinalista' );
?>
<div class="wrap">
	<h1 class="wp-heading-inline">Editar promesa</h1>
	<hr class="wp-header-end">

	<?php if ( isset( $_GET['pm_msg'] ) && 'updated' === sanitize_text_field( wp_unslash( $_GET['pm_msg'] ) ) ) : ?>
		<div class="notice notice-success is-dismissible"><p>Promesa actualizada.</p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'pm_promesa_save' ); ?>
		<input type="hidden" name="action" value="pm_promesa_save">
		<input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
		<input type="hidden" name="hash" value="<?php echo esc_attr( $row['hash'] ); ?>">

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label>Nombre</label></th>
				<td><input name="first_name" type="text" class="regular-text" value="<?php echo esc_attr( $row['first_name'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label>Apellido</label></th>
				<td><input name="last_name" type="text" class="regular-text" value="<?php echo esc_attr( $row['last_name'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label>Alias</label></th>
				<td><input name="alias" type="text" class="regular-text" value="<?php echo esc_attr( $row['alias'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label>Instagram</label></th>
				<td><input name="instagram" type="text" class="regular-text" value="<?php echo esc_attr( $row['instagram'] ); ?>"></td>
			</tr>
			<tr>
				<th scope="row"><label>Condición</label></th>
				<td>
					<select name="condicion">
						<?php foreach ( $allowed as $opt ) : ?>
							<option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $row['condicion'], $opt ); ?>><?php echo esc_html( $opt ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label>Promesa</label></th>
				<td><textarea name="promesa_texto" rows="6" class="large-text"><?php echo esc_textarea( $row['promesa_texto'] ); ?></textarea></td>
			</tr>
			<tr>
				<th scope="row"><label>Estado</label></th>
				<td>
					<select name="status">
						<option value="published" <?php selected( $row['status'], 'published' ); ?>>Publicada</option>
						<option value="trash" <?php selected( $row['status'], 'trash' ); ?>>Papelera</option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label>Imagen</label></th>
				<td>
					<?php if ( ! empty( $row['image_url'] ) ) : ?>
						<img src="<?php echo esc_url( $row['image_url'] ); ?>" style="max-width:240px;height:auto;border:1px solid #ddd;border-radius:8px;display:block;">
						<p><a href="<?php echo esc_url( home_url( '/promesa/' . $row['hash'] . '/' ) ); ?>" target="_blank" rel="noopener">Ver promesa pública</a></p>
					<?php else : ?>
						<p>No hay imagen generada.</p>
					<?php endif; ?>
				</td>
			</tr>
		</table>

		<?php submit_button( 'Guardar cambios' ); ?>
	</form>
</div>
