<?php
/**
 * Survey OnField: copia di ogni risposta nel gestionale interno (Airtable, base "Contabilita' & Marketing").
 * Decisioni Guido 30/10/2026: tabella "Survey OnField", invio a ogni risposta, i rami A (prova) e B (demo) aprono anche
 * un'Opportunita' collegata al Contatto. La copia di riferimento resta nella tabella WordPress: Airtable e' una copia.
 *
 * - Il salvataggio della risposta non aspetta Airtable: a ogni risposta (e a ogni commento) si pianifica un evento cron
 *   immediato; un secondo evento ogni 15 minuti riprova le righe rimaste indietro (al massimo TP_SURVEY_AT_MAX_TRIES).
 * - Gli id Airtable (risposta, contatto, opportunita') si salvano nella riga man mano: un nuovo tentativo non duplica.
 * - Token: costante TP_AIRTABLE_SURVEY_TOKEN in wp-config.php (mai nel repo), scope data.records:read + write sulla sola
 *   base. Senza token le righe restano "pending" e partono al primo giro utile.
 * - Manuale: wp tp-survey sync [<id>] [--all]
 */

const TP_SURVEY_AT_BASE      = 'appXiH9eh4Ko9H108';
const TP_SURVEY_AT_SURVEY    = 'tbl4qMC0DfOMBcYFr';
const TP_SURVEY_AT_CONTACTS  = 'tbl9AUHD1kF5o7MJJ';
const TP_SURVEY_AT_OPPS      = 'tbl0avctPioRGc1hH';
const TP_SURVEY_AT_MAX_TRIES = 5;

/** Etichette leggibili dei rami, uguali alle opzioni del campo "Ramo" su Airtable. */
function tp_survey_at_branch_label( $branch ) {
	$map = array( 'A' => 'A · Prova', 'B' => 'B · Demo', 'C' => 'C · Più avanti', 'D' => 'D · Non interessato' );
	return $map[ $branch ] ?? '';
}

/** Chiave -> etichetta per ogni domanda, dalla definizione della survey (unica fonte delle opzioni). */
function tp_survey_at_labels() {
	$out = array();
	foreach ( tp_survey_definition()['steps'] as $step ) {
		foreach ( $step['options'] as $o ) {
			$out[ $step['id'] ][ $o['v'] ] = $o['l'];
		}
	}
	return $out;
}

function tp_survey_at_label( array $labels, $step, $value ) {
	return ( '' === (string) $value ) ? null : ( $labels[ $step ][ $value ] ?? null );
}

function tp_survey_at_multi( array $labels, $step, $csv ) {
	$out = array();
	foreach ( array_filter( explode( ',', (string) $csv ) ) as $v ) {
		if ( isset( $labels[ $step ][ $v ] ) ) {
			$out[] = $labels[ $step ][ $v ];
		}
	}
	return $out;
}

/** Data MySQL in UTC -> ISO 8601 per Airtable. */
function tp_survey_at_iso( $mysql ) {
	return $mysql ? gmdate( 'Y-m-d\TH:i:s\Z', strtotime( $mysql . ' UTC' ) ) : null;
}

/** Chiamata all'API Airtable. Ritorna [array risposta, null] oppure [null, messaggio d'errore]. */
function tp_survey_at_request( $method, $path, $body = null, $query = array() ) {
	$url = 'https://api.airtable.com/v0/' . TP_SURVEY_AT_BASE . '/' . $path;
	if ( $query ) {
		$url .= '?' . http_build_query( $query );
	}
	$args = array(
		'method'  => $method,
		'timeout' => 15,
		'headers' => array(
			'Authorization' => 'Bearer ' . TP_AIRTABLE_SURVEY_TOKEN,
			'Content-Type'  => 'application/json',
		),
	);
	if ( null !== $body ) {
		$args['body'] = wp_json_encode( $body );
	}
	$res = wp_remote_request( $url, $args );
	if ( is_wp_error( $res ) ) {
		return array( null, $res->get_error_message() );
	}
	$code = (int) wp_remote_retrieve_response_code( $res );
	$json = json_decode( (string) wp_remote_retrieve_body( $res ), true );
	if ( $code < 200 || $code >= 300 ) {
		$msg = is_array( $json ) && isset( $json['error'] ) ? wp_json_encode( $json['error'] ) : 'HTTP ' . $code;
		return array( null, 'HTTP ' . $code . ' ' . $msg );
	}
	return array( is_array( $json ) ? $json : array(), null );
}

/** Formula Airtable con una stringa racchiusa fra apici, escape incluso. */
function tp_survey_at_quote( $s ) {
	return "'" . str_replace( array( '\\', "'" ), array( '\\\\', "\\'" ), $s ) . "'";
}

/** Cerca un Contatto per email o per numero (solo cifre). Ritorna l'id o ''. */
function tp_survey_at_find_contact( $email, $phone_digits ) {
	$terms = array();
	if ( '' !== $email ) {
		$terms[] = 'LOWER({Email}) = ' . tp_survey_at_quote( strtolower( $email ) );
	}
	if ( strlen( $phone_digits ) >= 7 ) {
		$terms[] = "REGEX_REPLACE({Telefono}, '[^0-9]', '') = " . tp_survey_at_quote( $phone_digits );
	}
	if ( ! $terms ) {
		return array( '', null );
	}
	list( $res, $err ) = tp_survey_at_request( 'GET', TP_SURVEY_AT_CONTACTS, null, array(
		'filterByFormula' => 'OR(' . implode( ', ', $terms ) . ')',
		'maxRecords'      => 1,
		'fields[]'        => 'Nome',
	) );
	if ( $err ) {
		return array( '', $err );
	}
	return array( $res['records'][0]['id'] ?? '', null );
}

/** Riepilogo leggibile per le note dell'Opportunita'. */
function tp_survey_at_summary( array $r, array $labels ) {
	$lines = array(
		'Survey OnField (Dubai) - ' . tp_survey_at_branch_label( $r['branch'] ),
		'Ruolo: ' . ( tp_survey_at_label( $labels, 'role', $r['role'] ) ?? '-' ),
		'Attivita: ' . ( tp_survey_at_label( $labels, 'business_type', $r['business_type'] ) ?? '-' ),
		'Addetti: ' . ( tp_survey_at_label( $labels, 'team_size', $r['team_size'] ) ?? '-' ),
		'Lavori a settimana: ' . ( tp_survey_at_label( $labels, 'weekly_jobs', $r['weekly_jobs'] ) ?? '-' ),
		'Problema principale: ' . ( tp_survey_at_label( $labels, 'challenge', $r['challenge'] ) ?? '-' ),
		'Prezzo atteso: ' . ( tp_survey_at_label( $labels, 'price_expectation', $r['price_expectation'] ) ?? '-' ),
	);
	return implode( "\n", $lines );
}

/** Sincronizza una risposta. Ritorna true se e' tutto su Airtable. */
function tp_survey_at_sync_row( $id ) {
	global $wpdb;
	$table = tp_survey_table();
	$r     = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
	if ( ! $r || ! defined( 'TP_AIRTABLE_SURVEY_TOKEN' ) || '' === TP_AIRTABLE_SURVEY_TOKEN ) {
		return false;
	}
	$set = function ( array $cols ) use ( $wpdb, $table, $id ) {
		$wpdb->update( $table, $cols, array( 'id' => $id ) );
	};
	$fail = function ( $msg ) use ( $set, $r ) {
		$set( array(
			'at_status' => 'error',
			'at_tries'  => (int) $r['at_tries'] + 1,
			'at_error'  => mb_substr( (string) $msg, 0, 255 ),
		) );
		return false;
	};

	$labels      = tp_survey_at_labels();
	$now         = tp_survey_at_iso( $r['created_at'] );
	$email       = strtolower( trim( (string) $r['email'] ) );
	$digits      = preg_replace( '/\D+/', '', (string) $r['whatsapp'] );
	$has_contact = '' !== $email || '' !== $digits;

	// 1. Contatto (solo se ha lasciato un recapito): prima si cerca, poi si crea.
	$contact_id = (string) $r['at_contact_id'];
	if ( $has_contact && '' === $contact_id ) {
		list( $contact_id, $err ) = tp_survey_at_find_contact( $email, $digits );
		if ( $err ) {
			return $fail( 'contatto (ricerca): ' . $err );
		}
		if ( '' === $contact_id ) {
			list( $res, $err ) = tp_survey_at_request( 'POST', TP_SURVEY_AT_CONTACTS, array(
				'typecast' => true,
				'records'  => array( array( 'fields' => array_filter( array(
					'Nome'                  => '' !== $r['name'] ? $r['name'] : ( '' !== $r['company'] ? $r['company'] : 'Survey OnField #' . $id ),
					'Email'                 => '' !== $email ? $email : null,
					'Telefono'              => '' !== $r['whatsapp'] ? $r['whatsapp'] : null,
					'Tipo'                  => 'SaaS Prospect',
					'Stato'                 => 'Nuovo',
					'Mercato'               => 'Dubai',
					'Origine'               => 'Form web',
					'Natura'                => 'Azienda',
					'Ragione Sociale'       => '' !== $r['company'] ? $r['company'] : null,
					'Data Acquisizione'     => substr( (string) $r['created_at'], 0, 10 ),
					'Data/Ora Ricezione'    => $now,
					'Consenso Marketing'    => $r['consent'] ? 'Sì' : null,
					'Data Consenso Marketing' => $r['consent'] ? tp_survey_at_iso( $r['consent_at'] ) : null,
					'Fonte Consenso'        => $r['consent'] ? $r['consent_ref'] . ' (contatto su survey e prodotto OnField)' : null,
				), function ( $v ) { return null !== $v; } ) ) ),
			) );
			if ( $err ) {
				return $fail( 'contatto (creazione): ' . $err );
			}
			$contact_id = (string) ( $res['records'][0]['id'] ?? '' );
		}
		$set( array( 'at_contact_id' => $contact_id ) );
	}

	// 2. Opportunita': solo rami A (prova) e B (demo), una volta sola.
	$opp_id = (string) $r['at_opp_id'];
	if ( in_array( $r['branch'], array( 'A', 'B' ), true ) && '' === $opp_id ) {
		list( $res, $err ) = tp_survey_at_request( 'POST', TP_SURVEY_AT_OPPS, array(
			'typecast' => true,
			'records'  => array( array( 'fields' => array_filter( array(
				'Contatto'             => '' !== $contact_id ? array( $contact_id ) : null,
				'Stato'                => 'Da contattare',
				'Servizio'             => 'OnField',
				'Zona'                 => 'Dubai',
				'Canale'               => 'Form web',
				'Landing / Form'       => 'trovapulizie.it/survey-dubai',
				'Informativa Versione' => $r['consent_ref'],
				'Data Apertura'        => substr( (string) $r['created_at'], 0, 10 ),
				'Data/Ora Richiesta'   => $now,
				'Note'                 => tp_survey_at_summary( $r, $labels ),
			), function ( $v ) { return null !== $v && '' !== $v; } ) ) ),
		) );
		if ( $err ) {
			return $fail( 'opportunita: ' . $err );
		}
		$opp_id = (string) ( $res['records'][0]['id'] ?? '' );
		$set( array( 'at_opp_id' => $opp_id ) );
	}

	// 3. Risposta: creata la prima volta, aggiornata dopo (es. commento arrivato dopo il contatto).
	$title  = 'OnField #' . $id . ( '' !== $r['company'] ? ' - ' . $r['company'] : ( '' !== $r['name'] ? ' - ' . $r['name'] : '' ) );
	$fields = array(
		'Risposta'             => $title,
		'WP ID'                => (int) $id,
		'Data'                 => $now,
		'Versione'             => (int) $r['survey_version'],
		'Ramo'                 => tp_survey_at_branch_label( $r['branch'] ),
		'Ruolo'                => tp_survey_at_label( $labels, 'role', $r['role'] ),
		'Tipo attività'        => tp_survey_at_label( $labels, 'business_type', $r['business_type'] ),
		'Addetti'              => tp_survey_at_label( $labels, 'team_size', $r['team_size'] ),
		'Lavori a settimana'   => tp_survey_at_label( $labels, 'weekly_jobs', $r['weekly_jobs'] ),
		'Strumenti'            => tp_survey_at_multi( $labels, 'tools', $r['tools'] ),
		'Strumenti altro'      => $r['tools_other'],
		'Software usati'       => tp_survey_at_multi( $labels, 'software_names', $r['software_names'] ),
		'Software altro'       => $r['software_other'],
		'Problema principale'  => tp_survey_at_label( $labels, 'challenge', $r['challenge'] ),
		'Problema altro'       => $r['challenge_other'],
		'Spesa software oggi'  => tp_survey_at_label( $labels, 'software_spend', $r['software_spend'] ),
		'Prezzo atteso'        => tp_survey_at_label( $labels, 'price_expectation', $r['price_expectation'] ),
		'Nome'                 => $r['name'],
		'Azienda'              => $r['company'],
		'Email'                => '' !== $email ? $email : null,
		'WhatsApp'             => $r['whatsapp'],
		'Consenso'             => (bool) $r['consent'],
		'Data consenso'        => tp_survey_at_iso( $r['consent_at'] ),
		'Riferimento consenso' => $r['consent_ref'],
		'Commento'             => $r['feedback'],
		'Contatto'             => '' !== $contact_id ? array( $contact_id ) : array(),
		'Opportunità'          => '' !== $opp_id ? array( $opp_id ) : array(),
	);
	$record = array( 'fields' => $fields );
	if ( '' !== (string) $r['at_record_id'] ) {
		$record['id'] = $r['at_record_id'];
		list( $res, $err ) = tp_survey_at_request( 'PATCH', TP_SURVEY_AT_SURVEY, array( 'records' => array( $record ) ) );
	} else {
		list( $res, $err ) = tp_survey_at_request( 'POST', TP_SURVEY_AT_SURVEY, array( 'records' => array( $record ) ) );
	}
	if ( $err ) {
		return $fail( 'risposta: ' . $err );
	}
	$set( array(
		'at_record_id' => (string) ( $res['records'][0]['id'] ?? $r['at_record_id'] ),
		'at_status'    => 'ok',
		'at_error'     => '',
		'at_synced_at' => current_time( 'mysql', true ),
	) );
	return true;
}

/* ---- Pianificazione ------------------------------------------------------------------------------- */

function tp_survey_at_queue( $id ) {
	global $wpdb;
	$wpdb->update( tp_survey_table(), array( 'at_status' => 'pending' ), array( 'id' => (int) $id ) );
	wp_schedule_single_event( time(), 'tp_survey_at_sync', array( (int) $id ) );
	spawn_cron();
}
add_action( 'tp_survey_saved', 'tp_survey_at_queue' );
add_action( 'tp_survey_feedback_saved', 'tp_survey_at_queue' );
add_action( 'tp_survey_at_sync', 'tp_survey_at_sync_row' );

add_filter( 'cron_schedules', function ( $s ) {
	$s['tp_quarter_hour'] = array( 'interval' => 15 * MINUTE_IN_SECONDS, 'display' => 'Ogni 15 minuti' );
	return $s;
} );

/** Riprova le righe non ancora su Airtable (al massimo TP_SURVEY_AT_MAX_TRIES tentativi falliti per riga). */
function tp_survey_at_sweep() {
	global $wpdb;
	$ids = $wpdb->get_col( $wpdb->prepare(
		'SELECT id FROM ' . tp_survey_table() . " WHERE at_status IN ('pending','error') AND at_tries < %d ORDER BY id LIMIT 20",
		TP_SURVEY_AT_MAX_TRIES
	) );
	foreach ( $ids as $id ) {
		tp_survey_at_sync_row( (int) $id );
	}
}
add_action( 'tp_survey_at_sweep', 'tp_survey_at_sweep' );

add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'tp_survey_at_sweep' ) ) {
		wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS, 'tp_quarter_hour', 'tp_survey_at_sweep' );
	}
} );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Sincronizza le risposte della survey con il gestionale interno (Airtable).
	 *
	 * ## OPTIONS
	 * [<id>]
	 * : Una sola risposta.
	 * [--all]
	 * : Tutte quelle non ancora sincronizzate, anche oltre il limite di tentativi.
	 */
	WP_CLI::add_command( 'tp-survey sync', function ( $args, $assoc ) {
		global $wpdb;
		if ( ! empty( $args[0] ) ) {
			$ids = array( (int) $args[0] );
		} else {
			$where = empty( $assoc['all'] ) ? $wpdb->prepare( ' AND at_tries < %d', TP_SURVEY_AT_MAX_TRIES ) : '';
			$ids   = $wpdb->get_col( 'SELECT id FROM ' . tp_survey_table() . " WHERE at_status <> 'ok'" . $where . ' ORDER BY id' );
		}
		foreach ( $ids as $id ) {
			$ok  = tp_survey_at_sync_row( (int) $id );
			$err = $ok ? '' : (string) $wpdb->get_var( $wpdb->prepare( 'SELECT at_error FROM ' . tp_survey_table() . ' WHERE id = %d', $id ) );
			WP_CLI::log( $id . ': ' . ( $ok ? 'ok' : 'non sincronizzata ' . $err ) );
		}
	} );
}
