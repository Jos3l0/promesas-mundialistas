<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class PM_Censura {

	public static function bad_words() {
		$words = array(
			// ES/AR (ajustable). Lista inicial.
			'puta',
			'puto',
			'mierda',
			'carajo',
			'concha',
			'pelotudo',
			'pelotuda',
			'boludo',
			'boluda',
			'forro',
			'tarado',
			'tarada',
			'imbecil',
			'idiota',
			'gil',
			'cagon',
			'cagona',
			'cagar',
			'chingar',
			'joder',
			'poronga',
			'pija',
			'pingo',
			'orto',
			'maricon',
			'marica',
			'putazo',
			'hdp',
			'hijodeputa',
		);

		$words = array_values( array_unique( array_filter( array_map( 'strval', $words ) ) ) );
		$words = apply_filters( 'pm_bad_words_list', $words );
		$words = array_values( array_unique( array_filter( array_map( 'strval', (array) $words ) ) ) );
		return $words;
	}

	private static function normalize_token( $token ) {
		$token = (string) $token;
		$token = strtolower( $token );
		$token = remove_accents( $token );

		$map = array(
			'@' => 'a',
			'4' => 'a',
			'8' => 'b',
			'3' => 'e',
			'1' => 'i',
			'!' => 'i',
			'|' => 'i',
			'0' => 'o',
			'$' => 's',
			'5' => 's',
			'7' => 't',
		);
		$token = strtr( $token, $map );

		// Eliminar todo lo que no sea letra/número.
		$token = preg_replace( '/[^a-z0-9]/', '', $token );
		if ( '' === $token ) {
			return '';
		}

		// Colapsar repeticiones: putaaa => puta
		$token = preg_replace( '/(.)\1{2,}/', '$1', $token );

		return $token;
	}

	public static function contains_bad_words( $text, &$matched = '' ) {
		$matched = '';
		$text    = (string) $text;
		$text    = strtolower( remove_accents( $text ) );

		// Tokenizar por espacios para evitar falsos positivos dentro de otras palabras.
		$raw_tokens = preg_split( '/\s+/', $text );
		if ( ! is_array( $raw_tokens ) ) {
			$raw_tokens = array();
		}

		$bad = self::bad_words();
		$bad_norm = array();
		foreach ( $bad as $w ) {
			$wn = self::normalize_token( $w );
			if ( '' !== $wn ) {
				$bad_norm[ $wn ] = true;
			}
		}

		foreach ( $raw_tokens as $tok ) {
			$tok_n = self::normalize_token( $tok );
			if ( '' === $tok_n ) {
				continue;
			}
			if ( isset( $bad_norm[ $tok_n ] ) ) {
				$matched = $tok_n;
				return true;
			}
		}

		// Caso especial: abreviaturas sin espacios (ej: "hijo-de-puta" / "hijodeputa")
		$compact = self::normalize_token( $text );
		if ( '' !== $compact ) {
			foreach ( array_keys( $bad_norm ) as $w ) {
				// Solo igualdades o compuestos exactos (evita "computadora" contenga "puta").
				if ( $compact === $w ) {
					$matched = $w;
					return true;
				}
			}
		}

		return false;
	}
}