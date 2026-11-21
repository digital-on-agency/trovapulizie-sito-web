<?php
/**
 * Carosello a scorrimento con indicatore annunciato (SPEC-homepage-v2 §7.1, TICKET-122). Componente generico a nome
 * neutro: lo usa Come funziona della Home v2 (home-steps-v2.php) e lo riusera' il ticket delle recensioni (TICKET-124).
 * Non decide l'aspetto: larghezza delle schede, spazi e il passaggio a griglia o a pila su desktop stanno nel CSS del
 * chiamante (per Come funziona: `.hv2-steps` in style.css). Il CSS di base del componente (`.tp-carousel`) mette solo
 * il scroll-snap orizzontale e lo stile dell'indicatore.
 *
 * Markup:
 *   <div class="tp-carousel" role="region" aria-roledescription="carosello" aria-label="..." data-tp-carousel>
 *     <ul class="tp-carousel__track" tabindex="0">  una <li class="tp-carousel__slide" style="--i: <?php echo (int) $tp_car_i; ?>" role="group" aria-roledescription="scheda" aria-label="N di M"> per scheda
 *     <div class="tp-carousel__nav" data-tp-carousel-nav hidden>   frecce, puntini (button) e "N di M" in aria-live="polite"
 * L'indicatore ha l'attributo `hidden` finche' tp-carousel.js non lo toglie: senza JavaScript il carosello scorre a mano
 * (scroll-snap CSS) e non mostra un indicatore che non funzionerebbe.
 *
 * Argomenti (tutti facoltativi; le letture Carbon stanno nel chiamante):
 * - slides (string[], array()): l'HTML gia' escluso di ogni scheda, dentro il <li>. Meno di una scheda = non stampa nulla.
 * - label (string, 'Carosello'): nome del carosello per gli screen reader (aria-label della regione).
 * - class (string, ''): classi extra sul contenitore (il chiamante vi appoggia il proprio blocco CSS).
 * - count_format (string, '%1$d di %2$d'): come si annuncia la posizione (sprintf: 1 = scheda corrente, 2 = totale).
 * - prev_label / next_label (string, 'Scheda precedente' / 'Scheda successiva'): nomi accessibili delle frecce.
 * - dot_label (string, 'Vai alla scheda %d'): nome accessibile di ogni puntino.
 *
 * JS: assets/js/tp-carousel.js (selettori [data-tp-carousel], evento `tp:carousel-change`). Caricato dal template che usa il componente.
 *
 * Esempio:
 *   get_template_part( 'template-parts/tp-carousel', null, array( 'slides' => array( '<p>Uno</p>', '<p>Due</p>' ), 'label' => 'Passi', 'class' => 'hv2-steps__carousel' ) );
 */
$args = wp_parse_args( $args ?? array(), array(
	'slides'       => array(),
	'label'        => 'Carosello',
	'class'        => '',
	'count_format' => '%1$d di %2$d',
	'prev_label'   => 'Scheda precedente',
	'next_label'   => 'Scheda successiva',
	'dot_label'    => 'Vai alla scheda %d',
) );
$tp_car_slides = array_values( array_filter( (array) $args['slides'], static function ( $s ) {
	return is_string( $s ) && '' !== trim( $s );
} ) );
$tp_car_n = count( $tp_car_slides );
if ( $tp_car_n < 1 ) {
	return;
}
$tp_car_count = static function ( $i ) use ( $args, $tp_car_n ) {
	return sprintf( $args['count_format'], $i, $tp_car_n );
};
?>
<div class="tp-carousel<?php echo '' !== trim( (string) $args['class'] ) ? ' ' . esc_attr( trim( (string) $args['class'] ) ) : ''; ?>" role="region" aria-roledescription="carosello" aria-label="<?php echo esc_attr( $args['label'] ); ?>" data-tp-carousel>
	<ul class="tp-carousel__track" tabindex="0">
		<?php foreach ( $tp_car_slides as $tp_car_i => $tp_car_html ) : ?>
			<li class="tp-carousel__slide" style="--i: <?php echo (int) $tp_car_i; ?>" role="group" aria-roledescription="scheda" aria-label="<?php echo esc_attr( $tp_car_count( $tp_car_i + 1 ) ); ?>"><?php echo $tp_car_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML gia' escluso dal chiamante ?></li>
		<?php endforeach; ?>
	</ul>
	<?php if ( $tp_car_n > 1 ) : ?>
		<div class="tp-carousel__nav" data-tp-carousel-nav hidden>
			<div class="tp-carousel__dots">
				<?php for ( $tp_car_k = 0; $tp_car_k < $tp_car_n; $tp_car_k++ ) : ?>
					<button type="button" class="tp-carousel__dot<?php echo 0 === $tp_car_k ? ' is-active' : ''; ?>" data-tp-carousel-dot="<?php echo (int) $tp_car_k; ?>" aria-label="<?php echo esc_attr( sprintf( $args['dot_label'], $tp_car_k + 1 ) ); ?>"<?php echo 0 === $tp_car_k ? ' aria-current="true"' : ''; ?>><span aria-hidden="true"></span></button>
				<?php endfor; ?>
			</div>
			<p class="tp-carousel__count" aria-live="polite" data-tp-carousel-count data-format="<?php echo esc_attr( $args['count_format'] ); ?>"><?php echo esc_html( $tp_car_count( 1 ) ); ?></p>
			<div class="tp-carousel__arrows">
				<button type="button" class="tp-carousel__arrow tp-carousel__arrow--prev" data-tp-carousel-prev aria-label="<?php echo esc_attr( $args['prev_label'] ); ?>" disabled><span aria-hidden="true">&larr;</span></button>
				<button type="button" class="tp-carousel__arrow tp-carousel__arrow--next" data-tp-carousel-next aria-label="<?php echo esc_attr( $args['next_label'] ); ?>"><span aria-hidden="true">&rarr;</span></button>
			</div>
		</div>
	<?php endif; ?>
</div>
