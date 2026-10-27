<?php
/**
 * Fascia scura "Zanzara tigre" — landing zanzare (Figma 4849:1003; TICKET-74). A sinistra eyebrow, titolo, testo e
 * una riga con la spunta; a destra un riquadro con i focolai in chip e una nota. Sotto 1024px impilata.
 *
 * Args: eyebrow, title, text, check, box_title, chips (array di stringhe), box_note, id. Senza titolo non esce.
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'   => '',
	'title'     => '',
	'text'      => '',
	'check'     => '',
	'box_title' => '',
	'chips'     => array(),
	'box_note'  => '',
	'id'        => 'zanzara-tigre',
) );
if ( ! $args['title'] ) {
	return;
}
$chips = array_values( array_filter( array_map( 'trim', (array) $args['chips'] ) ) );
?>
<section class="zz-tiger" id="<?php echo esc_attr( $args['id'] ); ?>" data-reveal>
	<div class="zz-tiger__inner">
		<div class="zz-tiger__copy">
			<?php if ( $args['eyebrow'] ) : ?><p class="zz-tiger__eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p><?php endif; ?>
			<h2 class="zz-tiger__title"><?php echo esc_html( $args['title'] ); ?></h2>
			<?php if ( $args['text'] ) : ?><p class="zz-tiger__text"><?php echo esc_html( $args['text'] ); ?></p><?php endif; ?>
			<?php if ( $args['check'] ) : ?><p class="zz-tiger__check"><?php echo tp_icon_span( 'assets/icons/ic-check.svg', 'zz-tiger__check-icon' ); ?><span><?php echo esc_html( $args['check'] ); ?></span></p><?php endif; ?>
		</div>
		<?php if ( $chips ) : ?>
		<div class="zz-tiger__box">
			<?php if ( $args['box_title'] ) : ?><p class="zz-tiger__box-title"><?php echo esc_html( $args['box_title'] ); ?></p><?php endif; ?>
			<ul class="zz-tiger__chips">
				<?php foreach ( $chips as $c ) : ?><li><?php echo tp_icon_span( 'assets/icons/ic-check.svg', 'zz-tiger__chip-icon' ); ?><?php echo esc_html( $c ); ?></li><?php endforeach; ?>
			</ul>
			<?php if ( $args['box_note'] ) : ?><p class="zz-tiger__note"><?php echo esc_html( $args['box_note'] ); ?></p><?php endif; ?>
		</div>
		<?php endif; ?>
	</div>
</section>
