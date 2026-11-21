<?php
/**
 * Sezione zone della Home v2, `07-zone` (SPEC-homepage-v2 US-04, TICKET-125). Figma file UiW4TPgvqCODW5bhyFV5eK,
 * pagina 30: desktop 7903:7567 (mappa 7903:7580, controllo 7909:10298, esito 7909:10303), mobile 7946:1972.
 *
 * Contenuto: scritta, titolo "Arriviamo anche da te?", sottotitolo, controllo del CAP (nascosto: lo mostra
 * assets/js/zones-check.js), chip delle zone attive come testo e la mappa di Roma con il radar.
 *
 * Controllo del CAP, forma dei dati e messaggi (nessuna chiamata di rete: la lista e' nel markup):
 * - la lista dei CAP attivi arriva da tp_home_v2_zones_caps() (inc/home-v2-zones.php) ed esce in data-caps del form come
 *   JSON { "00186": "Centro Storico (Navona, Pantheon)", ... }; lista vuota = il form non viene stampato;
 * - il form e' stampato con l'attributo hidden: senza JavaScript non si vede, mappa e chip restano come testo;
 * - esiti (aria-live="polite"):
 *   coperto  -> "<CAP> · Ci siamo: sei in <quartiere>" e il link "Apri l'app" (data-cta-position="zones_check");
 *   non coperto (CAP di Roma fuori lista) -> "Non ancora da te" e il link "Scrivici su WhatsApp" (tp_whatsapp_parts(),
 *   data-cta-position="zones_whatsapp", il testo precompilato porta il CAP scritto); nessun campo email;
 *   non valido (non 5 cifre, o fuori da 00118-00199) -> "Scrivi un CAP di Roma (5 cifre)"; il campo resta compilato e
 *   prende il focus.
 *
 * Mappa: i path di zones-map-cap.svg che cadono nel disco (SVG inline, il CSS colora i singoli CAP); i CAP attivi in verde.
 * 00194 e' attivo ma non ha un poligono: l'esito e' positivo, la mappa non lo evidenzia. Radar, impulsi, monumenti e
 * etichette sono decorativi (aria-hidden); l'elenco dei quartieri e' il testo equivalente.
 *
 * Argomenti (le letture Carbon stanno in page-home-v2.php; tutti con default):
 * - title (string): H2. Default "Arriviamo anche da te?" (maiuscolo da CSS).
 * - eyebrow (string): default "Le zone". - subtitle (string): default "Siamo partiti dal centro di Roma e ci allarghiamo
 *   quartiere per quartiere."
 * - caps (array): [cap => nome] attivi (tp_home_v2_zones_caps()).
 *
 * CSS: blocco `TICKET-125` di style.css (.hv2-zones). JS: assets/js/zones-check.js.
 */
$args = wp_parse_args( $args ?? array(), array(
	'title'    => '',
	'eyebrow'  => '',
	'subtitle' => '',
	'caps'     => array(),
) );
$hz_pick = static function ( $v, $default ) {
	$v = trim( (string) $v );
	return '' !== $v ? $v : $default;
};
$hz_caps = (array) $args['caps'];
$hz_wa   = tp_whatsapp_parts( TP_CT_WHATSAPP, '' );
// Riquadro della mappa (viewBox "142 -16 440 440"); x e y in percentuale a partire da coordinate dell'SVG.
$hz_pct  = static function ( $x, $y ) {
	return array( round( ( $x - 142 ) / 4.4, 2 ), round( ( $y + 16 ) / 4.4, 2 ) );
};
// Monumenti (lat/lon proiettati sull'SVG dei CAP) e impulsi delle due zone con etichetta.
$hz_mon   = array(
	array( 'san-pietro', 'San Pietro', 'zona-sanpietro.svg', 309, 164, 'sotto' ),
	array( 'pantheon', 'Pantheon', 'zona-pantheon.svg', 346, 172, 'sopra' ),
	array( 'colosseo', 'Colosseo', 'zona-colosseo.svg', 372, 191, 'sotto' ),
);
$hz_pulse = array(
	array( 'prati', 'Prati', 318, 148, 'sinistra' ),
	array( 'centro', 'Centro storico', 356, 162, 'destra' ),
);
$hz_chips   = array();
$hz_featured = array( '00186', '00192', '00183', '00153', '00184', '00196' );
foreach ( $hz_caps as $cap => $nome ) {
	$hz_chips[ $cap ] = tp_home_v2_zones_short_name( $nome );
}
$hz_first = array_intersect_key( $hz_chips, array_flip( $hz_featured ) );
$hz_rest  = array_diff_key( $hz_chips, $hz_first );
$hz_img   = static function ( $f ) {
	return tp_theme_asset( 'assets/img/home-v2/' . $f );
};
?>
<section class="hv2-zones" data-section="07-zone" aria-labelledby="hv2-zones-titolo">
	<span class="hv2-zones__light hv2-zones__light--a" aria-hidden="true"></span>
	<span class="hv2-zones__light hv2-zones__light--b" aria-hidden="true"></span>
	<div class="hv2-zones__inner">
		<div class="hv2-zones__text" data-reveal>
			<p class="hv2-zones__eyebrow"><?php echo esc_html( $hz_pick( $args['eyebrow'], 'Le zone' ) ); ?></p>
			<h2 class="hv2-zones__title" id="hv2-zones-titolo"><?php echo esc_html( $hz_pick( $args['title'], 'Arriviamo anche da te?' ) ); ?></h2>
			<p class="hv2-zones__subtitle"><?php echo esc_html( $hz_pick( $args['subtitle'], 'Siamo partiti dal centro di Roma e ci allarghiamo quartiere per quartiere.' ) ); ?></p>

			<?php if ( $hz_caps ) : ?>
				<form class="hv2-zones__check" hidden novalidate
					data-caps="<?php echo esc_attr( wp_json_encode( (object) $hz_caps ) ); ?>"
					data-app-url="<?php echo esc_url( tp_app_url() ); ?>"
					data-wa-url="<?php echo esc_url( $hz_wa ? $hz_wa['link'] : '' ); ?>">
					<div class="hv2-zones__field">
						<img class="hv2-zones__pin" src="<?php echo esc_url( $hz_img( 'zona-pin.svg' ) ); ?>" alt="" width="14" height="18" decoding="async">
						<label class="screen-reader-text" for="hv2-zones-cap">Il tuo CAP</label>
						<input class="hv2-zones__input" id="hv2-zones-cap" name="cap" type="text" inputmode="numeric" autocomplete="postal-code" maxlength="10" placeholder="Il tuo CAP" aria-describedby="hv2-zones-esito">
						<button class="hv2-zones__submit" type="submit">Controlla</button>
					</div>
					<div class="hv2-zones__result" id="hv2-zones-esito" role="status" aria-live="polite"></div>
				</form>
			<?php endif; ?>

			<?php if ( $hz_chips ) : ?>
				<div class="hv2-zones__list">
					<p class="hv2-zones__list-label" id="hv2-zones-lista">Le zone attive</p>
					<ul class="hv2-zones__chips" aria-labelledby="hv2-zones-lista">
						<?php foreach ( $hz_first as $cap => $nome ) : ?>
							<li class="hv2-zones__chip" data-cap="<?php echo esc_attr( $cap ); ?>"><?php echo esc_html( $nome ); ?></li>
						<?php endforeach; ?>
					</ul>
					<?php if ( $hz_rest ) : ?>
						<details class="hv2-zones__more">
							<summary>Tutti i quartieri (<?php echo (int) count( $hz_chips ); ?>)</summary>
							<ul class="hv2-zones__chips hv2-zones__chips--all">
								<?php foreach ( $hz_rest as $cap => $nome ) : ?>
									<li class="hv2-zones__chip" data-cap="<?php echo esc_attr( $cap ); ?>"><?php echo esc_html( $nome ); ?></li>
								<?php endforeach; ?>
							</ul>
						</details>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="hv2-zones__map" data-reveal style="--i: 1" aria-hidden="true">
			<div class="hv2-zones__disc">
				<div class="hv2-zones__stage">
					<svg class="hv2-zones__svg" viewBox="142 -16 440 440" focusable="false"><g class="hv2-zones__caps"><?php echo tp_home_v2_zones_paths( array_keys( $hz_caps ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- path del tema, d e data-cap validati ?></g></svg>
					<?php foreach ( $hz_pulse as $p ) : ?>
						<?php list( $px, $py ) = $hz_pct( $p[2], $p[3] ); ?>
						<span class="hv2-zones__pulse hv2-zones__pulse--<?php echo esc_attr( $p[0] ); ?>" style="left: <?php echo esc_attr( $px ); ?>%; top: <?php echo esc_attr( $py ); ?>%"><i></i><i></i><b></b><em class="hv2-zones__tag hv2-zones__tag--<?php echo esc_attr( $p[4] ); ?>"><?php echo esc_html( $p[1] ); ?></em></span>
					<?php endforeach; ?>
					<?php foreach ( $hz_mon as $mo ) : ?>
						<?php list( $mx, $my ) = $hz_pct( $mo[3], $mo[4] ); ?>
						<span class="hv2-zones__mon hv2-zones__mon--<?php echo esc_attr( $mo[0] ); ?>" style="left: <?php echo esc_attr( $mx ); ?>%; top: <?php echo esc_attr( $my ); ?>%"><img src="<?php echo esc_url( $hz_img( $mo[2] ) ); ?>" alt="" width="75" height="75" decoding="async"><em class="hv2-zones__mon-label hv2-zones__mon-label--<?php echo esc_attr( $mo[5] ); ?>"><?php echo esc_html( $mo[1] ); ?></em></span>
					<?php endforeach; ?>
				</div>
				<span class="hv2-zones__ring hv2-zones__ring--1"></span>
				<span class="hv2-zones__ring hv2-zones__ring--2"></span>
				<span class="hv2-zones__ring hv2-zones__ring--3"></span>
				<span class="hv2-zones__sweep" data-tp-loop></span>
			</div>
		</div>
	</div>
</section>
