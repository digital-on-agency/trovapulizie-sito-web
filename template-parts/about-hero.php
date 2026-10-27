<?php
/**
 * Hero di Chi siamo — manifesto chiaro con la mappa (Figma 6814:895; TICKET-66). Pannello menta: pillola, H1
 * centrato con una parola in verde, testo, due bottoni; sotto un riquadro largo con la mappa dei CAP di Roma, due
 * card flottanti e i numeri della copertura.
 *
 * La mappa e' l'SVG dei CAP del tema (tools/zones-map/build-svg.py) incluso inline e colorato lato server: ogni CAP
 * con stato 'active' o 'future' nel file di copertura diventa data-state="covered". Nessun JavaScript. Il viewBox
 * viene ristretto al riquadro delle zone coperte e la mappa sta tutta nel riquadro (preserveAspectRatio meet): le
 * zone arrivano fino a Ostia, un ritaglio le taglierebbe. Senza copertura leggibile la mappa esce tutta grigia.
 *
 * Args: eyebrow, title (una riga per invio), accent (parola del titolo da colorare), text, cta_primary {label, link,
 * position}, cta_secondary {label, link, position}, floats[] {icon ('shield' | 'pin'), title, text},
 * stats[] {value, label}, coverage (percorso assoluto del JSON di copertura).
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'       => '',
	'title'         => '',
	'accent'        => '',
	'text'          => '',
	'cta_primary'   => array(),
	'cta_secondary' => array(),
	'floats'        => array(),
	'stats'         => array(),
	'coverage'      => '',
) );

/** SVG dei CAP con le zone coperte marcate e il viewBox stretto sulle zone coperte. '' se il file manca. */
$tp_about_map = function ( $coverage_file ) {
	$svg = (string) @file_get_contents( get_stylesheet_directory() . '/assets/img/zones-map-cap.svg' );
	if ( '' === $svg ) {
		return '';
	}
	$covered = array();
	$json    = $coverage_file ? json_decode( (string) @file_get_contents( $coverage_file ), true ) : null;
	foreach ( (array) ( $json['zones'] ?? array() ) as $z ) {
		if ( in_array( $z['status'] ?? null, array( 'active', 'future' ), true ) ) {
			$covered[ (string) $z['cap'] ] = true;
		}
	}
	$min = array( INF, INF );
	$max = array( -INF, -INF );
	// I CAP con un nome hanno data-name fra data-cap e data-state: gli attributi intermedi restano com'erano.
	$svg = preg_replace_callback( '/<path data-cap="(\d{5})"([^>]*?) data-state="[a-z]*" d="([^"]*)"/', function ( $m ) use ( $covered, &$min, &$max ) {
		if ( empty( $covered[ $m[1] ] ) ) {
			return '<path data-cap="' . $m[1] . '"' . $m[2] . ' data-state="none" d="' . $m[3] . '"';
		}
		preg_match_all( '/(-?[\d.]+)[ ,](-?[\d.]+)/', $m[3], $pt, PREG_SET_ORDER );
		foreach ( $pt as $p ) {
			$min = array( min( $min[0], (float) $p[1] ), min( $min[1], (float) $p[2] ) );
			$max = array( max( $max[0], (float) $p[1] ), max( $max[1], (float) $p[2] ) );
		}
		return '<path data-cap="' . $m[1] . '"' . $m[2] . ' data-state="covered" d="' . $m[3] . '"';
	}, $svg );
	if ( is_finite( $min[0] ) ) {
		$pad = 40;
		$box = sprintf( '%.1f %.1f %.1f %.1f', $min[0] - $pad, $min[1] - $pad, $max[0] - $min[0] + 2 * $pad, $max[1] - $min[1] + 2 * $pad );
		$svg = preg_replace( '/viewBox="[^"]*"/', 'viewBox="' . $box . '"', $svg, 1 );
	}
	$svg = preg_replace( '/class="zones-map__svg"/', 'class="ab-hero__svg" preserveAspectRatio="xMidYMid meet" aria-hidden="true" focusable="false"', $svg, 1 );
	return preg_replace( '/ role="img" aria-label="[^"]*"/', '', $svg, 1 );
};

$icons = array(
	'shield' => '<path d="M12 3 5 6v5c0 4.4 3 8.3 7 9.5 4-1.2 7-5.1 7-9.5V6z"/><path d="m9 12 2 2 4-4"/>',
	'pin'    => '<path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
);
$lines = tp_lines( $args['title'] );
$btn   = function ( $b, $class ) {
	if ( empty( $b['label'] ) || empty( $b['link'] ) ) {
		return;
	}
	printf(
		'<a class="tp-btn %1$s" href="%2$s" data-cta-position="%3$s">%4$s</a>',
		esc_attr( $class ),
		esc_url( $b['link'] ),
		esc_attr( $b['position'] ?? 'hero' ),
		esc_html( $b['label'] ) . ( false !== strpos( $class, 'primary' ) ? ' <span class="dc-arrow" aria-hidden="true">&rarr;</span>' : '' )
	);
};
?>
<section class="ab-hero" id="hero">
	<div class="ab-hero__panel">
		<div class="ab-hero__copy">
			<?php if ( $args['eyebrow'] ) : ?><p class="ab-hero__pill"><span class="ab-hero__dot" aria-hidden="true"></span><?php echo esc_html( $args['eyebrow'] ); ?></p><?php endif; ?>
			<h1 class="ab-hero__title"><?php
			foreach ( $lines as $i => $line ) {
				$html = esc_html( $line );
				if ( $args['accent'] ) {
					$html = preg_replace( '/' . preg_quote( esc_html( $args['accent'] ), '/' ) . '/u', '<span class="ab-hero__accent">$0</span>', $html, 1 );
				}
				// Lo spazio prima del <br> tiene separate le parole nel testo letto da motori e screen reader.
				echo ( $i ? ' <br>' : '' ) . $html; // phpcs:ignore -- testo gia' passato da esc_html
			}
			?></h1>
			<?php if ( $args['text'] ) : ?><p class="ab-hero__text"><?php echo esc_html( $args['text'] ); ?></p><?php endif; ?>
			<div class="ab-hero__ctas">
				<?php $btn( $args['cta_primary'], 'tp-btn--primary tp-btn--lg ab-btn' ); ?>
				<?php $btn( $args['cta_secondary'], 'tp-btn--lg ab-btn ab-btn--ghost' ); ?>
			</div>
		</div>
		<div class="ab-hero__map">
			<?php echo $tp_about_map( $args['coverage'] ); // phpcs:ignore -- SVG del tema, path ricostruiti sopra ?>
			<?php foreach ( (array) $args['floats'] as $i => $f ) :
				if ( empty( $f['title'] ) ) {
					continue;
				} ?>
				<div class="ab-float ab-float--<?php echo (int) $i + 1; ?>">
					<span class="ab-float__icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?php echo $icons[ $f['icon'] ?? '' ] ?? $icons['pin']; // phpcs:ignore -- markup fisso ?></svg></span>
					<span class="ab-float__text"><strong><?php echo esc_html( $f['title'] ); ?></strong><?php if ( ! empty( $f['text'] ) ) : ?><span><?php echo esc_html( $f['text'] ); ?></span><?php endif; ?></span>
				</div>
			<?php endforeach; ?>
			<?php $stats = array_filter( (array) $args['stats'], function ( $s ) { return '' !== trim( (string) ( $s['value'] ?? '' ) ); } ); ?>
			<?php if ( $stats ) : ?>
				<dl class="ab-hero__stats">
					<?php foreach ( $stats as $s ) : ?>
						<div><dt><?php echo esc_html( $s['label'] ?? '' ); ?></dt><dd><?php echo esc_html( $s['value'] ); ?></dd></div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>
		</div>
	</div>
</section>
