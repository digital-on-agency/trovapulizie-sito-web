<?php
/**
 * Survey dopo la serata Wine Venture Club del 25/11/2026, pagina /serata-25-11/ (template page-serata.php).
 * Specifica: vault clients/trovapulizie/projects/sito-web/spec/SPEC-survey-serata-25-11.md (approvata da Guido il 26/11).
 *
 * - tp_serata_definition(): unica fonte di domande e testi. Va in JSON alla pagina (assets/js/serata.js costruisce le
 *   schermate) e alla validazione lato server (tp_serata_validate, funzione pura).
 * - Il nome arriva gia' compilato dal link personale: ?n=Nome%20Cognome (ripulito qui, mai letto dal JS). Il link puo'
 *   portare anche ?c=<id del Contatto nel gestionale> (recXXXXXXXXXXXXXX): la risposta si collega a quel Contatto.
 * - Salvataggio nella tabella {prefix}tp_serata_response, copia nel gestionale interno (inc/serata-airtable.php),
 *   nessuna notifica. Admin: inc/serata-admin.php (Strumenti > Survey serata 25/11: elenco, cancellazione, export CSV).
 * - Domande v0.3 (testo incollato da Guido il 26/11): nome, telefono ed email sulla stessa schermata (almeno uno dei due), tre voti da 1 a 5, cosa migliorare,
 *   aspetto piu' interessante, interesse a valutare un investimento (con domanda di approfondimento se "No"), "E adesso?".
 * - Video della serata: chi sceglie "Vorrei ricevere materiale" trova il video nella schermata finale, se l'opzione
 *   WordPress tp_serata_video_url e' impostata.
 * - Guardie riusate dalla Survey OnField (inc/survey.php): origine, nonce, tempo minimo firmato, honeypot, limite
 *   orario per IP hash contato solo dopo un salvataggio riuscito.
 * - Pagina noindex,nofollow, fuori dalla sitemap, mai in cache LiteSpeed, senza tracking ne' banner dei cookie.
 *
 * [ATTENZIONE] Nessuna cifra del round, della valutazione o dei ticket nei testi: la pagina e' raggiungibile da
 * chiunque abbia il link (art. 2468 c.c.). Trovapulizie era startup ospite: mai "evento Trovapulizie".
 */

const TP_SERATA_DB_VERSION   = '4'; // 2: contact_ref e colonne at_*; 3: domande v0.3 (le colonne della v1 restano, vuote); 4: telefono ed email separati
const TP_SERATA_MAX_PER_HOUR = 10;

/** True se la richiesta corrente e' la pagina della survey della serata. */
function tp_is_serata_page() {
	return is_singular( 'page' ) && is_page_template( 'page-serata.php' );
}

const TP_SERATA_VIDEO_ID = 112; // libreria media: "Una luce" (una-luce-sito-v1.mp4), video 1 proiettato in sala

/**
 * URL del video della serata: l'opzione tp_serata_video_url se impostata, altrimenti l'allegato TP_SERATA_VIDEO_ID.
 * '' se nessuno dei due esiste. Allegato nel codice perche' le opzioni scritte da WP-CLI non arrivano al sito (cache
 * degli oggetti solo lato web).
 */
function tp_serata_video_url() {
	$url = (string) get_option( 'tp_serata_video_url', '' );
	if ( '' === $url ) {
		$url = (string) wp_get_attachment_url( TP_SERATA_VIDEO_ID );
	}
	return $url;
}

/** Domande e testi. Chiavi dei campi = colonne della tabella. */
function tp_serata_definition() {
	$scale = array( 'Per niente', 'Poco', 'Abbastanza', 'Molto', 'Moltissimo' );
	return array(
		'brand'   => 'Trovapulizie',
		'total'   => 9,
		'welcome' => array(
			'title' => "Com'è andata la II tappa del Wine Venture Club?",
			'body'  => 'Siamo Guido e Christian, founder di Trovapulizie. Nel ringraziarti di essere stato con noi da Beef Bazar, volevamo farti qualche domanda. Ci vogliono solo 2 minuti!',
			'sign'  => '',
			'cta'   => 'Inizia',
		),
		'steps'   => array(
			array( 'id' => 'serata', 'type' => 'scale', 'count' => 1, 'required' => true, 'title' => 'Ti è piaciuta la serata?', 'min' => 1, 'max' => 5, 'labels' => $scale ),
			array( 'id' => 'progetto', 'type' => 'scale', 'count' => 2, 'required' => true, 'title' => 'Ti piace il progetto Trovapulizie?', 'min' => 1, 'max' => 5, 'labels' => $scale ),
			array( 'id' => 'chiaro', 'type' => 'scale', 'count' => 3, 'required' => true, 'title' => 'Ti è chiaro il modello di Trovapulizie?', 'min' => 1, 'max' => 5, 'labels' => $scale ),
			array( 'id' => 'migliorare', 'type' => 'textarea', 'count' => 4, 'required' => false, 'title' => 'Aiutaci a migliorare: cosa non ti è sembrato chiaro o cosa vorresti approfondire?', 'placeholder' => 'Scrivi qui', 'max' => 2000 ),
			array( 'id' => 'interesse', 'type' => 'single', 'count' => 5, 'required' => true, 'title' => 'Quale aspetto trovi più interessante?', 'other_field' => 'interesse_altro', 'options' => tp_survey_opts( array(
				array( 'piattaforma', 'La piattaforma (gestionale per aziende e professionisti)' ), array( 'app', "L'applicazione (marketplace)" ),
				array( 'progetto', "L'intero progetto" ), array( 'altro', 'Altro', true ),
			) ) ),
			array( 'id' => 'investimento', 'type' => 'single', 'count' => 6, 'required' => true, 'title' => 'Potrebbe interessarti valutare un investimento in Trovapulizie?', 'options' => tp_survey_opts( array(
				array( 'si', 'Sì' ), array( 'forse', 'Forse, vorrei prima approfondire' ), array( 'no', 'No, non fa per me in questa fase' ),
			) ) ),
			array( 'id' => 'investimento_serve', 'type' => 'textarea', 'count' => 6, 'required' => false, 'when' => array( 'investimento', array( 'no' ) ), 'title' => 'Cosa ti servirebbe vedere o chiarire per valutare un investimento?', 'placeholder' => 'Scrivi qui', 'max' => 2000 ),
			array( 'id' => 'adesso', 'type' => 'single', 'count' => 7, 'required' => true, 'title' => 'E adesso?', 'options' => tp_survey_opts( array(
				array( 'materiale', 'Vorrei ricevere materiale (es. pitch deck, one-pager, link al video)' ), array( 'contatto', 'Restiamo in contatto' ),
				array( 'prossima', 'Ci vediamo alla prossima tappa!' ),
			) ) ),
			array( 'id' => 'name', 'type' => 'text', 'count' => 8, 'required' => true, 'title' => 'Nome e cognome', 'placeholder' => 'Il tuo nome e cognome', 'max' => 120, 'autocomplete' => 'name' ),
			array( 'id' => 'recapiti', 'type' => 'contact', 'count' => 9, 'required' => true, 'last' => true, 'title' => 'Come ti ricontattiamo?', 'hint' => 'Basta uno dei due: ci serve per mandarti quello che ci chiedi.', 'fields' => array(
				array( 'id' => 'telefono', 'label' => 'Numero di telefono', 'type' => 'tel', 'autocomplete' => 'tel', 'placeholder' => '+39 333 123 4567', 'max' => 40 ),
				array( 'id' => 'email', 'label' => 'Email', 'type' => 'email', 'autocomplete' => 'email', 'placeholder' => 'nome@esempio.it', 'max' => 120 ),
			) ),
		),
		'thanks'  => array( 'title' => 'Grazie%s.', 'body' => 'Ci sentiamo presto.', 'sign' => 'Christian e Guido', 'video' => 'Il video della serata', 'video_when' => array( 'adesso', 'materiale' ) ),
		'ui'      => array(
			'next' => 'Avanti', 'skip' => 'Salta', 'send' => 'Invia', 'sending' => 'Invio in corso…', 'back' => 'Indietro',
			'other' => 'Scrivi qui', 'required' => 'Serve una risposta per andare avanti.', 'other_required' => 'Scrivi qualcosa nel campo, oppure scegli un’altra risposta.',
			'contact_required' => 'Lascia almeno il numero di telefono o l’email.',
			'phone_invalid' => 'Il numero di telefono non sembra valido: controllalo oppure lascia vuoto.',
			'email_invalid' => 'L’email non sembra valida: controllala oppure lascia vuoto.',
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
	// Colonne dalla rating in giu' fino a adesso_altro (tranne adesso): domande della v1, non piu' scritte ma lasciate
	// (dbDelta non toglie colonne). Le text NOT NULL della v1 ricevono '' in tp_serata_legacy_defaults().
	dbDelta( "CREATE TABLE {$table} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		created_at datetime NOT NULL,
		name varchar(120) NOT NULL DEFAULT '',
		telefono varchar(40) NOT NULL DEFAULT '',
		email varchar(120) NOT NULL DEFAULT '',
		serata tinyint(3) unsigned NOT NULL DEFAULT 0,
		progetto tinyint(3) unsigned NOT NULL DEFAULT 0,
		chiaro tinyint(3) unsigned NOT NULL DEFAULT 0,
		migliorare text NULL,
		interesse varchar(20) NOT NULL DEFAULT '',
		interesse_altro varchar(255) NOT NULL DEFAULT '',
		investimento varchar(10) NOT NULL DEFAULT '',
		investimento_serve text NULL,
		rating tinyint(3) unsigned NOT NULL DEFAULT 0,
		ricordo text NOT NULL,
		chiarezza varchar(20) NOT NULL DEFAULT '',
		dubbio text NOT NULL,
		servizi varchar(60) NOT NULL DEFAULT '',
		approfondire varchar(120) NOT NULL DEFAULT '',
		adesso varchar(20) NOT NULL DEFAULT '',
		adesso_altro varchar(255) NOT NULL DEFAULT '',
		contact_ref varchar(17) NOT NULL DEFAULT '',
		ip_hash char(16) NOT NULL DEFAULT '',
		user_agent varchar(255) NOT NULL DEFAULT '',
		at_record_id varchar(17) NOT NULL DEFAULT '',
		at_status varchar(10) NOT NULL DEFAULT 'pending',
		at_tries tinyint(3) unsigned NOT NULL DEFAULT 0,
		at_error varchar(255) NOT NULL DEFAULT '',
		at_synced_at datetime NULL DEFAULT NULL,
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
	return tp_is_serata_page() ? "Com'è andata la II tappa del Wine Venture Club? · Trovapulizie" : $title;
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

/** Id del Contatto nel gestionale (record Airtable): esattamente rec + 14 caratteri alfanumerici, altrimenti ''. */
function tp_serata_contact_ref( $v ) {
	$v = is_string( $v ) ? trim( $v ) : '';
	return preg_match( '/^rec[A-Za-z0-9]{14}$/', $v ) ? $v : '';
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
		'contact'  => tp_serata_contact_ref( isset( $_GET['c'] ) ? wp_unslash( $_GET['c'] ) : '' ),
		'logo'     => tp_theme_asset( 'assets/img/logo.png' ),
		'video'    => tp_serata_video_url(),
		'poster'   => (string) get_option( 'tp_serata_video_poster', '' ),
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

/** Telefono facoltativo: cifre, spazi e separatori, almeno 7 cifre. Ritorna [valore, valido]. */
function tp_serata_phone_value( $v ) {
	$v = tp_survey_text( $v, 40 );
	return array( $v, '' === $v || ( preg_match( '/^\+?[0-9 .\/()-]+$/', $v ) && strlen( preg_replace( '/\D+/', '', $v ) ) >= 7 ) );
}

/** Email facoltativa. Ritorna [valore in minuscolo, valida]. */
function tp_serata_email_value( $v ) {
	$v = strtolower( tp_survey_text( $v, 120 ) );
	return array( $v, '' === $v || (bool) is_email( $v ) );
}

/** Valida e normalizza le risposte. Ritorna [dati, errori[campo]=>true]. Funzione pura. */
function tp_serata_validate( array $p, array $def ) {
	$steps = array();
	foreach ( $def['steps'] as $s ) {
		$steps[ $s['id'] ] = $s;
	}
	$data = array();
	$errs = array();

	$data['name'] = tp_survey_text( $p['name'] ?? '', 120 );
	if ( '' === $data['name'] ) {
		$errs['name'] = true;
	}
	list( $data['telefono'], $ok ) = tp_serata_phone_value( $p['telefono'] ?? '' );
	if ( ! $ok ) {
		$errs['telefono'] = true;
	}
	list( $data['email'], $ok ) = tp_serata_email_value( $p['email'] ?? '' );
	if ( ! $ok ) {
		$errs['email'] = true;
	}
	// Almeno un recapito (Guido 26/11 sera): l'errore va sul telefono, il client torna alla schermata dei recapiti.
	if ( '' === $data['telefono'] && '' === $data['email'] ) {
		$errs['telefono'] = true;
	}
	$data['contact_ref'] = tp_serata_contact_ref( $p['contact'] ?? '' );

	foreach ( array( 'serata', 'progetto', 'chiaro' ) as $k ) {
		$n = is_numeric( $p[ $k ] ?? null ) ? (int) $p[ $k ] : 0;
		if ( $n < 1 || $n > 5 ) {
			$errs[ $k ] = true;
		}
		$data[ $k ] = $n;
	}
	$data['migliorare'] = tp_survey_text( $p['migliorare'] ?? '', 2000 );

	foreach ( array( 'interesse', 'investimento', 'adesso' ) as $k ) {
		$v = is_scalar( $p[ $k ] ?? null ) ? (string) $p[ $k ] : '';
		if ( ! in_array( $v, array_column( $steps[ $k ]['options'], 'v' ), true ) ) {
			$errs[ $k ] = true;
		}
		$data[ $k ] = $v;
	}
	// Il testo di "Altro" vale solo con "Altro", e li' e' obbligatorio; la domanda di approfondimento solo con "No".
	$data['interesse_altro'] = 'altro' === $data['interesse'] ? tp_survey_text( $p['interesse_altro'] ?? '', 255 ) : '';
	if ( 'altro' === $data['interesse'] && '' === $data['interesse_altro'] ) {
		$errs['interesse_altro'] = true;
	}
	$data['investimento_serve'] = 'no' === $data['investimento'] ? tp_survey_text( $p['investimento_serve'] ?? '', 2000 ) : '';
	return array( $data, $errs );
}

/** Valori per le colonne della v1 (text NOT NULL senza default): la riga nuova le lascia vuote. */
function tp_serata_legacy_defaults() {
	return array( 'ricordo' => '', 'dubbio' => '' );
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
	$row = array_merge( tp_serata_legacy_defaults(), $data, array(
		'created_at' => current_time( 'mysql', true ),
		'ip_hash'    => tp_survey_ip_hash(),
		'user_agent' => mb_substr( (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 255 ),
	) );
	if ( ! $wpdb->insert( tp_serata_table(), $row ) ) {
		return tp_survey_err( 'tp_survey_db', 'Error', 500 );
	}
	tp_survey_rate_limited( 's', TP_SERATA_MAX_PER_HOUR, true );
	do_action( 'tp_serata_saved', (int) $wpdb->insert_id );
	return new WP_REST_Response( array( 'ok' => true ), 201 );
}

require_once __DIR__ . '/serata-admin.php';
require_once __DIR__ . '/survey-risposte.php';
require_once __DIR__ . '/serata-airtable.php';
