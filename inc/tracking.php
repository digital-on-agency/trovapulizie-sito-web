<?php
/**
 * Tracking di base del tema (TICKET-38): consenso iubenda, Google Tag Manager, Microsoft Clarity.
 * Gli ID vengono da Theme Options > Tracking (inc/carbon-fields.php, page file tp-tracking); con un campo vuoto il
 * relativo snippet non esce. Gli snippet e la configurazione iubenda sono copiati dal sorgente del live
 * (www.trovapulizie.it, 25/10/2026): stesso siteId e cookiePolicyId, stesso consent mode con default negato e
 * concessione dei tipi ad al momento dell'accettazione analytics.
 *
 * Ordine nel <head> (priorita' 0, prima di ogni altro script): configurazione iubenda, autoblocking, cs.js,
 * consent default + funzione gtag, snippet GTM, Clarity. In wp_body_open il noscript di GTM.
 * assets/js/tp-track.js (caricato in testa, non deferred) espone tpTrack/tpGetGclid/tpGetUtm ai moduli.
 *
 * [ATTENZIONE] Un solo GTM per pagina: la lezione del 24/09 sul portale era un contenitore caricato due volte.
 * Niente altro snippet GTM in header.php, nei template o in plugin "inserisci header e footer".
 */

function tp_tracking_ids() {
	if ( ! function_exists( 'carbon_get_theme_option' ) ) {
		return array( 'gtm' => '', 'iub_site' => '', 'iub_policy' => '', 'clarity' => '' );
	}
	return array(
		'gtm'        => trim( (string) carbon_get_theme_option( 'tp_gtm_id' ) ),
		'iub_site'   => trim( (string) carbon_get_theme_option( 'tp_iub_site_id' ) ),
		'iub_policy' => trim( (string) carbon_get_theme_option( 'tp_iub_policy_id' ) ),
		'clarity'    => trim( (string) carbon_get_theme_option( 'tp_clarity_id' ) ),
	);
}

add_action( 'wp_head', function () {
	if ( function_exists( 'tp_is_survey_page' ) && tp_is_survey_page() ) {
		return; // La survey OnField non ha tracking: raccoglie dati personali (inc/survey.php).
	}
	$ids = tp_tracking_ids();

	if ( $ids['iub_site'] && $ids['iub_policy'] ) {
		$iub_site   = (int) $ids['iub_site'];
		$iub_policy = (int) $ids['iub_policy'];
		$cs = array(
			'askConsentAtCookiePolicyUpdate'  => true,
			'floatingPreferencesButtonDisplay' => 'bottom-left',
			'perPurposeConsent'               => true,
			'siteId'                          => $iub_site,
			'whitelabel'                      => false,
			'cookiePolicyId'                  => $iub_policy,
			'privacyPolicyUrl'                => home_url( '/privacy/' ),
			'googleConsentMode'               => true,
			'banner'                          => array(
				'acceptButtonColor'          => '#00C896',
				'acceptButtonDisplay'        => true,
				'backgroundColor'            => '#FFFFFF',
				'closeButtonRejects'         => true,
				'customizeButtonCaptionColor' => '#1F2937',
				'customizeButtonColor'       => '#F7F8FA',
				'customizeButtonDisplay'     => true,
				'explicitWithdrawal'         => true,
				'fontSizeBody'               => '12px',
				'linksColor'                 => '#008866',
				'listPurposes'               => true,
				'position'                   => 'bottom',
				'showTitle'                  => false,
				'textColor'                  => '#374151',
			),
		);
		echo '<script type="text/javascript">var _iub = _iub || []; _iub.csConfiguration = ' . wp_json_encode( $cs ) . '; _iub.csLangConfiguration = {"it":{"cookiePolicyId":' . $iub_policy . '}};</script>' . "\n";
		echo '<script type="text/javascript" src="https://cs.iubenda.com/autoblocking/' . $iub_site . '.js"></script>' . "\n";
		echo '<script type="text/javascript" src="//cdn.iubenda.com/cs/iubenda_cs.js" charset="UTF-8" async></script>' . "\n";
	}

	if ( $ids['gtm'] ) {
		$gtm        = esc_js( $ids['gtm'] );
		$iub_policy = $ids['iub_policy'] ? (int) $ids['iub_policy'] : 0;
		// Consent mode per finalita' (11/11/2026, privacy policy v1.0): default dalle scelte gia' espresse nel cookie
		// iubenda, finalita' 4 (misurazione) -> analytics_storage, finalita' 5 (marketing) -> tipi ad; poi gli
		// aggiornamenti li manda iubenda (googleConsentMode). Prima ogni preferenza registrata, anche un rifiuto,
		// concedeva tutto, e il consenso alle statistiche concedeva anche la pubblicita'.
		?>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
(function(){
	var p = {};
	try {
		var c = document.cookie.split(';').filter(function(s){ return s.trim().indexOf('_iub_cs-<?php echo $iub_policy; ?>=') === 0; })[0];
		if (c) { var val = JSON.parse(decodeURIComponent(c.trim().split('=').slice(1).join('='))); p = (val && val.purposes) || {}; }
	} catch (e) {}
	var a = p['4'] === true ? 'granted' : 'denied', m = p['5'] === true ? 'granted' : 'denied';
	gtag('consent','default',{'analytics_storage':a,'ad_storage':m,'ad_user_data':m,'ad_personalization':m,'wait_for_update':500});
})();
</script>
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?php echo $gtm; ?>');</script>
		<?php
	}

	if ( $ids['clarity'] ) {
		$clarity = esc_js( $ids['clarity'] );
		echo '<script type="text/javascript">(function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);})(window, document, "clarity", "script", "' . $clarity . '");</script>' . "\n";
	}
}, 0 );

add_action( 'wp_body_open', function () {
	if ( function_exists( 'tp_is_survey_page' ) && tp_is_survey_page() ) {
		return;
	}
	$ids = tp_tracking_ids();
	if ( $ids['gtm'] ) {
		echo '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . esc_attr( $ids['gtm'] ) . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . "\n";
	}
}, 0 );

/* Helper dataLayer per i moduli del tema: in testa e senza defer, cosi' esiste prima di lead-form.js e degli altri. */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_script( 'trovapulizie-tp-track', get_stylesheet_directory_uri() . '/assets/js/tp-track.js', array(), tp_theme_version(), false );
}, 5 );
