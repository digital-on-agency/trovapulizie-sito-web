<?php
/**
 * Barra CTA fissa su mobile — Figma 3946:1280 (TICKET-31).
 * Compare sotto 1024px quando la hero esce dallo schermo e riporta alla ricerca.
 * Args: label, target (selettore o URL). Senza label non esce.
 */
$args = wp_parse_args( $args ?? array(), array(
	'label'  => '',
	'target' => '#cerca',
) );
if ( ! $args['label'] ) {
	return;
}
?>
<div class="sticky-cta" data-sticky-cta hidden>
	<a class="sticky-cta__btn tp-btn tp-btn--primary tp-btn--lg" href="<?php echo esc_url( $args['target'] ); ?>">
		<?php echo esc_html( $args['label'] ); ?>
	</a>
</div>
