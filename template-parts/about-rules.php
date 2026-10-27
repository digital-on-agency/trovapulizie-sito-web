<?php
/**
 * "Come lavoriamo" — Chi siamo (Figma 6795:1034; TICKET-67). Testa centrata e quattro card menta con icona, titolo e
 * testo. Due colonne sotto 1024px, una sotto 640px.
 *
 * Args: eyebrow, title, items[] {icon ('star' | 'receipt' | 'chat' | 'map'), title, text}. Senza voci non esce.
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow' => '',
	'title'   => '',
	'items'   => array(),
) );
$items = array_values( array_filter( (array) $args['items'], function ( $i ) {
	return ! empty( $i['title'] );
} ) );
if ( ! $items ) {
	return;
}
$icons = array(
	'star'    => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9z"/>',
	'receipt' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6M9 16h3"/>',
	'chat'    => '<path d="M21 12a8.5 8.5 0 0 1-12.6 7.4L3 21l1.6-5.2A8.5 8.5 0 1 1 21 12z"/>',
	'map'     => '<path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3z"/><path d="M9 3v15M15 6v15"/>',
);
?>
<section class="ab-rules" id="come-lavoriamo" data-reveal>
	<div class="ab-rules__inner">
		<div class="sg-section-head">
			<?php if ( $args['eyebrow'] ) : ?><p class="ab-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p><?php endif; ?>
			<?php if ( $args['title'] ) : ?><h2><?php echo esc_html( $args['title'] ); ?></h2><?php endif; ?>
		</div>
		<ul class="ab-rules__cards">
			<?php foreach ( $items as $i => $it ) : ?>
				<li class="ab-rules__card" style="--i:<?php echo (int) $i; ?>">
					<span class="ab-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?php echo $icons[ $it['icon'] ?? '' ] ?? $icons['star']; // phpcs:ignore -- markup fisso ?></svg></span>
					<h3 class="ab-rules__title"><?php echo esc_html( $it['title'] ); ?></h3>
					<?php if ( ! empty( $it['text'] ) ) : ?><p class="ab-rules__text"><?php echo esc_html( $it['text'] ); ?></p><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
