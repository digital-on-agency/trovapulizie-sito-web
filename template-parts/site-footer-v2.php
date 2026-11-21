<?php
/**
 * Footer nuovo (SPEC-homepage-v2, TICKET-128). Figma 7904:2856 (desktop, 1440) e 7946:2202 (mobile, 390, nel frame 7946:2).
 * Lo chiama footer.php quando tp_footer_v2_on() e' vero (interruttore Theme Options > Sito v2 oppure pagina col template
 * page-home-v2.php); con l'interruttore spento footer.php stampa il footer di sempre e questo file non viene letto.
 * Stampa solo <footer>: wp_footer() e la chiusura del documento restano a footer.php.
 *
 * Argomenti: nessuno. Tutto viene da Theme Options > Footer (inc/carbon-fields.php); campo vuoto = default qui sotto, gli
 * stessi valori pubblicati oggi, e una colonna senza titolo e senza link non viene stampata.
 * Campi letti: footer_v2_tagline, footer_v2_phone, footer_v2_email, footer_social (riuso: senza URL
 * l'icona non esce), footer_v2_columns (max 3, "Etichetta | URL" per riga), footer_v2_b2b_title / _text / _label / _link,
 * footer_v2_legal_line, footer_legal (riuso, "Etichetta | URL" per riga). La colonna "Servizi a Roma" viene da
 * tp_service_pages() come nel footer attuale (link interni SEO, TICKET-78): non e' nel Figma, vedi nota sotto.
 *
 * Tracciamento: il riquadro aziende porta data-cta-position="footer_b2b" (valore della SPEC §2). tp-track.js oggi emette
 * l'evento solo con [data-landing] nella pagina: l'estensione a tutte le pagine e' di TICKET-130.
 * Senza reveal: il footer non porta data-reveal, quindi e' visibile anche senza reveal.js e con JavaScript spento.
 * Mobile (max-width: 1023px): le colonne diventano una fisarmonica con <details>/<summary>; senza JavaScript restano
 * tutte aperte (attributo open), con assets/js/site-footer-v2.js sono chiuse e se ne apre una per volta.
 * La scritta grande e' decorativa (aria-hidden) e statica: il movimento legato allo scroll e' di TICKET-129.
 * CSS: blocco "TICKET-128" in fondo a style.css, solo selettori .site-footer-v2*.
 *
 * [DEVIAZIONE dal Figma] "Servizi a Roma" (11 link interni) e' un quarto gruppo sotto le colonne: il footer attuale lo
 * porta su ogni pagina per la SEO (SPEC-seo-post-cutover) e toglierlo e' una decisione di Guido, non del ticket.
 */
$tpf_brand   = 'Trovapulizie'; // Marchio: logo + nome fissi (il campo "Wordmark" del footer attuale puo' essere in minuscolo).
$tpf_tagline = trim( (string) carbon_get_theme_option( 'footer_v2_tagline' ) ) ?: "Le pulizie, in un’app.\nA Roma, quartiere per quartiere.";
$tpf_phone_raw = trim( (string) carbon_get_theme_option( 'footer_v2_phone' ) ) ?: TP_CT_WHATSAPP;
$tpf_phone   = tp_whatsapp_parts( $tpf_phone_raw ); // solo per il link tel:; il testo resta come scritto (un fisso non va spezzato come un cellulare)
$tpf_email   = trim( (string) carbon_get_theme_option( 'footer_v2_email' ) ) ?: TP_CT_EMAIL;
$tpf_networks = array( 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok' );
$tpf_social  = array_filter( (array) carbon_get_theme_option( 'footer_social' ), function ( $s ) use ( $tpf_networks ) {
	return ! empty( $s['url'] ) && isset( $tpf_networks[ $s['network'] ?? '' ] );
} );

// Colonne di link: dal campo se compilato, altrimenti le tre del Figma. Il rimando a "Come funziona" resta sulla pagina
// d'anteprima, altrove porta alla Home (come la barra, navbar-v2.php).
$tpf_anchor  = function_exists( 'tp_is_home_v2_preview' ) && tp_is_home_v2_preview() ? '#come-funziona' : home_url( '/#come-funziona' );
$tpf_cols    = array();
foreach ( (array) carbon_get_theme_option( 'footer_v2_columns' ) as $c ) {
	$links = tp_link_lines( $c['links'] ?? '' );
	if ( ! empty( $c['title'] ) || $links ) {
		$tpf_cols[] = array( 'title' => (string) ( $c['title'] ?? '' ), 'links' => $links );
	}
}
if ( ! $tpf_cols ) {
	$tpf_cols = array(
		array( 'title' => 'Piattaforma', 'links' => array(
			array( 'label' => 'Come funziona', 'url' => $tpf_anchor ),
			array( 'label' => 'Servizi', 'url' => home_url( '/roma/' ) ),
			array( 'label' => 'Prezzi', 'url' => home_url( '/roma/prezzi-pulizie/' ) ),
			array( 'label' => 'FAQ', 'url' => home_url( '/#faq' ) ),
		) ),
		array( 'title' => 'Per i professionisti', 'links' => array(
			array( 'label' => 'Diventa professionista', 'url' => home_url( '/diventa-cleaner/' ) ),
			array( 'label' => 'Requisiti', 'url' => home_url( '/diventa-cleaner/#requisiti' ) ),
			array( 'label' => 'Come guadagnare', 'url' => home_url( '/diventa-cleaner/#guadagni' ) ),
		) ),
		array( 'title' => 'Azienda', 'links' => array(
			array( 'label' => 'Chi siamo', 'url' => home_url( '/chi-siamo/' ) ),
			array( 'label' => 'Contatti', 'url' => home_url( '/contatti/' ) ),
			array( 'label' => 'Blog', 'url' => home_url( '/blog/' ) ),
		) ),
	);
}

// Riquadro aziende e condomini.
$tpf_b2b_title = trim( (string) carbon_get_theme_option( 'footer_v2_b2b_title' ) ) ?: 'Hai un ufficio, un condominio o un B&B?';
$tpf_b2b_text  = trim( (string) carbon_get_theme_option( 'footer_v2_b2b_text' ) ) ?: 'Preventivi da imprese verificate.';
$tpf_b2b_label = trim( (string) carbon_get_theme_option( 'footer_v2_b2b_label' ) ) ?: 'Richiedi un preventivo';
$tpf_b2b_link  = trim( (string) carbon_get_theme_option( 'footer_v2_b2b_link' ) ) ?: home_url( '/roma/pulizie-uffici/' );

// Dati legali.
$tpf_legal_line  = trim( (string) carbon_get_theme_option( 'footer_v2_legal_line' ) ) ?: '© ' . gmdate( 'Y' ) . ' Trovapulizie S.R.L. · Via Paolo Albera 63, 00181 Roma · P.IVA 18445521000 · REA RM-1785357';
$tpf_legal_links = tp_link_lines( carbon_get_theme_option( 'footer_legal' ) );
if ( ! $tpf_legal_links ) {
	$tpf_legal_links = array(
		array( 'label' => 'Privacy Policy', 'url' => home_url( '/privacy/' ) ),
		array( 'label' => 'Termini e Condizioni', 'url' => home_url( '/terms/' ) ),
		array( 'label' => 'Cookie', 'url' => home_url( '/cookie-policy/' ) ),
	);
}

// Poppins Black per la scritta grande (sulle altre pagine con il footer v2) e script della
// fisarmonica. Accodati qui perche' footer.php chiama questo file prima di wp_footer().
// Sulla Home v2 il 900 e' gia' self-hosted (inc/home-v2.php, tp_home_v2_fonts_css()): niente richiesta a Google.
if ( ! is_page_template( 'page-home-v2.php' ) ) {
	wp_enqueue_style( 'trovapulizie-fonts-black', 'https://fonts.googleapis.com/css2?family=Poppins:wght@900&display=swap', array(), null );
}
wp_enqueue_script( 'trovapulizie-site-footer-v2', get_stylesheet_directory_uri() . '/assets/js/site-footer-v2.js', array(), tp_theme_version(), array( 'strategy' => 'defer', 'in_footer' => true ) );
$tpf_chevron = '<span class="site-footer-v2__chevron" aria-hidden="true"></span>';
?>
<footer class="site-footer-v2">
	<div class="site-footer-v2__inner">
		<div class="site-footer-v2__top">
			<div class="site-footer-v2__brand">
				<a class="site-footer-v2__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<img src="<?php echo esc_url( tp_theme_asset( 'assets/img/home-v2/logo-icon.svg' ) ); ?>" alt="" width="36" height="33">
					<span><?php echo esc_html( $tpf_brand ); ?></span>
				</a>
				<p class="site-footer-v2__tagline"><?php echo nl2br( esc_html( $tpf_tagline ) ); ?></p>
				<ul class="site-footer-v2__contacts">
					<?php if ( $tpf_phone ) : ?><li><a href="<?php echo esc_url( 'tel:' . $tpf_phone['e164'] ); ?>"><?php echo tp_icon_span( 'assets/icons/ic-phone.svg', 'site-footer-v2__ico' ); ?><span><?php echo esc_html( $tpf_phone_raw ); ?></span></a></li><?php endif; ?>
					<li><a href="<?php echo esc_url( 'mailto:' . $tpf_email ); ?>"><?php echo tp_icon_span( 'assets/icons/ic-mail.svg', 'site-footer-v2__ico' ); ?><span><?php echo esc_html( $tpf_email ); ?></span></a></li>
				</ul>
				<?php if ( $tpf_social ) : ?>
				<ul class="site-footer-v2__social">
					<?php foreach ( $tpf_social as $s ) : ?>
					<li><a href="<?php echo esc_url( $s['url'] ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $tpf_networks[ $s['network'] ] ); ?>"><?php echo tp_icon_span( 'assets/icons/social-' . $s['network'] . '.svg', 'site-footer-v2__social-ico' ); ?></a></li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>
			</div>
			<div class="site-footer-v2__nav">
				<?php foreach ( $tpf_cols as $col ) : ?>
				<details class="site-footer-v2__group" open>
					<summary class="site-footer-v2__group-title"><span><?php echo esc_html( $col['title'] ); ?></span><?php echo $tpf_chevron; ?></summary>
					<ul class="site-footer-v2__links">
						<?php foreach ( $col['links'] as $l ) : ?><li><a href="<?php echo esc_url( $l['url'] ); ?>"><?php echo esc_html( $l['label'] ); ?></a></li><?php endforeach; ?>
					</ul>
				</details>
				<?php endforeach; ?>
			</div>
			<div class="site-footer-v2__b2b">
				<p class="site-footer-v2__b2b-title"><?php echo nl2br( esc_html( $tpf_b2b_title ) ); ?></p>
				<p class="site-footer-v2__b2b-text"><?php echo esc_html( $tpf_b2b_text ); ?></p>
				<a class="site-footer-v2__b2b-btn" href="<?php echo esc_url( $tpf_b2b_link ); ?>" data-cta-position="footer_b2b"><span><?php echo esc_html( $tpf_b2b_label ); ?></span> <span aria-hidden="true">&rarr;</span></a>
			</div>
		</div>
		<details class="site-footer-v2__group site-footer-v2__services" open>
			<summary class="site-footer-v2__group-title"><span>Servizi a Roma</span><?php echo $tpf_chevron; ?></summary>
			<ul class="site-footer-v2__links">
				<?php foreach ( tp_service_pages() as $percorso => $anchor ) : ?><li><a href="<?php echo esc_url( home_url( $percorso ) ); ?>"><?php echo esc_html( $anchor ); ?></a></li><?php endforeach; ?>
			</ul>
		</details>
		<div class="site-footer-v2__divider" aria-hidden="true"></div>
		<div class="site-footer-v2__bottom">
			<p class="site-footer-v2__legal-line"><?php echo esc_html( $tpf_legal_line ); ?></p>
			<ul class="site-footer-v2__legal-links">
				<?php foreach ( $tpf_legal_links as $l ) : ?><li><a href="<?php echo esc_url( $l['url'] ); ?>"><?php echo esc_html( $l['label'] ); ?></a></li><?php endforeach; ?>
			</ul>
		</div>
	</div>
	<div class="site-footer-v2__word" aria-hidden="true"><span class="site-footer-v2__word-text">TROVAPULIZIE</span></div>
</footer>
