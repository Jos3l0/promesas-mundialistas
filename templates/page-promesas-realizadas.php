<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<div class="pm-wrap">
	<div class="pm-container">
		<h1 class="pm-title">Promesas realizadas</h1>
		<p class="pm-subtitle">Buscá por nombre o apellido y explorá las promesas registradas.</p>

		<div class="pm-list-head">
			<div class="pm-search">
				<label for="pm-q" class="pm-pill">Buscar</label>
				<input id="pm-q" type="text" placeholder="Nombre o apellido">
				<button id="pm-btn-search" class="pm-btn pm-btn-primary" type="button">Buscar</button>
			</div>
			<div class="pm-pill"><span id="pm-total">0</span> resultados</div>
		</div>

		<div id="pm-grid" class="pm-grid"></div>

		<div class="pm-pagination">
			<button id="pm-prev" class="pm-btn" type="button">Anterior</button>
			<span class="pm-pill">Página <span id="pm-page">1</span></span>
			<button id="pm-next" class="pm-btn" type="button">Siguiente</button>
		</div>

		<div id="pm-empty" class="pm-alert pm-alert-error pm-hidden">No hay resultados.</div>
	</div>
</div>
<?php get_footer(); ?>
