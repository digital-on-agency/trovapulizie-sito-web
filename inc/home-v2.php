<?php
/**
 * Home v2 app-first (SPEC-homepage-v2, ADR-005): funzioni condivise, noindex e sitemap dell'anteprima, caricamento
 * degli script per template e precaricamento dell'immagine LCP. Contenuti e markup stanno in page-home-v2.php e nei
 * template-part dei ticket successivi; i campi Carbon in inc/carbon-fields.php ("Home v2 - Sezioni", prefisso h2_).
 *
 * Il template vive su una pagina pubblicata con slug /anteprima-pagina-iniziale/ (non /home-*: inc/redirects.php
 * rimanda all'app tutto cio' che comincia con /home). Finche' non e' la pagina iniziale e' noindex, fuori dalla
 * sitemap e non linkata; al rilascio (page_on_front sulla nuova pagina) il noindex e l'esclusione si spengono da
 * soli, col rollback tornano da soli: nessun campo da ricordare.
 */

/**
 * Interruttore del sito v2 (Theme Options > Sito v2). Spento di default. E' l'unica lettura del valore: header chiaro e
 * footer nuovo (ticket successivi) si decidono qui. Con l'interruttore spento le pagine diverse dall'anteprima
 * hanno il markup di prima.
 *
 * @return bool
 */
function tp_site_v2_on() {
	static $on = null;
	if ( null === $on ) {
		$on = function_exists( 'carbon_get_theme_option' ) && (bool) carbon_get_theme_option( 'tp_site_v2' );
	}
	return $on;
}

/**
 * True se la richiesta e' la pagina d'anteprima: template page-home-v2.php e pagina NON iniziale. Unica funzione che
 * decide "anteprima": la usano robots, header X-Robots-Tag e (per ID) la sitemap. Quando la pagina diventa iniziale
 * (is_front_page) torna false e la pagina si indicizza.
 *
 * @return bool
 */
function tp_is_home_v2_preview() {
	return is_singular( 'page' ) && is_page_template( 'page-home-v2.php' ) && ! is_front_page();
}

/**
 * ID delle pagine col template page-home-v2.php escluse la pagina iniziale. Servono alla sitemap: si esclude per ID e
 * non per template, perche' per template la radice uscirebbe dalla sitemap dopo il rilascio.
 *
 * @return int[]
 */
function tp_home_v2_preview_ids() {
	$ids = get_posts( array(
		'post_type'              => 'page',
		'post_status'            => 'any',
		'posts_per_page'         => -1,
		'fields'                 => 'ids',
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
		'meta_key'               => '_wp_page_template',
		'meta_value'             => 'page-home-v2.php',
	) );
	return array_values( array_diff( array_map( 'intval', $ids ), array( (int) get_option( 'page_on_front' ) ) ) );
}

/* ---- noindex ----------------------------------------------------------------------------------------------- */

add_filter( 'wp_robots', function ( $robots ) {
	if ( tp_is_home_v2_preview() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset( $robots['max-image-preview'] );
	}
	return $robots;
}, 20 );

add_action( 'template_redirect', function () {
	if ( tp_is_home_v2_preview() ) {
		header( 'X-Robots-Tag: noindex, follow' );
	}
} );

/* Sitemap: priorita' 20, dopo il filtro della thank-you (functions.php, priorita' 10, che sostituisce meta_query).
   Non tocca meta_query: aggiunge solo post__not_in, unendolo a quello che c'e' gia'. */
add_filter( 'wp_sitemaps_posts_query_args', function ( $args, $post_type ) {
	if ( 'page' === $post_type ) {
		$escluse = tp_home_v2_preview_ids();
		if ( $escluse ) {
			$args['post__not_in'] = array_values( array_unique( array_merge( (array) ( $args['post__not_in'] ?? array() ), $escluse ) ) );
		}
	}
	return $args;
}, 20, 2 );

/* ---- Script per template ----------------------------------------------------------------------------------- */

/* Solo gli ingressi (reveal) e la fisarmonica delle FAQ. Carosello, movimento e controllo CAP li aggiungono i ticket
   delle rispettive sezioni. Niente Three.js (nessun importmap) e niente GSAP a questo livello. */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_page_template( 'page-home-v2.php' ) ) {
		return;
	}
	foreach ( array( 'reveal', 'faq-accordion' ) as $handle ) {
		wp_enqueue_script( 'trovapulizie-' . $handle, get_stylesheet_directory_uri() . '/assets/js/' . $handle . '.js', array(), tp_theme_version(), array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
} );

/* ---- Precaricamento LCP ------------------------------------------------------------------------------------ */

/**
 * Stampa un preload per ogni file assets/img/home-v2/hero-lcp-*.webp. Se non esiste nessun file non stampa nulla (la
 * hero arriva col ticket T-B). Il suffisso decide la soglia: "-mobile" solo sotto 1024 px, "-desktop" da 1024 px,
 * qualunque altro suffisso senza media query. Stessa soglia del token --tp-bp-desktop.
 */
add_action( 'wp_head', function () {
	if ( ! is_page_template( 'page-home-v2.php' ) ) {
		return;
	}
	$dir = get_stylesheet_directory() . '/assets/img/home-v2/';
	foreach ( (array) glob( $dir . 'hero-lcp-*.webp' ) as $file ) {
		$nome  = basename( $file, '.webp' );
		$media = '';
		if ( substr( $nome, -7 ) === '-mobile' ) {
			$media = ' media="(max-width: 1023px)"';
		} elseif ( substr( $nome, -8 ) === '-desktop' ) {
			$media = ' media="(min-width: 1024px)"';
		}
		echo '<link rel="preload" as="image" type="image/webp" fetchpriority="high" href="' . esc_url( tp_theme_asset( 'assets/img/home-v2/' . basename( $file ) ) ) . '"' . $media . '>' . "\n";
	}
}, 2 );
