<?php
/**
 * Bento "Un'app che lavora per te" — pagina Diventa cleaner (Figma 6752:895, sezione L'app; TICKET-53).
 * Griglia a tre colonne da 384px: una tile larga (due colonne, testo in alto a sinistra, schermo a destra a filo del
 * bordo inferiore) e le altre a una colonna (testo sopra, schermo sotto). Sotto 1024px una colonna.
 *
 * Args: eyebrow, title, subtitle, id, tiles[] {title, text, screen (URL), wide (bool)}.
 * Gli schermi sono gia' ritagliati sulla porzione da mostrare (assets/img/cleaner/tile-*.webp): il CSS li ancora
 * in alto. Una tile senza schermo mostra solo il testo. Senza tile la sezione non esce.
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'  => '',
	'title'    => '',
	'subtitle' => '',
	'id'       => 'app',
	'tiles'    => array(),
) );
$tiles = array_values( array_filter( (array) $args['tiles'], function ( $t ) {
	return ! empty( $t['title'] );
} ) );
if ( ! $tiles ) {
	return;
}
?>
<section class="dc-bento" id="<?php echo esc_attr( $args['id'] ); ?>" data-reveal>
	<div class="dc-bento__inner">
		<div class="sg-section-head">
			<?php if ( $args['eyebrow'] ) : ?><p class="sg-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p><?php endif; ?>
			<?php if ( $args['title'] ) : ?><h2><?php echo esc_html( $args['title'] ); ?></h2><?php endif; ?>
			<?php if ( $args['subtitle'] ) : ?><p class="sg-section-head__sub"><?php echo esc_html( $args['subtitle'] ); ?></p><?php endif; ?>
		</div>
		<div class="dc-bento__grid">
			<?php foreach ( $tiles as $i => $t ) : $wide = ! empty( $t['wide'] ); ?>
				<article class="dc-tile<?php echo $wide ? ' dc-tile--wide' : ''; ?>" style="--i:<?php echo (int) $i; ?>">
					<div class="dc-tile__text">
						<h3 class="dc-tile__title"><?php echo esc_html( $t['title'] ); ?></h3>
						<?php if ( ! empty( $t['text'] ) ) : ?><p class="dc-tile__desc"><?php echo esc_html( $t['text'] ); ?></p><?php endif; ?>
					</div>
					<?php if ( ! empty( $t['screen'] ) ) : ?>
						<div class="dc-tile__screen dc-peek" aria-hidden="true"><img src="<?php echo esc_url( $t['screen'] ); ?>" alt="" width="680" height="488" loading="lazy" decoding="async"></div>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
