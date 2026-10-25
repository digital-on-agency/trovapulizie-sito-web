<?php
/**
 * Barra CTA fissa su mobile — Figma 3946:1280 (TICKET-31) e 4364:7535 (landing, TICKET-39).
 * Compare sotto 1024px quando l'elemento osservato esce dallo schermo e riporta alla ricerca o al form.
 * Args: label, target (selettore o URL), watch (selettore dell'elemento osservato, default .hub-hero),
 * variant ('single' | 'dual'); con 'dual' anche phone_href e phone_label (Chiama + CTA, Figma 4364:7536).
 * Senza label non esce. Comportamento in assets/js/sticky-cta.js.
 */
$args = wp_parse_args( $args ?? array(), array(
	'label'       => '',
	'target'      => '#cerca',
	'watch'       => '.hub-hero',
	'variant'     => 'single',
	'phone_href'  => '',
	'phone_label' => 'Chiama',
) );
if ( ! $args['label'] ) {
	return;
}
$dual = 'dual' === $args['variant'] && $args['phone_href'];
?>
<div class="sticky-cta<?php echo $dual ? ' sticky-cta--dual' : ''; ?>" data-sticky-cta data-watch="<?php echo esc_attr( $args['watch'] ); ?>" hidden>
	<?php if ( $dual ) : ?>
		<a class="sticky-cta__call" href="<?php echo esc_url( $args['phone_href'] ); ?>">
			<?php echo tp_icon_span( 'assets/icons/ic-phone.svg', 'sticky-cta__call-icon' ); ?>
			<span><?php echo esc_html( $args['phone_label'] ); ?></span>
		</a>
	<?php endif; ?>
	<a class="sticky-cta__btn tp-btn tp-btn--primary tp-btn--lg" href="<?php echo esc_url( $args['target'] ); ?>">
		<?php echo esc_html( $args['label'] ); ?>
	</a>
</div>
