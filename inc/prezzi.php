<?php
/**
 * Formula unica dei prezzi dello sgrosso, lato server (TICKET-96, SPEC-crescita-organica-fase-b US-01).
 *
 * E' la stessa formula di assets/js/sgrosso-estimator.js (funzione `stima`):
 *   base = base + mq x per_mq
 *   min  = round10(base + extra x extra_min)
 *   max  = round10(base x max_mult + extra x extra_max)
 * con round10(v) = Math.round(v / 10) * 10. Il JS legge i coefficienti dai data-attribute della sezione
 * [data-estimator], che template-parts/sgrosso-estimator.php scrive a partire da tp_sgrosso_coef(): i coefficienti
 * di default vivono qui, in un solo posto lato PHP, e il JS tiene gli stessi valori come ripiego se l'attributo manca.
 * Chi cambia la formula nel JS deve cambiare anche tp_sgrosso_stima(): tools/qa-guida-prezzi.js confronta le cifre
 * della tabella con window.tpStimaSgrosso nel browser e fallisce se divergono.
 *
 * Usata dalla guida prezzi (page-guida-prezzi.php, template-parts/price-guide-table.php) e dal calcolatore delle
 * landing (template-parts/sgrosso-estimator.php, page-sgrosso.php): nessun prezzo e' scritto a mano in un template.
 */

/** Coefficienti di default della formula (gli stessi di sgrosso-estimator.js e dei campi sg_calc_* della landing). */
function tp_sgrosso_coef() {
	return array(
		'base'      => 200,
		'per_mq'    => 2.2,
		'extra_min' => 25,
		'extra_max' => 40,
		'max_mult'  => 1.35,
	);
}

/** Math.round(v / 10) * 10 del JS: floor(x + 0.5) coincide con Math.round per i valori positivi. */
function tp_sgrosso_round10( $v ) {
	return (int) ( floor( $v / 10 + 0.5 ) * 10 );
}

/**
 * Fascia di prezzo per $mq metri quadri e $extra extra scelti.
 * $coef: coefficienti (default tp_sgrosso_coef()); le chiavi mancanti ripiegano sul default.
 * Ritorna array( 'min' => int, 'max' => int ).
 */
function tp_sgrosso_stima( $mq, $extra = 0, $coef = null ) {
	$c    = wp_parse_args( (array) $coef, tp_sgrosso_coef() );
	$mq   = (float) $mq;
	$base = (float) $c['base'] + $mq * (float) $c['per_mq'];
	return array(
		'min' => tp_sgrosso_round10( $base + (float) $extra * (float) $c['extra_min'] ),
		'max' => tp_sgrosso_round10( $base * (float) $c['max_mult'] + (float) $extra * (float) $c['extra_max'] ),
	);
}

/** Metrature in colonna nella tabella della guida prezzi (SPEC US-01). */
function tp_guida_prezzi_metrature() {
	return array( 50, 80, 100, 120 );
}

/** Elenco "50, 80, 100 e 120" per i testi, ricavato da tp_guida_prezzi_metrature(). */
function tp_guida_prezzi_metrature_testo() {
	$mq   = tp_guida_prezzi_metrature();
	$last = array_pop( $mq );
	return $mq ? implode( ', ', $mq ) . ' e ' . $last : (string) $last;
}

/** Percorsi dei cinque servizi sgrosso in tabella, nell'ordine della SPEC. L'etichetta e' l'anchor di tp_service_pages(). */
function tp_guida_prezzi_servizi() {
	return array(
		'/roma/sgrosso-casa/',
		'/roma/sgrosso-post-ristrutturazione/',
		'/roma/sgrosso-trasloco/',
		'/roma/sgrosso-fine-affitto/',
		'/roma/sgrosso-prima-pulizia/',
	);
}

/**
 * Righe della tabella: per ogni servizio percorso, etichetta e fasce per metratura (extra = 0).
 * Ritorna array di array( 'path' => string, 'label' => string, 'fasce' => array( mq => array( 'min' => int, 'max' => int ) ) ).
 * La formula e' unica, quindi le fasce sono uguali in ogni riga: 'uguali' lo dice al template, che lo dichiara in pagina.
 */
function tp_guida_prezzi_righe() {
	$pagine = tp_service_pages();
	$righe  = array();
	foreach ( tp_guida_prezzi_servizi() as $path ) {
		if ( ! isset( $pagine[ $path ] ) ) {
			continue;
		}
		$fasce = array();
		foreach ( tp_guida_prezzi_metrature() as $mq ) {
			$fasce[ $mq ] = tp_sgrosso_stima( $mq, 0 );
		}
		$righe[] = array( 'path' => $path, 'label' => $pagine[ $path ], 'fasce' => $fasce );
	}
	return $righe;
}

/** True se tutte le righe hanno le stesse fasce (oggi sempre, perche' la formula non dipende dal servizio). */
function tp_guida_prezzi_uguali( $righe ) {
	$prima = null;
	foreach ( $righe as $r ) {
		if ( null === $prima ) {
			$prima = $r['fasce'];
			continue;
		}
		if ( $r['fasce'] !== $prima ) {
			return false;
		}
	}
	return true;
}

/** Intervallo "310–420 €" per la tabella (trattino en, euro dopo lo spazio non separabile). */
function tp_guida_prezzi_formato( $fascia ) {
	return number_format( (int) $fascia['min'], 0, ',', '.' ) . '–' . number_format( (int) $fascia['max'], 0, ',', '.' ) . "\u{00A0}€";
}
