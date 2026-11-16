<?php
/**
 * Testi di default e collegamenti dell'hub /roma/ (TICKET-98, SPEC-crescita-organica-fase-b US-03).
 *
 * L'hub risponde alla ricerca "impresa di pulizie a Roma": H1 e title con la keyword, un'introduzione che spiega
 * cosa fa Trovapulizie (marketplace, non impresa), e una sezione "Servizi a Roma" con un link per ogni pagina di
 * servizio PUBBLICATA, letta da tp_service_pages() (unica fonte dei percorsi e degli anchor).
 *
 * I campi Carbon roma_hero_eyebrow, roma_hero_title, roma_hero_subtitle, roma_intro_eyebrow e roma_intro_text, se
 * compilati sulla pagina, vincono sui default (tp_hub_roma_testi()); lo stesso vale per _seo_title e
 * _seo_description (inc/seo.php). Prima di un rilascio si controlla cosa c'e' nel database di produzione: vedi lo
 * script di rilascio.
 *
 * [ATTENZIONE] Vincoli della SPEC §2 su ogni testo di questo file: nessun numero di ditte, clienti, recensioni o
 * zone; nessuna promessa nuova (tempi, garanzie, certificazioni); "verificate" solo dove il sito lo dice gia';
 * nessun prezzo per uffici e condomini. Un testo nuovo lo legge e lo approva Guido.
 */

/** Testi di default dell'hub: chiave => stringa. Il template li usa quando il campo Carbon e' vuoto. */
function tp_hub_roma_defaults() {
	return array(
		'eyebrow'         => 'Pulizie a Roma',
		'title'           => "Trova un'impresa di pulizie a Roma",
		'subtitle'        => 'Trovapulizie ti mette in contatto con imprese di pulizia verificate. Cerca con il tuo indirizzo oppure scegli il servizio qui sotto.',
		'intro_eyebrow'   => '',
		'intro_title'     => 'Cosa fa Trovapulizie',
		'intro_text'      => "Trovapulizie è un marketplace che mette in contatto privati, aziende e condomini di Roma con imprese di pulizia verificate. Dici cosa ti serve e trovi l'impresa giusta per il tuo caso.\n\n"
			. "Per le pulizie di casa cerchi e prenoti dall'app: inserisci l'indirizzo, scegli il servizio e prenoti.\n\n"
			. 'Per le pulizie straordinarie della casa, per uffici, negozi e condomini si parte da un sopralluogo gratuito: vediamo gli spazi insieme a te e ricevi un preventivo su misura, senza impegno.',
		'services_title'  => 'Servizi a Roma',
		'services_text'   => 'Scegli la pagina del servizio che ti serve.',
		'seo_title'       => 'Impresa di pulizie a Roma: privati e aziende | Trovapulizie',
		'seo_description' => "Cerchi un'impresa di pulizie a Roma? Trovapulizie ti collega a imprese verificate: prenoti dall'app per la casa, sopralluogo gratuito per uffici e condomini.",
	);
}

/**
 * Testi dell'hub per la pagina corrente: il valore del campo Carbon se compilato, altrimenti il default.
 * Ritorna chiave => testo per tutte le chiavi di tp_hub_roma_defaults().
 */
function tp_hub_roma_testi() {
	static $testi = null;
	if ( null !== $testi ) {
		return $testi;
	}
	$campi = array(
		'eyebrow'       => 'roma_hero_eyebrow',
		'title'         => 'roma_hero_title',
		'subtitle'      => 'roma_hero_subtitle',
		'intro_eyebrow' => 'roma_intro_eyebrow',
		'intro_text'    => 'roma_intro_text',
	);
	$testi = tp_hub_roma_defaults();
	foreach ( $campi as $chiave => $campo ) {
		$v = trim( (string) carbon_get_the_post_meta( $campo ) );
		if ( '' !== $v ) {
			$testi[ $chiave ] = $v;
		}
	}
	return $testi;
}

/**
 * Pagine di servizio pubblicate: percorso => anchor, nell'ordine di tp_service_pages(), senza l'hub.
 * Una pagina in bozza, privata, cestinata o mai creata non compare: nessun link a una 404.
 * Risultato in cache per richiesta.
 */
function tp_service_pages_published() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$cache = array();
	foreach ( tp_service_pages() as $percorso => $anchor ) {
		if ( '/roma/' === $percorso ) {
			continue;
		}
		$pagina = get_page_by_path( trim( $percorso, '/' ), OBJECT, 'page' );
		if ( $pagina && 'publish' === $pagina->post_status ) {
			$cache[ $percorso ] = $anchor;
		}
	}
	return $cache;
}

/** Riga di descrizione per percorso. Una pagina nuova di tp_service_pages() senza voce qui mostra il solo anchor. */
function tp_hub_roma_descrizioni() {
	return array(
		'/roma/sgrosso-casa/'                  => 'Pulizia straordinaria una tantum della casa, con sopralluogo gratuito.',
		'/roma/sgrosso-post-ristrutturazione/' => 'Via polvere e residui di cantiere dopo i lavori di ristrutturazione.',
		'/roma/sgrosso-trasloco/'              => 'Pulizia della casa prima di entrare o dopo averla lasciata.',
		'/roma/sgrosso-fine-affitto/'          => "Pulizia per la riconsegna dell'immobile a fine affitto.",
		'/roma/sgrosso-prima-pulizia/'         => 'Pulizia approfondita per le case trascurate da tempo.',
		'/roma/prezzi-pulizie/'                => 'Fasce di prezzo per metratura e una stima sul tuo caso.',
		'/roma/sgrosso-commerciale/'           => 'Negozi, supermercati, palestre e locali aperti al pubblico.',
		'/roma/pulizie-uffici/'                => 'Postazioni, bagni, aree ristoro e vetri per uffici e studi professionali.',
		'/roma/pulizie-condomini/'             => 'Scale, androni, cortili e parti comuni per amministratori e condòmini.',
		'/roma/zanzare-disinfestazione/'       => 'Disinfestazione dalle zanzare per privati e aziende.',
	);
}

/** Gruppi dell'elenco: titolo => percorsi. */
function tp_hub_roma_gruppi() {
	return array(
		'Per la casa'                    => array(
			'/roma/sgrosso-casa/',
			'/roma/sgrosso-post-ristrutturazione/',
			'/roma/sgrosso-trasloco/',
			'/roma/sgrosso-fine-affitto/',
			'/roma/sgrosso-prima-pulizia/',
			'/roma/prezzi-pulizie/',
		),
		'Per uffici, negozi e condomini' => array(
			'/roma/pulizie-uffici/',
			'/roma/pulizie-condomini/',
			'/roma/sgrosso-commerciale/',
		),
		'Disinfestazione'                => array(
			'/roma/zanzare-disinfestazione/',
		),
	);
}

/**
 * Elenco dei servizi per il template: array di array( 'title' => string, 'voci' => array( array( 'path', 'label', 'text' ) ) ).
 * Solo pagine pubblicate; un gruppo senza voci non compare. Una pagina pubblicata presente in tp_service_pages() ma
 * non nei gruppi esce da sola nel gruppo "Altri servizi", cosi' una pagina aggiunta in futuro non resta senza link.
 */
function tp_hub_roma_servizi() {
	$pubblicate = tp_service_pages_published();
	$testi      = tp_hub_roma_descrizioni();
	$usati      = array();
	$out        = array();
	foreach ( tp_hub_roma_gruppi() as $titolo => $percorsi ) {
		$voci = array();
		foreach ( $percorsi as $p ) {
			if ( isset( $pubblicate[ $p ] ) ) {
				$voci[]  = array( 'path' => $p, 'label' => $pubblicate[ $p ], 'text' => $testi[ $p ] ?? '' );
				$usati[] = $p;
			}
		}
		if ( $voci ) {
			$out[] = array( 'title' => $titolo, 'voci' => $voci );
		}
	}
	$altre = array();
	foreach ( $pubblicate as $p => $anchor ) {
		if ( ! in_array( $p, $usati, true ) ) {
			$altre[] = array( 'path' => $p, 'label' => $anchor, 'text' => $testi[ $p ] ?? '' );
		}
	}
	if ( $altre ) {
		$out[] = array( 'title' => 'Altri servizi', 'voci' => $altre );
	}
	return $out;
}
