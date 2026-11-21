<?php
/**
 * Sezione Servizi della Home v2, `03-servizi` (SPEC-homepage-v2, TICKET-121). Figma file UiW4TPgvqCODW5bhyFV5eK,
 * pagina 30: desktop 7903:6228, mobile 7946:668 (nel frame bianco 7946:2). Scritta, titolo, sottotitolo, riga "Altri
 * servizi a Roma", link "Scopri i servizi nell'app" e un riquadro verde con le sei icone dell'app.
 *
 * Mappa icona -> destinazione (default; il motivo e' SEO: link interni dalla Home verso le landing, SPEC US-02):
 *   Casa           -> /roma/                          (hub di Roma)
 *   Affitti brevi  -> nessuna, "presto"
 *   Aziende        -> /roma/pulizie-uffici/
 *   Condomini      -> /roma/pulizie-condomini/
 *   Disinfestazione-> /roma/zanzare-disinfestazione/
 *   Manutenzione   -> nessuna, "presto"
 * Un'icona e' un link solo se tp_home_v2_service_href() ritorna un URL: non "presto", URL compilato, e se interno la
 * pagina di destinazione e' pubblicata. Altrimenti resta visibile come <span>, uguale a "presto" ma senza l'etichetta
 * (un'icona "presto" porta la parola "presto", letta dagli screen reader; una senza destinazione no). Mai un link a una 404.
 *
 * Riga "Altri servizi a Roma": tp_home_v2_altri_servizi() (sei pagine, solo pubblicate), che legge percorsi e anchor
 * da tp_service_pages(). Nessun URL ne' anchor di questa riga e' scritto qui. La riga sostituisce il blocco omonimo
 * che la Home attuale mette in fondo alla pagina (related-services.php).
 *
 * Argomenti (le letture Carbon stanno in page-home-v2.php; tutti con default):
 * - title (string): titolo H2, in maiuscolo da CSS. Default "Dalla casa all’ufficio." Un a capo (\n) e' rispettato.
 * - eyebrow (string): scritta sopra il titolo. Default "I servizi".
 * - subtitle (string): sottotitolo. Default "Quattro servizi oggi, altri in arrivo. Scegli e vedi subito chi è libero." (testo
 *   del frame mobile 7946:668; il desktop 7903:6228 dice "Sei servizi, un'app sola": vedi dubbi di TICKET-121 e SPEC §6).
 * - more_label (string): etichetta della riga di link. Default "Altri servizi a Roma".
 * - cta_label (string): testo del link all'app. Default "Scopri i servizi nell’app".
 * - items (array): righe { name, icon (URL), url, soon (bool) }. Vuoto = le sei di default. Un'icona vuota prende quella
 *   di default della riga con lo stesso numero d'ordine.
 *
 * Tracciamento: "Scopri i servizi nell'app" porta data-cta-position="services", href tp_app_url(), stessa scheda.
 * Ingressi: data-reveal sulla colonna di testo e sul riquadro (--i 0 e 1; il riquadro entra da destra di 32 px); le icone spuntano
 * con rimbalzo a stagger 40 ms (--k = posizione, CSS del blocco TICKET-129).
 * Hover (solo dispositivi con mouse): icona su di 4 px (150 ms) e nome sottolineato, sulle icone che sono link.
 * Immagini: WebP in assets/img/home-v2/svc-*.webp, con width e height, lazy. CSS: blocco `.hv2-services` di style.css.
 * JS: nessuno.
 *
 * Esempio: get_template_part( 'template-parts/home-services-v2', null, array( 'title' => 'Servizi' ) );
 */
$args = wp_parse_args( $args ?? array(), array(
	'title'      => '',
	'eyebrow'    => '',
	'subtitle'   => '',
	'more_label' => '',
	'cta_label'  => '',
	'items'      => array(),
) );
$hv2s_pick = static function ( $valore, $default ) {
	$valore = trim( (string) $valore );
	return '' !== $valore ? $valore : $default;
};
$hv2s_img = static function ( $file ) {
	return tp_theme_asset( 'assets/img/home-v2/' . $file );
};
// Default: nome, icona (file nel tema), dimensioni dell'immagine, URL interno, "presto".
$hv2s_default = array(
	array( 'name' => 'Casa', 'file' => 'svc-casa.webp', 'w' => 216, 'h' => 216, 'em' => 4.5, 'url' => '/roma/', 'soon' => false ),
	array( 'name' => 'Affitti brevi', 'file' => 'svc-affitti.webp', 'w' => 186, 'h' => 235, 'em' => 3.857, 'url' => '', 'soon' => true ),
	array( 'name' => 'Aziende', 'file' => 'svc-aziende.webp', 'w' => 216, 'h' => 216, 'em' => 4.5, 'url' => '/roma/pulizie-uffici/', 'soon' => false ),
	array( 'name' => 'Condomini', 'file' => 'svc-condomini.webp', 'w' => 216, 'h' => 216, 'em' => 4.5, 'url' => '/roma/pulizie-condomini/', 'soon' => false ),
	array( 'name' => 'Disinfestazione', 'file' => 'svc-disinfestazione.webp', 'w' => 260, 'h' => 260, 'em' => 5.4, 'url' => '/roma/zanzare-disinfestazione/', 'soon' => false ),
	array( 'name' => 'Manutenzione', 'file' => 'svc-manutenzione.webp', 'w' => 260, 'h' => 260, 'em' => 5.4, 'url' => '', 'soon' => true ),
);
$hv2s_rows = array();
$hv2s_in   = array_values( array_filter( (array) $args['items'], static function ( $r ) {
	return is_array( $r ) && '' !== trim( (string) ( $r['name'] ?? '' ) );
} ) );
if ( $hv2s_in ) {
	foreach ( $hv2s_in as $i => $r ) {
		$d = $hv2s_default[ $i ] ?? array( 'file' => '', 'w' => 260, 'h' => 260, 'em' => 4.5 );
		$hv2s_rows[] = array(
			'name' => trim( (string) $r['name'] ),
			'src'  => '' !== trim( (string) ( $r['icon'] ?? '' ) ) ? trim( (string) $r['icon'] ) : ( $d['file'] ? $hv2s_img( $d['file'] ) : '' ),
			'w'    => '' !== trim( (string) ( $r['icon'] ?? '' ) ) ? 0 : $d['w'],
			'h'    => '' !== trim( (string) ( $r['icon'] ?? '' ) ) ? 0 : $d['h'],
			'url'  => (string) ( $r['url'] ?? '' ),
			'em'   => $d['em'],
			'soon' => ! empty( $r['soon'] ),
		);
	}
} else {
	foreach ( $hv2s_default as $d ) {
		$hv2s_rows[] = array( 'name' => $d['name'], 'src' => $hv2s_img( $d['file'] ), 'w' => $d['w'], 'h' => $d['h'], 'em' => $d['em'], 'url' => $d['url'], 'soon' => $d['soon'] );
	}
}
$hv2s_title = $hv2s_pick( $args['title'], "Dalla casa\nall’ufficio." );
$hv2s_more  = tp_home_v2_altri_servizi();
?>
<section class="hv2-section hv2-services" data-section="03-servizi">
	<span class="hv2-services__light hv2-services__light--a" aria-hidden="true"></span>
	<span class="hv2-services__light hv2-services__light--b" aria-hidden="true"></span>
	<div class="hv2-services__inner">
		<div class="hv2-services__head" data-reveal>
			<p class="hv2-services__eyebrow"><?php echo esc_html( $hv2s_pick( $args['eyebrow'], 'I servizi' ) ); ?></p>
			<h2 class="hv2-services__title"><?php echo wp_kses( nl2br( esc_html( $hv2s_title ) ), array( 'br' => array() ) ); ?></h2>
			<p class="hv2-services__subtitle"><?php echo esc_html( $hv2s_pick( $args['subtitle'], 'Quattro servizi oggi, altri in arrivo. Scegli e vedi subito chi è libero.' ) ); ?></p>
		</div>
		<div class="hv2-services__panel" data-reveal style="--i: 1">
			<ul class="hv2-services__grid">
				<?php foreach ( $hv2s_rows as $hv2s_k => $hv2s_r ) : ?>
					<?php
					$hv2s_href = tp_home_v2_service_href( $hv2s_r['url'], $hv2s_r['soon'] );
					$hv2s_tag  = $hv2s_href ? 'a' : 'span';
					?>
					<li class="hv2-services__cell" style="--k: <?php echo (int) min( $hv2s_k, 11 ); ?>">
						<<?php echo $hv2s_tag; ?> class="hv2-services__item<?php echo $hv2s_href ? '' : ' is-static'; ?>"<?php echo $hv2s_href ? ' href="' . esc_url( $hv2s_href ) . '"' : ''; ?>>
							<span class="hv2-services__tile">
								<?php if ( '' !== $hv2s_r['src'] ) : ?>
									<img src="<?php echo esc_url( $hv2s_r['src'] ); ?>" alt=""<?php echo $hv2s_r['w'] ? ' width="' . (int) $hv2s_r['w'] . '" height="' . (int) $hv2s_r['h'] . '"' : ''; ?> style="width: <?php echo esc_attr( $hv2s_r['em'] ); ?>em" loading="lazy" decoding="async">
								<?php endif; ?>
							</span>
							<span class="hv2-services__label"><span class="hv2-services__name"><?php echo esc_html( $hv2s_r['name'] ); ?></span><?php if ( $hv2s_r['soon'] ) : ?><span class="hv2-services__soon">presto</span><?php endif; ?></span>
						</<?php echo $hv2s_tag; ?>>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<div class="hv2-services__more" data-reveal style="--i: 2">
			<?php if ( $hv2s_more ) : ?>
				<p class="hv2-services__more-label" id="hv2-services-more"><?php echo esc_html( $hv2s_pick( $args['more_label'], 'Altri servizi a Roma' ) ); ?></p>
				<ul class="hv2-services__chips" aria-labelledby="hv2-services-more">
					<?php foreach ( $hv2s_more as $hv2s_path => $hv2s_anchor ) : ?>
						<li><a class="hv2-services__chip" href="<?php echo esc_url( home_url( $hv2s_path ) ); ?>"><?php echo esc_html( $hv2s_anchor ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<a class="hv2-services__cta" href="<?php echo esc_url( tp_app_url() ); ?>" data-cta-position="services"><span><?php echo esc_html( $hv2s_pick( $args['cta_label'], 'Scopri i servizi nell’app' ) ); ?></span><span aria-hidden="true">→</span></a>
		</div>
	</div>
</section>
