<?php
/**
 * Hero della Home v2, sezione `01-hero` (SPEC-homepage-v2, TICKET-120). Figma file UiW4TPgvqCODW5bhyFV5eK, pagina 30:
 * desktop 7903:5257, mobile 7946:3 (dentro il frame bianco 7946:2). Etichetta, un solo H1, sottotitolo, "Apri l'app" e
 * "Come funziona", riquadro "Recensioni su Google" (spento di default), riquadro QR (solo desktop), composizione di
 * due telefoni con il testo circolare (un telefono solo e testo circolare piu' piccolo su mobile).
 *
 * Argomenti (le letture Carbon stanno in page-home-v2.php; tutti con default, tutti facoltativi):
 * - title (string): prima riga dell'H1, colore scuro. Default "La pulizia cambia."
 * - accent (string): seconda riga dell'H1, verde. Default "Il modo di sceglierla, anche."
 * - label (string): etichetta sopra il titolo. Default "La nuova app di Trovapulizie, a Roma".
 * - subtitle (string): sottotitolo. Default "Professionisti verificati vicino a te e il prezzo prima di prenotare. Scegli, prenota e segui tutto dal telefono."
 * - orbit (string): testo circolare, maiuscolo, con separatori " · " e spazio finale. Default: le quattro promesse.
 * - google_badge (bool, false) e google_rating (string, ''): il riquadro "Recensioni su Google" esce solo se il primo e'
 *   vero E il voto non e' vuoto (SPEC §6: profilo sotto 5 recensioni, riquadro spento). Se non esce non resta nulla nel
 *   DOM e il riquadro QR sale al suo posto, senza buchi (colonna flex). Nessun JSON-LD AggregateRating, mai.
 *
 * Tracciamento: "Apri l'app" porta data-cta-position="hero" (tp_app_url(), stessa scheda); "Come funziona" e' l'ancora
 * #come-funziona (sezione 04, id messo da page-home-v2.php). Il QR e' un SVG statico del tema (assets/img/home-v2/qr-app.svg,
 * https://app.trovapulizie.com/ senza parametri, SPEC §4), sotto 1024 px non e' visibile.
 *
 * Prestazioni (SPEC §7.1): il telefono Home e' l'immagine LCP. Ha width e height, fetchpriority="high", e un
 * <picture> con due file (hero-lcp-desktop.webp da 1024 px, hero-lcp-mobile.webp sotto); i due file sono quelli che
 * inc/home-v2.php precarica con media query uguali. Il telefono Profilo e' lazy (su mobile e' display:none, quindi non
 * viene mai richiesto). Niente data-reveal in questa sezione: contiene l'LCP e l'ingresso non deve nasconderlo.
 * Telefoni: template-parts/phone-mockup.php (esteso); testo circolare: template-parts/tp-orbit-text.php.
 * CSS: blocco `.hv2-hero` di style.css. JS: nessuno (parallasse e rotazione arrivano con TICKET-129).
 *
 * Esempio: get_template_part( 'template-parts/home-hero-v2', null, array( 'google_badge' => false ) );
 */
$args = wp_parse_args( $args ?? array(), array(
	'title'         => '',
	'accent'        => '',
	'label'         => '',
	'subtitle'      => '',
	'orbit'         => '',
	'google_badge'  => false,
	'google_rating' => '',
) );
$hv2_pick = static function ( $valore, $default ) {
	$valore = trim( (string) $valore );
	return '' !== $valore ? $valore : $default;
};
$hv2_title    = $hv2_pick( $args['title'], 'La pulizia cambia.' );
$hv2_accent   = $hv2_pick( $args['accent'], 'Il modo di sceglierla, anche.' );
$hv2_label    = $hv2_pick( $args['label'], 'La nuova app di Trovapulizie, a Roma' );
$hv2_subtitle = $hv2_pick( $args['subtitle'], 'Professionisti verificati vicino a te e il prezzo prima di prenotare. Scegli, prenota e segui tutto dal telefono.' );
$hv2_orbit    = trim( (string) $args['orbit'] ) !== '' ? (string) $args['orbit'] : 'PROFESSIONISTI VERIFICATI · PREZZO CHIARO · PAGAMENTO SICURO · TUTTO IN UN’APP · ';
$hv2_rating   = trim( (string) $args['google_rating'] );
$hv2_google   = ! empty( $args['google_badge'] ) && '' !== $hv2_rating;
$hv2_img      = static function ( $file ) {
	return tp_theme_asset( 'assets/img/home-v2/' . $file );
};
?>
<section class="hv2-hero" data-section="01-hero">
	<span class="hv2-hero__light hv2-hero__light--a" aria-hidden="true"></span>
	<span class="hv2-hero__light hv2-hero__light--b" aria-hidden="true"></span>
	<span class="hv2-hero__light hv2-hero__light--c" aria-hidden="true"></span>
	<div class="hv2-hero__inner">
		<div class="hv2-hero__text">
			<p class="hv2-hero__label"><span class="hv2-hero__dot" aria-hidden="true"></span><?php echo esc_html( $hv2_label ); ?></p>
			<h1 class="hv2-hero__title"><span class="hv2-hero__title-line"><?php echo esc_html( $hv2_title ); ?></span> <span class="hv2-hero__title-line hv2-hero__accent"><?php echo esc_html( $hv2_accent ); ?></span></h1>
			<p class="hv2-hero__subtitle"><?php echo esc_html( $hv2_subtitle ); ?></p>
			<div class="hv2-hero__actions">
				<a class="hv2-hero__cta" href="<?php echo esc_url( tp_app_url() ); ?>" data-cta-position="hero"><span>Apri l’app</span><span class="hv2-hero__cta-arrow" aria-hidden="true">→</span></a>
				<a class="hv2-hero__ghost" href="#come-funziona">Come funziona</a>
			</div>
			<?php if ( $hv2_google ) : ?>
				<p class="hv2-hero__google"><img src="<?php echo esc_url( $hv2_img( 'google-g.svg' ) ); ?>" alt="" width="22" height="22"><strong><?php echo esc_html( $hv2_rating ); ?></strong><span class="hv2-hero__google-stars" aria-hidden="true">★★★★★</span><span>Recensioni su Google</span></p>
			<?php endif; ?>
			<div class="hv2-hero__qr">
				<img src="<?php echo esc_url( $hv2_img( 'qr-app.svg' ) ); ?>" alt="Codice QR che apre app.trovapulizie.com" width="76" height="76" loading="lazy">
				<p><strong>Sei al computer?</strong><span>Inquadra il codice e apri l’app sul telefono.</span></p>
			</div>
		</div>
		<div class="hv2-hero__stage">
			<div class="hv2-hero__stage-in">
				<?php
				get_template_part( 'template-parts/tp-orbit-text', null, array(
					'text'      => $hv2_orbit,
					'id'        => 'hv2-orbit',
					'size'      => 820,
					'radius'    => 339,
					'font_size' => 28,
					'class'     => 'hv2-hero__orbit',
				) );
				?>
				<span class="hv2-hero__disc" aria-hidden="true"></span>
				<?php
				get_template_part( 'template-parts/phone-mockup', null, array(
					'class'   => 'hv2-hero__phone hv2-hero__phone--profilo',
					'screen'  => $hv2_img( 'hero-screen-profilo.webp' ),
					'width'   => 430,
					'height'  => 930,
					'loading' => 'lazy',
					'alt'     => 'Schermata del profilo di una professionista nell’app: valutazione, prezzo orario e disponibilità',
				) );
				get_template_part( 'template-parts/phone-mockup', null, array(
					'class'         => 'hv2-hero__phone hv2-hero__phone--home',
					'screen'        => $hv2_img( 'hero-lcp-desktop.webp' ),
					'screen_mobile' => $hv2_img( 'hero-lcp-mobile.webp' ),
					'width'         => 483,
					'height'        => 1044,
					'fetchpriority' => 'high',
					'alt'           => 'Schermata Home dell’app Trovapulizie: i servizi per casa e aziende a Roma',
				) );
				?>
			</div>
		</div>
	</div>
</section>
