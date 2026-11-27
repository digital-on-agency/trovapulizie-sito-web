<?php
/**
 * Tabella di arrivo comune delle survey scritte su misura: "Risposte survey" nel gestionale interno (base
 * "Contabilita' & Marketing"). Decisione Guido 27/11, vault gestionale-interno SPEC-risposte-survey.
 *
 * - Ogni survey, nel proprio sync verso Airtable, chiama tp_risposte_upsert() con i campi comuni e il testo delle
 *   risposte: la pagina /survey-risposte del gestionale la mostra senza modifiche al blocco Softr.
 * - Upsert sulla "Chiave origine" (<survey>:<id WP>): rilanciare il sync aggiorna la riga e non la duplica, e non tocca
 *   i campi scritti dal gestionale (Ricontattato, Data ricontatto, Nota ricontatto).
 * - Stesso token e stesso client HTTP della Survey OnField (tp_survey_at_request, inc/survey-airtable.php).
 * - La Survey Dubai non passa da qui: le sue risposte restano in "Survey OnField" e in /lead-onfield.
 * Come collegare una survey nuova: docs/survey-risposte.md.
 */

const TP_RISPOSTE_AT_TABLE = 'tblqZTo192SVPN21a'; // "Risposte survey", creata il 27/11

/** Testo leggibile da coppie [domanda, risposta], una domanda per paragrafo. Le risposte vuote si saltano. */
function tp_risposte_testo( array $pairs ) {
	$out = array();
	foreach ( $pairs as $p ) {
		$a = trim( (string) ( $p[1] ?? '' ) );
		if ( '' !== $a ) {
			$out[] = $p[0] . "\n" . $a;
		}
	}
	return implode( "\n\n", $out );
}

/**
 * Crea o aggiorna la riga di una risposta. $fields usa i nomi dei campi della tabella e deve contenere
 * "Chiave origine". Ritorna [id del record, errore o null].
 */
function tp_risposte_upsert( array $fields ) {
	if ( empty( $fields['Chiave origine'] ) ) {
		return array( '', 'Chiave origine mancante' );
	}
	list( $res, $err ) = tp_survey_at_request( 'PATCH', TP_RISPOSTE_AT_TABLE, array(
		'performUpsert' => array( 'fieldsToMergeOn' => array( 'Chiave origine' ) ),
		'records'       => array( array( 'fields' => $fields ) ),
		'typecast'      => true, // una survey nuova crea la sua scelta nel campo Survey
	) );
	if ( $err ) {
		return array( '', $err );
	}
	return array( (string) ( $res['records'][0]['id'] ?? '' ), null );
}
