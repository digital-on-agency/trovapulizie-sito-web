<?php
/**
 * Sezione "Come funziona" statica — Figma desktop 4467:921, mobile 4471:921 (TICKET-28).
 * Colonna editoriale con tre passi numerati e un telefono fermo accanto.
 *
 * Args: eyebrow, title, subtitle, steps (array di title/description), screen (URL immagine).
 *
 * [ATTENZIONE] Volutamente senza coreografia: niente pin, niente video scrubbed, niente telefono 3D.
 * Quella e' della sola Home (ADR-002). Ripeterla su due pagine la svaluta e costa scroll; qui la
 * sezione ha altezza normale e non carica ne' GSAP ne' Three.js.
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'  => '',
	'title'    => '',
	'subtitle' => '',
	'steps'    => array(),
	'screen'   => '',
) );
$steps = array_values( array_filter( (array) $args['steps'], function ( $s ) {
	return ! empty( $s['title'] );
} ) );
if ( ! $steps ) {
	return;
}
?>
<section class="hiw-static" id="come-funziona" data-reveal>
	<div class="hiw-static__inner">

		<div class="hiw-static__left">
			<?php if ( $args['eyebrow'] ) : ?>
				<p class="hiw-static__eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p>
			<?php endif; ?>

			<?php if ( $args['title'] ) : ?>
				<h2 class="hiw-static__title"><?php echo esc_html( $args['title'] ); ?></h2>
			<?php endif; ?>

			<?php if ( $args['subtitle'] ) : ?>
				<p class="hiw-static__subtitle"><?php echo esc_html( $args['subtitle'] ); ?></p>
			<?php endif; ?>

			<ol class="hiw-static__steps">
				<?php foreach ( $steps as $i => $step ) : ?>
					<li class="hiw-static__step" style="--i:<?php echo (int) $i; ?>">
						<span class="hiw-static__num" aria-hidden="true"><?php echo (int) ( $i + 1 ); ?></span>
						<span class="hiw-static__step-text">
							<strong><?php echo esc_html( $step['title'] ); ?></strong>
							<?php if ( ! empty( $step['description'] ) ) : ?>
								<span><?php echo esc_html( $step['description'] ); ?></span>
							<?php endif; ?>
						</span>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>

		<div class="hiw-static__phone">
			<?php get_template_part( 'template-parts/phone-mockup', null, array(
				'screen' => $args['screen'],
			) ); ?>
		</div>

	</div>
</section>
