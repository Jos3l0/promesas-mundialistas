<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

$hash = get_query_var( 'pm_promesa_hash' );
$item = PM_DB::get_by_hash( $hash );
?>
<div class="pm-wrap">
	<div class="pm-container">
		<?php if ( ! $item ) : ?>
			<div class="pm-alert pm-alert-error">Promesa no encontrada.</div>
		<?php else : ?>

			<h1 class="pm-title">Promesa mundialista</h1>

			<p class="pm-subtitle">
				<strong><?php echo esc_html( $item['first_name'] . ' ' . $item['last_name'] ); ?></strong>
				<?php if ( ! empty( $item['alias'] ) ) : ?>
					<span class="pm-pill"><?php echo esc_html( $item['alias'] ); ?></span>
				<?php endif; ?>
			</p>

			<div class="pm-card pm-single-card">

				<div class="pm-condition">
					Condición: <strong><?php echo esc_html( $item['condicion'] ); ?></strong>
				</div>

				<?php if ( ! empty( $item['image_url'] ) ) : ?>
					<div class="pm-ig-preview">
						<img
							class="pm-ig-image"
							src="<?php echo esc_url( $item['image_url'] ); ?>"
							alt="Promesa mundialista"
							loading="lazy"
						>
					</div>

					<div class="pm-actions">
						<a class="pm-btn" href="<?php echo esc_url( $item['image_url'] ); ?>" download>
							Descargar imagen
						</a>
					</div>
				<?php endif; ?>

			</div>

		<?php endif; ?>
	</div>
</div>
<?php get_footer(); ?>