<?php
/**
 * Chiusura "Apri l'app" della Home v2, sezione `10-chiusura` (SPEC-homepage-v2 §2 e §6, TICKET-127). Figma file
 * UiW4TPgvqCODW5bhyFV5eK, pagina 30: desktop 7903:7666 (1440 x 630), mobile 7946:2115 (390 x 754), note di animazione 7882:4118.
 * Fascia verde #37B299 con "ULTIMA COSA", il titolo a 128 px, il sottotitolo, il bottone "Apri l'app" (bianco con testo scuro),
 * il riquadro QR e la nota sullo store; a destra lo smartphone con l'avviso "Aggiunta alla schermata Home" e il tentacolo del polpo
 * che lo regge.
 *
 * Regola mobile (SPEC §2, decisione di Guido 21/11): sotto 1024 px il QR e il tentacolo NON compaiono e le loro immagini NON vengono
 * richieste. Il QR e' un <img loading="lazy"> dentro un riquadro display:none sotto 1024 px (Chrome non scarica un'immagine lazy
 * nascosta); il tentacolo sta in due <picture> con <source media="(min-width: 1024px)"> e, come <img> di ripiego, una GIF trasparente
 * 1 x 1 in data URI: sotto 1024 px il browser sceglie la GIF e non scarica nulla. Il bottone sale a tutta larghezza.
 *
 * [ATTENZIONE] Eccezione di brand dichiarata: il bottone di questa chiusura e' BIANCO con testo #062A22 (sul verde pieno un bottone
 * verde non si vedrebbe). La regola "testo bianco nei bottoni verdi pieni" vale per i bottoni verdi (hero, barra); qui non si applica.
 *
 * Argomenti (le letture Carbon stanno in page-home-v2.php; tutti facoltativi, vuoto = default):
 * - eyebrow (string): "ULTIMA COSA" (il maiuscolo lo fa il CSS).
 * - title (string): "Apri l'app." (H2, maiuscolo da CSS).
 * - subtitle (string): "La prima pulizia la prenoti in due minuti. Il resto lo fa chi sa farlo."
 * - cta_label (string): "Apri l'app".
 * - qr_title (string): "Sei al computer?"; qr_text (string): "Inquadra e aprila sul telefono."
 * - note (string): "Si apre dal browser e la aggiungi alla Home in un tocco. Non serve passare dallo store." Vale finche' l'app non e'
 *   sugli store (SPEC §6): per toglierla si spunta note_hide nel tab Chiusura.
 * - note_hide (bool, false): non stampa la nota.
 *
 * Tracciamento: "Apri l'app" porta data-cta-position="cta_final" e va su tp_app_url() nella stessa scheda, senza parametri (gli UTM li
 * aggiunge l'inoltro di SPEC-utm-verso-app, non il sito). Il QR e' lo stesso SVG della hero (assets/img/home-v2/qr-app.svg, versione
 * https://app.trovapulizie.com/ senza parametri). Il tentacolo e l'avviso del telefono sono decorativi (alt vuoto, aria-hidden).
 * Il telefono e' template-parts/phone-mockup.php con lo schermo assets/img/home-v2/cta-screen.svg; l'avviso e' HTML sopra lo schermo
 * (TICKET-129: cade dall'alto con rimbalzo, poi battito dell'icona; telefono che sale, tentacoli che entrano da destra: CSS legato a .is-in del palco).
 *
 * Immagini del tentacolo: Higgsfield (account Digital On, nano_banana_pro, 20/11), scontornate; qui WebP (cta-tentacolo.webp 1100 x 1473,
 * cta-tentacolo-ricciolo.webp 420 x 400). Futuro: video generato al posto delle immagini (nota 7882:4118).
 * Fondo: la fascia verde ha il proprio fondo (main.hv2 da' la menta alle altre sezioni, 0.75.1); la luce dietro il telefono non e' tagliata
 * dal bordo della sezione, il tentacolo e il telefono si' tagliano solo in verticale (.hv2-cta__clip, clip-path con margine laterale: il telefono esce
 * dal bordo inferiore come nel Figma).
 * CSS: blocco `TICKET-127` in fondo a style.css (classi .hv2-cta). JS: nessuno (reveal.js per gli ingressi; il movimento e' CSS, blocco TICKET-129).
 *
 * Esempio: get_template_part( 'template-parts/home-cta-v2', null, array( 'note_hide' => true ) );
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'   => '',
	'title'     => '',
	'subtitle'  => '',
	'cta_label' => '',
	'qr_title'  => '',
	'qr_text'   => '',
	'note'      => '',
	'note_hide' => false,
) );
$hv2_pick = static function ( $valore, $default ) {
	$valore = trim( (string) $valore );
	return '' !== $valore ? $valore : $default;
};
$hv2_img = static function ( $file ) {
	return tp_theme_asset( 'assets/img/home-v2/' . $file );
};
$hv2_eyebrow = $hv2_pick( $args['eyebrow'], 'Ultima cosa' );
$hv2_title   = $hv2_pick( $args['title'], 'Apri l’app.' );
$hv2_sub     = $hv2_pick( $args['subtitle'], 'La prima pulizia la prenoti in due minuti. Il resto lo fa chi sa farlo.' );
$hv2_cta     = $hv2_pick( $args['cta_label'], 'Apri l’app' );
$hv2_qr_t    = $hv2_pick( $args['qr_title'], 'Sei al computer?' );
$hv2_qr_x    = $hv2_pick( $args['qr_text'], 'Inquadra e aprila sul telefono.' );
$hv2_note    = empty( $args['note_hide'] ) ? $hv2_pick( $args['note'], 'Si apre dal browser e la aggiungi alla Home in un tocco. Non serve passare dallo store.' ) : '';
// GIF trasparente 1 x 1: ripiego del <picture> sotto 1024 px (nessuna richiesta di rete).
$hv2_pixel = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';
?>
<section class="hv2-cta" data-section="10-chiusura" aria-labelledby="hv2-cta-title">
	<div class="hv2-cta__in">
		<div class="hv2-cta__text">
			<p class="hv2-cta__eyebrow" data-reveal style="--i:0"><?php echo esc_html( $hv2_eyebrow ); ?></p>
			<h2 class="hv2-cta__title" id="hv2-cta-title" data-reveal style="--i:1"><?php echo esc_html( $hv2_title ); ?></h2>
			<p class="hv2-cta__sub" data-reveal style="--i:2"><?php echo esc_html( $hv2_sub ); ?></p>
			<div class="hv2-cta__actions" data-reveal style="--i:3">
				<a class="hv2-cta__btn" href="<?php echo esc_url( tp_app_url() ); ?>" data-cta-position="cta_final"><span><?php echo esc_html( $hv2_cta ); ?></span><span class="hv2-cta__btn-arrow" aria-hidden="true">→</span></a>
				<div class="hv2-cta__qr">
					<img src="<?php echo esc_url( $hv2_img( 'qr-app.svg' ) ); ?>" alt="Codice QR che apre app.trovapulizie.com" width="72" height="72" loading="lazy">
					<p><strong><?php echo esc_html( $hv2_qr_t ); ?></strong><span><?php echo esc_html( $hv2_qr_x ); ?></span></p>
				</div>
			</div>
			<?php if ( '' !== $hv2_note ) : ?>
				<p class="hv2-cta__note" data-reveal style="--i:4"><img src="<?php echo esc_url( $hv2_img( 'cta-add-icon.svg' ) ); ?>" alt="" width="14" height="14" loading="lazy"><span><?php echo esc_html( $hv2_note ); ?></span></p>
			<?php endif; ?>
		</div>
		<div class="hv2-cta__stage" data-reveal style="--i:2">
			<div class="hv2-cta__scale">
				<span class="hv2-cta__glow" aria-hidden="true"></span>
				<div class="hv2-cta__clip">
				<picture class="hv2-cta__tent hv2-cta__tent--back" aria-hidden="true">
					<source media="(min-width: 1024px)" srcset="<?php echo esc_url( $hv2_img( 'cta-tentacolo.webp' ) ); ?>">
					<img src="<?php echo esc_attr( $hv2_pixel ); ?>" alt="" width="883" height="1183">
				</picture>
				<div class="hv2-cta__device">
					<?php
					get_template_part( 'template-parts/phone-mockup', null, array(
						'class'   => 'hv2-cta__phone',
						'screen'  => $hv2_img( 'cta-screen.svg' ),
						'width'   => 322,
						'height'  => 685,
						'loading' => 'lazy',
						'alt'     => '',
					) );
					?>
					<div class="hv2-cta__notif" aria-hidden="true">
						<img src="<?php echo esc_url( $hv2_img( 'cta-notif-icon.svg' ) ); ?>" alt="" width="36" height="33" loading="lazy">
						<p><strong>Trovapulizie</strong><span>Aggiunta alla schermata Home</span></p>
					</div>
				</div>
				<picture class="hv2-cta__tent hv2-cta__tent--curl" aria-hidden="true">
					<source media="(min-width: 1024px)" srcset="<?php echo esc_url( $hv2_img( 'cta-tentacolo-ricciolo.webp' ) ); ?>">
					<img src="<?php echo esc_attr( $hv2_pixel ); ?>" alt="" width="207" height="197">
				</picture>
				</div>
			</div>
		</div>
	</div>
</section>
