<?php
/**
 * Banner coupon (Figma 4364:6951, mobile 4364:7308; testi dal live): importo grande, titolo, testo e CTA scura verso
 * il form in fondo. Sulla landing Sgrosso sta subito sotto le recensioni. Args: amount, title, text, cta_label,
 * cta_link. Senza importo e titolo non esce.
 */
$args = wp_parse_args( $args ?? array(), array(
	'amount'    => '',
	'title'     => '',
	'text'      => '',
	'cta_label' => '',
	'cta_link'  => '#form-bottom',
) );
if ( ! $args['amount'] && ! $args['title'] ) {
	return;
}
?>
<section class="sg-coupon" aria-label="Coupon">
	<div class="sg-coupon__band" data-reveal>
		<div class="sg-coupon__left">
			<?php if ( $args['amount'] ) : ?><span class="sg-coupon__amount"><?php echo esc_html( $args['amount'] ); ?></span><?php endif; ?>
			<div class="sg-coupon__copy">
				<?php if ( $args['title'] ) : ?><p class="sg-coupon__title"><?php echo esc_html( $args['title'] ); ?></p><?php endif; ?>
				<?php if ( $args['text'] ) : ?><p class="sg-coupon__text"><?php echo esc_html( $args['text'] ); ?></p><?php endif; ?>
			</div>
		</div>
		<?php if ( $args['cta_label'] ) : ?>
			<a class="sg-btn-dark sg-coupon__cta" href="<?php echo esc_url( $args['cta_link'] ); ?>"><?php echo esc_html( $args['cta_label'] ); ?> <span class="sg-cta__arrow" aria-hidden="true">&rarr;</span></a>
		<?php endif; ?>
	</div>
</section>
