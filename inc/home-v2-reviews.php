<?php
/**
 * Recensioni e fascia loghi della Home v2 (SPEC-homepage-v2 US-03, TICKET-124): lettura dei campi Carbon e argomenti
 * di template-parts/home-reviews-notes.php.
 *
 * Una sola funzione, tp_home_v2_reviews_args(), perche' il template di pagina resti di poche righe. Non ci sono default:
 * campi mai salvati o vuoti = array vuoti = la sezione (o la sola fascia) non viene stampata. [BLOCCO] Nessuna recensione,
 * foto o logo di esempio entra in questo file ne' nel tema: contenuti solo veri e con consenso; loghi di Igea Clean
 * Service, Ready Solution e Disinfestazione Veloce solo con autorizzazione scritta (SPEC §6).
 */

/**
 * Argomenti di home-reviews-notes.php letti dai campi della pagina.
 *
 * @param int $post_id Pagina col template page-home-v2.php.
 * @return array{title:string,eyebrow:string,items:array,logos_label:string,logos:array}
 */
function tp_home_v2_reviews_args( $post_id ) {
	$items = array();
	foreach ( (array) carbon_get_post_meta( $post_id, 'h2_recensioni_items' ) as $riga ) {
		if ( is_array( $riga ) ) {
			$items[] = array(
				'text'         => $riga['text'] ?? '',
				'name'         => $riga['name'] ?? '',
				'zone'         => $riga['zone'] ?? '',
				'service'      => $riga['service'] ?? '',
				'stars'        => $riga['stars'] ?? 5,
				'photo_before' => $riga['photo_before'] ?? '',
				'photo_after'  => $riga['photo_after'] ?? '',
				'avatar'       => $riga['avatar'] ?? '',
			);
		}
	}
	$logos = array();
	foreach ( (array) carbon_get_post_meta( $post_id, 'h2_loghi_items' ) as $riga ) {
		if ( is_array( $riga ) ) {
			$logos[] = array(
				'name'  => $riga['name'] ?? '',
				'image' => $riga['image'] ?? '',
				'kind'  => $riga['kind'] ?? 'logo',
			);
		}
	}
	return array(
		'title'       => (string) carbon_get_post_meta( $post_id, 'h2_recensioni_title' ),
		'eyebrow'     => (string) carbon_get_post_meta( $post_id, 'h2_recensioni_eyebrow' ),
		'items'       => $items,
		'logos_label' => (string) carbon_get_post_meta( $post_id, 'h2_loghi_label' ),
		'logos'       => $logos,
	);
}
