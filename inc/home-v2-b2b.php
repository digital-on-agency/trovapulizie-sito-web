<?php
/**
 * Fascia aziende e FAQ con i fondatori della Home v2 (SPEC-homepage-v2, TICKET-126): default dei testi e argomenti dei
 * due template-part, letti dai campi Carbon delle tab "Aziende" e "FAQ" (inc/carbon-fields.php).
 *
 * Una sola fonte per i default: li usano il campo Carbon `h2_faq_items` (valore precompilato nel pannello) e
 * template-parts/home-b2b-band.php. I testi si cambiano da WordPress senza deploy.
 *
 * Fondatori: nomi, ruolo e foto della card FAQ vengono da tp_founders_contact() (Theme Options "Fondatori", TICKET-119),
 * non da campi della pagina: il cambio di pagina iniziale non svuota le card delle landing.
 */

/** Percorso (nel sito) della richiesta di preventivo B2B: destinazione del bottone della fascia aziende. */
const TP_HV2_B2B_CTA_PATH = '/roma/sgrosso-commerciale/';
/** Percorso della pagina pulizie per uffici: destinazione del link secondario della fascia aziende. */
const TP_HV2_B2B_LINK_PATH = '/roma/pulizie-uffici/';

/**
 * Le tre garanzie della fascia aziende del Figma (7903:7608), una per riga nel campo Carbon. "Preventivo entro 24 ore"
 * e' nel Figma ma NON e' confermato (Guido, 21/11): non e' un default; chi lo conferma lo aggiunge nel campo.
 *
 * @return string[]
 */
function tp_home_v2_b2b_default_badges() {
	return array( 'Sopralluogo gratuito', 'Imprese verificate' );
}

/**
 * Le sei domande del Figma (7903:7623) con le risposte di default. Le risposte sono riprese dai testi gia' presenti sulle
 * landing del tema e dal Manifesto, cosi' restano coerenti con prezzo, sostituto e B2B:
 * - prezzo: testo del Figma;
 * - professionisti: verifica di identita' e documenti (page-sgrosso.php, "Le ditte sono affidabili?");
 * - pagamento e sostituto: Manifesto (inc/home-v2-manifesto.php: "addebito solo quando il professionista accetta",
 *   "te ne proponiamo un altro, stesse condizioni");
 * - uffici e condomini: sopralluogo gratuito e preventivo su misura (inc/b2b-variants.php).
 * "Devo scaricare un'app?" non ha un testo gia' scritto sul sito: risposta da confermare (Guido, SPEC §6).
 *
 * @return array<int,array{question:string,answer:string}>
 */
function tp_home_v2_faq_defaults() {
	return array(
		array(
			'question' => 'Quanto costa una pulizia?',
			'answer'   => 'Dipende dal servizio, dalle ore e dal professionista. Il prezzo lo vedi sempre prima di inviare la richiesta, senza costi nascosti.',
		),
		array(
			'question' => 'Devo scaricare un’app?',
			'answer'   => 'No. Apri Trovapulizie dal browser del telefono o del computer, senza passare dallo store, e prenoti da lì.',
		),
		array(
			'question' => 'Chi sono i professionisti?',
			'answer'   => 'Imprese e professionisti di pulizia di Roma. Ognuno passa una verifica di identità e documenti prima di poter lavorare.',
		),
		array(
			'question' => 'Quando pago?',
			'answer'   => 'Paghi con carta dall’app, ma l’addebito parte solo quando il professionista accetta la tua richiesta.',
		),
		array(
			'question' => 'E se il professionista non può più venire?',
			'answer'   => 'Te ne proponiamo un altro, alle stesse condizioni. Non resti mai senza pulizie per un imprevisto.',
		),
		array(
			'question' => 'Fate anche uffici e condomini?',
			'answer'   => 'Sì. Per uffici e condomini il sopralluogo è gratuito e senza impegno: poi ricevi un preventivo su misura, senza prezzi fissi.',
		),
	);
}

/**
 * Valore di un campo di testo: quello compilato oppure il default.
 *
 * @param mixed  $valore  Valore letto da Carbon.
 * @param string $default Testo di ripiego.
 * @return string
 */
function tp_home_v2_b2b_pick( $valore, $default ) {
	$valore = trim( (string) $valore );
	return '' !== $valore ? $valore : $default;
}

/**
 * Argomenti di template-parts/home-b2b-band.php dai campi Carbon della pagina. Gli URL vengono da campi precompilati:
 * un URL salvato vuoto toglie il bottone (o il link); un URL non pubblicato o non valido lo toglie ugualmente.
 *
 * @param int $post_id Pagina con il template page-home-v2.php.
 * @return array
 */
function tp_home_v2_b2b_args( $post_id ) {
	$g = static function ( $campo ) use ( $post_id ) {
		return carbon_get_post_meta( $post_id, 'h2_aziende_' . $campo );
	};
	return array(
		'eyebrow'    => $g( 'eyebrow' ),
		'title'      => $g( 'title' ),
		'text'       => $g( 'text' ),
		'badges'     => tp_lines( (string) $g( 'badges' ) ),
		'cta_label'  => $g( 'cta_label' ),
		'cta_url'    => tp_home_v2_service_href( (string) $g( 'cta_url' ) ),
		'link_label' => $g( 'link_label' ),
		'link_url'   => tp_home_v2_service_href( (string) $g( 'link_url' ) ),
		'image'      => $g( 'image' ),
	);
}

/**
 * Argomenti di template-parts/faq-accordion.php per la Home v2: variante `v2`, domande dal campo Carbon (precompilato
 * con tp_home_v2_faq_defaults(); Carbon restituisce i default anche per un campo complex svuotato; righe senza domanda o risposta scartate; "Nascondi la FAQ" acceso o nessuna riga completa = items vuoto = sezione non stampata, niente JSON-LD) e
 * card "Rispondiamo di persona" con fondatori da tp_founders_contact() e bottone WhatsApp (cta_position faq_whatsapp).
 *
 * @param int $post_id Pagina con il template page-home-v2.php.
 * @return array
 */
function tp_home_v2_faq_args( $post_id ) {
	$g       = static function ( $campo ) use ( $post_id ) {
		return carbon_get_post_meta( $post_id, 'h2_faq_' . $campo );
	};
	$items   = array();
	foreach ( (array) $g( 'items' ) as $riga ) {
		$q = is_array( $riga ) ? trim( (string) ( $riga['question'] ?? '' ) ) : '';
		$a = is_array( $riga ) ? trim( (string) ( $riga['answer'] ?? '' ) ) : '';
		if ( '' !== $q && '' !== $a ) {
			$items[] = array( 'question' => $q, 'answer' => $a );
		}
	}
	if ( $g( 'hide' ) ) {
		$items = array();
	}
	$wa = tp_whatsapp_parts( TP_CT_WHATSAPP, '' );
	return array(
		'variant'  => 'v2',
		'eyebrow'  => tp_home_v2_b2b_pick( $g( 'eyebrow' ), 'Domande' ),
		'title'    => tp_home_v2_b2b_pick( $g( 'title' ), 'Le chiedete spesso.' ),
		'subtitle' => tp_home_v2_b2b_pick( $g( 'subtitle' ), 'Le risposte rapide prima di aprire l’app. Per tutto il resto, ti rispondiamo noi.' ),
		'items'    => $items,
		'contact'  => array_merge( tp_founders_contact(), array(
			'title'        => tp_home_v2_b2b_pick( $g( 'contact_title' ), 'Rispondiamo di persona' ),
			'text'         => tp_home_v2_b2b_pick( $g( 'contact_text' ), 'Scrivici su WhatsApp: ti risponde Guido o Christian, in base alla disponibilità. I fondatori, non un call center.' ),
			'cta_label'    => tp_home_v2_b2b_pick( $g( 'contact_cta' ), 'Scrivici su WhatsApp' ),
			'cta_link'     => $wa ? $wa['link'] : '',
			'cta_position' => 'faq_whatsapp',
		) ),
	);
}
