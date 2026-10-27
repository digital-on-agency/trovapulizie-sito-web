<?php
/**
 * Prezzi a piani — landing zanzare (Figma 4849:1160; TICKET-75). Testa centrata, card affiancate (il piano
 * `highlighted` ha bordo verde e badge), nota sotto. Impilate sotto 1024px.
 *
 * Args: eyebrow, title, subtitle, id, note, tiers[] {label, badge, price, unit, text, features[], cta_label,
 * cta_link, cta_position, highlighted (bool)}. Senza piani non esce.
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'  => '',
	'title'    => '',
	'subtitle' => '',
	'id'       => 'prezzi',
	'note'     => '',
	'tiers'    => array(),
) );
$tiers = array_values( array_filter( (array) $args['tiers'], function ( $t ) {
	return ! empty( $t['price'] );
} ) );
if ( ! $tiers ) {
	return;
}
?>
<section class="zz-price" id="<?php echo esc_attr( $args['id'] ); ?>" data-reveal>
	<div class="zz-price__inner">
		<div class="sg-section-head">
			<?php if ( $args['eyebrow'] ) : ?><p class="sg-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p><?php endif; ?>
			<?php if ( $args['title'] ) : ?><h2><?php echo esc_html( $args['title'] ); ?></h2><?php endif; ?>
			<?php if ( $args['subtitle'] ) : ?><p class="sg-section-head__sub"><?php echo esc_html( $args['subtitle'] ); ?></p><?php endif; ?>
		</div>
		<div class="zz-price__tiers">
			<?php foreach ( $tiers as $i => $t ) : $hi = ! empty( $t['highlighted'] ); ?>
				<article class="zz-price__tier<?php echo $hi ? ' is-highlighted' : ''; ?>" style="--i:<?php echo (int) $i; ?>">
					<p class="zz-price__label"><span><?php echo esc_html( $t['label'] ?? '' ); ?></span><?php if ( ! empty( $t['badge'] ) ) : ?><span class="zz-price__badge"><?php echo esc_html( $t['badge'] ); ?></span><?php endif; ?></p>
					<p class="zz-price__amount"><strong><?php echo esc_html( $t['price'] ); ?></strong><?php if ( ! empty( $t['unit'] ) ) : ?> <span><?php echo esc_html( $t['unit'] ); ?></span><?php endif; ?></p>
					<?php if ( ! empty( $t['text'] ) ) : ?><p class="zz-price__text"><?php echo esc_html( $t['text'] ); ?></p><?php endif; ?>
					<?php if ( ! empty( $t['features'] ) ) : ?>
						<ul class="zz-price__list">
							<?php foreach ( (array) $t['features'] as $f ) : ?><li><?php echo tp_icon_span( 'assets/icons/ic-check.svg', 'zz-price__check' ); ?><span><?php echo esc_html( $f ); ?></span></li><?php endforeach; ?>
						</ul>
					<?php endif; ?>
					<?php if ( ! empty( $t['cta_label'] ) ) : ?>
						<a class="tp-btn <?php echo $hi ? 'tp-btn--primary' : 'tp-btn--secondary'; ?> zz-price__cta" href="<?php echo esc_url( $t['cta_link'] ?? '#form' ); ?>"<?php if ( ! empty( $t['cta_position'] ) ) : ?> data-cta-position="<?php echo esc_attr( $t['cta_position'] ); ?>"<?php endif; ?>><?php echo esc_html( $t['cta_label'] ); ?></a>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
		<?php if ( $args['note'] ) : ?><p class="zz-price__note"><?php echo esc_html( $args['note'] ); ?></p><?php endif; ?>
	</div>
</section>
