<?php
/**
 * Tema Trovapulizie: bootstrap e helper condivisi.
 *
 * - Helper: tp_theme_version / tp_versioned_url / tp_theme_asset (cache-busting via Version: di style.css),
 *   tp_lines / tp_link_lines (textarea multi-riga dei campi Carbon), tp_icon_span (SVG come maschera CSS).
 * - Enqueue: Poppins + style.css sempre; i moduli JS della Home solo sul template page-home.php, defer.
 * - Importmap Three.js (solo Home): home-motion.js importa phone-3d.js in modo dinamico, mai altrove.
 * - Carbon Fields: boot qui, campi in inc/carbon-fields.php.
 *
 * Mappa completa della Home (file, campi, sezioni, coreografia): docs/homepage.md.
 */
require_once __DIR__ . '/vendor/autoload.php';

/* ---- Helper del tema (asset versionati, liste "una voce per riga", icone a maschera) ---- */
function tp_theme_version() {
	return wp_get_theme()->get( 'Version' );
}
// Aggiunge ?ver= del tema: Hostinger cacha gli asset 7 giorni, la versione del tema li invalida. '' resta ''.
function tp_versioned_url( $url ) {
	return $url ? add_query_arg( 'ver', tp_theme_version(), $url ) : '';
}
function tp_theme_asset( $rel ) {
	return tp_versioned_url( get_stylesheet_directory_uri() . '/' . ltrim( $rel, '/' ) );
}
// Textarea "una voce per riga" -> array di stringhe non vuote
function tp_lines( $text ) {
	return array_values( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $text ) ) ) );
}
// Textarea "Etichetta | URL" per riga -> array di [label, url]
function tp_link_lines( $text ) {
	$out = array();
	foreach ( tp_lines( $text ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		if ( $parts[0] ) {
			$out[] = array( 'label' => $parts[0], 'url' => $parts[1] ?? '#' );
		}
	}
	return $out;
}
// Icona SVG monocromatica resa come maschera in currentColor (classe .tp-icon-mask); '' se il file non esiste
function tp_icon_span( $rel, $class ) {
	if ( ! file_exists( get_stylesheet_directory() . '/' . ltrim( $rel, '/' ) ) ) {
		return '';
	}
	return '<span class="' . esc_attr( $class ) . ' tp-icon-mask" style="--icon:url(\'' . esc_url( tp_theme_asset( $rel ) ) . '\')" aria-hidden="true"></span>';
}

add_action( 'after_setup_theme', function () {
	\Carbon_Fields\Carbon_Fields::boot();
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'trovapulizie-fonts', 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap', array(), null );
	wp_enqueue_style( 'trovapulizie-style', get_stylesheet_uri(), array( 'trovapulizie-fonts' ), tp_theme_version() );
	if ( is_page_template( 'page-home.php' ) ) {
		foreach ( array( 'home-motion', 'reveal', 'faq-accordion', 'services-tabs', 'hero-video' ) as $handle ) {
			wp_enqueue_script( 'trovapulizie-' . $handle, get_stylesheet_directory_uri() . '/assets/js/' . $handle . '.js', array(), tp_theme_version(), array( 'strategy' => 'defer', 'in_footer' => true ) );
		}
	}
} );

// Importmap per Three.js (ADR-002): stessa versione e stessa CDN per core e addons, solo sulla Home.
// Priorita' 1 in wp_head: l'importmap deve precedere qualunque <script type="module">.
add_action( 'wp_head', function () {
	if ( ! is_page_template( 'page-home.php' ) ) {
		return;
	}
	echo '<script type="importmap">{"imports":{"three":"https://cdn.jsdelivr.net/npm/three@0.186.1/build/three.module.js","three/addons/":"https://cdn.jsdelivr.net/npm/three@0.186.1/examples/jsm/"}}</script>' . "\n";
}, 1 );

add_action( 'init', function () {
	remove_action( 'wp_head', 'wp_generator' );
} );
require_once __DIR__ . "/inc/carbon-fields.php";
