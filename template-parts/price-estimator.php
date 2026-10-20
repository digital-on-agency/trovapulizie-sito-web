<?php
/**
 * Sezione "Quanto costa" — Figma desktop 4447:965, mobile 4452:965 (TICKET-34).
 * Due colonne: a sinistra i controlli (metri quadri, tipo di casa, frequenza), a destra la stima.
 *
 * Args: eyebrow, title, subtitle, note, tariffa_min, tariffa_max, cta_label, cta_link.
 *
 * [ATTENZIONE] La tariffa oraria e' un campo, non una costante nel codice: deve restare
 * allineata a quella dichiarata nelle FAQ della stessa pagina, altrimenti la pagina dice due
 * numeri diversi. La formula sta in assets/js/price-estimator.js, con la sua derivazione.
 * Senza JavaScript i controlli non compaiono e resta il testo di ripiego: una stima e' un
 * calcolo, non ha senso mostrarne i comandi se non calcolano.
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'     => '',
	'title'       => '',
	'subtitle'    => '',
	'note'        => '',
	'tariffa_min' => 15,
	'tariffa_max' => 20,
	'cta_label'   => '',
	'cta_link'    => '',
) );
if ( ! $args['title'] ) {
	return;
}
$tipi = array(
	'monolocale'  => 'Monolocale',
	'bilocale'    => 'Bilocale',
	'trilocale'   => 'Trilocale',
	'quattro-piu' => '4+ locali',
);
$frequenze = array(
	'una-tantum'   => 'Una tantum',
	'settimanale'  => 'Settimanale',
	'quindicinale' => 'Ogni 2 settimane',
);
?>
<section class="price" id="quanto-costa" data-reveal data-price-estimator
	data-tariffa-min="<?php echo esc_attr( $args['tariffa_min'] ); ?>"
	data-tariffa-max="<?php echo esc_attr( $args['tariffa_max'] ); ?>">

	<div class="price__head">
		<?php if ( $args['eyebrow'] ) : ?>
			<p class="price__eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p>
		<?php endif; ?>
		<h2 class="price__title"><?php echo esc_html( $args['title'] ); ?></h2>
		<?php if ( $args['subtitle'] ) : ?>
			<p class="price__subtitle"><?php echo esc_html( $args['subtitle'] ); ?></p>
		<?php endif; ?>
	</div>

	<div class="price__card">
		<div class="price__controls">
			<div class="price__field">
				<div class="price__label-row">
					<label for="price-mq">Metri quadri</label>
					<span class="price__value" data-mq-out>80 m&sup2;</span>
				</div>
				<input class="price__slider" type="range" id="price-mq" name="mq" min="30" max="200" step="5" value="80" data-mq>
				<div class="price__scale"><span>30 m&sup2;</span><span>200 m&sup2;</span></div>
			</div>

			<div class="price__field">
				<p class="price__label">Tipo di casa</p>
				<div class="price__chips" data-gruppo="tipo">
					<?php foreach ( $tipi as $valore => $etichetta ) : ?>
						<button type="button" class="price__chip<?php echo 'bilocale' === $valore ? ' is-active' : ''; ?>" data-valore="<?php echo esc_attr( $valore ); ?>" aria-pressed="<?php echo 'bilocale' === $valore ? 'true' : 'false'; ?>"><?php echo esc_html( $etichetta ); ?></button>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="price__field">
				<p class="price__label">Frequenza</p>
				<div class="price__chips" data-gruppo="frequenza">
					<?php foreach ( $frequenze as $valore => $etichetta ) : ?>
						<button type="button" class="price__chip<?php echo 'una-tantum' === $valore ? ' is-active' : ''; ?>" data-valore="<?php echo esc_attr( $valore ); ?>" aria-pressed="<?php echo 'una-tantum' === $valore ? 'true' : 'false'; ?>"><?php echo esc_html( $etichetta ); ?></button>
					<?php endforeach; ?>
				</div>
			</div>
		</div>

		<div class="price__result">
			<p class="price__result-label">STIMA INDICATIVA</p>
			<p class="price__amount" data-stima aria-live="polite">&euro; 48 &ndash; 64</p>
			<p class="price__summary" data-riepilogo>&asymp; 80 m&sup2; &middot; bilocale &middot; una tantum</p>
			<?php if ( $args['cta_label'] && $args['cta_link'] ) : ?>
				<a class="price__cta" href="<?php echo esc_url( $args['cta_link'] ); ?>">
					<span><?php echo esc_html( $args['cta_label'] ); ?></span>
					<span aria-hidden="true">&rarr;</span>
				</a>
			<?php endif; ?>
			<?php if ( $args['note'] ) : ?>
				<p class="price__note"><?php echo esc_html( $args['note'] ); ?></p>
			<?php endif; ?>
		</div>
	</div>

	<noscript>
		<p class="price__fallback">La stima si calcola nel browser. Le pulizie domestiche a Roma vanno da <?php echo esc_html( $args['tariffa_min'] ); ?> a <?php echo esc_html( $args['tariffa_max'] ); ?> euro l'ora: il costo esatto dipende dal cleaner che scegli.</p>
	</noscript>
</section>
