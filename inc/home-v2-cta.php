<?php
/**
 * Chiusura "Apri l'app" della Home v2 (SPEC-homepage-v2, TICKET-127): legge i campi Carbon del tab "Chiusura" (container
 * "Home v2 - Sezioni", prefisso h2_chiusura_) e li passa a template-parts/home-cta-v2.php. Il markup, i default e la regola
 * "mobile senza QR ne' tentacolo" sono documentati in quel template-part; i campi sono dichiarati in inc/carbon-fields.php.
 *
 * Campi: h2_chiusura_title (titolo), _eyebrow, _subtitle, _cta_label (bottone), _qr_title, _qr_text, _note, _note_hide
 * (casella: nasconde la nota sullo store, da spuntare quando l'app sara' sugli store). Vuoto = default del template.
 */

/**
 * Argomenti di template-parts/home-cta-v2.php letti dai campi Carbon della pagina.
 *
 * @param int $post_id Pagina col template page-home-v2.php.
 * @return array<string,mixed>
 */
function tp_home_v2_cta_args( $post_id ) {
	$campo = static function ( $chiave ) use ( $post_id ) {
		return carbon_get_post_meta( $post_id, 'h2_chiusura_' . $chiave );
	};
	return array(
		'title'     => $campo( 'title' ),
		'eyebrow'   => $campo( 'eyebrow' ),
		'subtitle'  => $campo( 'subtitle' ),
		'cta_label' => $campo( 'cta_label' ),
		'qr_title'  => $campo( 'qr_title' ),
		'qr_text'   => $campo( 'qr_text' ),
		'note'      => $campo( 'note' ),
		'note_hide' => (bool) $campo( 'note_hide' ),
	);
}
