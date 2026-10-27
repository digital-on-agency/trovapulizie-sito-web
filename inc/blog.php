<?php
/**
 * Blog (SPEC-blog, TICKET-70..72): helper condivisi da home.php, category.php, single.php e dai template-part
 * blog-index.php e post-card.php.
 *
 * Impostazioni WordPress richieste (fatte con WP-CLI sullo slot, vedi docs/blog.md): pagina "Blog" come pagina
 * degli articoli, struttura permalink /blog/%postname%/, category_base "categoria", cinque categorie.
 *
 * - tp_blog_services(): i servizi collegabili a un articolo (campo "Servizio collegato"), con testi e link.
 * - tp_blog_toc(): ancore sugli H2 del testo e voci dell'indice; l'indice esce solo con almeno 3 H2.
 * - [tp_servizio]: shortcode che mette il box del servizio nel punto scelto; senza shortcode il box va in fondo.
 * - Autore unico "Redazione Trovapulizie": niente pagine autore (redirect al blog), niente commenti.
 * - 7 articoli per pagina su indice e categorie: in pagina 1 uno in evidenza e sei in griglia.
 */

/** Servizi collegabili a un articolo: chiave del campo => testi del box e destinazione. */
function tp_blog_services() {
	$sopralluogo = 'Sopralluogo gratuito, preventivo prima di confermare, pagamento protetto.';
	return array(
		'sgrosso-fine-affitto'  => array( 'label' => 'Sgrosso fine affitto', 'eyebrow' => 'SGROSSO FINE AFFITTO', 'title' => "Devi riconsegnare casa? Ci pensa un'impresa verificata.", 'text' => $sopralluogo, 'cta' => 'Richiedi il sopralluogo', 'url' => '/roma/sgrosso-fine-affitto/' ),
		'sgrosso-casa'          => array( 'label' => 'Sgrosso casa', 'eyebrow' => 'SGROSSO CASA', 'title' => 'Una pulizia a fondo, fatta da professionisti.', 'text' => $sopralluogo, 'cta' => 'Richiedi il sopralluogo', 'url' => '/roma/sgrosso-casa/' ),
		'post-ristrutturazione' => array( 'label' => 'Sgrosso post-ristrutturazione', 'eyebrow' => 'DOPO I LAVORI', 'title' => "Fine cantiere? Lo sgrosso lo fa un'impresa verificata.", 'text' => $sopralluogo, 'cta' => 'Richiedi il sopralluogo', 'url' => '/roma/sgrosso-post-ristrutturazione/' ),
		'aziende'               => array( 'label' => 'Pulizie per aziende', 'eyebrow' => 'PULIZIE PER AZIENDE', 'title' => 'Uffici, negozi e condomini: un preventivo su misura.', 'text' => 'Sopralluogo gratuito in sede, fattura, personale verificato.', 'cta' => 'Richiedi un preventivo', 'url' => '/roma/sgrosso-commerciale/' ),
		'diventa-cleaner'       => array( 'label' => 'Diventa cleaner', 'eyebrow' => 'PER I PROFESSIONISTI', 'title' => 'Fai pulizie per mestiere? Ricevi richieste nella tua zona.', 'text' => 'Registrazione gratuita, zone e tariffe le scegli tu.', 'cta' => 'Diventa partner', 'url' => '/diventa-cleaner/' ),
	);
}

/** Box del servizio collegato all'articolo corrente, '' se il campo e' vuoto o "nessuno". */
function tp_blog_service_box( $post_id = 0 ) {
	$key = (string) carbon_get_post_meta( $post_id ?: get_the_ID(), 'tp_servizio' );
	$all = tp_blog_services();
	if ( empty( $all[ $key ] ) ) {
		return '';
	}
	$s = $all[ $key ];
	return sprintf(
		'<aside class="bl-service"><div class="bl-service__text"><p class="bl-service__eyebrow">%1$s</p><p class="bl-service__title">%2$s</p><p class="bl-service__sub">%3$s</p></div><a class="tp-btn tp-btn--primary bl-service__cta" href="%4$s" data-cta-position="servizio">%5$s <span class="dc-arrow" aria-hidden="true">&rarr;</span></a></aside>',
		esc_html( $s['eyebrow'] ),
		esc_html( $s['title'] ),
		esc_html( $s['text'] ),
		esc_url( home_url( $s['url'] ) ),
		esc_html( $s['cta'] )
	);
}

add_shortcode( 'tp_servizio', function () {
	$GLOBALS['tp_blog_service_used'] = true;
	return tp_blog_service_box();
} );

/**
 * Aggiunge un id a ogni H2 del testo (slug del titolo, unico) e restituisce [html, voci]. Gli H2 che hanno gia'
 * un id lo tengono.
 */
function tp_blog_toc( $html ) {
	$voci  = array();
	$usati = array();
	$html  = preg_replace_callback( '/<h2([^>]*)>(.*?)<\/h2>/is', function ( $m ) use ( &$voci, &$usati ) {
		$titolo = trim( wp_strip_all_tags( $m[2] ) );
		if ( preg_match( '/\sid="([^"]+)"/', $m[1], $id ) ) {
			$slug = $id[1];
			$attr = $m[1];
		} else {
			$base = sanitize_title( $titolo ) ?: 'sezione';
			$slug = $base;
			for ( $i = 2; isset( $usati[ $slug ] ); $i++ ) {
				$slug = $base . '-' . $i;
			}
			$attr = $m[1] . ' id="' . esc_attr( $slug ) . '"';
		}
		$usati[ $slug ] = true;
		$voci[]         = array( 'id' => $slug, 'title' => $titolo );
		return '<h2' . $attr . '>' . $m[2] . '</h2>';
	}, $html );
	return array( $html, $voci );
}

/**
 * Minuti di lettura a 200 parole al minuto, minimo 1. Le parole si contano sugli spazi: str_word_count conosce solo
 * le lettere ASCII e spezza "più" o "è" in piu' parole o nessuna.
 */
function tp_blog_minutes( $post = null ) {
	$testo  = trim( wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $post ) ) ) );
	$parole = '' === $testo ? 0 : count( preg_split( '/\s+/u', $testo ) );
	return max( 1, (int) ceil( $parole / 200 ) );
}

/** "20 ottobre 2026 · 7 min di lettura" (mese minuscolo come in italiano, qualunque sia la traduzione di WP). */
function tp_blog_meta_line( $post = null ) {
	return mb_strtolower( get_the_date( 'j F Y', $post ) ) . ' · ' . tp_blog_minutes( $post ) . ' min di lettura';
}

/** Prima categoria dell'articolo diversa da quella di default, o null. */
function tp_blog_category( $post = null ) {
	$default = (int) get_option( 'default_category' );
	foreach ( (array) get_the_category( $post ? ( is_object( $post ) ? $post->ID : $post ) : 0 ) as $c ) {
		if ( (int) $c->term_id !== $default ) {
			return $c;
		}
	}
	return null;
}

/** URL dell'indice del blog (pagina degli articoli). */
function tp_blog_url() {
	$id = (int) get_option( 'page_for_posts' );
	return $id ? get_permalink( $id ) : home_url( '/blog/' );
}

// 7 articoli per pagina su indice e categorie.
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() && $q->is_main_query() && ( $q->is_home() || $q->is_category() ) ) {
		$q->set( 'posts_per_page', 7 );
	}
} );

// Autore unico: niente pagine autore, si torna al blog.
add_action( 'template_redirect', function () {
	if ( is_author() ) {
		wp_safe_redirect( tp_blog_url(), 301 );
		exit;
	}
} );

// Niente commenti sugli articoli.
add_filter( 'comments_open', '__return_false' );
add_filter( 'pings_open', '__return_false' );

/* SEO: title e description di indice, categorie e articoli. inc/seo.php li chiede qui prima del default. */
function tp_blog_seo_title() {
	if ( is_home() ) {
		return 'Blog — Trovapulizie: guide pratiche sulle pulizie a Roma';
	}
	if ( is_category() ) {
		return single_cat_title( '', false ) . ' — Blog Trovapulizie';
	}
	if ( is_singular( 'post' ) ) {
		return get_the_title() . ' — Trovapulizie';
	}
	return '';
}
function tp_blog_seo_description() {
	if ( is_home() ) {
		return 'Guide pratiche sulle pulizie a Roma: prezzi, checklist e consigli da chi pulisce per mestiere. Un articolo al mese sui dubbi che riceviamo più spesso.';
	}
	if ( is_category() ) {
		$d = trim( wp_strip_all_tags( category_description() ) );
		return $d ?: 'Articoli del blog di Trovapulizie nella categoria ' . single_cat_title( '', false ) . '.';
	}
	if ( is_singular( 'post' ) ) {
		return wp_strip_all_tags( get_the_excerpt() );
	}
	return '';
}

/* JSON-LD: Blog sull'indice, BlogPosting e BreadcrumbList sull'articolo. Autore ed editore = l'organizzazione. */
add_action( 'wp_head', function () {
	$org = array( '@type' => 'Organization', 'name' => 'Trovapulizie', 'url' => home_url( '/' ), 'logo' => array( '@type' => 'ImageObject', 'url' => tp_theme_asset( 'assets/img/logo.png' ) ) );
	$out = array();
	if ( is_home() ) {
		$out[] = array( '@context' => 'https://schema.org', '@type' => 'Blog', 'name' => 'Blog di Trovapulizie', 'url' => tp_blog_url(), 'description' => tp_blog_seo_description(), 'publisher' => $org );
	}
	if ( is_singular( 'post' ) ) {
		$img   = get_the_post_thumbnail_url( null, 'large' );
		$post  = array(
			'@context'         => 'https://schema.org',
			'@type'            => 'BlogPosting',
			'headline'         => get_the_title(),
			'description'      => wp_strip_all_tags( get_the_excerpt() ),
			'datePublished'    => get_the_date( 'c' ),
			'dateModified'     => get_the_modified_date( 'c' ),
			'mainEntityOfPage' => get_permalink(),
			'author'           => $org,
			'publisher'        => $org,
		);
		if ( $img ) {
			$post['image'] = $img;
		}
		$out[] = $post;
		$voci  = array( array( 'Blog', tp_blog_url() ) );
		$cat   = tp_blog_category();
		if ( $cat ) {
			$voci[] = array( $cat->name, get_category_link( $cat ) );
		}
		$voci[] = array( get_the_title(), get_permalink() );
		$out[]  = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => array_map( function ( $v, $i ) {
				return array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => $v[0], 'item' => $v[1] );
			}, $voci, array_keys( $voci ) ),
		);
	}
	foreach ( $out as $d ) {
		echo '<script type="application/ld+json">' . wp_json_encode( $d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}
}, 5 );
