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
		// Consent mode come sul live: negato finche' iubenda non concede analytics; al grant analytics
		// vengono concessi anche i tipi ad (non dichiarati nella cookie policy, iubenda da solo non li tocca).
		?>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
(function(){
	var granted = false;
	try {
		var c = document.cookie.split(';').filter(function(s){ return s.trim().indexOf('_iub_cs-<?php echo $iub_policy; ?>=') === 0; })[0];
		if (c) { var val = JSON.parse(decodeURIComponent(c.trim().split('=').slice(1).join('='))); granted = !!(val && val.cons); }
	} catch (e) {}
	if (granted) {
		gtag('consent','default',{'analytics_storage':'granted','ad_storage':'granted','ad_user_data':'granted','ad_personalization':'granted'});
	} else {
		gtag('consent','default',{'analytics_storage':'denied','ad_storage':'denied','ad_user_data':'denied','ad_personalization':'denied','wait_for_update':500});
		(function(){
			var _g = gtag;
			gtag = function(){
				_g.apply(this, arguments);
				if (arguments[0]==='consent' && arguments[1]==='update' && arguments[2] && arguments[2].analytics_storage==='granted') {
					_g('consent','update',{'ad_storage':'granted','ad_user_data':'granted','ad_personalization':'granted'});
					gtag = _g;
				}
			};
		})();
	}
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
	$ids = tp_tracking_ids();
	if ( $ids['gtm'] ) {
		echo '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . esc_attr( $ids['gtm'] ) . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>' . "\n";
	}
}, 0 );

/* Helper dataLayer per i moduli del tema: in testa e senza defer, cosi' esiste prima di lead-form.js e degli altri. */
add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_script( 'trovapulizie-tp-track', get_stylesheet_directory_uri() . '/assets/js/tp-track.js', array(), tp_theme_version(), false );
}, 5 );
