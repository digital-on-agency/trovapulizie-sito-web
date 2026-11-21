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

/**
 * True se la richiesta usa la barra in alto chiara (header.php, variante v2): pagina col template page-home-v2.php
 * (anteprima e, dopo il rilascio, pagina iniziale) oppure interruttore tp_site_v2 acceso. Con interruttore spento e
 * qualunque altra pagina e' false e header.php stampa il markup di sempre. E' l'unico punto che decide la barra:
 * lo usano header.php e il filtro body_class qui sotto.
 *
 * @return bool
 */
function tp_header_v2_on() {
	return tp_site_v2_on() || is_page_template( 'page-home-v2.php' );
}

/* Classe di body per il ramo v2: porta --tp-navbar-h alla misura della barra chiara (style.css, blocco Home v2). */
add_filter( 'body_class', function ( $classes ) {
	if ( tp_header_v2_on() ) {
		$classes[] = 'tp-v2-header';
	}
	return $classes;
} );

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

/* Ingressi (reveal), fisarmonica delle FAQ, pausa dei loop fuori schermo (tp-loops, TICKET-121) e movimento della hero
   (home-v2-motion: pausa del testo circolare, parallasse da desktop con GSAP pigro). Carosello, movimento e controllo CAP li aggiungono i ticket
   delle rispettive sezioni. Niente Three.js (nessun importmap) e niente GSAP a questo livello. */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_page_template( 'page-home-v2.php' ) ) {
		return;
	}
	foreach ( array( 'reveal', 'faq-accordion', 'tp-loops', 'home-v2-motion' ) as $handle ) {
		wp_enqueue_script( 'trovapulizie-' . $handle, get_stylesheet_directory_uri() . '/assets/js/' . $handle . '.js', array(), tp_theme_version(), array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
} );

/* Barra chiara: trasparente in cima alla pagina (assets/js/navbar-v2.js). Caricato ovunque la barra v2 e' attiva. */
add_action( 'wp_enqueue_scripts', function () {
	if ( tp_header_v2_on() ) {
		wp_enqueue_script( 'trovapulizie-navbar-v2', get_stylesheet_directory_uri() . '/assets/js/navbar-v2.js', array(), tp_theme_version(), array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
} );

/* ---- Servizi (TICKET-121) ---------------------------------------------------------------------------------- */

/**
 * Destinazione di un'icona della sezione Servizi: l'URL da usare come link, oppure '' se l'icona non deve essere un
 * link. E' l'unica funzione che decide "link o span" (template-parts/home-services-v2.php). Restituisce '' se:
 * il servizio e' segnato "presto"; l'URL e' vuoto; l'URL e' interno (percorso che comincia con "/" o stesso host) e la
 * pagina di destinazione non esiste o non e' pubblicata (nessun link verso una 404). Un URL esterno vale cosi' com'e'.
 * Risultato in cache per richiesta.
 *
 * @param string $url  URL o percorso interno (es. "/roma/pulizie-uffici/").
 * @param bool   $soon True se il servizio e' "presto".
 * @return string URL assoluto o ''.
 */
function tp_home_v2_service_href( $url, $soon = false ) {
	static $cache = array();
	$url = trim( (string) $url );
	if ( $soon || '' === $url ) {
		return '';
	}
	if ( isset( $cache[ $url ] ) ) {
		return $cache[ $url ];
	}
	$host    = wp_parse_url( home_url(), PHP_URL_HOST );
	$u_host  = wp_parse_url( $url, PHP_URL_HOST );
	$interno = ( 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ) || ( $u_host && $u_host === $host );
	if ( ! $interno && ! $u_host ) {
		return $cache[ $url ] = '';
	}
	$abs = 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' ) ? home_url( $url ) : $url;
	if ( $interno ) {
		$id = url_to_postid( $abs );
		if ( ! $id || 'publish' !== get_post_status( $id ) ) {
			return $cache[ $url ] = '';
		}
	}
	return $cache[ $url ] = $abs;
}

/**
 * Riga "Altri servizi a Roma": le sei pagine nell'ordine del Figma (sgrosso casa, post-ristrutturazione, trasloco,
 * fine affitto, pulizie commerciali, guida ai prezzi), solo se pubblicate, percorso => anchor. Percorsi e anchor
 * vengono da tp_service_pages() (unica fonte SEO): qui si sceglie soltanto quali voci mostrare e in che ordine, per
 * chiave dell'array; nessun anchor o URL e' scritto nel template. Una voce che sparisce da tp_service_pages() sparisce
 * anche da qui.
 *
 * @return array<string,string>
 */
function tp_home_v2_altri_servizi() {
	$scelte = array(
		'/roma/sgrosso-casa/',
		'/roma/sgrosso-post-ristrutturazione/',
		'/roma/sgrosso-trasloco/',
		'/roma/sgrosso-fine-affitto/',
		'/roma/sgrosso-commerciale/',
		'/roma/prezzi-pulizie/',
	);
	$pubblicate = function_exists( 'tp_service_pages_published' ) ? tp_service_pages_published() : array();
	$out        = array();
	foreach ( $scelte as $percorso ) {
		if ( isset( $pubblicate[ $percorso ] ) ) {
			$out[ $percorso ] = $pubblicate[ $percorso ];
		}
	}
	return $out;
}

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
