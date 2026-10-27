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
// Canali pubblici del brand (decisione Guido 27/10, CLAUDE.md cliente): default della pagina Contatti e del suo JSON-LD.
define( 'TP_CT_WHATSAPP', '320 098 0144' );
define( 'TP_CT_EMAIL', 'help@trovapulizie.it' );

/**
 * Numero WhatsApp in qualunque formato ("320 098 0144", "+39 3200980144") -> testo da mostrare, link wa.me (con
 * testo precompilato opzionale) e formato E.164 per il JSON-LD. Prefisso 39 se manca. Null se il numero e' vuoto o
 * troppo corto. Pagina Contatti (TICKET-64): una sola fonte per card, canali e dati strutturati.
 */
function tp_whatsapp_parts( $numero, $testo = '' ) {
	$cifre = preg_replace( '/\D+/', '', (string) $numero );
	if ( strlen( $cifre ) < 9 ) {
		return null;
	}
	$e164    = 0 === strpos( $cifre, '39' ) && strlen( $cifre ) > 10 ? $cifre : '39' . $cifre;
	$locale  = substr( $e164, 2 );
	$display = preg_replace( '/^(\d{3})(\d{3})(\d+)$/', '$1 $2 $3', $locale );
	$link    = 'https://wa.me/' . $e164 . ( '' !== $testo ? '?text=' . rawurlencode( $testo ) : '' );
	return array( 'display' => $display, 'link' => $link, 'e164' => '+' . $e164 );
}
// Textarea "Titolo | testo" per riga -> array di [title, text] (testo opzionale). Pagina Diventa cleaner (TICKET-56).
function tp_pipe_lines( $text ) {
	$out = array();
	foreach ( tp_lines( $text ) as $line ) {
		$parts = array_map( 'trim', explode( '|', $line, 2 ) );
		if ( $parts[0] ) {
			$out[] = array( 'title' => $parts[0], 'text' => $parts[1] ?? '' );
		}
	}
	return $out;
}
// Card di contatto dei fondatori nelle FAQ: foto, nomi e ruolo vengono dalla scheda della Home (campi faq_contact_*),
// unica fonte per tutte le pagine. Prima le landing ripiegavano sui file del tema, che non sono le foto in uso.
function tp_founders_contact() {
	$home = (int) get_option( 'page_on_front' );
	$get  = function ( $k ) use ( $home ) {
		return $home ? (string) carbon_get_post_meta( $home, $k ) : '';
	};
	return array(
		'people'   => $get( 'faq_contact_people' ) ?: 'Guido & Christian',
		'role'     => $get( 'faq_contact_role' ) ?: 'Fondatori',
		'avatar_1' => $get( 'faq_contact_avatar_1' ),
		'avatar_2' => $get( 'faq_contact_avatar_2' ),
	);
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

/* Integrazioni (Theme Options > Integrazioni, inc/carbon-fields.php): base URL dell'API del portale per il form
   lead (ADR-004) e chiave HERE per l'autocomplete dell'indirizzo. Sullo staging l'API e' quella di staging. */
function tp_api_base() {
	$v = function_exists( 'carbon_get_theme_option' ) ? trim( (string) carbon_get_theme_option( 'tp_api_base' ) ) : '';
	return rtrim( $v ?: 'https://api.trovapulizie.com', '/' );
}
function tp_here_key() {
	return function_exists( 'carbon_get_theme_option' ) ? trim( (string) carbon_get_theme_option( 'tp_here_key' ) ) : '';
}

/* URL dell'app transazionale. Vive qui e non sparso nei template: al cutover cambia in un posto solo.
   Diventera' un campo di Theme Options con TICKET-08. */
function tp_app_url( $percorso = '' ) {
	return 'https://app.trovapulizie.com/' . ltrim( $percorso, '/' );
}

/* Tipi di servizio dell'app (GET /api/services/types), letti dal server WordPress e tenuti in cache 6 ore: la
   barra di ricerca della Home li mostra nel menu del servizio. Lato server niente CORS (lo slot di staging e'
   bloccato dall'allowlist del backend) e il menu esce gia' nell'HTML, anche senza JavaScript. Se l'API non
   risponde vale l'elenco di ripiego, uguale a quello di produzione al 25/10/2026; la risposta vuota o in errore
   non va in cache per piu' di 10 minuti.
   Ritorna [ { id, label, b2b } ] in ordine di sort_order. */
function tp_service_types() {
	$cached = get_transient( 'tp_service_types' );
	if ( is_array( $cached ) && $cached ) {
		return $cached;
	}
	$out = array();
	$res = wp_remote_get( tp_api_base() . '/api/services/types', array( 'timeout' => 4 ) );
	if ( ! is_wp_error( $res ) && 200 === (int) wp_remote_retrieve_response_code( $res ) ) {
		$data = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( is_array( $data ) ) {
			usort( $data, function ( $a, $b ) {
				return (int) ( $a['sort_order'] ?? 0 ) <=> (int) ( $b['sort_order'] ?? 0 );
			} );
			foreach ( $data as $t ) {
				if ( ! empty( $t['id'] ) && ! empty( $t['name_it'] ) ) {
					$out[] = array( 'id' => (string) $t['id'], 'label' => (string) $t['name_it'], 'b2b' => ! empty( $t['b2b_only'] ) );
				}
			}
		}
	}
	if ( $out ) {
		set_transient( 'tp_service_types', $out, 6 * HOUR_IN_SECONDS );
		return $out;
	}
	$ripiego = array(
		array( 'id' => 'pulizia_domestica', 'label' => 'Pulizia ordinaria', 'b2b' => false ),
		array( 'id' => 'pulizia_uffici', 'label' => 'Uffici', 'b2b' => true ),
		array( 'id' => 'pulizia_scale', 'label' => 'Condomini', 'b2b' => true ),
		array( 'id' => 'pulizie_industriali', 'label' => 'Sgrosso', 'b2b' => false ),
		array( 'id' => 'sanificazioni', 'label' => 'Sanificazione', 'b2b' => false ),
		array( 'id' => 'pulizia_esterni', 'label' => 'Affitti brevi', 'b2b' => true ),
	);
	set_transient( 'tp_service_types', $ripiego, 10 * MINUTE_IN_SECONDS );
	return $ripiego;
}

/* Thank-you (TICKET-42): noindex e fuori dalla sitemap. E' una pagina di servizio raggiunta solo dopo un lead. */
add_filter( 'wp_robots', function ( $robots ) {
	if ( is_page_template( 'page-thank-you.php' ) ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
		unset( $robots['max-image-preview'] );
	}
	return $robots;
} );
add_filter( 'wp_sitemaps_posts_query_args', function ( $args, $post_type ) {
	if ( 'page' === $post_type ) {
		$args['meta_query'] = array(
			'relation' => 'OR',
			array( 'key' => '_wp_page_template', 'compare' => 'NOT EXISTS' ),
			array( 'key' => '_wp_page_template', 'value' => 'page-thank-you.php', 'compare' => '!=' ),
		);
	}
	return $args;
}, 10, 2 );

/* Title, meta description e JSON-LD per pagina: inc/seo.php (campo SEO > default della variante > WordPress). */

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
	$moduli_sgrosso   = array( 'sticky-cta', 'scenario-tabs', 'sgrosso-estimator', 'lead-form' );
	// Coreografia della sola Home (ADR-002): telefono pinnato, sequenza schermate, video hero.
	$moduli_home      = array( 'home-motion', 'hiw-carousel', 'hero-video', 'search-form' );
	// Pagina Diventa cleaner (TICKET-51/54): barra fissa e calcolatore dei guadagni.
	$moduli_cleaner   = array( 'sticky-cta', 'earnings-calculator', 'cleaner-motion' );
	// Landing B2B (TICKET-58/59): barra fissa doppia, tab dei settori e confronto prima/dopo, form a un passo.
	$moduli_b2b       = array( 'sticky-cta', 'scenario-tabs', 'lead-form' );
	$pagine_con_sezioni = array( 'page-home.php', 'page-hub-roma.php', 'page-sgrosso.php', 'page-cleaner-landing.php', 'page-landing-b2b.php', 'page-contact.php', 'page-about.php', 'page-landing-zanzare.php' );

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
	if ( is_page_template( 'page-cleaner-landing.php' ) ) {
		// Dei moduli condivisi usa solo reveal e FAQ: niente mappa, servizi, recensioni.
		$handles = array_merge( array( 'reveal', 'faq-accordion' ), $moduli_cleaner );
	}
	if ( is_page_template( 'page-landing-zanzare.php' ) ) {
		// Landing zanzare (TICKET-73..75): stessi moduli della B2B (barra fissa, tab, form a un passo).
		$handles = array_merge( array( 'reveal', 'faq-accordion' ), $moduli_b2b );
	}
	if ( is_page_template( 'page-landing-b2b.php' ) ) {
		// Dei moduli condivisi usa reveal e FAQ: niente mappa, servizi; le recensioni sono in griglia ferma.
		$handles = array_merge( array( 'reveal', 'faq-accordion' ), $moduli_b2b );
	}
	if ( is_page_template( 'page-contact.php' ) ) {
		// Contatti (TICKET-63/65): solo ingressi a cascata e FAQ, nessun modulo proprio.
		$handles = array( 'reveal', 'faq-accordion' );
	}
	if ( is_page_template( 'page-about.php' ) ) {
		// Chi siamo (TICKET-66..69): solo ingressi a cascata; la mappa della hero e' statica.
		$handles = array( 'reveal' );
	}
	if ( is_home() || is_category() || is_singular( 'post' ) ) {
		// Blog (SPEC-blog): solo l'ingresso della CTA finale; indice e paginazione sono HTML puro.
		$handles = array( 'reveal' );
	}
	if ( is_page_template( 'page-thank-you.php' ) ) {
		$handles[] = 'thank-you';
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
require_once __DIR__ . '/inc/tracking.php';
require_once __DIR__ . '/inc/blog.php';
require_once __DIR__ . '/inc/redirects.php';
require_once __DIR__ . '/inc/seo.php';
