<?php
/**
 * Sezione "Perche' noi" — landing B2B (Figma 6782:895, TICKET-60; testi del live VALUE_PROPS, quarta card del
 * ridisegno). Quattro card in griglia 2x2 su fondo grigio chiaro: icona monocroma (tp_icon_span, maschera CSS),
 * titolo, testo. Riutilizzabile: nessun testo fisso qui.
 *
 * Args: eyebrow, title, subtitle, id, items[] {icon (percorso relativo al tema, es. assets/icons/ic-clock.svg),
 * title, text}. Senza voci non esce.
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'  => '',
	'title'    => '',
	'subtitle' => '',
	'id'       => 'perche-noi',
	'items'    => array(),
) );
$items = array_values( array_filter( (array) $args['items'], function ( $v ) {
	return ! empty( $v['title'] );
} ) );
if ( ! $items ) {
	return;
}
?>
<section class="b2b-why" id="<?php echo esc_attr( $args['id'] ); ?>" data-reveal>
	<div class="b2b-why__inner">
		<div class="sg-section-head">
			<?php if ( $args['eyebrow'] ) : ?><p class="sg-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p><?php endif; ?>
			<?php if ( $args['title'] ) : ?><h2><?php echo esc_html( $args['title'] ); ?></h2><?php endif; ?>
			<?php if ( $args['subtitle'] ) : ?><p class="sg-section-head__sub"><?php echo esc_html( $args['subtitle'] ); ?></p><?php endif; ?>
		</div>
		<ul class="b2b-why__grid">
			<?php foreach ( $items as $i => $v ) : ?>
				<li class="b2b-why__card" style="--i:<?php echo (int) $i; ?>">
					<?php if ( ! empty( $v['icon'] ) ) : ?><span class="b2b-why__icon"><?php echo tp_icon_span( $v['icon'], 'b2b-why__glyph' ); ?></span><?php endif; ?>
					<h3 class="b2b-why__title"><?php echo esc_html( $v['title'] ); ?></h3>
					<?php if ( ! empty( $v['text'] ) ) : ?><p class="b2b-why__text"><?php echo esc_html( $v['text'] ); ?></p><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
