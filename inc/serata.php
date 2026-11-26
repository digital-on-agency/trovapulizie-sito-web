<?php
/**
 * Survey dopo la serata Wine Venture Club del 25/11/2026, pagina /serata-25-11/ (template page-serata.php).
 * Specifica: vault clients/trovapulizie/projects/sito-web/spec/SPEC-survey-serata-25-11.md (approvata da Guido il 26/11).
 *
 * - tp_serata_definition(): unica fonte di domande e testi. Va in JSON alla pagina (assets/js/serata.js costruisce le
 *   schermate) e alla validazione lato server (tp_serata_validate, funzione pura).
 * - Il nome arriva gia' compilato dal link personale: ?n=Nome%20Cognome (ripulito qui, mai letto dal JS).
 * - Salvataggio nella tabella {prefix}tp_serata_response, nessuna copia su Airtable, nessuna notifica.
 *   Admin: inc/serata-admin.php (Strumenti > Survey serata 25/11: elenco, cancellazione, export CSV).
 * - Guardie riusate dalla Survey OnField (inc/survey.php): origine, nonce, tempo minimo firmato, honeypot, limite
 *   orario per IP hash contato solo dopo un salvataggio riuscito.
 * - Pagina noindex,nofollow, fuori dalla sitemap, mai in cache LiteSpeed, senza tracking ne' banner dei cookie.
 *
 * [ATTENZIONE] Nessuna cifra del round, della valutazione o dei ticket nei testi: la pagina e' raggiungibile da
 * chiunque abbia il link (art. 2468 c.c.). Trovapulizie era startup ospite: mai "evento Trovapulizie".
 */

const TP_SERATA_DB_VERSION   = '1';
const TP_SERATA_MAX_PER_HOUR = 10;

/** True se la richiesta corrente e' la pagina della survey della serata. */
function tp_is_serata_page() {
	return is_singular( 'page' ) && is_page_template( 'page-serata.php' );
}

/** Domande e testi. Chiavi dei campi = colonne della tabella. */
function tp_serata_definition() {
	return array(
		'brand'   => 'Trovapulizie',
		'total'   => 7,
		'welcome' => array(
			'title' => "Com'è andata la serata del 25?",
			'body'  => "Grazie per essere stato con noi mercoledì sera da Beef Bazar. Sono sette domande e servono due minuti: ci aiutano a capire cosa è arrivato della serata e cosa no.",
			'sign'  => 'Christian e Guido',
			'cta'   => 'Inizia',
		),
		'steps'   => array(
			array( 'id' => 'name', 'type' => 'text', 'count' => 1, 'required' => true, 'title' => 'Nome e cognome', 'placeholder' => 'Il tuo nome e cognome', 'max' => 120 ),
			array( 'id' => 'rating', 'type' => 'scale', 'count' => 2, 'required' => true, 'title' => "Com'è andata la serata nel complesso?", 'min' => 1, 'max' => 5, 'low' => 'Così così', 'high' => 'Benissimo' ),
			array( 'id' => 'ricordo', 'type' => 'textarea', 'count' => 3, 'required' => false, 'title' => 'Della presentazione di Trovapulizie, cosa ti è rimasto in mente?', 'placeholder' => 'Anche una parola', 'max' => 2000 ),
			array( 'id' => 'chiarezza', 'type' => 'single', 'count' => 4, 'required' => true, 'title' => 'Il modello di Trovapulizie ti è risultato chiaro?', 'options' => tp_survey_opts( array(
				array( 'chiaro', 'Sì, chiaro' ), array( 'dubbio', 'Abbastanza, ma ho qualche dubbio' ), array( 'poco', 'Poco chiaro' ),
			) ) ),
			array( 'id' => 'dubbio', 'type' => 'textarea', 'count' => 4, 'required' => false, 'when' => array( 'chiarezza', array( 'dubbio', 'poco' ) ), 'title' => 'Se hai un dubbio, scrivilo qui: ti rispondiamo noi.', 'placeholder' => 'Il tuo dubbio', 'max' => 2000 ),
			array( 'id' => 'servizi', 'type' => 'multi', 'count' => 5, 'required' => false, 'title' => 'Usi servizi di pulizia o manutenzione?', 'exclusive' => 'no', 'options' => tp_survey_opts( array(
				array( 'casa', 'Sì, per casa' ), array( 'azienda', "Sì, per un'azienda o un ufficio" ), array( 'no', 'No' ),
			) ) ),
			array( 'id' => 'approfondire', 'type' => 'multi', 'count' => 6, 'required' => false, 'title' => 'Cosa ti piacerebbe approfondire?', 'hint' => 'Puoi scegliere più risposte.', 'exclusive' => 'niente', 'options' => tp_survey_opts( array(
				array( 'modello', 'Come funziona il modello' ), array( 'team', 'Il team' ), array( 'tecnologia', "La tecnologia e l'app" ),
				array( 'partecipare', 'Come partecipare al progetto' ), array( 'servizio', 'Provare il servizio' ), array( 'niente', 'Niente per ora' ),
			) ) ),
			array( 'id' => 'adesso', 'type' => 'single', 'count' => 7, 'required' => true, 'last' => true, 'title' => 'E adesso?', 'other_field' => 'adesso_altro', 'options' => tp_survey_opts( array(
				array( 'sentiamoci', 'Sentiamoci' ), array( 'materiale', 'Vorrei ricevere del materiale' ),
				array( 'piacere', 'È stato un piacere conoscervi' ), array( 'altro', 'Altro', true ),
			) ) ),
		),
		'thanks'  => array( 'title' => 'Grazie%s.', 'body' => 'Ci sentiamo presto.', 'sign' => 'Christian e Guido' ),
		'ui'      => array(
			'next' => 'Avanti', 'skip' => 'Salta', 'send' => 'Invia', 'sending' => 'Invio in corso…', 'back' => 'Indietro',
			'other' => 'Scrivi qui', 'required' => 'Serve una risposta per andare avanti.', 'other_required' => 'Scrivi qualcosa nel campo, oppure scegli un’altra risposta.',
			'privacy' => 'Inviando accetti l’', 'privacy_link' => 'informativa privacy',
			'error' => 'Qualcosa non è andato: riprova tra un momento.', 'expired' => 'La pagina è rimasta aperta troppo a lungo: ricaricala e riprova.',
			'limit' => 'Troppi invii da questa connessione: riprova più tardi.', 'nojs' => 'Per rispondere serve JavaScript attivo nel browser.',
		),
	);
}

function tp_serata_table() {
	global $wpdb;
	return $wpdb->prefix . 'tp_serata_response';
}

function tp_serata_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$charset = $wpdb->get_charset_collate();
	$table   = tp_serata_table();
	dbDelta( "CREATE TABLE {$table} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		created_at datetime NOT NULL,
		name varchar(120) NOT NULL DEFAULT '',
		rating tinyint(3) unsigned NOT NULL DEFAULT 0,
		ricordo text NOT NULL,
		chiarezza varchar(20) NOT NULL DEFAULT '',
		dubbio text NOT NULL,
		servizi varchar(60) NOT NULL DEFAULT '',
		approfondire varchar(120) NOT NULL DEFAULT '',
		adesso varchar(20) NOT NULL DEFAULT '',
		adesso_altro varchar(255) NOT NULL DEFAULT '',
		ip_hash char(16) NOT NULL DEFAULT '',
		user_agent varchar(255) NOT NULL DEFAULT '',
		PRIMARY KEY  (id),
		KEY created_at (created_at)
	) {$charset};" );
	update_option( 'tp_serata_db_version', TP_SERATA_DB_VERSION, false );
}
add_action( 'after_switch_theme', 'tp_serata_install' );
add_action( 'init', function () {
	if ( get_option( 'tp_serata_db_version' ) !== TP_SERATA_DB_VERSION ) {
		tp_serata_install();
	}
} );

/* ---- Pagina: robots, sitemap, cache, titolo, asset -------------------------------------------------- */

add_filter( 'wp_robots', function ( $robots ) {
	if ( tp_is_serata_page() ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
		unset( $robots['max-image-preview'], $robots['follow'] );
	}
	return $robots;
}, 20 );

add_filter( 'wp_sitemaps_posts_query_args', function ( $args, $post_type ) {
	if ( 'page' === $post_type ) {
		$args['meta_query'] = array(
			'relation' => 'AND',
			isset( $args['meta_query'] ) ? $args['meta_query'] : array(),
			array(
				'relation' => 'OR',
				array( 'key' => '_wp_page_template', 'compare' => 'NOT EXISTS' ),
				array( 'key' => '_wp_page_template', 'value' => 'page-serata.php', 'compare' => '!=' ),
			),
		);
	}
	return $args;
}, 21, 2 );

add_action( 'template_redirect', function () {
	if ( ! tp_is_serata_page() ) {
		return;
	}
	header( 'X-Robots-Tag: noindex, nofollow' );
	// Nonce, tempo firmato e nome del link stanno nell'HTML: la pagina non deve restare in cache.
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	nocache_headers();
	do_action( 'litespeed_control_set_nocache', 'serata nonce' );
} );

add_filter( 'pre_get_document_title', function ( $title ) {
	return tp_is_serata_page() ? "Com'è andata la serata del 25? · Trovapulizie" : $title;
}, 30 );

add_action( 'wp_enqueue_scripts', function () {
	if ( ! tp_is_serata_page() ) {
		return;
	}
	// Pagina autonoma come la Survey OnField: stessa pelle (survey.css) con i colori Trovapulizie (serata.css).
	foreach ( array( 'trovapulizie-style', 'trovapulizie-fonts' ) as $h ) {
		wp_dequeue_style( $h );
	}
	foreach ( array( 'trovapulizie-nav-mobile', 'trovapulizie-tp-track' ) as $h ) {
		wp_dequeue_script( $h );
	}
	$base = get_stylesheet_directory_uri();
	wp_enqueue_style( 'tp-survey', $base . '/assets/css/survey.css', array(), tp_theme_version() );
	wp_enqueue_style( 'tp-serata', $base . '/assets/css/serata.css', array( 'tp-survey' ), tp_theme_version() );
	wp_enqueue_script( 'tp-serata', $base . '/assets/js/serata.js', array(), tp_theme_version(), array( 'strategy' => 'defer', 'in_footer' => true ) );
}, 20 );

add_action( 'wp_head', function () {
	if ( tp_is_serata_page() ) {
		echo '<link rel="preload" as="font" type="font/woff2" crossorigin href="' . esc_url( tp_theme_asset( 'assets/fonts/inter-latin.woff2' ) ) . '">' . "\n";
	}
}, 2 );

/** Nome dal link personale (?n=): testo semplice, al massimo 120 caratteri. */
function tp_serata_prefill_name() {
	$n = isset( $_GET['n'] ) && is_string( $_GET['n'] ) ? sanitize_text_field( wp_unslash( $_GET['n'] ) ) : '';
	return mb_substr( $n, 0, 120 );
}

/** Configurazione passata alla pagina: definizione, endpoint, nonce, tempo firmato, nome precompilato. */
function tp_serata_page_config() {
	$ts = time();
	return array(
		'def'      => tp_serata_definition(),
		'endpoint' => esc_url_raw( rest_url( 'tp/v1/serata' ) ),
		'nonce'    => wp_create_nonce( 'tp_survey' ),
		'ts'       => $ts,
		'sig'      => tp_survey_sign( 'ts:' . $ts ),
		'privacy'  => home_url( '/privacy/' ),
		'name'     => tp_serata_prefill_name(),
	);
}

/* ---- Endpoint ---------------------------------------------------------------------------------------- */

add_action( 'rest_api_init', function () {
	register_rest_route( 'tp/v1', '/serata', array(
		'methods'             => 'POST',
		'callback'            => 'tp_serata_rest_create',
		'permission_callback' => '__return_true',
	) );
} );

/** Valida e normalizza le risposte. Ritorna [dati, errori[campo]=>true]. Funzione pura. */
function tp_serata_validate( array $p, array $def ) {
	$steps = array();
	foreach ( $def['steps'] as $s ) {
		$steps[ $s['id'] ] = $s;
	}
	$allowed = function ( $id ) use ( $steps ) {
		return array_column( $steps[ $id ]['options'], 'v' );
	};
	$data = array();
	$errs = array();

	$data['name'] = tp_survey_text( $p['name'] ?? '', 120 );
	if ( '' === $data['name'] ) {
		$errs['name'] = true;
	}
	$rating = is_numeric( $p['rating'] ?? null ) ? (int) $p['rating'] : 0;
	if ( $rating < 1 || $rating > 5 ) {
		$errs['rating'] = true;
	}
	$data['rating']  = $rating;
	$data['ricordo'] = tp_survey_text( $p['ricordo'] ?? '', 2000 );

	foreach ( array( 'chiarezza', 'adesso' ) as $k ) {
		$v = is_scalar( $p[ $k ] ?? null ) ? (string) $p[ $k ] : '';
		if ( ! in_array( $v, $allowed( $k ), true ) ) {
			$errs[ $k ] = true;
		}
		$data[ $k ] = $v;
	}
	// Il dubbio vale solo se il modello non era chiaro; il testo di "Altro" solo con "Altro", e li' e' obbligatorio.
	$data['dubbio']       = in_array( $data['chiarezza'], array( 'dubbio', 'poco' ), true ) ? tp_survey_text( $p['dubbio'] ?? '', 2000 ) : '';
	$data['adesso_altro'] = 'altro' === $data['adesso'] ? tp_survey_text( $p['adesso_altro'] ?? '', 255 ) : '';
	if ( 'altro' === $data['adesso'] && '' === $data['adesso_altro'] ) {
		$errs['adesso_altro'] = true;
	}

	foreach ( array( 'servizi', 'approfondire' ) as $k ) {
		$in  = isset( $p[ $k ] ) && is_array( $p[ $k ] ) ? $p[ $k ] : array();
		$ok  = $allowed( $k );
		$out = array();
		foreach ( $in as $v ) {
			if ( is_scalar( $v ) && in_array( (string) $v, $ok, true ) && ! in_array( (string) $v, $out, true ) ) {
				$out[] = (string) $v;
			}
		}
		// L'opzione esclusiva ("No", "Niente per ora") non sta insieme alle altre.
		$excl = $steps[ $k ]['exclusive'];
		if ( in_array( $excl, $out, true ) && count( $out ) > 1 ) {
			$errs[ $k ] = true;
		}
		$data[ $k ] = implode( ',', $out );
	}
	return array( $data, $errs );
}

function tp_serata_rest_create( WP_REST_Request $req ) {
	global $wpdb;
	$p = $req->get_json_params();
	$p = is_array( $p ) ? $p : $req->get_params();

	// Honeypot: un bot compila il campo nascosto. Risposta di successo finta, nulla viene salvato.
	if ( '' !== trim( (string) ( $p['website'] ?? '' ) ) ) {
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}
	$guard = tp_survey_guard( $p, true );
	if ( $guard ) {
		return $guard;
	}
	if ( tp_survey_rate_limited( 's', TP_SERATA_MAX_PER_HOUR, false ) ) {
		return tp_survey_err( 'tp_survey_limit', 'Rate limit', 429 );
	}
	list( $data, $errs ) = tp_serata_validate( $p, tp_serata_definition() );
	if ( $errs ) {
		return tp_survey_err( 'tp_survey_invalid', 'Invalid', 400, array( 'fields' => array_keys( $errs ) ) );
	}
	$row = array_merge( $data, array(
		'created_at' => current_time( 'mysql', true ),
		'ip_hash'    => tp_survey_ip_hash(),
		'user_agent' => mb_substr( (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 255 ),
	) );
	if ( ! $wpdb->insert( tp_serata_table(), $row ) ) {
		return tp_survey_err( 'tp_survey_db', 'Error', 500 );
	}
	tp_survey_rate_limited( 's', TP_SERATA_MAX_PER_HOUR, true );
	return new WP_REST_Response( array( 'ok' => true ), 201 );
}

require_once __DIR__ . '/serata-admin.php';
