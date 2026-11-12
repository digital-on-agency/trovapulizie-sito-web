<?php
/**
 * Tracking di base del tema (TICKET-38): consenso ai cookie, Google Tag Manager, Microsoft Clarity.
 * Gli ID vengono da Theme Options > Tracking (inc/carbon-fields.php, page file tp-tracking); con un campo vuoto il
 * relativo snippet non esce.
 *
 * Consenso (11/11/2026): banner del tema al posto di iubenda (assets/js/tp-consent.js, markup in wp_footer), con
 * due finalita' facoltative: statistiche (GA4, Clarity) e marketing (Google Ads, pixel Meta). La scelta sta nel
 * cookie `tp_consent` {v, ts, stats, ads} per 6 mesi. Consent mode v2: default dal cookie, negato senza scelta;
 * gli aggiornamenti li manda tp-consent.js. Statistiche -> analytics_storage, marketing -> ad_storage,
 * ad_user_data, ad_personalization. Clarity la carica tp-consent.js solo con le statistiche. I tag Meta nel
 * contenitore GTM chiedono ad_storage (impostazione di consenso aggiuntiva dei tag).
 *
 * Ordine nel <head> (priorita' 0, prima di ogni altro script): consent default + funzione gtag, snippet GTM.
 * assets/js/tp-track.js (caricato in testa, non deferred) espone tpTrack/tpGetGclid/tpGetUtm/tpConsent ai moduli.
 * Niente noscript di GTM: senza JavaScript il consenso non si puo' raccogliere.
 *
 * [ATTENZIONE] Un solo GTM per pagina: la lezione del 24/09 sul portale era un contenitore caricato due volte.
 * Niente altro snippet GTM in header.php, nei template o in plugin "inserisci header e footer".
 */

function tp_tracking_ids() {
	if ( ! function_exists( 'carbon_get_theme_option' ) ) {
		return array( 'gtm' => '', 'clarity' => '' );
	}
	return array(
		'gtm'     => trim( (string) carbon_get_theme_option( 'tp_gtm_id' ) ),
		'clarity' => trim( (string) carbon_get_theme_option( 'tp_clarity_id' ) ),
	);
}

/* La survey OnField non ha tracking ne' banner: raccoglie dati personali e non usa cookie (inc/survey.php). */
function tp_tracking_off() {
	return function_exists( 'tp_is_survey_page' ) && tp_is_survey_page();
}

add_action( 'wp_head', function () {
	if ( tp_tracking_off() ) {
		return;
	}
	$ids = tp_tracking_ids();
	if ( $ids['gtm'] ) {
		$gtm = esc_js( $ids['gtm'] );
		?>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
(function(){
	var s = {};
	try {
		var c = document.cookie.split(';').filter(function(x){ return x.trim().indexOf('tp_consent=') === 0; })[0];
		if (c) { s = JSON.parse(decodeURIComponent(c.trim().slice(11))) || {}; }
	} catch (e) {}
	var a = s.stats === true ? 'granted' : 'denied', m = s.ads === true ? 'granted' : 'denied';
	gtag('consent','default',{'analytics_storage':a,'ad_storage':m,'ad_user_data':m,'ad_personalization':m,'functionality_storage':'granted','security_storage':'granted','wait_for_update':500});
	gtag('set','ads_data_redaction',m === 'denied');
})();
</script>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?php echo $gtm; ?>');</script>
		<?php
	}
}, 0 );

/* Helper dataLayer e banner del consenso. tp-track in testa e senza defer, cosi' esiste prima di lead-form.js. */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_script( 'trovapulizie-tp-track', get_stylesheet_directory_uri() . '/assets/js/tp-track.js', array(), tp_theme_version(), false );
	if ( tp_tracking_off() ) {
		return;
	}
	wp_enqueue_script( 'trovapulizie-tp-consent', get_stylesheet_directory_uri() . '/assets/js/tp-consent.js', array( 'trovapulizie-tp-track' ), tp_theme_version(), true );
	$ids = tp_tracking_ids();
	wp_localize_script( 'trovapulizie-tp-consent', 'tpConsentConfig', array( 'clarity' => $ids['clarity'] ) );
}, 5 );

/* Banner e pannello delle preferenze. Si riaprono da ogni link con href="#preferenze-cookie" (piede di pagina). */
add_action( 'wp_footer', function () {
	if ( tp_tracking_off() ) {
		return;
	}
	$privacy = home_url( '/privacy/' );
	$cookie  = home_url( '/cookie-policy/' );
	?>
<div class="tp-consent" id="tp-consent" role="dialog" aria-modal="false" aria-labelledby="tp-consent-title" hidden>
	<div class="tp-consent__box">
		<p class="tp-consent__title" id="tp-consent-title">Cookie e privacy</p>
		<p class="tp-consent__text">Usiamo cookie tecnici e, con il tuo consenso, strumenti di statistica e pubblicità. Cambi idea da «Preferenze cookie». <a href="<?php echo esc_url( $cookie ); ?>">Cookie policy</a> · <a href="<?php echo esc_url( $privacy ); ?>">Privacy policy</a></p>
		<div class="tp-consent__prefs" id="tp-consent-prefs" hidden>
			<label class="tp-consent__opt"><input type="checkbox" checked disabled> <span><strong>Necessari</strong> Funzionamento del sito e memorizzazione della scelta. Sempre attivi.</span></label>
			<label class="tp-consent__opt"><input type="checkbox" data-consent="stats"> <span><strong>Statistiche</strong> Google Analytics e Microsoft Clarity: come viene usato il sito.</span></label>
			<label class="tp-consent__opt"><input type="checkbox" data-consent="ads"> <span><strong>Marketing</strong> Google Ads e pixel di Meta: misurazione delle campagne e pubblicità personalizzata.</span></label>
		</div>
		<div class="tp-consent__actions">
			<button type="button" class="tp-btn tp-btn--secondary" data-consent-action="reject">Rifiuta</button>
			<button type="button" class="tp-btn tp-btn--secondary" data-consent-action="customize">Personalizza</button>
			<button type="button" class="tp-btn tp-btn--secondary" data-consent-action="save" hidden>Salva le scelte</button>
			<button type="button" class="tp-btn tp-btn--primary" data-consent-action="accept">Accetta tutto</button>
		</div>
	</div>
</div>
	<?php
}, 5 );
