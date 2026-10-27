<?php
/**
 * "La nostra storia" — Chi siamo (Figma 6795:1099; TICKET-68). Fondo scuro, testa centrata, tappe su una linea
 * orizzontale con un cerchio per tappa; l'ultima ha il cerchio pieno (la tappa corrente). Sotto 1024px la linea
 * diventa verticale a sinistra.
 *
 * Args: eyebrow, title, steps[] {date, title, text}. Senza tappe non esce.
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow' => '',
	'title'   => '',
	'steps'   => array(),
) );
$steps = array_values( array_filter( (array) $args['steps'], function ( $s ) {
	return ! empty( $s['title'] );
} ) );
if ( ! $steps ) {
	return;
}
$last = count( $steps ) - 1;
?>
<section class="ab-story" id="storia" data-reveal>
	<div class="ab-story__inner">
		<div class="sg-section-head">
			<?php if ( $args['eyebrow'] ) : ?><p class="ab-eyebrow ab-eyebrow--dark"><?php echo esc_html( $args['eyebrow'] ); ?></p><?php endif; ?>
			<?php if ( $args['title'] ) : ?><h2><?php echo esc_html( $args['title'] ); ?></h2><?php endif; ?>
		</div>
		<ol class="ab-story__steps" style="--steps:<?php echo (int) count( $steps ); ?>">
			<?php foreach ( $steps as $i => $s ) : ?>
				<li class="ab-story__step<?php echo $i === $last ? ' is-current' : ''; ?>" style="--i:<?php echo (int) $i; ?>">
					<span class="ab-story__dot" aria-hidden="true"></span>
					<?php if ( ! empty( $s['date'] ) ) : ?><p class="ab-story__date"><?php echo esc_html( $s['date'] ); ?></p><?php endif; ?>
					<h3 class="ab-story__title"><?php echo esc_html( $s['title'] ); ?></h3>
					<?php if ( ! empty( $s['text'] ) ) : ?><p class="ab-story__text"><?php echo esc_html( $s['text'] ); ?></p><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
