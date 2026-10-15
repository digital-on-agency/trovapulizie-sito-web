<?php
/**
 * Sezione "CTA finale" — Figma 3564:162 (mobile 4025:1007). Args: title, subtitle, button_text, button_link, note,
 * image (URL illustrazione, vuota = mascotte con il mocio del Figma), theme_uri.
 * Motion spec §9: card in fade + scale .96 -> 1, illustrazione da sinistra con 120 ms di ritardo; hover bottone
 * lift + ombra, freccia che avanza di 3 px. Reveal via reveal.js ([data-reveal]). Riutilizzabile nelle landing.
 */
$args = wp_parse_args( $args ?? array(), array(
	'title'       => '',
	'subtitle'    => '',
	'button_text' => '',
	'button_link' => '',
	'note'        => '',
	'image'       => '',
	'theme_uri'   => get_stylesheet_directory_uri(),
) );
if ( ! $args['title'] && ! $args['button_text'] ) {
	return;
}
$image = $args['image'] ?: $args['theme_uri'] . '/assets/img/mascotte-mocio.png';
?>
<section class="cta-final" id="registrati" data-reveal>
	<div class="cta-final__card">
		<img class="cta-final__art" src="<?php echo esc_url( $image ); ?>" alt="" width="273" height="294" loading="lazy" aria-hidden="true">
		<div class="cta-final__text">
			<?php if ( $args['title'] ) : ?><h2 class="cta-final__title"><?php echo esc_html( $args['title'] ); ?></h2><?php endif; ?>
			<?php if ( $args['subtitle'] ) : ?><p class="cta-final__subtitle"><?php echo esc_html( $args['subtitle'] ); ?></p><?php endif; ?>
			<?php if ( $args['button_text'] && $args['button_link'] ) : ?>
				<a class="cta-final__button" href="<?php echo esc_url( $args['button_link'] ); ?>"><span><?php echo esc_html( $args['button_text'] ); ?></span><span class="cta-final__arrow" aria-hidden="true">&rarr;</span></a>
			<?php endif; ?>
			<?php if ( $args['note'] ) : ?><p class="cta-final__note"><?php echo esc_html( $args['note'] ); ?></p><?php endif; ?>
		</div>
	</div>
</section>
