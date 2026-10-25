<?php
/**
 * Template Name: Thank-you
 *
 * Pagina di atterraggio dopo un lead (Figma 4418:922, mobile 4414:2; testi dal live ThankYouPage.tsx): riepilogo
 * della richiesta letto da sessionStorage.tp_thankyou (scritto da lead-form.js), i tre passi successivi e la card
 * "Crea l'account" che porta al signup dell'app con l'email precompilata in query string (TICKET-42).
 * noindex e fuori dalla sitemap (functions.php). Testata delle landing con la CTA verso la landing Sgrosso.
 *
 * [ATTENZIONE] Il signup dell'app legge il prefill solo dallo stato del router: finche' Niccolo' non aggiunge la
 * lettura di ?email= (spec in TICKET-42), il bottone apre il signup senza email precompilata.
 */
$t = function ( $field, $fallback ) {
	$v = carbon_get_the_post_meta( $field );
	return is_string( $v ) && trim( $v ) !== '' ? $v : $fallback;
};
$sgrosso_url = home_url( '/roma/sgrosso-casa/' );
$signup_url  = tp_app_url( 'searcher-signup' );

get_header( 'landing', array(
	'promo_desktop' => $t( 'ty_promo_desktop', 'Sopralluogo gratuito · -30€ sul primo lavoro · ti rispondiamo entro 1 ora' ),
	'promo_mobile'  => $t( 'ty_promo_mobile', 'Sopralluogo gratuito · -30€ sul primo lavoro' ),
	'links'         => array(),
	'phone_label'   => $t( 'ty_phone', '06 9151 6075' ),
	'phone_href'    => 'tel:' . preg_replace( '/\D+/', '', $t( 'ty_phone', '06 9151 6075' ) ),
	'cta_label'     => $t( 'ty_nav_cta', 'Sopralluogo gratuito' ),
	'cta_short'     => 'Sopralluogo',
	'cta_href'      => $sgrosso_url . '#form',
) );
?>
<main class="ty" id="contenuto" data-signup-url="<?php echo esc_url( $signup_url ); ?>">
	<section class="ty__inner">
		<span class="ty__check" aria-hidden="true"><?php echo tp_icon_span( 'assets/icons/ic-check.svg', 'ty__check-icon' ); ?></span>
		<p class="ty__badge"><?php echo esc_html( $t( 'ty_badge', 'RICHIESTA RICEVUTA' ) ); ?></p>
		<h1><?php echo nl2br( esc_html( $t( 'ty_title', "Richiesta ricevuta\nCi pensiamo noi" ) ) ); ?></h1>
		<p class="ty__lead"><?php echo esc_html( $t( 'ty_text', "Ti scriviamo entro 1 ora con i prossimi passi. Intanto crea l'account: segui la richiesta, ricevi i preventivi e parli col team da un unico posto." ) ); ?></p>

		<div class="ty__grid">
			<div class="ty__card">
				<div>
					<h2 class="ty__card-title">La tua richiesta</h2>
					<p class="ty__card-sub">Ecco cosa ci hai inviato</p>
					<dl class="ty__rows">
						<div class="ty__row" data-row="zona" hidden><dt>Zona / CAP</dt><dd data-val="zona"></dd></div>
						<div class="ty__row" data-row="tipoCasa" hidden><dt>Tipo di casa</dt><dd data-val="tipoCasa"></dd></div>
						<div class="ty__row" data-row="email" hidden><dt>Email</dt><dd data-val="email"></dd></div>
						<div class="ty__row"><dt>Coupon</dt><dd class="ty__coupon" data-val="coupon"><?php echo esc_html( $t( 'ty_coupon', '-30€ sul primo lavoro' ) ); ?></dd></div>
					</dl>
				</div>
				<hr class="ty__divider">
				<div>
					<h3 class="ty__card-title ty__card-title--sm">Cosa succede ora</h3>
					<ol class="ty__steps">
						<li><span class="ty__step-n">1</span><span><b>Ti contattiamo entro 1 ora</b><span>Ti scriviamo per email o WhatsApp e fissiamo il sopralluogo.</span></span></li>
						<li><span class="ty__step-n">2</span><span><b>Sopralluogo gratuito</b><span>Vediamo la casa, senza impegno e senza costi.</span></span></li>
						<li><span class="ty__step-n">3</span><span><b>Preventivo a prezzo fisso</b><span>Concordiamo tempi e prezzo, confermato dopo la visita.</span></span></li>
					</ol>
				</div>
			</div>

			<div class="ty__card">
				<div>
					<h2 class="ty__card-title">Crea l'account e monitora</h2>
					<p class="ty__card-sub">Segui lo stato della richiesta e ricevi i preventivi da un unico posto.</p>
				</div>
				<form class="ty__form" data-ty-form novalidate>
					<label class="ty__label" for="ty-email">Email</label>
					<div class="ty__input-wrap">
						<input class="ty__input" id="ty-email" name="email" type="email" inputmode="email" autocomplete="email" placeholder="la-tua-email@esempio.it">
						<span class="ty__verified" data-verified hidden>verificata</span>
					</div>
					<button type="submit" class="tp-btn tp-btn--primary ty__submit">Crea account e monitora</button>
				</form>
				<div class="ty__or"><span>oppure</span></div>
				<a class="ty__google" href="<?php echo esc_url( $signup_url ); ?>" data-ty-google>
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
					Continua con Google
				</a>
				<ul class="ty__perks">
					<li><?php echo tp_icon_span( 'assets/icons/ic-check.svg', 'ty__perk-check' ); ?><span>Segui lo stato in tempo reale</span></li>
					<li><?php echo tp_icon_span( 'assets/icons/ic-check.svg', 'ty__perk-check' ); ?><span>Ricevi e confronti i preventivi</span></li>
					<li><?php echo tp_icon_span( 'assets/icons/ic-check.svg', 'ty__perk-check' ); ?><span>Parli col team e col cleaner</span></li>
				</ul>
				<p class="ty__legal">Creando l'account accetti <a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>">Termini e Privacy</a>.</p>
			</div>
		</div>
	</section>
</main>
<?php
get_footer();
