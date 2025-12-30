<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PM_Image_Generator {

	public static function generate( array $item ) {
		// Requiere GD + FreeType.
		if ( ! function_exists( 'imagecreatefrompng' ) || ! function_exists( 'imagettftext' ) ) {
			return '';
		}

		$hash      = isset( $item['hash'] ) ? (string) $item['hash'] : '';
		$nombre    = isset( $item['first_name'] ) ? (string) $item['first_name'] : '';
		$apellido  = isset( $item['last_name'] ) ? (string) $item['last_name'] : '';
		$alias     = isset( $item['alias'] ) ? (string) $item['alias'] : '';
		$instagram = isset( $item['instagram'] ) ? (string) $item['instagram'] : '';
		$condicion = isset( $item['condicion'] ) ? (string) $item['condicion'] : '';
		$promesa   = isset( $item['promesa_texto'] ) ? (string) $item['promesa_texto'] : '';

		$hash = trim( $hash );
		if ( '' === $hash ) { return ''; }

		// === CONFIGURACIÓN EDITABLE (posiciones, fuentes y colores) ===
		$cfg = array(
			// Dimensiones Instagram Feed (Vertical 4:5)
			'width'  => 1080,
			'height' => 1350,

			// Fuentes (incluidas en el plugin)
			'font_regular' => PM_PLUGIN_DIR . 'assets/fonts/DejaVuSans.ttf',
			'font_bold'    => PM_PLUGIN_DIR . 'assets/fonts/DejaVuSans-Bold.ttf',

			// Color (RGB)
			'color' => array( 0, 0, 0 ),

			// Layout (editar manualmente)
			'x' => 90,
			'y_name'      => 180,
			'y_instagram' => 255,
			'promesa_box' => array(
				'x'        => 90,
				'y'        => 360,
				'width'    => 900,
				'height'   => 900,
				'line_gap' => 10,
			),
			'font_sizes' => array(
				'name'        => 52,
				'instagram'   => 34,
				'promesa_max' => 46,
				'promesa_min' => 18,
			),
		);

		// === Selección de fondo por día (fondos dentro del plugin) ===
		$fondo = self::background_for_today();
		if ( '' === $fondo || ! file_exists( $fondo ) ) {
			return '';
		}

		$img = @imagecreatefrompng( $fondo );
		if ( ! $img ) { return ''; }

		// Asegurar alpha.
		imagealphablending( $img, true );
		imagesavealpha( $img, true );

		// Ajuste a dimensiones objetivo (cover + crop) para evitar deformaciones.
		$img = self::ensure_size_cover( $img, (int) $cfg['width'], (int) $cfg['height'] );

		$color = imagecolorallocate( $img, (int) $cfg['color'][0], (int) $cfg['color'][1], (int) $cfg['color'][2] );

		$font_regular = $cfg['font_regular'];
		$font_bold    = $cfg['font_bold'];

		if ( ! file_exists( $font_regular ) || ! file_exists( $font_bold ) ) {
			imagedestroy( $img );
			return '';
		}

		// Textos
		$nombre_completo  = trim( $nombre . ' ' . $apellido );
		$instagram_clean  = ltrim( trim( $instagram ), '@' );
		$promesa          = trim( $promesa );

		// Nombre
		if ( '' !== $nombre_completo ) {
			imagettftext( $img, (int) $cfg['font_sizes']['name'], 0, (int) $cfg['x'], (int) $cfg['y_name'], $color, $font_bold, $nombre_completo );
		}

		// Instagram
		if ( '' !== $instagram_clean ) {
			imagettftext( $img, (int) $cfg['font_sizes']['instagram'], 0, (int) $cfg['x'], (int) $cfg['y_instagram'], $color, $font_regular, '@' . $instagram_clean );
		}

		// Promesa: tamaño dinámico + wrap, debe entrar en promesa_box
		$box = $cfg['promesa_box'];
		if ( '' !== $promesa ) {
			$fit = self::fit_text_to_box(
				$promesa,
				$font_regular,
				(int) $cfg['font_sizes']['promesa_max'],
				(int) $cfg['font_sizes']['promesa_min'],
				(int) $box['width'],
				(int) $box['height'],
				(int) $box['line_gap']
			);

			$lines = $fit['lines'];
			$size  = $fit['size'];

			$y  = (int) $box['y'];
			$lh = $size + (int) $box['line_gap'];

			foreach ( $lines as $line ) {
				imagettftext( $img, $size, 0, (int) $box['x'], $y, $color, $font_regular, $line );
				$y += $lh;
			}
		}

		// Guardado en uploads/promesas/YYYY/MM/promesa-<hash>-<apellido>.png
		$upload = wp_upload_dir();
		$subdir = 'promesas/' . gmdate( 'Y' ) . '/' . gmdate( 'm' );
		$dir = trailingslashit( $upload['basedir'] ) . $subdir;

		if ( ! wp_mkdir_p( $dir ) ) {
			imagedestroy( $img );
			return '';
		}

		$apellido_slug = sanitize_title( $apellido );
		if ( '' === $apellido_slug ) { $apellido_slug = 'sin-apellido'; }

		$filename = 'promesa-' . $hash . '-' . $apellido_slug . '.png';
		$path = trailingslashit( $dir ) . $filename;

		@imagepng( $img, $path );
		imagedestroy( $img );

		if ( ! file_exists( $path ) ) {
			return '';
		}

		return trailingslashit( $upload['baseurl'] ) . $subdir . '/' . $filename;
	}

	private static function background_for_today() {
		$ts = (int) current_time( 'timestamp' );
		$en = gmdate( 'l', $ts ); // Monday..Sunday
		$map = array(
			'Monday'    => 'lunes',
			'Tuesday'   => 'martes',
			'Wednesday' => 'miercoles',
			'Thursday'  => 'jueves',
			'Friday'    => 'viernes',
			'Saturday'  => 'sabado',
			'Sunday'    => 'domingo',
		);

		$day = isset( $map[ $en ] ) ? $map[ $en ] : 'lunes';
		return PM_PLUGIN_DIR . 'imagenes/fondo-' . $day . '.png';
	}

	/**
	 * Resize tipo "cover" + crop centrado (evita deformación del fondo).
	 */
	private static function ensure_size_cover( $img, $w, $h ) {
		$src_w = imagesx( $img );
		$src_h = imagesy( $img );

		if ( $src_w === $w && $src_h === $h ) {
			return $img;
		}

		$scale = max( $w / $src_w, $h / $src_h );
		$new_w = (int) ceil( $src_w * $scale );
		$new_h = (int) ceil( $src_h * $scale );

		$tmp = imagecreatetruecolor( $new_w, $new_h );
		imagealphablending( $tmp, false );
		imagesavealpha( $tmp, true );
		$transparent = imagecolorallocatealpha( $tmp, 0, 0, 0, 127 );
		imagefilledrectangle( $tmp, 0, 0, $new_w, $new_h, $transparent );
		imagecopyresampled( $tmp, $img, 0, 0, 0, 0, $new_w, $new_h, $src_w, $src_h );
		imagedestroy( $img );

		$dst = imagecreatetruecolor( $w, $h );
		imagealphablending( $dst, false );
		imagesavealpha( $dst, true );
		$transparent = imagecolorallocatealpha( $dst, 0, 0, 0, 127 );
		imagefilledrectangle( $dst, 0, 0, $w, $h, $transparent );

		$src_x = (int) floor( ( $new_w - $w ) / 2 );
		$src_y = (int) floor( ( $new_h - $h ) / 2 );
		imagecopy( $dst, $tmp, 0, 0, $src_x, $src_y, $w, $h );
		imagedestroy( $tmp );

		return $dst;
	}

	// Backward helper (por si en el futuro se reutiliza).
	private static function ensure_size( $img, $w, $h ) {
		return self::ensure_size_cover( $img, $w, $h );
	}

	private static function fit_text_to_box( $text, $font, $max_size, $min_size, $max_width, $max_height, $line_gap ) {
		$text = preg_replace( "/\r\n|\r/", "\n", (string) $text );
		$paragraphs = explode( "\n", $text );

		for ( $size = $max_size; $size >= $min_size; $size-- ) {
			$lines = array();

			foreach ( $paragraphs as $p ) {
				$p = trim( $p );
				if ( '' === $p ) {
					$lines[] = '';
					continue;
				}
				$wrapped = self::wrap_line( $p, $font, $size, $max_width );
				$lines = array_merge( $lines, $wrapped );
			}

			$line_height = $size + $line_gap;
			$total_h = count( $lines ) * $line_height;

			if ( $total_h <= $max_height ) {
				return array( 'size' => $size, 'lines' => $lines );
			}
		}

		// Fallback: min size, y recortar líneas si aún excede (para que no rompa imagen).
		$lines = array();
		foreach ( $paragraphs as $p ) {
			$p = trim( $p );
			$wrapped = self::wrap_line( $p, $font, $min_size, $max_width );
			$lines = array_merge( $lines, $wrapped );
		}
		$line_height = $min_size + $line_gap;
		$max_lines = (int) floor( $max_height / $line_height );
		if ( $max_lines > 0 && count( $lines ) > $max_lines ) {
			$lines = array_slice( $lines, 0, $max_lines );
		}
		return array( 'size' => $min_size, 'lines' => $lines );
	}

	private static function wrap_line( $text, $font, $size, $max_width ) {
		$words = preg_split( '/\s+/', trim( $text ) );
		$lines = array();
		$line = '';

		foreach ( $words as $word ) {
			$test = ( '' === $line ) ? $word : ( $line . ' ' . $word );
			$box = imagettfbbox( $size, 0, $font, $test );
			$w = abs( $box[2] - $box[0] );
			if ( $w > $max_width && '' !== $line ) {
				$lines[] = $line;
				$line = $word;
			} else {
				$line = $test;
			}
		}

		if ( '' !== $line ) {
			$lines[] = $line;
		}

		return $lines;
	}
}