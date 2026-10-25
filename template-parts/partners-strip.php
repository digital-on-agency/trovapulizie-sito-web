<?php
/**
 * Strip "Lavorano gia' con noi" — pagina Diventa cleaner (TICKET-55). Nomi dei partner come pill di testo e una
 * riga di coda. [ATTENZIONE] Nasce vuota: i nomi si scrivono nel campo solo dopo l'ok dei partner
 * (SPEC-pagina-diventa-cleaner, assunzione 4). Senza nomi la sezione non esiste nel DOM.
 * Args: title, names (array di stringhe), tail.
 */
$args = wp_parse_args( $args ?? array(), array(
	'title' => '',
	'names' => array(),
	'tail'  => '',
) );
$names = array_values( array_filter( array_map( 'trim', (array) $args['names'] ) ) );
if ( ! $names ) {
	return;
}
?>
<section class="dc-partners" aria-label="<?php echo esc_attr( $args['title'] ?: 'Partner' ); ?>">
	<?php if ( $args['title'] ) : ?><p class="sg-eyebrow"><?php echo esc_html( $args['title'] ); ?></p><?php endif; ?>
	<ul class="dc-partners__list">
		<?php foreach ( $names as $n ) : ?><li class="dc-partners__pill"><?php echo esc_html( $n ); ?></li><?php endforeach; ?>
		<?php if ( $args['tail'] ) : ?><li class="dc-partners__tail"><?php echo esc_html( $args['tail'] ); ?></li><?php endif; ?>
	</ul>
</section>
