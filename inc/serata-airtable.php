<?php
/**
 * Survey della serata 25/11: copia di ogni risposta nel gestionale interno (Airtable, base "Contabilita' & Marketing",
 * tabella "Survey Serata 25-11"). Decisione Guido 26/11 (SPEC-survey-serata-25-11 v0.2). La copia di riferimento resta
 * nella tabella WordPress: Airtable e' una copia.
 *
 * - Stesso schema della Survey OnField (inc/survey-airtable.php, di cui riusa tp_survey_at_request e il token
 *   TP_AIRTABLE_SURVEY_TOKEN): evento cron immediato a ogni risposta, giro ogni 15 minuti per le righe rimaste indietro.
 * - Contatto: solo se il link portava ?c=<id>. L'id si verifica prima di collegarlo (un id sbagliato farebbe fallire la
 *   copia): se il Contatto non esiste la risposta arriva senza collegamento, con l'errore nelle note della riga.
 * - Nessuna Opportunita': chi era in sala non e' un lead di servizio, e una nuova Opportunita' farebbe partire
 *   "TP - Notifica lead al team" (WhatsApp operativo). Il ricontatto si segue dalla spunta "Da ricontattare".
 * - Manuale: wp tp-serata sync [<id>] [--all]
 */

const TP_SERATA_AT_TABLE     = '';  // "Survey Serata 25-11", id assegnato da Airtable alla creazione della tabella
const TP_SERATA_AT_MAX_TRIES = 5;

/** True se la copia e' attiva: token presente e tabella configurata. */
function tp_serata_at_enabled() {
	return '' !== TP_SERATA_AT_TABLE && defined( 'TP_AIRTABLE_SURVEY_TOKEN' ) && '' !== TP_AIRTABLE_SURVEY_TOKEN;
}

/** Valori separati da virgola -> etichette (array), per i campi a scelta multipla. */
function tp_serata_at_multi( $field, $csv ) {
	$out = array();
	foreach ( array_filter( explode( ',', (string) $csv ) ) as $v ) {
		$out[] = tp_serata_label( $field, $v );
	}
	return $out;
}

/** Sincronizza una risposta. Ritorna true se e' su Airtable. */
function tp_serata_at_sync_row( $id ) {
	global $wpdb;
	$table = tp_serata_table();
	$r     = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
	if ( ! $r || ! tp_serata_at_enabled() ) {
		return false;
	}
	$set  = function ( array $cols ) use ( $wpdb, $table, $id ) {
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

	// Contatto dal link: si collega solo se esiste davvero nella tabella Contatti.
	$contact = (string) $r['contact_ref'];
	$warn    = '';
	if ( '' !== $contact ) {
		list( , $err ) = tp_survey_at_request( 'GET', TP_SURVEY_AT_CONTACTS . '/' . rawurlencode( $contact ), null, array( 'fields[]' => 'Nome' ) );
		if ( $err ) {
			if ( 0 !== strpos( $err, 'HTTP 404' ) ) {
				return $fail( 'contatto (verifica): ' . $err );
			}
			$warn    = 'Contatto ' . $contact . ' del link non trovato nel gestionale.';
			$contact = '';
		}
	}

	$approfondire = tp_serata_at_multi( 'approfondire', $r['approfondire'] );
	$fields       = array(
		'Risposta'          => 'Serata #' . $id . ' - ' . $r['name'],
		'WP ID'             => (int) $id,
		'Data'              => tp_survey_at_iso( $r['created_at'] ),
		'Nome'              => $r['name'],
		'Voto'              => (int) $r['rating'],
		'Cosa è rimasto'    => $r['ricordo'],
		'Modello chiaro'    => '' !== $r['chiarezza'] ? tp_serata_label( 'chiarezza', $r['chiarezza'] ) : null,
		'Dubbio'            => $r['dubbio'],
		'Usa servizi'       => tp_serata_at_multi( 'servizi', $r['servizi'] ),
		'Da approfondire'   => $approfondire,
		'E adesso'          => '' !== $r['adesso'] ? tp_serata_label( 'adesso', $r['adesso'] ) : null,
		'E adesso (altro)'  => $r['adesso_altro'],
		'Video richiesto'   => in_array( 'video', explode( ',', (string) $r['approfondire'] ), true ),
		'Da ricontattare'   => in_array( $r['adesso'], array( 'sentiamoci', 'materiale' ), true ),
		'Contatto'          => '' !== $contact ? array( $contact ) : array(),
		'Note sincronizzazione' => $warn,
	);
	$record = array( 'fields' => $fields );
	// typecast: un'etichetta nuova (es. l'opzione del video) crea la scelta nel campo invece di far fallire la copia.
	if ( '' !== (string) $r['at_record_id'] ) {
		$record['id'] = $r['at_record_id'];
		list( $res, $err ) = tp_survey_at_request( 'PATCH', TP_SERATA_AT_TABLE, array( 'records' => array( $record ), 'typecast' => true ) );
	} else {
		list( $res, $err ) = tp_survey_at_request( 'POST', TP_SERATA_AT_TABLE, array( 'records' => array( $record ), 'typecast' => true ) );
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

function tp_serata_at_queue( $id ) {
	if ( ! tp_serata_at_enabled() ) {
		return; // Resta "pending": parte al primo giro dopo la configurazione.
	}
	wp_schedule_single_event( time(), 'tp_serata_at_sync', array( (int) $id ) );
	spawn_cron();
}
add_action( 'tp_serata_saved', 'tp_serata_at_queue' );
add_action( 'tp_serata_at_sync', 'tp_serata_at_sync_row' );

/** Riprova le righe non ancora su Airtable (al massimo TP_SERATA_AT_MAX_TRIES tentativi falliti per riga). */
function tp_serata_at_sweep() {
	global $wpdb;
	if ( ! tp_serata_at_enabled() ) {
		return;
	}
	$ids = $wpdb->get_col( $wpdb->prepare(
		'SELECT id FROM ' . tp_serata_table() . " WHERE at_status IN ('pending','error') AND at_tries < %d ORDER BY id LIMIT 20",
		TP_SERATA_AT_MAX_TRIES
	) );
	foreach ( $ids as $id ) {
		tp_serata_at_sync_row( (int) $id );
	}
}
add_action( 'tp_serata_at_sweep', 'tp_serata_at_sweep' );

// tp_quarter_hour e' registrato da inc/survey-airtable.php.
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'tp_serata_at_sweep' ) ) {
		wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS, 'tp_quarter_hour', 'tp_serata_at_sweep' );
	}
} );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Sincronizza le risposte della survey della serata con il gestionale interno (Airtable).
	 *
	 * ## OPTIONS
	 * [<id>]
	 * : Una sola risposta.
	 * [--all]
	 * : Tutte quelle non ancora sincronizzate, anche oltre il limite di tentativi.
	 */
	WP_CLI::add_command( 'tp-serata sync', function ( $args, $assoc ) {
		global $wpdb;
		if ( ! empty( $args[0] ) ) {
			$ids = array( (int) $args[0] );
		} else {
			$where = empty( $assoc['all'] ) ? $wpdb->prepare( ' AND at_tries < %d', TP_SERATA_AT_MAX_TRIES ) : '';
			$ids   = $wpdb->get_col( 'SELECT id FROM ' . tp_serata_table() . " WHERE at_status <> 'ok'" . $where . ' ORDER BY id' );
		}
		foreach ( $ids as $id ) {
			$ok  = tp_serata_at_sync_row( (int) $id );
			$err = $ok ? '' : (string) $wpdb->get_var( $wpdb->prepare( 'SELECT at_error FROM ' . tp_serata_table() . ' WHERE id = %d', $id ) );
			WP_CLI::log( $id . ': ' . ( $ok ? 'ok' : 'non sincronizzata ' . $err ) );
		}
	} );
}
