<?php
/**
 * SEO per pagina (TICKET-48): title, meta description e dati strutturati delle landing Sgrosso.
 *
 * Title e description: campo della scheda SEO se compilato, altrimenti sulle landing Sgrosso il default
 * della variante (inc/sgrosso-variants.php, gli stessi testi di VARIANT_CONFIG del live), altrimenti il titolo
 * WordPress e il motto del sito. JSON-LD Service sulle landing Sgrosso: stessa forma di serviceJsonLd() della SPA
 * (front-end/src/lib/shared/jsonLd.ts): url canonico, areaServed Roma e provincia, offers con PriceSpecification
 * 200-700 EUR (decisione Guido 03/10, solo sgrosso), provider LocalBusiness senza indirizzo stradale.
 *
 * [ATTENZIONE] Niente aggregateRating da nessuna parte: un placeholder di quel tipo era finito su 21 pagine del
 * portale. Il FAQPage lo emette gia' template-parts/faq-accordion.php: non aggiungerne un secondo qui.
 */
require_once __DIR__ . '/sgrosso-variants.php';

/**
 * Pagine di servizio e hub di Roma con l'anchor text fisso della SPEC-seo-post-cutover (TICKET-78).
 * Unica fonte per il blocco "Altri servizi a Roma", la colonna del footer e il BreadcrumbList (TICKET-80):
 * percorso => anchor. L'hub e' la prima voce. Gli anchor portano la keyword della pagina, mai "scopri di piu'".
 */
function tp_service_pages() {
	return array(
		'/roma/'                               => 'Pulizie casa a Roma',
		'/roma/sgrosso-casa/'                  => 'Sgrosso casa Roma',
		'/roma/sgrosso-post-ristrutturazione/' => 'Pulizia post-ristrutturazione Roma',
		'/roma/sgrosso-trasloco/'              => 'Pulizia post trasloco Roma',
		'/roma/sgrosso-fine-affitto/'          => 'Pulizia fine affitto Roma',
		'/roma/sgrosso-prima-pulizia/'         => 'Pulizia straordinaria casa Roma',
		'/roma/sgrosso-commerciale/'           => 'Pulizie commerciali Roma',
		'/roma/zanzare-disinfestazione/'       => 'Disinfestazione zanzare Roma',
	);
}

/** Percorso normalizzato (con slash finale) dell'URL corrente, per confrontarlo con tp_service_pages(). */
function tp_current_path() {
	$path = (string) wp_parse_url( get_permalink(), PHP_URL_PATH );
	return trailingslashit( $path );
}

/** Default della variante quando la pagina corrente e' una landing Sgrosso, altrimenti null. */
function tp_seo_sgrosso_defaults() {
	if ( ! is_singular() || ! is_page_template( 'page-sgrosso.php' ) ) {
		return null;
	}
	return tp_sg_defaults( carbon_get_the_post_meta( 'sg_variant' ) ?: 'sgrosso-casa' );
}

function tp_seo_title() {
	$custom = is_singular() ? trim( (string) carbon_get_the_post_meta( 'seo_title' ) ) : '';
	if ( $custom ) {
		return $custom;
	}
	// Blog (SPEC-blog): indice, categorie e articoli, inc/blog.php.
	$blog = tp_blog_seo_title();
	if ( $blog ) {
		return $blog;
	}
	$d = tp_seo_sgrosso_defaults();
	if ( $d ) {
		return $d['page_title'];
	}
	if ( is_singular() && is_page_template( 'page-thank-you.php' ) ) {
		return 'Richiesta ricevuta | Trovapulizie';
	}
	// Hub /roma/ (TICKET-81): senza campo SEO compilato il title era il solo titolo della pagina ("Roma").
	if ( is_singular() && is_page_template( 'page-hub-roma.php' ) ) {
		return 'Pulizie casa a Roma, prenoti in 2 minuti | Trovapulizie';
	}
	// Diventa cleaner (TICKET-51): stesso title del live React.
	if ( is_singular() && is_page_template( 'page-cleaner-landing.php' ) ) {
		return 'Diventa cleaner Trovapulizie — Lavora con clienti verificati a Roma';
	}
	// Landing B2B (TICKET-58): stesso title del live React.
	if ( is_singular() && is_page_template( 'page-landing-b2b.php' ) ) {
		return 'Pulizie commerciali Roma — Uffici, negozi e locali | Trovapulizie';
	}
	// Landing zanzare (TICKET-73): stesso title del live React.
	if ( is_singular() && is_page_template( 'page-landing-zanzare.php' ) ) {
		return 'Disinfestazione zanzare Roma — Sopralluogo gratuito | Trovapulizie';
	}
	// Contatti (TICKET-63): pagina nuova, non esiste sul live.
	if ( is_singular() && is_page_template( 'page-contact.php' ) ) {
		return 'Contatti — Trovapulizie';
	}
	// Chi siamo (TICKET-66): pagina nuova, non esiste sul live.
	if ( is_singular() && is_page_template( 'page-about.php' ) ) {
		return 'Chi siamo — Trovapulizie';
	}
	// Pagine legali: stesso schema del live ("Privacy Policy — TrovaPulizie", "Termini e Condizioni — Trovapulizie").
	if ( is_singular() && is_page_template( 'page-legal.php' ) ) {
		return get_the_title() . ' — Trovapulizie';
	}
	return '';
}

function tp_seo_description() {
	$custom = is_singular() ? trim( (string) carbon_get_the_post_meta( 'seo_description' ) ) : '';
	if ( $custom ) {
		return $custom;
	}
	$blog = tp_blog_seo_description();
	if ( $blog ) {
		return $blog;
	}
	$d = tp_seo_sgrosso_defaults();
	if ( $d ) {
		return $d['page_description'];
	}
	if ( is_singular() && is_page_template( 'page-thank-you.php' ) ) {
		return 'Richiesta ricevuta: il team Trovapulizie ti ricontatta entro 1 ora per confermare il tuo preventivo gratuito.';
	}
	if ( is_singular() && is_page_template( 'page-cleaner-landing.php' ) ) {
		return 'Lavora come cleaner a Roma con Trovapulizie: clienti verificati, pagamenti puntuali via app, zero commissioni nascoste. Iscriviti gratis in 5 minuti.';
	}
	if ( is_singular() && is_page_template( 'page-landing-b2b.php' ) ) {
		return 'Pulizie commerciali a Roma per uffici, negozi e locali: preventivo su misura, fattura, personale verificato. Sopralluogo gratuito, risposta in 1 ora.';
	}
	if ( is_singular() && is_page_template( 'page-landing-zanzare.php' ) ) {
		return 'Disinfestazione zanzare a Roma per privati e aziende: sopralluogo gratuito, trattamento professionale e garanzia sui risultati. Preventivo in giornata.';
	}
	if ( is_singular() && is_page_template( 'page-contact.php' ) ) {
		return 'Come contattare Trovapulizie: scrivici su WhatsApp o a help@trovapulizie.it, oppure trova subito la pagina giusta per prenotare, chiedere un preventivo aziendale o lavorare con noi.';
	}
	if ( is_singular() && is_page_template( 'page-about.php' ) ) {
		return "Trovapulizie è la startup romana che mette in contatto privati e aziende con imprese di pulizia verificate: chi siamo, come lavoriamo, il team e i dati della società.";
	}
	return get_bloginfo( 'description' );
}

/* Contatti (TICKET-64) e Chi siamo (TICKET-66): Organization con ContactPoint, dagli stessi campi che la pagina
   Contatti mostra (numero ed email). Su Chi siamo anche data di fondazione e fondatori.
   Indirizzo solo a livello di citta', come il provider del Service qui sotto: lo streetAddress resta fuori finche'
   non si chiarisce se il profilo Google e' un'attivita' con area di servizio (CLAUDE.md cliente). */
add_action( 'wp_head', function () {
	$contatti = is_singular() && is_page_template( 'page-contact.php' );
	$chi      = is_singular() && is_page_template( 'page-about.php' );
	if ( ! $contatti && ! $chi ) {
		return;
	}
	// I campi stanno sulla pagina Contatti: da Chi siamo si leggono da li'.
	$fonte = $contatti ? get_the_ID() : (int) url_to_postid( home_url( '/contatti/' ) );
	$campo = function ( $nome, $default ) use ( $fonte ) {
		$v = $fonte ? trim( (string) carbon_get_post_meta( $fonte, $nome ) ) : '';
		return '' !== $v ? $v : $default;
	};
	$wa    = tp_whatsapp_parts( $campo( 'ct_whatsapp', TP_CT_WHATSAPP ) );
	$email = sanitize_email( $campo( 'ct_email', TP_CT_EMAIL ) );
	$punto = array(
		'@type'             => 'ContactPoint',
		'contactType'       => 'customer support',
		'areaServed'        => 'Roma',
		'availableLanguage' => 'it',
	);
	if ( $wa ) {
		$punto['telephone'] = $wa['e164'];
	}
	if ( $email ) {
		$punto['email'] = $email;
	}
	$dati = array(
		'@context'  => 'https://schema.org',
		'@type'     => 'Organization',
		'name'      => 'Trovapulizie',
		'legalName' => 'Trovapulizie SRL',
		'url'       => home_url( '/' ),
		'vatID'     => 'IT18445521000',
		'address'   => array(
			'@type'           => 'PostalAddress',
			'addressLocality' => 'Roma',
			'addressRegion'   => 'Lazio',
			'addressCountry'  => 'IT',
		),
	);
	if ( $email ) {
		$dati['email'] = $email;
	}
	if ( $wa || $email ) {
		$dati['contactPoint'] = $punto;
	}
	if ( $chi ) {
		$dati['foundingDate'] = '2026-02-19';
		$dati['founder']      = array(
			array( '@type' => 'Person', 'name' => 'Christian Pascolini' ),
			array( '@type' => 'Person', 'name' => 'Guido Spanu' ),
		);
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $dati, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}, 5 );

add_filter( 'pre_get_document_title', function ( $title ) {
	$t = tp_seo_title();
	return $t ? $t : $title;
}, 20 );

add_action( 'wp_head', function () {
	$descrizione = tp_seo_description();
	if ( $descrizione ) {
		echo '<meta name="description" content="' . esc_attr( wp_strip_all_tags( $descrizione ) ) . '">' . "\n";
	}
}, 2 );

add_action( 'wp_head', function () {
	$d   = tp_seo_sgrosso_defaults();
	// Landing B2B (TICKET-58): stesso Service, senza offerta: il live non pubblica prezzi per le aziende.
	$b2b = is_singular() && is_page_template( 'page-landing-b2b.php' );
	// Landing zanzare (TICKET-73): stesso Service della B2B, senza offerta.
	$zz  = is_singular() && is_page_template( 'page-landing-zanzare.php' );
	if ( ! $d && ! $b2b && ! $zz ) {
		return;
	}
	$dati = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Service',
		'serviceType' => $zz ? 'Disinfestazione zanzare' : ( $b2b ? 'Pulizie commerciali per uffici, negozi e locali' : 'Pulizia straordinaria (sgrosso)' ),
		'description' => wp_strip_all_tags( tp_seo_description() ),
		'url'         => get_permalink(),
		'areaServed'  => array(
			array( '@type' => 'City', 'name' => 'Roma' ),
			array( '@type' => 'AdministrativeArea', 'name' => 'Provincia di Roma' ),
		),
		'offers'      => array(
			'@type'              => 'Offer',
			'priceSpecification' => array(
				'@type'         => 'PriceSpecification',
				'minPrice'      => 200,
				'maxPrice'      => 700,
				'priceCurrency' => 'EUR',
			),
		),
		'provider'    => array(
			'@type'     => 'LocalBusiness',
			'name'      => 'Trovapulizie',
			'legalName' => 'Trovapulizie SRL',
			'telephone' => '+393200980144',
			'address'   => array(
				'@type'           => 'PostalAddress',
				'addressLocality' => 'Roma',
				'addressRegion'   => 'Lazio',
				'addressCountry'  => 'IT',
			),
		),
	);
	if ( $b2b || $zz ) {
		unset( $dati['offers'] );
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $dati, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}, 5 );

/* ---- Open Graph e Twitter Card (SPEC-seo-post-cutover, TICKET-79) ----------------------------------------------
   Stesso hook wp_head dei meta esistenti, priorita' 3 (subito dopo la meta description a 2). Nessun altro hook del
   tema ne' plugin SEO emette og: o twitter: (verificato con grep sul tema: questo e' l'unico punto).
   title = titolo del documento, description = meta description, url = canonical della pagina. Niente sulle pagine
   noindex di servizio (thank-you, survey) e su 404 e ricerca. */

/** URL canonico della pagina corrente (lo stesso che WordPress stampa in rel=canonical per le pagine singole). */
function tp_seo_canonical_url() {
	if ( is_singular() ) {
		return (string) wp_get_canonical_url();
	}
	if ( is_home() ) {
		return tp_blog_url();
	}
	if ( is_category() ) {
		return (string) get_category_link( get_queried_object_id() );
	}
	return home_url( '/' );
}

/**
 * Immagine Open Graph della pagina: array( url, width, height ) con width/height a 0 se non note.
 * Ordine: immagine in evidenza; hero della pagina quando e' un campo o un file del tema (landing Sgrosso:
 * campo sg_hero_image, altrimenti la foto della variante; landing B2B: campo b2b_hero_image); infine
 * assets/img/og-default.jpg (1200x630, ricavata dalla foto della hero della Home).
 */
function tp_seo_og_image() {
	if ( is_singular() ) {
		$id = get_post_thumbnail_id();
		if ( $id ) {
			$src = wp_get_attachment_image_src( $id, 'large' );
			if ( $src ) {
				return array( 'url' => $src[0], 'width' => (int) $src[1], 'height' => (int) $src[2] );
			}
		}
		if ( is_page_template( 'page-sgrosso.php' ) ) {
			$campo = trim( (string) carbon_get_the_post_meta( 'sg_hero_image' ) );
			if ( $campo ) {
				return array( 'url' => $campo, 'width' => 0, 'height' => 0 );
			}
			$d = tp_seo_sgrosso_defaults();
			if ( $d && ! empty( $d['hero_image'] ) ) {
				return array( 'url' => tp_theme_asset( 'assets/img/sgrosso/' . $d['hero_image'] . '.jpg' ), 'width' => 1920, 'height' => 1071 );
			}
		}
		if ( is_page_template( 'page-landing-b2b.php' ) ) {
			$campo = trim( (string) carbon_get_the_post_meta( 'b2b_hero_image' ) );
			if ( $campo ) {
				return array( 'url' => $campo, 'width' => 0, 'height' => 0 );
			}
		}
	}
	return array( 'url' => tp_theme_asset( 'assets/img/og-default.jpg' ), 'width' => 1200, 'height' => 630 );
}

add_action( 'wp_head', function () {
	if ( is_404() || is_search() ) {
		return;
	}
	if ( is_singular() && ( is_page_template( 'page-thank-you.php' ) || is_page_template( 'page-survey.php' ) ) ) {
		return;
	}
	$title       = wp_strip_all_tags( wp_get_document_title() );
	$descrizione = wp_strip_all_tags( tp_seo_description() );
	$url         = tp_seo_canonical_url();
	$img         = tp_seo_og_image();
	$tipo        = is_singular( 'post' ) ? 'article' : 'website';

	$og = array(
		'og:type'        => $tipo,
		'og:title'       => $title,
		'og:description' => $descrizione,
		'og:url'         => $url,
		'og:site_name'   => 'Trovapulizie',
		'og:locale'      => 'it_IT',
		'og:image'       => $img['url'],
	);
	if ( $img['width'] && $img['height'] ) {
		$og['og:image:width']  = (string) $img['width'];
		$og['og:image:height'] = (string) $img['height'];
	}
	$tw = array(
		'twitter:card'        => 'summary_large_image',
		'twitter:title'       => $title,
		'twitter:description' => $descrizione,
		'twitter:image'       => $img['url'],
	);
	foreach ( $og as $chiave => $valore ) {
		if ( '' === (string) $valore ) {
			continue;
		}
		$campo = in_array( $chiave, array( 'og:url', 'og:image' ), true ) ? esc_url( $valore ) : esc_attr( $valore );
		echo '<meta property="' . esc_attr( $chiave ) . '" content="' . $campo . '">' . "\n";
	}
	foreach ( $tw as $chiave => $valore ) {
		if ( '' === (string) $valore ) {
			continue;
		}
		$campo = 'twitter:image' === $chiave ? esc_url( $valore ) : esc_attr( $valore );
		echo '<meta name="' . esc_attr( $chiave ) . '" content="' . $campo . '">' . "\n";
	}
}, 3 );

/* ---- Dati strutturati: Organization e WebSite (Home), LocalBusiness (hub), BreadcrumbList (SPEC-seo-post-cutover,
   TICKET-80). Service e FAQPage restano dove sono (sopra e template-parts/faq-accordion.php).

   [ATTENZIONE] Niente aggregateRating finche' non ci sono recensioni vere: un placeholder di quel tipo e' finito
   pubblicato su 21 pagine del portale. Niente telefono segnaposto, niente openingHours (nessun campo li contiene),
   nessun indirizzo stradale: solo citta', regione e paese finche' il Profilo Google Business non lo espone. */

/** Indirizzo postale senza via, uguale a quello gia' usato dal Service e da Organization di Contatti. */
function tp_seo_address() {
	return array(
		'@type'           => 'PostalAddress',
		'addressLocality' => 'Roma',
		'addressRegion'   => 'Lazio',
		'addressCountry'  => 'IT',
	);
}

/** Profili social con URL compilato in Theme Options > Footer, per `sameAs`. Vuoto se nessuno e' compilato. */
function tp_seo_same_as() {
	$urls = array();
	foreach ( (array) carbon_get_theme_option( 'footer_social' ) as $s ) {
		$u = ! empty( $s['url'] ) ? esc_url_raw( trim( (string) $s['url'] ) ) : '';
		if ( $u ) {
			$urls[] = $u;
		}
	}
	return array_values( array_unique( $urls ) );
}

/** Email di contatto: campo della pagina Contatti se compilato, altrimenti TP_CT_EMAIL (come Organization di Contatti). */
function tp_seo_contact_email() {
	$fonte = (int) url_to_postid( home_url( '/contatti/' ) );
	$email = $fonte ? sanitize_email( trim( (string) carbon_get_post_meta( $fonte, 'ct_email' ) ) ) : '';
	return $email ?: TP_CT_EMAIL;
}

/** Dati comuni di Organization e LocalBusiness: nome, ragione sociale, telefono e email ufficiali, indirizzo, sameAs. */
function tp_seo_business_base() {
	$base = array(
		'name'      => 'Trovapulizie',
		'legalName' => 'Trovapulizie SRL',
		'url'       => home_url( '/' ),
		'telephone' => '+393200980144',
		'email'     => tp_seo_contact_email(),
		'address'   => tp_seo_address(),
	);
	$same = tp_seo_same_as();
	if ( $same ) {
		$base['sameAs'] = $same;
	}
	return $base;
}

function tp_seo_print_jsonld( $dati ) {
	echo '<script type="application/ld+json">' . wp_json_encode( $dati, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}

/* Home: Organization + WebSite. Stessi dati di Chi siamo (nome, ragione sociale, P.IVA, email, contactPoint). */
add_action( 'wp_head', function () {
	if ( ! is_singular() || ! is_page_template( 'page-home.php' ) ) {
		return;
	}
	$org = array_merge(
		array( '@context' => 'https://schema.org', '@type' => 'Organization' ),
		tp_seo_business_base(),
		array(
			'logo'         => tp_theme_asset( 'assets/img/logo.png' ),
			'vatID'        => 'IT18445521000',
			'contactPoint' => array(
				'@type'             => 'ContactPoint',
				'contactType'       => 'customer support',
				'telephone'         => '+393200980144',
				'email'             => tp_seo_contact_email(),
				'areaServed'        => 'Roma',
				'availableLanguage' => 'it',
			),
		)
	);
	tp_seo_print_jsonld( $org );
	tp_seo_print_jsonld( array(
		'@context'   => 'https://schema.org',
		'@type'      => 'WebSite',
		'url'        => home_url( '/' ),
		'name'       => 'Trovapulizie',
		'inLanguage' => 'it',
	) );
}, 5 );

/* Hub /roma/: LocalBusiness completo. priceRange dalla tariffa oraria dei campi della pagina (gli stessi numeri
   della FAQ e del calcolatore); omesso se i campi non sono numerici. image = foto della pagina o di default. */
add_action( 'wp_head', function () {
	if ( ! is_singular() || ! is_page_template( 'page-hub-roma.php' ) ) {
		return;
	}
	$min = trim( (string) ( carbon_get_the_post_meta( 'roma_price_min' ) ?: 15 ) );
	$max = trim( (string) ( carbon_get_the_post_meta( 'roma_price_max' ) ?: 20 ) );
	$img = tp_seo_og_image();
	$dati = array_merge(
		array( '@context' => 'https://schema.org', '@type' => 'LocalBusiness' ),
		tp_seo_business_base(),
		array(
			'url'         => get_permalink(),
			'description' => wp_strip_all_tags( tp_seo_description() ),
			'image'       => $img['url'],
			'areaServed'  => array(
				'@type' => 'City',
				'name'  => 'Roma',
			),
		)
	);
	if ( is_numeric( $min ) && is_numeric( $max ) ) {
		$dati['priceRange'] = 'EUR ' . $min . '-' . $max . '/ora';
	}
	tp_seo_print_jsonld( $dati );
}, 5 );

/* BreadcrumbList: hub (Home > Roma) e le 7 pagine di servizio (Home > Roma > titolo della pagina), URL assoluti. */
add_action( 'wp_head', function () {
	if ( ! is_singular( 'page' ) ) {
		return;
	}
	$servizi = tp_service_pages();
	$corrente = tp_current_path();
	if ( ! isset( $servizi[ $corrente ] ) ) {
		return;
	}
	$voci = array(
		array( 'name' => 'Home', 'item' => home_url( '/' ) ),
		array( 'name' => 'Roma', 'item' => home_url( '/roma/' ) ),
	);
	if ( '/roma/' !== $corrente ) {
		$voci[] = array( 'name' => wp_strip_all_tags( get_the_title() ), 'item' => get_permalink() );
	}
	$elementi = array();
	foreach ( $voci as $i => $v ) {
		$elementi[] = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $v['name'],
			'item'     => $v['item'],
		);
	}
	tp_seo_print_jsonld( array(
		'@context'        => 'https://schema.org',
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $elementi,
	) );
}, 5 );
