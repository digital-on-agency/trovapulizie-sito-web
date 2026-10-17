<?php
/**
 * Sezione "Dove siamo attivi" navigabile per CAP (ADR-003, TICKET-03).
 *
 * Due colonne: a sinistra l'SVG dei 72 CAP di Roma (generato da tools/zones-map/build-svg.py e incluso
 * inline, non via <img>: il CSS e il JS devono poter colorare i singoli path) con il select che e' il
 * percorso accessibile; a destra un pannello con tre stati (riposo, passaggio del mouse, zona scelta).
 * I tre blocchi del pannello stanno sempre nel DOM e il CSS mostra quello coerente con data-state:
 * cosi' il cambio di stato non muove la colonna.
 *
 * Args: title, description, cta_label, app_url, uncovered_label, fallback_text, credit, services (mappa
 * codice => etichetta), coverage_url. I valori arrivano dai campi WP con TICKET-08; qui i default
 * sono nel codice.
 *
 * [ATTENZIONE] Convive con la vecchia sezione raster (template-parts/zones-map.php, ADR-002 rev. 3),
 * che TICKET-08 rimuovera' insieme ai suoi campi. Nomi diversi apposta per non sovrascriverla.
 */
$args = wp_parse_args( $args ?? array(), array(
	'title'            => '',
	'description'      => '',
	'cta_label'        => 'Cerca in questa zona',
	'app_url'          => 'https://app.trovapulizie.com/',
	'uncovered_label'  => 'Dicci che sei qui',
	'fallback_text'    => 'Copertura in aggiornamento',
	'credit'           => 'Confini CAP: Zornade, dati OpenStreetMap (ODbL)',
	'services'         => array(),
	'coverage_url'     => '',
) );

$svg_path = get_stylesheet_directory() . '/assets/img/zones-map-cap.svg';
if ( ! file_exists( $svg_path ) ) {
	return; // senza l'SVG la sezione non ha nulla da mostrare: meglio assente che vuota
}
$svg = file_get_contents( $svg_path );

$names_path = get_stylesheet_directory() . '/assets/data/cap-names.json';
$names      = file_exists( $names_path ) ? json_decode( file_get_contents( $names_path ), true ) : array();
if ( ! is_array( $names ) ) {
	$names = array();
}
// Voci del select: "Nome - CAP" dove il nome c'e', altrimenti il solo CAP. Ordinate per nome, poi per CAP.
$options = array();
foreach ( $names as $cap => $meta ) {
	$nome      = is_array( $meta ) ? ( $meta['name'] ?? '' ) : '';
	$options[] = array( 'cap' => $cap, 'label' => $nome ? $nome . ' - ' . $cap : $cap, 'ord' => $nome ? '0' . $nome : '1' . $cap );
}
usort( $options, function ( $a, $b ) { return strcmp( $a['ord'], $b['ord'] ); } );
?>
<section class="zones-map" id="dove-siamo" data-reveal
	data-app-url="<?php echo esc_url( $args['app_url'] ); ?>"
	data-coverage-url="<?php echo esc_url( $args['coverage_url'] ); ?>"
	data-cta-label="<?php echo esc_attr( $args['cta_label'] ); ?>"
	data-uncovered-label="<?php echo esc_attr( $args['uncovered_label'] ); ?>"
	data-fallback-text="<?php echo esc_attr( $args['fallback_text'] ); ?>"
	data-services="<?php echo esc_attr( wp_json_encode( (object) $args['services'] ) ); ?>">
	<div class="zones-map__inner">
		<div class="zones-map__head">
			<?php if ( $args['title'] ) : ?><h2 class="zones-map__title"><?php echo esc_html( $args['title'] ); ?></h2><?php endif; ?>
			<?php if ( $args['description'] ) : ?><p class="zones-map__desc"><?php echo esc_html( $args['description'] ); ?></p><?php endif; ?>
		</div>

		<div class="zones-map__map">
			<?php echo $svg; // phpcs:ignore WordPress.Security.EscapeOutput -- file del tema generato da build-svg.py ?>
			<p class="zones-map__picker">
				<label for="zones-map-select">Scegli una zona</label>
				<select id="zones-map-select" class="zones-map__select">
					<option value="">Tutte le zone</option>
					<?php foreach ( $options as $o ) : ?>
						<option value="<?php echo esc_attr( $o['cap'] ); ?>"><?php echo esc_html( $o['label'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
			<p class="zones-map__credit"><?php echo esc_html( $args['credit'] ); ?></p>
		</div>

		<aside class="zones-map__panel" data-state="idle" aria-live="polite">
			<div class="zones-map__idle">
				<dl class="zones-map__totals">
					<div><dt>Persone</dt><dd data-total="individuals">-</dd></div>
					<div><dt>Imprese</dt><dd data-total="companies">-</dd></div>
					<div><dt>Zone coperte</dt><dd data-total="zones_covered">-</dd></div>
				</dl>
				<ul class="zones-map__legend">
					<li data-level="0">nessuno</li>
					<li data-level="1">1-2</li>
					<li data-level="2">3-5</li>
					<li data-level="3">6 e oltre</li>
				</ul>
				<p class="zones-map__hint">Passa sulla mappa o scegli una zona dall'elenco.</p>
			</div>

			<div class="zones-map__hover">
				<h3 class="zones-map__hover-name"></h3>
				<p class="zones-map__hover-hint">Clicca per vedere la copertura.</p>
			</div>

			<div class="zones-map__zone">
				<h3 class="zones-map__zone-name"></h3>
				<p class="zones-map__zone-meta"></p>
				<dl class="zones-map__counts">
					<div><dt>Persone</dt><dd data-count="individuals">0</dd></div>
					<div><dt>Imprese</dt><dd data-count="companies">0</dd></div>
				</dl>
				<ul class="zones-map__services"></ul>
				<a class="tp-btn tp-btn--primary zones-map__cta" href="<?php echo esc_url( $args['app_url'] ); ?>"><?php echo esc_html( $args['cta_label'] ); ?></a>
				<button type="button" class="tp-btn tp-btn--secondary zones-map__uncovered"><?php echo esc_html( $args['uncovered_label'] ); ?></button>
				<p class="zones-map__fallback"><?php echo esc_html( $args['fallback_text'] ); ?></p>
			</div>
		</aside>
	</div>
</section>
