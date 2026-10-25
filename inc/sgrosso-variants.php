<?php
/**
 * Testi di default per variante della landing Sgrosso (TICKET-48).
 *
 * Un solo template (page-sgrosso.php) serve cinque pagine: la generica /roma/sgrosso-casa e quattro
 * verticali per ad group. Cambiano solo i testi elencati qui, riportati parola per parola dal
 * VARIANT_CONFIG della landing React (front-end/src/pages/marketing/LandingSgrossoSopralluogo.tsx).
 * I campi Carbon della pagina, se compilati, vincono su questi default (tp_sg_text()).
 *
 * `scenario` = chiave del tipo di pulizia preselezionato nel form e tab aperta in Quando serve.
 * `landing`  = identificatore negli eventi dataLayer (preventivo_sgrosso_*, marketing_*).
 */

function tp_sg_variants() {
	return array(
		'sgrosso-casa' => array(
			'scenario'         => 'post-ristrutturazione',
			'eyebrow'          => 'SGROSSO CASA · ROMA',
			'h1'               => 'Sgrosso casa: la tua casa torna come nuova',
			'subtitle'         => 'Pulizia straordinaria una tantum post-cantiere, post-trasloco o casa trascurata — non un abbonamento.',
			'page_title'       => 'Sgrosso casa Roma — Pulizia straordinaria una tantum, sopralluogo gratuito | Trovapulizie',
			'page_description' => 'Sgrosso casa a Roma: pulizia straordinaria post-cantiere o trasloco con imprese verificate. Sopralluogo gratuito, prezzo fisso, nessuna sorpresa.',
			'quando_serve_h2'  => 'Uno sgrosso per ogni situazione',
			'confrontiamo_sub' => "Non scegli a caso: mettiamo a confronto le imprese di pulizia verificate della tua zona e ti proponiamo l'offerta più conveniente per il tuo sgrosso.",
			'cta_final_h2'     => 'Pronto a rimettere a nuovo casa tua?',
			'faq_first_q'      => 'Quanto costa uno sgrosso?',
			'faq_first_a'      => 'Dipende da dimensione e stato della casa: per questo il sopralluogo è gratuito. Vedi il prezzo fisso prima di decidere, senza sorprese.',
			'calc_h2'          => 'Quanto costa il tuo sgrosso?',
			'hero_image'       => 'sgrosso-hero',
		),
		'post-ristrutturazione' => array(
			'scenario'         => 'post-ristrutturazione',
			'eyebrow'          => 'PULIZIA POST-RISTRUTTURAZIONE · ROMA',
			'h1'               => 'Dopo il cantiere, la casa pronta da abitare in un giorno',
			'subtitle'         => 'Interventi intensivi post lavori edili e traslochi — pulizie di fondo, non pulizie ordinarie.',
			'page_title'       => 'Pulizia post-ristrutturazione Roma — Sopralluogo gratuito | Trovapulizie',
			'page_description' => 'Pulizia post-ristrutturazione a Roma: via polvere e residui di cantiere con imprese verificate. Sopralluogo gratuito e preventivo a prezzo fisso.',
			'quando_serve_h2'  => 'Cosa sparisce dopo il cantiere',
			'confrontiamo_sub' => "Non scegli a caso: mettiamo a confronto le imprese di pulizia verificate della tua zona e ti proponiamo l'offerta più conveniente per il tuo post-cantiere.",
			'cta_final_h2'     => 'Pronta ad abitarla, senza polvere?',
			'faq_first_q'      => 'Quanto costa una pulizia post-ristrutturazione?',
			'faq_first_a'      => 'Dipende da dimensione e quantità di residui di cantiere: per questo il sopralluogo è gratuito. Vedi il prezzo fisso prima di decidere, senza sorprese.',
			'calc_h2'          => 'Quanto costa la pulizia post-cantiere?',
			'hero_image'       => 'sgrosso-hero-post-ristrutturazione',
		),
		'trasloco' => array(
			'scenario'         => 'trasloco',
			'eyebrow'          => 'PULIZIA POST TRASLOCO · ROMA',
			'h1'               => 'Prima di entrare o dopo aver lasciato: la casa pulita per il trasloco',
			'subtitle'         => 'Interventi intensivi per traslochi — pulizie di fondo, non pulizie ordinarie.',
			'page_title'       => 'Pulizia post trasloco Roma — Sopralluogo gratuito | Trovapulizie',
			'page_description' => 'Pulizia post trasloco a Roma, prima di entrare o dopo aver lasciato casa. Imprese verificate, sopralluogo gratuito, preventivo a prezzo fisso.',
			'quando_serve_h2'  => 'Prima di entrare, dopo aver lasciato',
			'confrontiamo_sub' => "Non scegli a caso: mettiamo a confronto le imprese di pulizia verificate della tua zona e ti proponiamo l'offerta più conveniente per il tuo trasloco.",
			'cta_final_h2'     => 'Pronta per il trasloco?',
			'faq_first_q'      => 'Quanto costa una pulizia post-trasloco?',
			'faq_first_a'      => 'Dipende da dimensione e stato della casa — quella che lasci o quella dove entri: per questo il sopralluogo è gratuito. Vedi il prezzo fisso prima di decidere, senza sorprese.',
			'calc_h2'          => 'Quanto costa la pulizia post-trasloco?',
			'hero_image'       => 'sgrosso-hero-trasloco',
		),
		'fine-affitto' => array(
			'scenario'         => 'fine-affitto',
			'eyebrow'          => 'PULIZIA FINE AFFITTO · ROMA',
			'h1'               => 'Pulizia fine affitto: pronta per la riconsegna',
			'subtitle'         => 'Intervento straordinario una tantum prima della riconsegna chiavi — non un abbonamento.',
			'page_title'       => 'Pulizia fine affitto Roma — Sopralluogo gratuito | Trovapulizie',
			'page_description' => "Pulizia fine affitto a Roma per la riconsegna dell'immobile senza contestazioni. Imprese verificate, sopralluogo gratuito, prezzo fisso garantito.",
			'quando_serve_h2'  => 'Cosa serve per la riconsegna',
			'confrontiamo_sub' => "Non scegli a caso: mettiamo a confronto le imprese di pulizia verificate della tua zona e ti proponiamo l'offerta più conveniente per la riconsegna del tuo affitto.",
			'cta_final_h2'     => 'Pronta per la riconsegna, senza contestazioni?',
			'faq_first_q'      => 'Quanto costa una pulizia di fine affitto?',
			'faq_first_a'      => 'Dipende da dimensione e stato della casa: per questo il sopralluogo è gratuito. Vedi il prezzo fisso prima di decidere, senza sorprese — e senza rischiare la cauzione.',
			'calc_h2'          => 'Quanto costa la pulizia di fine affitto?',
			'hero_image'       => 'sgrosso-hero-fine-affitto',
		),
		'prima-pulizia-a-fondo' => array(
			'scenario'         => 'prima-pulizia-a-fondo',
			'eyebrow'          => 'PULIZIA STRAORDINARIA CASA · ROMA',
			'h1'               => 'Pulizia straordinaria: via lo sporco accumulato nel tempo',
			'subtitle'         => 'Intervento straordinario una tantum per case trascurate da tempo — non un abbonamento.',
			'page_title'       => 'Pulizia straordinaria casa Roma — Sopralluogo gratuito | Trovapulizie',
			'page_description' => 'Pulizia straordinaria a Roma per case trascurate da tempo: via lo sporco accumulato con imprese verificate. Sopralluogo gratuito, prezzo fisso.',
			'quando_serve_h2'  => "Cosa sparisce, anche se è lì da anni",
			'confrontiamo_sub' => "Non scegli a caso: mettiamo a confronto le imprese di pulizia verificate della tua zona e ti proponiamo l'offerta più conveniente per la tua pulizia straordinaria.",
			'cta_final_h2'     => 'Pronta a ripartire da zero?',
			'faq_first_q'      => 'Quanto costa una pulizia straordinaria?',
			'faq_first_a'      => 'Dipende da dimensione e livello di sporco accumulato: per questo il sopralluogo è gratuito. Vedi il prezzo fisso prima di decidere, senza sorprese.',
			'calc_h2'          => 'Quanto costa la tua pulizia straordinaria?',
			'hero_image'       => 'sgrosso-hero-prima-pulizia',
		),
	);
}

/** Default della variante richiesta; una chiave sconosciuta ricade sulla generica. */
function tp_sg_defaults( $variant ) {
	$all = tp_sg_variants();
	return isset( $all[ $variant ] ) ? $all[ $variant ] : $all['sgrosso-casa'];
}

/**
 * Testo di una sezione: il campo Carbon della pagina se compilato, altrimenti il default della variante.
 * $default_key e' la chiave in tp_sg_defaults(); null = nessun default (campo puramente editoriale).
 */
function tp_sg_text( $field, $defaults, $default_key = null, $fallback = '' ) {
	$v = carbon_get_the_post_meta( $field );
	if ( is_string( $v ) && trim( $v ) !== '' ) {
		return $v;
	}
	if ( $default_key !== null && isset( $defaults[ $default_key ] ) ) {
		return $defaults[ $default_key ];
	}
	return $fallback;
}
