<?php
/**
 * CTA finale con la seconda istanza del form lead (Figma 4473:4967, mobile 4364:7454; testi dal live "Ultimo passo").
 * A sinistra eyebrow a pillola, titolo della variante, sottotitolo, quattro punti con tile tonda e la mascotte;
 * a destra il form con id proprio (#form-bottom): le CTA della meta' inferiore della pagina puntano qui e non
 * risalgono all'hero. Il form riporta cta_position 'bottom' negli eventi.
 *
 * Args: eyebrow, title, subtitle, bullets[], image (vuota = nessuna mascotte), form (array di args per la parte del
 * form), form_part (default template-parts/lead-form; la landing B2B passa template-parts/lead-form-b2b).
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'  => '',
	'title'    => '',
	'subtitle' => '',
	'bullets'  => array(),
	'image'    => '',
	'form'     => array(),
	'form_part' => 'template-parts/lead-form',
) );
$form = wp_parse_args( (array) $args['form'], array(
	'id'           => 'lead-bottom',
	'cta_position' => 'bottom',
) );
?>
<section class="sg-final" id="ultimo-passo">
	<div class="sg-final__inner">
		<div class="sg-final__copy" data-reveal>
			<?php if ( $args['eyebrow'] ) : ?><p class="sg-eyebrow sg-eyebrow--pill"><?php echo esc_html( $args['eyebrow'] ); ?></p><?php endif; ?>
			<?php if ( $args['title'] ) : ?><h2><?php echo esc_html( $args['title'] ); ?></h2><?php endif; ?>
			<?php if ( $args['subtitle'] ) : ?><p class="sg-final__subtitle"><?php echo esc_html( $args['subtitle'] ); ?></p><?php endif; ?>
			<?php if ( $args['bullets'] ) : ?>
			<ul class="sg-final__bullets">
				<?php foreach ( (array) $args['bullets'] as $line ) : ?>
					<li><span class="sg-final__tile"><?php echo tp_icon_span( 'assets/icons/ic-check.svg', 'sg-final__check' ); ?></span><span><?php echo esc_html( $line ); ?></span></li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>
			<?php if ( $args['image'] ) : ?>
				<img class="sg-final__mascot" src="<?php echo esc_url( $args['image'] ); ?>" alt="" width="400" height="400" loading="lazy" decoding="async">
			<?php endif; ?>
		</div>
		<div class="sg-final__form" id="form-bottom">
			<?php get_template_part( $args['form_part'], null, $form ); ?>
		</div>
	</div>
</section>
