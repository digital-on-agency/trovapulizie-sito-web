<?php
/**
 * Trust bar — Figma 4364:6684 (desktop: barra bianca centrata con quattro voci e divisori) e 4364:7139
 * (mobile: due righe da due, senza barra). Subito sotto l'hero della landing Sgrosso (TICKET-43).
 * Args: items (array di stringhe). Senza voci non esce.
 */
$args = wp_parse_args( $args ?? array(), array(
	'items' => array(),
) );
$items = array_values( array_filter( array_map( 'trim', (array) $args['items'] ) ) );
if ( ! $items ) {
	return;
}
?>
<section class="sg-trust" aria-label="Garanzie">
	<ul class="sg-trust__bar">
		<?php foreach ( $items as $item ) : ?>
			<li>
				<span class="sg-trust__tile"><?php echo tp_icon_span( 'assets/icons/ic-check.svg', 'sg-trust__check' ); ?></span>
				<span><?php echo esc_html( $item ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
