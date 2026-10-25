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
	// Senza questo WordPress non stampa il <title>: la Home usciva con la scheda del browser senza nome
	// e nessun titolo nei risultati di ricerca.
	add_theme_support( 'title-tag' );
	\Carbon_Fields\Carbon_Fields::boot();
} );

/* URL dell'app transazionale. Vive qui e non sparso nei template: al cutover cambia in un posto solo.
   Diventera' un campo di Theme Options con TICKET-08. */
function tp_app_url( $percorso = '' ) {
	return 'https://app.trovapulizie.com/' . ltrim( $percorso, '/' );
}

/* Meta description: dal campo SEO della pagina, altrimenti dal motto del sito. Senza, la pagina si
   presenta ai motori con un estratto scelto da loro. */
add_action( 'wp_head', function () {
	$descrizione = function_exists( 'carbon_get_the_post_meta' ) && is_singular() ? carbon_get_the_post_meta( 'seo_description' ) : '';
	if ( ! $descrizione ) {
		$descrizione = get_bloginfo( 'description' );
	}
	if ( $descrizione ) {
		echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $descrizione ) ) . '">' . "\n";
	}
}, 2 );

/* Dati strutturati LocalBusiness sulla pagina hub di citta' (TICKET-32). Un solo blocco per pagina,
   generato da qui e non da un plugin SEO: il tema non ne ha.

   [ATTENZIONE] Niente aggregateRating finche' non ci sono recensioni vere: un placeholder di quel
   tipo e' gia' finito pubblicato su 21 pagine del portale. Niente telefono segnaposto. */
add_action( 'wp_head', function () {
	if ( ! is_page_template( 'page-hub-roma.php' ) ) {
		return;
	}
	$dati = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'LocalBusiness',
		'name'        => get_bloginfo( 'name' ),
		'url'         => get_permalink(),
		'description' => wp_strip_all_tags( (string) carbon_get_the_post_meta( 'seo_description' ) ) ?: get_bloginfo( 'description' ),
		'areaServed'  => array(
			'@type' => 'City',
			'name'  => 'Roma',
		),
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $dati, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}, 5 );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'trovapulizie-fonts', 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap', array(), null );
	wp_enqueue_style( 'trovapulizie-style', get_stylesheet_uri(), array( 'trovapulizie-fonts' ), tp_theme_version() );
	// nav-mobile serve su ogni pagina (la navbar e' in header.php).
	wp_enqueue_script( 'trovapulizie-nav-mobile', get_stylesheet_directory_uri() . '/assets/js/nav-mobile.js', array(), tp_theme_version(), array( 'strategy' => 'defer', 'in_footer' => true ) );

	// Moduli dei template-part condivisi: ogni pagina che riusa quelle sezioni ne ha bisogno,
	// altrimenti le sezioni escono mute (accordion che non apre, tab inerti, mappa ferma).
	// Aggiungendo una landing che riusa i template-part, aggiungerla a $pagine_con_sezioni.
	$moduli_condivisi = array( 'reveal', 'faq-accordion', 'services-tabs', 'zones-map', 'reviews-marquee' );
	// Solo sull'hub: la barra fissa non esiste sulla Home.
	$moduli_hub       = array( 'sticky-cta', 'price-estimator' );
	// Landing Sgrosso (ADR-004): barra fissa doppia; lead-form.js arriva con TICKET-40.
	$moduli_sgrosso   = array( 'sticky-cta' );
	// Coreografia della sola Home (ADR-002): telefono pinnato, sequenza schermate, video hero.
	$moduli_home      = array( 'home-motion', 'hiw-sequence', 'hero-video' );
	$pagine_con_sezioni = array( 'page-home.php', 'page-hub-roma.php', 'page-sgrosso.php' );

	$handles = array();
	foreach ( $pagine_con_sezioni as $template ) {
		if ( is_page_template( $template ) ) {
			$handles = $moduli_condivisi;
			break;
		}
	}
	if ( is_page_template( 'page-home.php' ) ) {
		$handles = array_merge( $handles, $moduli_home );
	}
	if ( is_page_template( 'page-hub-roma.php' ) ) {
		$handles = array_merge( $handles, $moduli_hub );
	}
	if ( is_page_template( 'page-sgrosso.php' ) ) {
		$handles = array_merge( $handles, $moduli_sgrosso );
	}
	foreach ( $handles as $handle ) {
		wp_enqueue_script( 'trovapulizie-' . $handle, get_stylesheet_directory_uri() . '/assets/js/' . $handle . '.js', array(), tp_theme_version(), array( 'strategy' => 'defer', 'in_footer' => true ) );
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
	// Script delle emoji di WordPress: 22 KB per sostituire emoji che il tema non usa (TICKET-20).
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
} );
require_once __DIR__ . "/inc/carbon-fields.php";
