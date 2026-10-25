<?php
/**
 * "Come funziona" a tre card numerate con frecce (Figma 4364:6830, mobile 4364:7282; testi dal live STEPS).
 * Versione senza telefono: la landing paid non ha la sequenza di schermate dell'hub (how-it-works-static.php),
 * che resta com'e'. Args: eyebrow, title, subtitle, steps[] {title, description}, id. Senza passi non esce.
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'  => '',
	'title'    => '',
	'subtitle' => '',
	'steps'    => array(),
	'id'       => 'come-funziona',
) );
$steps = array_values( array_filter( (array) $args['steps'], function ( $s ) {
	return ! empty( $s['title'] );
} ) );
if ( ! $steps ) {
	return;
}
$last = count( $steps ) - 1;
?>
<section class="sg-steps" id="<?php echo esc_attr( $args['id'] ); ?>" data-reveal>
	<div class="sg-steps__inner">
		<div class="sg-section-head">
			<?php if ( $args['eyebrow'] ) : ?><p class="sg-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p><?php endif; ?>
			<?php if ( $args['title'] ) : ?><h2><?php echo esc_html( $args['title'] ); ?></h2><?php endif; ?>
			<?php if ( $args['subtitle'] ) : ?><p class="sg-section-head__sub"><?php echo esc_html( $args['subtitle'] ); ?></p><?php endif; ?>
		</div>
		<ol class="sg-steps__row">
			<?php foreach ( $steps as $i => $step ) : ?>
				<li class="sg-step" style="--i:<?php echo (int) $i; ?>">
					<span class="sg-step__badge" aria-hidden="true"><?php echo (int) $i + 1; ?></span>
					<p class="sg-step__title"><?php echo esc_html( $step['title'] ); ?></p>
					<?php if ( ! empty( $step['description'] ) ) : ?><p class="sg-step__desc"><?php echo esc_html( $step['description'] ); ?></p><?php endif; ?>
				</li>
				<?php if ( $i < $last ) : ?><li class="sg-steps__arrow" aria-hidden="true">&rarr;</li><?php endif; ?>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
