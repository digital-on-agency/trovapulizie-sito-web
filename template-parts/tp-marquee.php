<?php
/**
 * Fascia di frasi che scorre in loop (Figma 7903:6209 desktop, 7946:662 mobile). Componente generico a nome neutro:
 * lo usa la fascia frasi della Home v2 (TICKET-121) e puo' servire a ogni altra fascia a scorrimento.
 *
 * Markup: <div class="tp-marquee" data-tp-loop> con una pista (.tp-marquee__track) che contiene `repeat` gruppi
 * identici di frasi (<ul>), separate da un punto decorativo. Solo il primo gruppo e' letto dagli screen reader: gli
 * altri sono copie per il giro continuo e portano aria-hidden="true", cosi' nessuna frase viene letta due volte.
 * La pista scorre verso sinistra di un gruppo intero (100 / repeat %) per ciclo, in linea retta: a fine ciclo il
 * gruppo successivo e' esattamente dove stava il primo, quindi il giro non si vede.
 *
 * Argomenti (tutti facoltativi, default fra parentesi):
 * - items (string[], array()): le frasi. Vuoto o senza frasi non vuote = il componente non stampa nulla.
 * - duration (float, 30): secondi per ciclo (un gruppo). Piu' alto = piu' lento. Va in --tp-marquee-dur.
 * - direction (string, 'left'): 'left' (default) o 'right'. Va nell'attributo data-direction.
 * - repeat (int, 4): numero di gruppi. Il gruppo da solo deve essere largo almeno quanto lo schermo diviso (repeat - 1);
 *   con tre frasi lunghe 4 gruppi bastano oltre i 2500 px. Minimo 2.
 * - items_html (string[], array()): alternativa a `items` per voci che non sono solo testo (loghi, foto: TICKET-124). Ogni voce e'
 *   l'HTML gia' escluso dal chiamante, messo dentro il <li>. Se compilato vince su `items`.
 * - dots (bool, true): il punto decorativo dopo ogni voce. Con `items_html` il chiamante di solito lo spegne.
 * - class (string, ''): classi extra sul contenitore (colori, corpo del carattere e margini stanno nel CSS del chiamante).
 *
 * Movimento: animazione CSS lineare (solo transform; la scheda 7882:4118 chiede un giro continuo a velocita' costante). In pausa al passaggio del mouse, quando il contenitore esce
 * dal viewport (assets/js/tp-loops.js: un IntersectionObserver condiviso che mette .is-paused) e sempre ferma con
 * "riduci movimento" (blocco prefers-reduced-motion della v2 in style.css). Senza JavaScript scorre comunque.
 *
 * Esempio:
 *   get_template_part( 'template-parts/tp-marquee', null, array(
 *       'items' => array( 'La casa pulita. Il weekend libero.', 'Il divano ringrazia.' ), 'duration' => 30, 'class' => 'hv2-frasi__marquee',
 *   ) );
 */
$args = wp_parse_args( $args ?? array(), array(
	'items'     => array(),
	'duration'  => 30,
	'direction' => 'left',
	'repeat'    => 4,
	'class'     => '',
	'items_html' => array(),
	'dots'      => true,
) );
$tp_mq_html = array_values( array_filter( (array) $args['items_html'], static function ( $v ) {
	return is_string( $v ) && '' !== trim( $v );
} ) );
$tp_mq_items = array_values( array_filter( array_map( static function ( $v ) {
	return trim( (string) $v );
}, (array) $args['items'] ), static function ( $v ) {
	return '' !== $v;
} ) );
if ( ! $tp_mq_items && ! $tp_mq_html ) {
	return;
}
$tp_mq_repeat = max( 2, (int) $args['repeat'] );
$tp_mq_dur    = max( 5, (float) $args['duration'] );
$tp_mq_dir    = 'right' === $args['direction'] ? 'right' : 'left';
?>
<div class="tp-marquee <?php echo esc_attr( $args['class'] ); ?>" data-tp-loop data-direction="<?php echo esc_attr( $tp_mq_dir ); ?>" style="--tp-marquee-dur: <?php echo esc_attr( $tp_mq_dur ); ?>s; --tp-marquee-n: <?php echo (int) $tp_mq_repeat; ?>">
	<div class="tp-marquee__track">
		<?php for ( $tp_mq_g = 0; $tp_mq_g < $tp_mq_repeat; $tp_mq_g++ ) : ?>
			<ul class="tp-marquee__group"<?php echo $tp_mq_g > 0 ? ' aria-hidden="true"' : ''; ?>>
				<?php if ( $tp_mq_html ) : ?>
					<?php foreach ( $tp_mq_html as $tp_mq_item ) : ?>
						<li class="tp-marquee__item"><?php echo $tp_mq_item; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML gia' escluso dal chiamante ?><?php echo $args['dots'] ? '<span class="tp-marquee__dot" aria-hidden="true"></span>' : ''; ?></li>
					<?php endforeach; ?>
				<?php else : ?>
					<?php foreach ( $tp_mq_items as $tp_mq_item ) : ?>
						<li class="tp-marquee__item"><span class="tp-marquee__text"><?php echo esc_html( $tp_mq_item ); ?></span><span class="tp-marquee__dot" aria-hidden="true"></span></li>
					<?php endforeach; ?>
				<?php endif; ?>
			</ul>
		<?php endfor; ?>
	</div>
</div>
