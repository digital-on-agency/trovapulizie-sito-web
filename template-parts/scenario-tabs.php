<?php
/**
 * Sezione "Quando serve" — Figma 4364:6769 (base) + stati 4601:936/995/1054, mobile 4364:7223; contenuti dal live
 * (SCENARIOS). Quattro scenari in tab (pattern WAI-ARIA tabs: frecce, Home/End); ogni pannello e' una card a tre
 * colonne: aree coperte (chip, evidenziate quelle dello scenario), confronto prima/dopo con maniglia, dettaglio con
 * riga in evidenza e CTA. Comportamento in assets/js/scenario-tabs.js; senza JavaScript resta il pannello di default
 * con la maniglia ferma a meta'.
 *
 * Le verticali (variante diversa dalla generica) mostrano il solo scenario della variante, senza tab, come il live.
 *
 * Args: eyebrow, title, scenarios[] {key, label, title, text, highlight, before, after, areas[]}, all_areas[],
 * areas_title, areas_text, cta_label, cta_link, default (key), show_tabs (bool), id.
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'     => '',
	'title'       => '',
	'scenarios'   => array(),
	'all_areas'   => array(),
	'areas_title' => '',
	'areas_text'  => '',
	'cta_label'   => '',
	'cta_link'    => '#form',
	'default'     => '',
	'show_tabs'   => true,
	'id'          => 'quando-serve',
) );
$scenarios = array_values( array_filter( (array) $args['scenarios'], function ( $s ) {
	return ! empty( $s['key'] ) && ! empty( $s['title'] );
} ) );
if ( ! $scenarios ) {
	return;
}
$keys    = array_column( $scenarios, 'key' );
$default = in_array( $args['default'], $keys, true ) ? $args['default'] : $keys[0];
if ( ! $args['show_tabs'] ) {
	// Verticale: resta il solo scenario di default.
	$scenarios = array_values( array_filter( $scenarios, function ( $s ) use ( $default ) {
		return $s['key'] === $default;
	} ) );
}
$uid = sanitize_html_class( $args['id'] );
?>
<section class="sg-when" id="<?php echo esc_attr( $args['id'] ); ?>" data-scenario-tabs>
	<div class="sg-when__inner">
		<div class="sg-when__head" data-reveal>
			<?php if ( $args['eyebrow'] ) : ?><p class="sg-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p><?php endif; ?>
			<?php if ( $args['title'] ) : ?><h2><?php echo esc_html( $args['title'] ); ?></h2><?php endif; ?>
		</div>

		<?php if ( $args['show_tabs'] && count( $scenarios ) > 1 ) : ?>
		<div class="sg-when__tabs" role="tablist" aria-label="Situazioni">
			<?php foreach ( $scenarios as $s ) : $on = $s['key'] === $default; ?>
				<button type="button" role="tab" class="sg-when__tab" id="<?php echo esc_attr( $uid . '-tab-' . $s['key'] ); ?>" data-key="<?php echo esc_attr( $s['key'] ); ?>" aria-selected="<?php echo $on ? 'true' : 'false'; ?>" aria-controls="<?php echo esc_attr( $uid . '-panel-' . $s['key'] ); ?>" tabindex="<?php echo $on ? '0' : '-1'; ?>"><?php echo esc_html( $s['label'] ?: $s['title'] ); ?></button>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php foreach ( $scenarios as $s ) :
			$on    = $s['key'] === $default;
			$areas = array_map( 'trim', (array) ( $s['areas'] ?? array() ) );
			?>
			<div class="sg-when__panel" role="tabpanel" id="<?php echo esc_attr( $uid . '-panel-' . $s['key'] ); ?>" data-key="<?php echo esc_attr( $s['key'] ); ?>" aria-labelledby="<?php echo esc_attr( $uid . '-tab-' . $s['key'] ); ?>" tabindex="0"<?php echo $on ? '' : ' hidden'; ?>>
				<div class="sg-when__areas">
					<?php if ( $args['areas_title'] ) : ?><p class="sg-when__areas-title"><?php echo esc_html( $args['areas_title'] ); ?></p><?php endif; ?>
					<?php if ( $args['all_areas'] ) : ?>
					<ul class="sg-when__chips">
						<?php foreach ( $args['all_areas'] as $area ) : ?>
							<li class="sg-area<?php echo in_array( $area, $areas, true ) ? ' is-on' : ''; ?>"><?php echo esc_html( $area ); ?></li>
						<?php endforeach; ?>
					</ul>
					<?php endif; ?>
					<?php if ( $args['areas_text'] ) : ?><p class="sg-when__areas-text"><?php echo esc_html( $args['areas_text'] ); ?></p><?php endif; ?>
				</div>

				<?php if ( ! empty( $s['after'] ) ) : ?>
				<div class="sg-compare-media" data-compare style="--pos: 50%">
					<img class="sg-compare-media__after" src="<?php echo esc_url( $s['after'] ); ?>" alt="Dopo lo sgrosso" width="1072" height="784" loading="lazy" decoding="async">
					<?php if ( ! empty( $s['before'] ) ) : ?>
						<img class="sg-compare-media__before" src="<?php echo esc_url( $s['before'] ); ?>" alt="Prima dello sgrosso" width="1072" height="784" loading="lazy" decoding="async">
						<span class="sg-compare-media__divider" aria-hidden="true"></span>
						<span class="sg-compare-media__handle" aria-hidden="true">&lsaquo;&rsaquo;</span>
						<span class="sg-compare-media__label sg-compare-media__label--before" aria-hidden="true">PRIMA</span>
						<span class="sg-compare-media__label sg-compare-media__label--after" aria-hidden="true">DOPO</span>
						<input class="sg-compare-media__range" type="range" min="0" max="100" value="50" aria-label="Confronta prima e dopo">
					<?php endif; ?>
				</div>
				<?php endif; ?>

				<div class="sg-when__detail">
					<p class="sg-when__detail-title"><?php echo esc_html( $s['title'] ); ?></p>
					<?php if ( ! empty( $s['text'] ) ) : ?><p class="sg-when__detail-text"><?php echo esc_html( $s['text'] ); ?></p><?php endif; ?>
					<?php if ( ! empty( $s['highlight'] ) ) : ?>
						<p class="sg-when__highlight"><?php echo tp_icon_span( 'assets/icons/ic-check.svg', 'sg-when__highlight-check' ); ?><span><?php echo esc_html( $s['highlight'] ); ?></span></p>
					<?php endif; ?>
					<?php if ( $args['cta_label'] ) : ?>
						<a class="tp-btn tp-btn--primary sg-when__cta" href="<?php echo esc_url( $args['cta_link'] ); ?>"><?php echo esc_html( $args['cta_label'] ); ?> <span class="sg-cta__arrow" aria-hidden="true">&rarr;</span></a>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</section>
