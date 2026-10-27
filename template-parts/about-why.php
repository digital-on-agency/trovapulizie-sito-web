<?php
/**
 * "Perche' esistiamo" — Chi siamo (Figma 6795:948; TICKET-67). Due colonne: a sinistra eyebrow, titolo, paragrafi e
 * una frase di chiusura in grassetto; a destra card bianche con icona, titolo e testo. Sotto 1024px impilate.
 *
 * Args: eyebrow, title, paragraphs (array di stringhe), closing, items[] {icon ('shield' | 'tag' | 'card'), title,
 * text}. Senza titolo la sezione non esce.
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'    => '',
	'title'      => '',
	'paragraphs' => array(),
	'closing'    => '',
	'items'      => array(),
) );
if ( ! $args['title'] ) {
	return;
}
$items = array_values( array_filter( (array) $args['items'], function ( $i ) {
	return ! empty( $i['title'] );
} ) );
$icons = array(
	'shield' => '<path d="M12 3 5 6v5c0 4.4 3 8.3 7 9.5 4-1.2 7-5.1 7-9.5V6z"/><path d="m9 12 2 2 4-4"/>',
	'tag'    => '<path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
	'card'   => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h4"/>',
);
?>
<section class="ab-why" id="perche" data-reveal>
	<div class="ab-why__inner">
		<div class="ab-why__copy">
			<?php if ( $args['eyebrow'] ) : ?><p class="ab-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p><?php endif; ?>
			<h2 class="ab-why__title"><?php echo esc_html( $args['title'] ); ?></h2>
			<?php foreach ( (array) $args['paragraphs'] as $p ) : ?><p class="ab-why__text"><?php echo esc_html( $p ); ?></p><?php endforeach; ?>
			<?php if ( $args['closing'] ) : ?><p class="ab-why__closing"><?php echo esc_html( $args['closing'] ); ?></p><?php endif; ?>
		</div>
		<?php if ( $items ) : ?>
		<ul class="ab-why__cards">
			<?php foreach ( $items as $i => $it ) : ?>
				<li class="ab-why__card" style="--i:<?php echo (int) $i; ?>">
					<span class="ab-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?php echo $icons[ $it['icon'] ?? '' ] ?? $icons['shield']; // phpcs:ignore -- markup fisso ?></svg></span>
					<span class="ab-why__card-text"><strong><?php echo esc_html( $it['title'] ); ?></strong><?php if ( ! empty( $it['text'] ) ) : ?><span><?php echo esc_html( $it['text'] ); ?></span><?php endif; ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>
	</div>
</section>
