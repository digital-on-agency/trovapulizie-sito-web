<?php
/**
 * Zone della Home v2 (SPEC-homepage-v2 US-04, TICKET-125): lista dei CAP attivi, mappa per CAP e script del controllo.
 *
 * Lista dei CAP attivi: UNA sola funzione, tp_home_v2_zones_caps(), decide quali sono. Fonti, in ordine:
 *  1. campo Carbon h2_zones_caps della pagina (textarea, una riga per CAP, "00186 | Centro storico"), se e' stato salvato;
 *  2. se il campo e' stato salvato VUOTO il controllo e' spento (resta il resto della sezione): lista vuota;
 *  3. se il campo non e' mai stato salvato vale la lista del tema, assets/data/caps-attivi.json (44 CAP decisi da Guido
 *     il 21/11, nomi da portale-transazionale/reference/microaree.md).
 * "Mai salvato" e "salvato vuoto" sono due stati diversi in Carbon: si distinguono con metadata_exists() sulla chiave
 * _h2_zones_caps. Un CAP fuori dall'intervallo di Roma (00118-00199) o scritto male viene scartato.
 *
 * Forma di caps-attivi.json: [ { "cap": "00186", "nome": "Centro Storico (Navona, Pantheon)" }, ... ].
 * Il JS (assets/js/zones-check.js) non conosce nessun CAP: legge la lista dal markup (data-caps).
 */

/**
 * Lista del tema: [ cap => nome ] da assets/data/caps-attivi.json. Vuota se il file manca o e' rotto.
 *
 * @return array<string,string>
 */
function tp_home_v2_zones_theme_caps() {
	static $lista = null;
	if ( null !== $lista ) {
		return $lista;
	}
	$lista = array();
	$file  = get_stylesheet_directory() . '/assets/data/caps-attivi.json';
	$dati  = file_exists( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null;
	foreach ( (array) $dati as $riga ) {
		$cap = is_array( $riga ) ? (string) ( $riga['cap'] ?? '' ) : '';
		if ( tp_home_v2_zones_cap_valid( $cap ) ) {
			$lista[ $cap ] = trim( (string) ( $riga['nome'] ?? '' ) );
		}
	}
	return $lista;
}

/**
 * True se la stringa e' un CAP di Roma: cinque cifre fra 00118 e 00199.
 *
 * @param string $cap CAP da controllare.
 * @return bool
 */
function tp_home_v2_zones_cap_valid( $cap ) {
	return (bool) preg_match( '/^00(11[89]|1[2-9]\d)$/', (string) $cap );
}

/**
 * Testo di default del campo h2_zones_caps: la lista del tema, una riga per CAP.
 *
 * @return string
 */
function tp_home_v2_zones_default_lines() {
	$righe = array();
	foreach ( tp_home_v2_zones_theme_caps() as $cap => $nome ) {
		$righe[] = $cap . ' | ' . $nome;
	}
	return implode( "\n", $righe );
}

/**
 * CAP attivi mostrati dalla sezione zone: [ cap => nome ] (vedi docblock del file per le tre fonti).
 *
 * @param int $post_id Pagina (0 = pagina corrente).
 * @return array<string,string> Vuota = controllo spento.
 */
function tp_home_v2_zones_caps( $post_id = 0 ) {
	$post_id = $post_id ? (int) $post_id : (int) get_queried_object_id();
	$tema    = tp_home_v2_zones_theme_caps();
	if ( ! $post_id || ! metadata_exists( 'post', $post_id, '_h2_zones_caps' ) ) {
		return $tema;
	}
	$out = array();
	foreach ( preg_split( '/\R/', (string) get_post_meta( $post_id, '_h2_zones_caps', true ) ) as $riga ) {
		$parti = array_map( 'trim', explode( '|', $riga, 2 ) );
		if ( tp_home_v2_zones_cap_valid( $parti[0] ) ) {
			$out[ $parti[0] ] = '' !== ( $parti[1] ?? '' ) ? $parti[1] : ( $tema[ $parti[0] ] ?? $parti[0] );
		}
	}
	return $out;
}

/**
 * Nome breve per i chip: senza parentesi e senza le voci dopo la prima barra ("Centro Storico (Navona, Pantheon)" ->
 * "Centro Storico", "Testaccio / Ostiense" -> "Testaccio").
 *
 * @param string $nome Nome intero del quartiere.
 * @return string
 */
function tp_home_v2_zones_short_name( $nome ) {
	$nome = trim( preg_replace( '/\s*\(.*$/u', '', (string) $nome ) );
	$nome = trim( explode( ' / ', $nome )[0] );
	return $nome;
}

/**
 * Mappa dei CAP per la sezione zone: i <path data-cap> di assets/img/zones-map-cap.svg che toccano il riquadro
 * visibile (viewBox 142 -16 440 440, il disco e' il cerchio inscritto), con data-state="active" sui CAP attivi.
 * Gli altri 60 CAP (periferia lontana, con coordinate molto fuori scala) non vengono stampati.
 *
 * @param string[] $attivi CAP attivi.
 * @return string Markup dei path, stringa vuota se l'SVG non c'e'.
 */
function tp_home_v2_zones_paths( array $attivi ) {
	$file = get_stylesheet_directory() . '/assets/img/zones-map-cap.svg';
	if ( ! file_exists( $file ) ) {
		return '';
	}
	$svg = (string) file_get_contents( $file );
	$out = '';
	if ( ! preg_match_all( '/<path data-cap="(\d{5})"[^>]*? d="([^"]+)"\/>/', $svg, $m, PREG_SET_ORDER ) ) {
		return '';
	}
	foreach ( $m as $p ) {
		preg_match_all( '/(-?\d+(?:\.\d+)?) (-?\d+(?:\.\d+)?)/', $p[2], $c, PREG_SET_ORDER );
		$visibile = false;
		foreach ( $c as $pt ) {
			if ( $pt[1] > 132 && $pt[1] < 592 && $pt[2] > -26 && $pt[2] < 434 ) {
				$visibile = true;
				break;
			}
		}
		if ( $visibile ) {
			$stato = in_array( $p[1], $attivi, true ) ? 'active' : 'none';
			$out  .= '<path data-cap="' . $p[1] . '" data-state="' . $stato . '" d="' . $p[2] . '"/>';
		}
	}
	return $out;
}

/* Il controllo del CAP: caricato solo sul template v2, dopo reveal.js. */
add_action( 'wp_enqueue_scripts', function () {
	if ( is_page_template( 'page-home-v2.php' ) ) {
		wp_enqueue_script( 'trovapulizie-zones-check', get_stylesheet_directory_uri() . '/assets/js/zones-check.js', array(), tp_theme_version(), array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
} );
