<?php
/**
 * "Cosa facciamo" — landing zanzare (Figma 4849:1077; TICKET-74). Testa centrata, quattro card con foto, titolo e
 * testo, e sotto un banner menta con spunta, titolo, testo e CTA (condomini e attivita'). Due colonne sotto 1024px,
 * una sotto 640px.
 *
 * Args: eyebrow, title, id, items[] {image, title, text}, banner {title, text, cta_label, cta_link, cta_position,
 * preselect}. Senza card non esce; senza titolo il banner non esce.
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow' => '',
	'title'   => '',
	'id'      => 'cosa-facciamo',
	'items'   => array(),
	'banner'  => array(),
) );
$items = array_values( array_filter( (array) $args['items'], function ( $i ) {
	return ! empty( $i['title'] );
} ) );
if ( ! $items ) {
	return;
}
$b = wp_parse_args( (array) $args['banner'], array( 'title' => '', 'text' => '', 'cta_label' => '', 'cta_link' => '#form', 'cta_position' => '', 'preselect' => '' ) );
?>
<section class="zz-services" id="<?php echo esc_attr( $args['id'] ); ?>" data-reveal>
	<div class="zz-services__inner">
		<div class="sg-section-head">
			<?php if ( $args['eyebrow'] ) : ?><p class="sg-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p><?php endif; ?>
			<?php if ( $args['title'] ) : ?><h2><?php echo esc_html( $args['title'] ); ?></h2><?php endif; ?>
		</div>
		<ul class="zz-services__grid">
			<?php foreach ( $items as $i => $it ) : ?>
				<li class="zz-services__card" style="--i:<?php echo (int) $i; ?>">
					<?php if ( ! empty( $it['image'] ) ) : ?><img class="zz-services__img" src="<?php echo esc_url( $it['image'] ); ?>" alt="" width="900" height="502" loading="lazy" decoding="async"><?php endif; ?>
					<h3 class="zz-services__title"><?php echo esc_html( $it['title'] ); ?></h3>
					<?php if ( ! empty( $it['text'] ) ) : ?><p class="zz-services__text"><?php echo esc_html( $it['text'] ); ?></p><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php if ( $b['title'] ) : ?>
			<div class="zz-services__banner">
				<span class="zz-services__banner-icon" aria-hidden="true"><?php echo tp_icon_span( 'assets/icons/ic-check.svg', 'zz-services__banner-check' ); ?></span>
				<div class="zz-services__banner-text">
					<p class="zz-services__banner-title"><?php echo esc_html( $b['title'] ); ?></p>
					<?php if ( $b['text'] ) : ?><p><?php echo esc_html( $b['text'] ); ?></p><?php endif; ?>
				</div>
				<?php if ( $b['cta_label'] ) : ?>
					<a class="tp-btn zz-services__banner-cta" href="<?php echo esc_url( $b['cta_link'] ); ?>"<?php if ( $b['cta_position'] ) : ?> data-cta-position="<?php echo esc_attr( $b['cta_position'] ); ?>"<?php endif; ?><?php if ( $b['preselect'] ) : ?> data-preselect="<?php echo esc_attr( $b['preselect'] ); ?>"<?php endif; ?>><?php echo esc_html( $b['cta_label'] ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
