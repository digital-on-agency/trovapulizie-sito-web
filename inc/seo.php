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
	$d = tp_seo_sgrosso_defaults();
	if ( $d ) {
		return $d['page_title'];
	}
	if ( is_singular() && is_page_template( 'page-thank-you.php' ) ) {
		return 'Richiesta ricevuta | Trovapulizie';
	}
	// Diventa cleaner (TICKET-51): stesso title del live React.
	if ( is_singular() && is_page_template( 'page-cleaner-landing.php' ) ) {
		return 'Diventa cleaner Trovapulizie — Lavora con clienti verificati a Roma';
	}
	// Landing B2B (TICKET-58): stesso title del live React.
	if ( is_singular() && is_page_template( 'page-landing-b2b.php' ) ) {
		return 'Pulizie commerciali Roma — Uffici, negozi e locali | Trovapulizie';
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
	if ( ! $d && ! $b2b ) {
		return;
	}
	$dati = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'Service',
		'serviceType' => $b2b ? 'Pulizie commerciali per uffici, negozi e locali' : 'Pulizia straordinaria (sgrosso)',
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
	if ( $b2b ) {
		unset( $dati['offers'] );
	}
	echo '<script type="application/ld+json">' . wp_json_encode( $dati, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}, 5 );
