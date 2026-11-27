<?php
/**
 * Survey della serata 25/11: copia di ogni risposta nel gestionale interno (Airtable, base "Contabilita' & Marketing",
 * tabella "Survey Serata 25-11"). Decisione Guido 26/11 (SPEC-survey-serata-25-11 v0.2). La copia di riferimento resta
 * nella tabella WordPress: Airtable e' una copia.
 *
 * - Stesso schema della Survey OnField (inc/survey-airtable.php, di cui riusa tp_survey_at_request e il token
 *   TP_AIRTABLE_SURVEY_TOKEN): evento cron immediato a ogni risposta, giro ogni 15 minuti per le righe rimaste indietro.
 * - Contatto: dall'id del link (?c=<id>, verificato prima di collegarlo: un id sbagliato farebbe fallire la copia),
 *   altrimenti cercato per telefono o email lasciati nella domanda 2 (tp_survey_at_find_contact). Nessun Contatto nuovo:
 *   se non si trova, la risposta arriva senza collegamento e con la nota.
 * - Nessuna Opportunita': chi era in sala non e' un lead di servizio, e una nuova Opportunita' farebbe partire
 *   "TP - Notifica lead al team" (WhatsApp operativo). Il ricontatto si segue dalla spunta "Da ricontattare"
 *   ("Vorrei ricevere materiale", "Restiamo in contatto" o interesse all'investimento "Sì" / "Forse").
 * - Ogni risposta finisce anche nella tabella comune "Risposte survey" (inc/survey-risposte.php), letta dalla pagina
 *   /survey-risposte del gestionale. Se quella copia fallisce la riga resta in errore e il giro dopo riprova entrambe.
 * - Manuale: wp tp-serata sync [<id>] [--all] [--resync]
 */

const TP_SERATA_AT_TABLE     = 'tbl7acCyAmt6QTlqR'; // "Survey Serata 25-11", creata il 26/11
const TP_SERATA_AT_MAX_TRIES = 5;

/** True se la copia e' attiva: token presente e tabella configurata. */
function tp_serata_at_enabled() {
	return '' !== TP_SERATA_AT_TABLE && defined( 'TP_AIRTABLE_SURVEY_TOKEN' ) && '' !== TP_AIRTABLE_SURVEY_TOKEN;
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
			// Un id che non esiste torna come 404 oppure come 403 INVALID_PERMISSIONS_OR_MODEL_NOT_FOUND (visto il 26/11).
			if ( 0 !== strpos( $err, 'HTTP 404' ) && false === strpos( $err, 'MODEL_NOT_FOUND' ) ) {
				return $fail( 'contatto (verifica): ' . $err );
			}
			$warn    = 'Contatto ' . $contact . ' del link non trovato nel gestionale.';
			$contact = '';
		}
	}
	if ( '' === $contact && ( '' !== (string) $r['telefono'] || '' !== (string) $r['email'] ) ) {
		list( $contact, $err ) = tp_survey_at_find_contact( (string) $r['email'], preg_replace( '/\D+/', '', (string) $r['telefono'] ) );
		if ( $err ) {
			return $fail( 'contatto (ricerca): ' . $err );
		}
		if ( '' === $contact ) {
			$warn = trim( $warn . ' Nessun Contatto con questo recapito nel gestionale.' );
		}
	}

	// Risposte arrivate con le domande della v1 (26/11 mattina): voto nella colonna Serata, il resto nelle note.
	$legacy = '';
	if ( 0 === (int) $r['serata'] && (int) $r['rating'] > 0 ) {
		$v1     = array(
			'chiaro' => 'Sì, chiaro', 'dubbio' => 'Abbastanza, ma ho qualche dubbio', 'poco' => 'Poco chiaro',
			'casa' => 'per casa', 'azienda' => "per un'azienda o un ufficio", 'no' => 'No',
			'modello' => 'Come funziona il modello', 'team' => 'Il team', 'tecnologia' => "La tecnologia e l'app",
			'partecipare' => 'Come partecipare al progetto', 'servizio' => 'Provare il servizio', 'video' => 'Rivedere il video', 'niente' => 'Niente per ora',
			'sentiamoci' => 'Sentiamoci', 'materiale' => 'Vorrei ricevere del materiale', 'piacere' => 'È stato un piacere conoscervi', 'altro' => 'Altro',
		);
		$lab    = function ( $csv ) use ( $v1 ) {
			return implode( ', ', array_map( function ( $x ) use ( $v1 ) { return $v1[ $x ] ?? $x; }, array_filter( explode( ',', (string) $csv ) ) ) );
		};
		$legacy = implode( "\n", array_filter( array(
			'Risposta alle domande della v1 (26/11 mattina):',
			'Cosa è rimasto: ' . ( '' !== (string) $r['ricordo'] ? $r['ricordo'] : '-' ),
			'Modello chiaro: ' . $lab( $r['chiarezza'] ) . ( '' !== (string) $r['dubbio'] ? ' - dubbio: ' . $r['dubbio'] : '' ),
			'Usa servizi: ' . ( $lab( $r['servizi'] ) ?: '-' ),
			'Da approfondire: ' . ( $lab( $r['approfondire'] ) ?: '-' ),
			'E adesso: ' . $lab( $r['adesso'] ) . ( '' !== (string) $r['adesso_altro'] ? ' - ' . $r['adesso_altro'] : '' ),
		) ) );
		$r['serata'] = $r['rating'];
		$r['adesso'] = array( 'materiale' => 'materiale', 'sentiamoci' => 'contatto' )[ $r['adesso'] ] ?? '';
	}
	$score = function ( $n ) {
		return (int) $n >= 1 ? (int) $n : null;
	};
	$fields = array(
		'Risposta'                  => 'Serata #' . $id . ' - ' . $r['name'],
		'WP ID'                     => (int) $id,
		'Data'                      => tp_survey_at_iso( $r['created_at'] ),
		'Nome'                      => $r['name'],
		'Telefono'                  => $r['telefono'],
		'Email'                     => '' !== $r['email'] ? $r['email'] : null,
		'Serata'                    => $score( $r['serata'] ),
		'Progetto'                  => $score( $r['progetto'] ),
		'Modello chiaro'            => $score( $r['chiaro'] ),
		'Da migliorare'             => (string) $r['migliorare'],
		'Aspetto più interessante'  => '' !== $r['interesse'] ? tp_serata_label( 'interesse', $r['interesse'] ) : null,
		'Aspetto (altro)'           => $r['interesse_altro'],
		'Investimento'              => '' !== $r['investimento'] ? tp_serata_label( 'investimento', $r['investimento'] ) : null,
		'Per valutare servirebbe'   => (string) $r['investimento_serve'],
		'E adesso'                  => '' !== $r['adesso'] ? tp_serata_label( 'adesso', $r['adesso'] ) : null,
		'Da ricontattare'           => in_array( $r['adesso'], array( 'materiale', 'contatto' ), true ) || in_array( $r['investimento'], array( 'si', 'forse' ), true ),
		'Contatto'                  => '' !== $contact ? array( $contact ) : array(),
		'Note sincronizzazione'     => trim( $legacy . ( '' !== $legacy && '' !== $warn ? "\n" : '' ) . $warn ),
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
	// L'id va salvato prima della copia comune: se quella fallisce, il tentativo dopo aggiorna e non duplica.
	$set( array( 'at_record_id' => (string) ( $res['records'][0]['id'] ?? $r['at_record_id'] ) ) );

	list( , $err ) = tp_risposte_upsert( array(
		'Risposta'              => $fields['Risposta'],
		'Survey'                => 'Serata 25-11',
		'Chiave origine'        => 'serata:' . $id,
		'Data'                  => $fields['Data'],
		'Nome'                  => $r['name'],
		'Telefono'              => $r['telefono'],
		'Email'                 => $fields['Email'],
		'Contatto'              => $fields['Contatto'],
		'Risposte'              => trim( tp_risposte_testo( tp_serata_risposte_pairs( $r ) ) . ( '' !== $legacy ? "\n\n" . $legacy : '' ) ),
		'Da ricontattare'       => $fields['Da ricontattare'],
		'Note sincronizzazione' => $warn,
	) );
	if ( $err ) {
		return $fail( 'risposte survey: ' . $err );
	}
	$set( array(
		'at_status'    => 'ok',
		'at_error'     => '',
		'at_synced_at' => current_time( 'mysql', true ),
	) );
	return true;
}

/**
 * Coppie [domanda, risposta] per il testo di "Risposte survey", nell'ordine della definizione. Nome e recapiti
 * restano fuori: hanno i loro campi.
 */
function tp_serata_risposte_pairs( array $r ) {
	$pairs = array();
	foreach ( tp_serata_definition()['steps'] as $s ) {
		$v = $r[ $s['id'] ] ?? '';
		switch ( $s['type'] ) {
			case 'scale':
				$n = (int) $v;
				$a = $n >= 1 ? $n . '/' . $s['max'] . ( isset( $s['labels'][ $n - 1 ] ) ? ' (' . $s['labels'][ $n - 1 ] . ')' : '' ) : '';
				break;
			case 'single':
				$a = tp_serata_label( $s['id'], $v );
				if ( '' !== $a && isset( $s['other_field'] ) && '' !== (string) ( $r[ $s['other_field'] ] ?? '' ) ) {
					$a .= ' - ' . $r[ $s['other_field'] ];
				}
				break;
			case 'textarea':
				$a = (string) $v;
				break;
			default:
				continue 2;
		}
		$pairs[] = array( $s['title'], $a );
	}
	return $pairs;
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
	 * [--resync]
	 * : Tutte le risposte, anche quelle gia' sincronizzate (es. per riempire "Risposte survey").
	 */
	WP_CLI::add_command( 'tp-serata sync', function ( $args, $assoc ) {
		global $wpdb;
		if ( ! empty( $args[0] ) ) {
			$ids = array( (int) $args[0] );
		} elseif ( ! empty( $assoc['resync'] ) ) {
			$ids = $wpdb->get_col( 'SELECT id FROM ' . tp_serata_table() . ' ORDER BY id' );
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
