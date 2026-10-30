<?php
/**
 * Survey Dubai v2 del prodotto OnField, pagina /survey-dubai/ (template page-survey.php).
 * Sorgente di testi, domande, flusso e diramazioni: vault clients/trovapulizie/projects/gestionale-saas/survey-dubai-v2.md
 * (approvata da Guido il 30/10/2026). Documentazione: docs/survey-dubai.md.
 *
 * - tp_survey_definition(): unica fonte di domande e testi. Va in JSON alla pagina (assets/js/survey.js costruisce le
 *   schermate) e alla validazione lato server. Un cambio di testo o di domanda e' una nuova versione (TP_SURVEY_VERSION):
 *   per questo i testi non stanno in Carbon Fields.
 * - Salvataggio nel database di WordPress (tabella {prefix}tp_survey_response, survey_version = 2), nessun webhook.
 *   Copia di ogni risposta nel gestionale interno (Airtable): inc/survey-airtable.php.
 *   Admin: inc/survey-admin.php (elenco, dettaglio, cancellazione, export CSV).
 * - Endpoint POST /wp-json/tp/v1/survey (+ /survey/feedback): nonce, tempo minimo dal caricamento firmato (anti-bot),
 *   honeypot, rate limit per IP (hash, mai l'IP in chiaro), consenso obbligatorio, almeno WhatsApp o email.
 * - Pagina noindex,nofollow (meta + X-Robots-Tag), fuori dalla sitemap, mai in cache LiteSpeed (il nonce deve restare
 *   fresco), senza tracking del sito (GTM, Clarity, iubenda): il form raccoglie dati personali e il marchio e' OnField.
 *
 * [ATTENZIONE] La v1 React dello stesso indirizzo sta sul portale (app.trovapulizie.com): al cutover la serve WordPress,
 * vedi cutover-checklist N2. Questo file non la rimanda all'app (sarebbe un ciclo, vedi inc/redirects.php).
 */

const TP_SURVEY_VERSION     = 2;
const TP_SURVEY_DB_VERSION  = '2'; // 2: colonne at_* della copia nel gestionale interno (inc/survey-airtable.php)
const TP_SURVEY_BRAND       = 'OnField';
const TP_SURVEY_MIN_SECONDS = 12;
const TP_SURVEY_MAX_PER_HOUR = 10; // salvataggi per IP e per ora
const TP_SURVEY_CONSENT_REF = 'onfield-survey-v2';

/** True se la richiesta corrente e' la pagina della survey. */
function tp_is_survey_page() {
	return is_singular( 'page' ) && is_page_template( 'page-survey.php' );
}

/** Opzioni [valore, etichetta, (other)]: other = apre il campo di testo. */
function tp_survey_opts( array $rows ) {
	$out = array();
	foreach ( $rows as $r ) {
		$out[] = array( 'v' => $r[0], 'l' => $r[1], 'other' => ! empty( $r[2] ) );
	}
	return $out;
}

/** Domande, testi e diramazioni della v2. Chiavi dei campi = colonne della tabella. */
function tp_survey_definition() {
	$brand = TP_SURVEY_BRAND;
	return array(
		'version' => TP_SURVEY_VERSION,
		'brand'   => $brand,
		'welcome' => array(
			'title' => 'Running a cleaning business in Dubai?',
			'body'  => "We're building a tool for cleaning companies in the UAE. Before we launch, we want to understand how you work today, and what slows you down.",
			'cta'   => 'Start →',
			'note'  => 'About 2 minutes · Your answers stay private',
		),
		'steps'   => array(
			array( 'id' => 'role', 'type' => 'single', 'count' => 1, 'title' => "What's your role in the business?", 'options' => tp_survey_opts( array(
				array( 'owner', 'Owner / co-founder' ), array( 'manager', 'Operations or general manager' ),
				array( 'supervisor', 'Supervisor / team leader' ), array( 'other', 'Other' ),
			) ) ),
			array( 'id' => 'business_type', 'type' => 'single', 'count' => 2, 'title' => 'What kind of cleaning do you mainly do?', 'hint' => 'Choose the one that brings most of your revenue.', 'options' => tp_survey_opts( array(
				array( 'maid_hourly', 'Hourly maid service (homes)' ), array( 'deep_cleaning', 'Residential deep cleaning / move-in, move-out' ),
				array( 'short_term_rental', 'Short-term rental / holiday home turnover' ), array( 'commercial', 'Offices and commercial spaces' ),
				array( 'facility_management', 'Facility management / building maintenance' ), array( 'hospitality', 'Hotels and hospitality' ),
				array( 'other', 'Other' ),
			) ) ),
			array( 'id' => 'team_size', 'type' => 'single', 'count' => 3, 'title' => 'How many cleaners work for you?', 'hint' => "Including part-time and freelancers. Don't count office staff.", 'options' => tp_survey_opts( array(
				array( '1-5', '1–5' ), array( '6-15', '6–15' ), array( '16-50', '16–50' ), array( '51-150', '51–150' ), array( '150+', 'More than 150' ),
			) ) ),
			array( 'id' => 'weekly_jobs', 'type' => 'single', 'count' => 4, 'title' => 'About how many jobs do you complete per week?', 'options' => tp_survey_opts( array(
				array( '<20', 'Fewer than 20' ), array( '20-100', '20–100' ), array( '100-300', '100–300' ), array( '300+', 'More than 300' ),
			) ) ),
			array( 'id' => 'tools', 'type' => 'multi', 'count' => 5, 'title' => 'How do you schedule jobs and manage your team today?', 'hint' => 'Select all that apply.', 'other_field' => 'tools_other', 'options' => tp_survey_opts( array(
				array( 'whatsapp', 'WhatsApp groups and calls' ), array( 'spreadsheets', 'Spreadsheets (Excel, Google Sheets)' ),
				array( 'paper', 'Paper or whiteboard' ), array( 'software', 'Booking or field-service software' ),
				array( 'accounting_only', 'Accounting software only (e.g. Zoho, QuickBooks)' ), array( 'other', 'Other', true ),
			) ) ),
			array( 'id' => 'software_names', 'type' => 'multi', 'count' => 5, 'when' => array( 'tools', 'software' ), 'title' => 'Which software do you use?', 'hint' => 'Select all that apply.', 'other_field' => 'software_other', 'options' => tp_survey_opts( array(
				array( 'bookingkoala', 'BookingKoala' ), array( 'jobber', 'Jobber' ), array( 'launch27', 'Launch27' ), array( 'servicem8', 'ServiceM8' ),
				array( 'connecteam', 'Connecteam' ), array( 'in_house', 'Built in-house' ), array( 'other', 'Other', true ),
			) ) ),
			array( 'id' => 'challenge', 'type' => 'single', 'count' => 6, 'title' => "What's your biggest headache right now?", 'hint' => 'Pick the one that costs you the most time or money.', 'other_field' => 'challenge_other', 'options' => tp_survey_opts( array(
				array( 'new_clients', 'Finding new clients' ), array( 'hiring', 'Hiring and keeping good cleaners' ),
				array( 'scheduling', 'Scheduling and last-minute changes' ), array( 'quality', 'Checking work quality on site' ),
				array( 'attendance', 'Tracking hours and attendance' ), array( 'invoicing', 'Invoicing and getting paid on time' ),
				array( 'supplies', 'Supplies and inventory' ), array( 'other', 'Something else', true ),
			) ) ),
			array( 'id' => 'software_spend', 'type' => 'single', 'count' => 7, 'title' => 'How much do you spend on business software today, per month?', 'hint' => 'All tools together: scheduling, accounting, messaging, etc.', 'options' => tp_survey_opts( array(
				array( 'none', 'Nothing' ), array( '<100', 'Under AED 100' ), array( '100-500', 'AED 100–500' ), array( '500-1500', 'AED 500–1,500' ),
				array( '1500+', 'More than AED 1,500' ), array( 'unknown', "I don't know" ),
			) ) ),
			array( 'id' => 'price_expectation', 'type' => 'single', 'count' => 8, 'title' => 'What would be a fair monthly price for one tool that handles scheduling, staff, supplies and invoicing for your business?', 'options' => tp_survey_opts( array(
				array( '<100', 'Under AED 100' ), array( '100-250', 'AED 100–250' ), array( '250-500', 'AED 250–500' ), array( '500-1000', 'AED 500–1,000' ),
				array( '1000+', 'More than AED 1,000' ), array( 'free_only', "I'd only use a free tool" ),
			) ) ),
			array( 'id' => 'interest', 'type' => 'single', 'count' => 9, 'title' => "We're opening {$brand} to cleaning companies in Dubai.", 'hint' => 'There\'s a free plan with no time limit, and paid plans for bigger teams. Would you like to try it?', 'options' => tp_survey_opts( array(
				array( 'yes', "Yes, I'd like to try it" ), array( 'demo', 'Show me a demo first' ), array( 'later', 'Maybe later' ), array( 'no', 'Not for me' ),
			) ) ),
		),
		// Diramazioni: valore di "interest" -> ramo. A/B/C passano dal contatto, D salta (link "Leave your contact anyway").
		'branches' => array( 'yes' => 'A', 'demo' => 'B', 'later' => 'C', 'no' => 'D' ),
		'contact'  => array(
			'count'   => 10,
			'titles'  => array(
				'A' => array( 'Where should we send your access?', 'Get my access →' ),
				'B' => array( 'Where can we reach you to set up the demo?', 'Book my demo →' ),
				'C' => array( 'Want us to keep you posted?', 'Keep me posted →' ),
			),
			'fields'  => array(
				'name'     => array( 'Name', 'Your name' ),
				'whatsapp' => array( 'WhatsApp', '+971 5x xxx xxxx' ),
				'email'    => array( 'Email', 'you@company.com' ),
				'company'  => array( 'Company name (optional)', 'Your company' ),
			),
			'helper'  => 'WhatsApp or email, whichever you prefer.',
			'consent' => 'I agree that ' . $brand . ' may contact me about this survey and the product.',
			'privacy' => 'Privacy policy',
			'skip'    => 'Leave your contact anyway',
		),
		'feedback' => array(
			'title'       => 'Anything else we should know?',
			'hint'        => "A feature you need, a problem we didn't ask about, anything.",
			'placeholder' => 'Type here (optional)',
			'send'        => 'Send →',
			'skip'        => 'Skip',
		),
		'thanks'   => array(
			'A' => array( 'Thank you!', "We'll send your access within 2 working days, on WhatsApp or email." ),
			'B' => array( 'Thank you!', "We'll contact you within 2 working days to pick a time for the demo." ),
			'C' => array( 'Thank you!', "We'll let you know when {$brand} is live in Dubai. No spam." ),
			'D' => array( 'Thanks for your time.', 'Your answers help us build something useful for cleaning businesses in the UAE.' ),
		),
		'ui'       => array(
			'next'    => 'Next →',
			'back'    => 'Back',
			'other'   => 'Please specify (optional)',
			'sending' => 'Sending…',
			'error'   => 'Something went wrong. Please try again.',
			'expired' => 'This page has expired. Please reload it and try again.',
			'limit'   => 'Too many attempts. Please try again later.',
			'invalid' => 'Please check the highlighted fields.',
		),
	);
}

/* ---- Tabella ---------------------------------------------------------------------------------------- */

function tp_survey_table() {
	global $wpdb;
	return $wpdb->prefix . 'tp_survey_response';
}

function tp_survey_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$charset = $wpdb->get_charset_collate();
	$table   = tp_survey_table();
	dbDelta( "CREATE TABLE {$table} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		created_at datetime NOT NULL,
		survey_version tinyint(3) unsigned NOT NULL DEFAULT 2,
		lang varchar(8) NOT NULL DEFAULT 'en',
		branch char(1) NOT NULL DEFAULT '',
		role varchar(40) NOT NULL DEFAULT '',
		business_type varchar(40) NOT NULL DEFAULT '',
		team_size varchar(40) NOT NULL DEFAULT '',
		weekly_jobs varchar(40) NOT NULL DEFAULT '',
		tools varchar(255) NOT NULL DEFAULT '',
		tools_other varchar(255) NOT NULL DEFAULT '',
		software_names varchar(255) NOT NULL DEFAULT '',
		software_other varchar(255) NOT NULL DEFAULT '',
		challenge varchar(40) NOT NULL DEFAULT '',
		challenge_other varchar(255) NOT NULL DEFAULT '',
		software_spend varchar(40) NOT NULL DEFAULT '',
		price_expectation varchar(40) NOT NULL DEFAULT '',
		interest varchar(40) NOT NULL DEFAULT '',
		name varchar(120) NOT NULL DEFAULT '',
		whatsapp varchar(40) NOT NULL DEFAULT '',
		email varchar(190) NOT NULL DEFAULT '',
		company varchar(160) NOT NULL DEFAULT '',
		feedback text NOT NULL,
		consent tinyint(1) NOT NULL DEFAULT 0,
		consent_at datetime DEFAULT NULL,
		consent_ref varchar(60) NOT NULL DEFAULT '',
		ip_hash char(16) NOT NULL DEFAULT '',
		user_agent varchar(255) NOT NULL DEFAULT '',
		at_status varchar(10) NOT NULL DEFAULT 'pending',
		at_tries tinyint(3) unsigned NOT NULL DEFAULT 0,
		at_error varchar(255) NOT NULL DEFAULT '',
		at_record_id varchar(20) NOT NULL DEFAULT '',
		at_contact_id varchar(20) NOT NULL DEFAULT '',
		at_opp_id varchar(20) NOT NULL DEFAULT '',
		at_synced_at datetime DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY survey_version (survey_version),
		KEY created_at (created_at)
	) {$charset};" );
	update_option( 'tp_survey_db_version', TP_SURVEY_DB_VERSION, false );
}
add_action( 'after_switch_theme', 'tp_survey_install' );
// Dopo un deploy del tema senza riattivarlo: la versione dello schema decide, un solo get_option (in cache) per richiesta.
add_action( 'init', function () {
	if ( get_option( 'tp_survey_db_version' ) !== TP_SURVEY_DB_VERSION ) {
		tp_survey_install();
	}
} );

/* ---- Pagina: robots, sitemap, cache, tracking ------------------------------------------------------- */

add_filter( 'wp_robots', function ( $robots ) {
	if ( tp_is_survey_page() ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
		unset( $robots['max-image-preview'], $robots['follow'] );
	}
	return $robots;
}, 20 );

// Fuori dalla sitemap: si aggiunge una condizione a quella gia' impostata per la thank-you (functions.php).
add_filter( 'wp_sitemaps_posts_query_args', function ( $args, $post_type ) {
	if ( 'page' === $post_type ) {
		$args['meta_query'] = array(
			'relation' => 'AND',
			isset( $args['meta_query'] ) ? $args['meta_query'] : array(),
			array(
				'relation' => 'OR',
				array( 'key' => '_wp_page_template', 'compare' => 'NOT EXISTS' ),
				array( 'key' => '_wp_page_template', 'value' => 'page-survey.php', 'compare' => '!=' ),
			),
		);
	}
	return $args;
}, 20, 2 );

add_action( 'template_redirect', function () {
	if ( ! tp_is_survey_page() ) {
		return;
	}
	header( 'X-Robots-Tag: noindex, nofollow' );
	// Il nonce e il tempo firmato stanno nell'HTML: la pagina non deve restare in cache.
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	nocache_headers();
	do_action( 'litespeed_control_set_nocache', 'survey nonce' );
} );

add_filter( 'pre_get_document_title', function ( $title ) {
	return tp_is_survey_page() ? TP_SURVEY_BRAND . ' · Quick survey for cleaning companies' : $title;
}, 30 );

add_filter( 'language_attributes', function ( $out ) {
	return tp_is_survey_page() ? 'lang="en"' : $out;
} );

add_action( 'wp_enqueue_scripts', function () {
	if ( ! tp_is_survey_page() ) {
		return;
	}
	// Pagina autonoma: niente foglio, font e script del sito (Poppins, navbar mobile, dataLayer). Pelle del Figma della v1: Inter (locale).
	foreach ( array( 'trovapulizie-style', 'trovapulizie-fonts' ) as $h ) {
		wp_dequeue_style( $h );
	}
	foreach ( array( 'trovapulizie-nav-mobile', 'trovapulizie-tp-track' ) as $h ) {
		wp_dequeue_script( $h );
	}
	wp_enqueue_style( 'tp-survey', get_stylesheet_directory_uri() . '/assets/css/survey.css', array(), tp_theme_version() );
	wp_enqueue_script( 'tp-survey', get_stylesheet_directory_uri() . '/assets/js/survey.js', array(), tp_theme_version(), array( 'strategy' => 'defer', 'in_footer' => true ) );
}, 20 );

// Inter in locale (sottoinsieme latin, variabile 400-700): nessuna richiesta a Google, nessun cookie, un file da 48 KB.
add_action( 'wp_head', function () {
	if ( tp_is_survey_page() ) {
		echo '<link rel="preload" as="font" type="font/woff2" crossorigin href="' . esc_url( tp_theme_asset( 'assets/fonts/inter-latin.woff2' ) ) . '">' . "\n";
	}
}, 2 );

/** Configurazione passata alla pagina: definizione, endpoint, nonce e tempo firmato. */
function tp_survey_page_config() {
	$ts = time();
	return array(
		'def'      => tp_survey_definition(),
		'endpoint' => esc_url_raw( rest_url( 'tp/v1/survey' ) ),
		'nonce'    => wp_create_nonce( 'tp_survey' ),
		'ts'       => $ts,
		'sig'      => tp_survey_sign( 'ts:' . $ts ),
		'privacy'  => home_url( '/privacy/' ),
		'min'      => TP_SURVEY_MIN_SECONDS,
	);
}

function tp_survey_sign( $msg ) {
	return hash_hmac( 'sha256', $msg, wp_salt( 'auth' ) );
}

/* ---- Endpoint ---------------------------------------------------------------------------------------- */

add_action( 'rest_api_init', function () {
	register_rest_route( 'tp/v1', '/survey', array(
		'methods'             => 'POST',
		'callback'            => 'tp_survey_rest_create',
		'permission_callback' => '__return_true',
	) );
	register_rest_route( 'tp/v1', '/survey/feedback', array(
		'methods'             => 'POST',
		'callback'            => 'tp_survey_rest_feedback',
		'permission_callback' => '__return_true',
	) );
} );

function tp_survey_client_ip() {
	$ip = (string) ( $_SERVER['REMOTE_ADDR'] ?? '' );
	// Dietro un proxy (CDN) REMOTE_ADDR e' un indirizzo privato o riservato: in quel caso vale il primo X-Forwarded-For.
	$public = filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
	if ( ! $public && ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$first = trim( explode( ',', (string) $_SERVER['HTTP_X_FORWARDED_FOR'] )[0] );
		if ( filter_var( $first, FILTER_VALIDATE_IP ) ) {
			$ip = $first;
		}
	}
	return $ip;
}

function tp_survey_ip_hash() {
	return substr( hash_hmac( 'sha256', tp_survey_client_ip(), wp_salt( 'auth' ) ), 0, 16 );
}

/**
 * Rate limit semplice per IP (hash): al massimo $max salvataggi per ora. Con $consume = false controlla soltanto,
 * con true conta una chiamata. Si conta solo dopo un salvataggio riuscito: chi sbaglia i campi non si blocca da solo,
 * e un invio non valido non scrive nulla. True se il limite e' raggiunto.
 */
function tp_survey_rate_limited( $bucket, $max, $consume ) {
	$key   = 'tp_svrl_' . $bucket . '_' . tp_survey_ip_hash();
	$count = (int) get_transient( $key );
	if ( $count >= $max ) {
		return true;
	}
	if ( $consume ) {
		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	}
	return false;
}

function tp_survey_err( $code, $message, $status, $extra = array() ) {
	return new WP_Error( $code, $message, array_merge( array( 'status' => $status ), $extra ) );
}

/** Controlli comuni: origine, nonce, tempo. Ritorna null se ok, WP_Error altrimenti. */
function tp_survey_guard( $p, $check_time ) {
	$origin = (string) ( $_SERVER['HTTP_ORIGIN'] ?? '' );
	if ( '' !== $origin && wp_parse_url( $origin, PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
		return tp_survey_err( 'tp_survey_origin', 'Forbidden', 403 );
	}
	if ( ! wp_verify_nonce( (string) ( $p['nonce'] ?? '' ), 'tp_survey' ) ) {
		return tp_survey_err( 'tp_survey_expired', 'Expired', 403 );
	}
	if ( $check_time ) {
		$ts  = (int) ( $p['ts'] ?? 0 );
		$sig = (string) ( $p['sig'] ?? '' );
		if ( ! $ts || ! hash_equals( tp_survey_sign( 'ts:' . $ts ), $sig ) || time() - $ts > DAY_IN_SECONDS ) {
			return tp_survey_err( 'tp_survey_expired', 'Expired', 403 );
		}
		if ( time() - $ts < TP_SURVEY_MIN_SECONDS ) {
			return tp_survey_err( 'tp_survey_fast', 'Too fast', 429 );
		}
	}
	return null;
}

function tp_survey_text( $v, $max ) {
	$v = is_scalar( $v ) ? (string) $v : '';
	$v = trim( preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', wp_unslash( $v ) ) );
	return mb_substr( $v, 0, $max );
}

/** Valida e normalizza le risposte. Ritorna [dati, errori[campo]=>true]. Funzione pura (testata in tools/). */
function tp_survey_validate( array $p, array $def ) {
	$data = array();
	$errs = array();
	$steps = array();
	foreach ( $def['steps'] as $s ) {
		$steps[ $s['id'] ] = $s;
	}
	$allowed = function ( $step ) {
		return array_column( $step['options'], 'v' );
	};
	foreach ( array( 'role', 'business_type', 'team_size', 'weekly_jobs', 'challenge', 'software_spend', 'price_expectation', 'interest' ) as $k ) {
		$v = is_scalar( $p[ $k ] ?? null ) ? (string) $p[ $k ] : '';
		if ( ! in_array( $v, $allowed( $steps[ $k ] ), true ) ) {
			$errs[ $k ] = true;
		}
		$data[ $k ] = $v;
	}
	$multi = function ( $k ) use ( $p, $steps, $allowed ) {
		$in  = isset( $p[ $k ] ) && is_array( $p[ $k ] ) ? $p[ $k ] : array();
		$ok  = $allowed( $steps[ $k ] );
		$out = array();
		foreach ( $in as $v ) {
			if ( is_scalar( $v ) && in_array( (string) $v, $ok, true ) && ! in_array( (string) $v, $out, true ) ) {
				$out[] = (string) $v;
			}
		}
		return $out;
	};
	$tools = $multi( 'tools' );
	if ( ! $tools ) {
		$errs['tools'] = true;
	}
	$software = in_array( 'software', $tools, true ) ? $multi( 'software_names' ) : array();
	$data['tools']          = implode( ',', $tools );
	$data['software_names'] = implode( ',', $software );
	// I testi liberi si tengono solo se l'opzione "altro" e' scelta.
	$data['tools_other']     = in_array( 'other', $tools, true ) ? tp_survey_text( $p['tools_other'] ?? '', 200 ) : '';
	$data['software_other']  = in_array( 'other', $software, true ) ? tp_survey_text( $p['software_other'] ?? '', 200 ) : '';
	$data['challenge_other'] = 'other' === $data['challenge'] ? tp_survey_text( $p['challenge_other'] ?? '', 200 ) : '';

	$branch = $def['branches'][ $data['interest'] ] ?? '';
	$data['branch'] = $branch;

	$data['name']     = tp_survey_text( $p['name'] ?? '', 120 );
	$data['whatsapp'] = tp_survey_text( $p['whatsapp'] ?? '', 40 );
	$data['email']    = tp_survey_text( $p['email'] ?? '', 190 );
	$data['company']  = tp_survey_text( $p['company'] ?? '', 160 );
	$data['feedback'] = tp_survey_text( $p['feedback'] ?? '', 2000 );
	$consent          = ! empty( $p['consent'] ) && ( true === $p['consent'] || '1' === (string) $p['consent'] || 'true' === $p['consent'] );

	$has_contact = '' !== $data['name'] || '' !== $data['whatsapp'] || '' !== $data['email'];
	// Rami A/B/C: contatto obbligatorio. Ramo D: facoltativo, ma se lo lascia vale tutto come per gli altri.
	if ( 'D' !== $branch || $has_contact ) {
		if ( '' === $data['name'] ) {
			$errs['name'] = true;
		}
		$wa_digits = preg_replace( '/\D+/', '', $data['whatsapp'] );
		if ( '' !== $data['whatsapp'] && ( strlen( $wa_digits ) < 7 || strlen( $wa_digits ) > 15 || preg_match( '/[^\d\s+()\-.]/', $data['whatsapp'] ) ) ) {
			$errs['whatsapp'] = true;
		}
		if ( '' !== $data['email'] && ! is_email( $data['email'] ) ) {
			$errs['email'] = true;
		}
		if ( '' === $data['whatsapp'] && '' === $data['email'] ) {
			$errs['whatsapp'] = true;
			$errs['email']    = true;
		}
		if ( ! $consent ) {
			$errs['consent'] = true;
		}
	}
	$data['consent'] = $has_contact && $consent ? 1 : 0;
	return array( $data, $errs );
}

function tp_survey_feedback_key( $id ) {
	return tp_survey_sign( 'fb:' . (int) $id );
}

function tp_survey_rest_create( WP_REST_Request $req ) {
	global $wpdb;
	$p = $req->get_json_params();
	$p = is_array( $p ) ? $p : $req->get_params();

	// Honeypot: un bot compila il campo nascosto. Risposta di successo finta, nulla viene salvato.
	if ( '' !== trim( (string) ( $p['website'] ?? '' ) ) ) {
		return new WP_REST_Response( array( 'ok' => true, 'id' => 0, 'key' => '' ), 200 );
	}
	$guard = tp_survey_guard( $p, true );
	if ( $guard ) {
		return $guard;
	}
	if ( tp_survey_rate_limited( 'c', TP_SURVEY_MAX_PER_HOUR, false ) ) {
		return tp_survey_err( 'tp_survey_limit', 'Rate limit', 429 );
	}
	list( $data, $errs ) = tp_survey_validate( $p, tp_survey_definition() );
	if ( $errs ) {
		return tp_survey_err( 'tp_survey_invalid', 'Invalid', 400, array( 'fields' => array_keys( $errs ) ) );
	}
	$now = current_time( 'mysql', true );
	$row = array_merge( $data, array(
		'created_at'     => $now,
		'survey_version' => TP_SURVEY_VERSION,
		'lang'           => 'en',
		'consent_at'     => $data['consent'] ? $now : null,
		'consent_ref'    => $data['consent'] ? TP_SURVEY_CONSENT_REF : '',
		'ip_hash'        => tp_survey_ip_hash(),
		'user_agent'     => mb_substr( (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 255 ),
	) );
	$ok = $wpdb->insert( tp_survey_table(), $row );
	if ( ! $ok ) {
		return tp_survey_err( 'tp_survey_db', 'Error', 500 );
	}
	$id = (int) $wpdb->insert_id; // subito: il transient del rate limit scrive a sua volta e cambia insert_id
	tp_survey_rate_limited( 'c', TP_SURVEY_MAX_PER_HOUR, true );
	do_action( 'tp_survey_saved', $id );
	return new WP_REST_Response( array( 'ok' => true, 'id' => $id, 'key' => tp_survey_feedback_key( $id ) ), 201 );
}

/** Aggiunge il commento libero (schermata finale) a una risposta gia' salvata: una volta sola, entro due ore. */
function tp_survey_rest_feedback( WP_REST_Request $req ) {
	global $wpdb;
	$p = $req->get_json_params();
	$p = is_array( $p ) ? $p : $req->get_params();
	$guard = tp_survey_guard( $p, false );
	if ( $guard ) {
		return $guard;
	}
	$id = (int) ( $p['id'] ?? 0 );
	if ( $id < 1 || ! hash_equals( tp_survey_feedback_key( $id ), (string) ( $p['key'] ?? '' ) ) ) {
		return tp_survey_err( 'tp_survey_forbidden', 'Forbidden', 403 );
	}
	if ( tp_survey_rate_limited( 'f', 20, true ) ) {
		return tp_survey_err( 'tp_survey_limit', 'Rate limit', 429 );
	}
	$text = tp_survey_text( $p['feedback'] ?? '', 2000 );
	if ( '' === $text ) {
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}
	$limit = gmdate( 'Y-m-d H:i:s', time() - 2 * HOUR_IN_SECONDS );
	$done  = $wpdb->query( $wpdb->prepare(
		'UPDATE ' . tp_survey_table() . " SET feedback = %s WHERE id = %d AND feedback = '' AND created_at >= %s",
		$text, $id, $limit
	) );
	if ( $done ) {
		do_action( 'tp_survey_feedback_saved', $id );
	}
	return new WP_REST_Response( array( 'ok' => (bool) $done ), 200 );
}

require_once __DIR__ . '/survey-admin.php';
require_once __DIR__ . '/survey-airtable.php';
