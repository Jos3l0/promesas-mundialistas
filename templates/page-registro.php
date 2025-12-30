<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<div class="pm-wrap">
	<div class="pm-container">

		<h1 class="pm-title">Registro de promesa</h1>
		<p class="pm-subtitle">
			Completá el formulario para registrar tu promesa mundialista.
		</p>

		<form id="pm-form" novalidate>

			<div class="pm-field">
				<label for="first_name">Nombre</label>
				<input type="text" name="first_name" id="first_name" autocomplete="given-name">
				<div class="pm-error"></div>
			</div>

			<div class="pm-field">
				<label for="last_name">Apellido</label>
				<input type="text" name="last_name" id="last_name" autocomplete="family-name">
				<div class="pm-error"></div>
			</div>

			<div class="pm-field">
				<label for="alias">Usuario de Instagram</label>
				<input type="text" name="alias" id="alias" placeholder="@usuario">
				<div class="pm-error"></div>
			</div>

			<div class="pm-field">
				<label for="instagram">Link de Instagram (opcional)</label>
				<input type="url" name="instagram" id="instagram" placeholder="https://instagram.com/usuario">
				<div class="pm-error"></div>
			</div>

			<div class="pm-field">
				<label for="condicion">Condición</label>
				<select name="condicion" id="condicion">
					<option value="">Seleccioná una condición</option>
					<option value="Argentina campeona">Argentina campeona</option>
					<option value="Argentina finalista">Argentina finalista</option>
					<option value="Argentina semifinalista">Argentina semifinalista</option>
				</select>
				<div class="pm-error"></div>
			</div>

			<div class="pm-field">
				<label for="promesa_texto">Tu promesa</label>
				<textarea name="promesa_texto" id="promesa_texto" maxlength="500"></textarea>
				<div class="pm-meta">
					<span id="pm-char-counter">0/500</span>
				</div>
				<div class="pm-error"></div>
			</div>

			<!-- 🔴 ALERTA GENERAL: JUSTO ARRIBA DEL BOTÓN -->
			<div id="pm-general-error" class="pm-alert pm-alert-error pm-hidden"></div>

			<div class="pm-actions">
				<button type="submit" class="pm-btn">Enviar comentario</button>
			</div>

			<div id="pm-success" class="pm-alert pm-alert-success pm-hidden">
				Promesa registrada correctamente.<br>
				<a id="pm-success-link" href="#" target="_blank" rel="noopener">Ver promesa</a>
				<button type="button" id="pm-copy-link" class="pm-btn pm-btn-secondary">Copiar enlace</button>
			</div>

		</form>

	</div>
</div>
<?php get_footer(); ?>