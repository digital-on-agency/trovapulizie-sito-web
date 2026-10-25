<?php
/**
 * "Per chi e'" — pagina Diventa cleaner (Figma 6752:895; TICKET-55). Due card affiancate da 588px: una chiara
 * (professionista con partita IVA, CTA al signup) e una scura (impresa, CTA WhatsApp). Sotto 1024px impilate.
 *
 * Args: eyebrow, title, subtitle, id, cards[] {badge, title, text, bullets (array di stringhe), cta_label, cta_link,
 * cta_position, dark (bool)}.
 * Badge e spunte della card scura su #08875E (4,6:1 col bianco): analisi UX §2. I link wa.me si aprono in una
 * nuova scheda; il click e' tracciato da tp-track.js (marketing_click_whatsapp con cta_position).
 * Senza card la sezione non esce.
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'  => '',
	'title'    => '',
	'subtitle' => '',
	'id'       => 'per-chi',
	'cards'    => array(),
) );
$cards = array_values( array_filter( (array) $args['cards'], function ( $c ) {
	return ! empty( $c['title'] );
} ) );
if ( ! $cards ) {
	return;
}
?>
<section class="dc-who" id="<?php echo esc_attr( $args['id'] ); ?>" data-reveal>
	<div class="dc-who__inner">
		<div class="sg-section-head">
			<?php if ( $args['eyebrow'] ) : ?><p class="sg-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p><?php endif; ?>
			<?php if ( $args['title'] ) : ?><h2><?php echo esc_html( $args['title'] ); ?></h2><?php endif; ?>
			<?php if ( $args['subtitle'] ) : ?><p class="sg-section-head__sub"><?php echo esc_html( $args['subtitle'] ); ?></p><?php endif; ?>
		</div>
		<div class="dc-who__cards">
			<?php foreach ( $cards as $c ) :
				$dark = ! empty( $c['dark'] );
				$wa   = ! empty( $c['cta_link'] ) && false !== strpos( $c['cta_link'], 'wa.me' );
				?>
				<article class="dc-who__card<?php echo $dark ? ' dc-who__card--dark' : ''; ?>">
					<?php if ( ! empty( $c['badge'] ) ) : ?><span class="dc-who__badge" aria-hidden="true"><?php echo esc_html( $c['badge'] ); ?></span><?php endif; ?>
					<h3 class="dc-who__title"><?php echo esc_html( $c['title'] ); ?></h3>
					<?php if ( ! empty( $c['text'] ) ) : ?><p class="dc-who__text"><?php echo esc_html( $c['text'] ); ?></p><?php endif; ?>
					<?php if ( ! empty( $c['bullets'] ) ) : ?>
						<ul class="dc-who__list">
							<?php foreach ( (array) $c['bullets'] as $b ) : ?>
								<li><span class="dc-check" aria-hidden="true"><?php echo tp_icon_span( 'assets/icons/ic-check.svg', 'dc-check__icon' ); ?></span><span><?php echo esc_html( $b ); ?></span></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<?php if ( ! empty( $c['cta_label'] ) && ! empty( $c['cta_link'] ) ) : ?>
						<a class="tp-btn <?php echo $dark ? 'dc-btn--light' : 'tp-btn--primary'; ?> dc-who__cta" href="<?php echo esc_url( $c['cta_link'] ); ?>" data-cta-position="<?php echo esc_attr( $c['cta_position'] ?? 'per-chi' ); ?>"<?php echo $wa ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html( $c['cta_label'] ); ?> <span class="dc-arrow" aria-hidden="true">&rarr;</span></a>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
