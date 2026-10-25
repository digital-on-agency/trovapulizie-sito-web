<?php
/**
 * "Quanto guadagni" — pagina Diventa cleaner (Figma 6752:895, sezione scura; TICKET-54).
 * A sinistra titolo e calcolatore (slider della tariffa oraria + chip delle ore a settimana), a destra la card bianca
 * con la stima mensile lorda, le righe dei costi e la CTA.
 *
 * Args: eyebrow, title, subtitle, rate_min, rate_max, rate_default, hours (array di interi), hours_default,
 * weeks (settimane al mese, 4,33), rows (array di [title, text] da tp_pipe_lines: etichetta e valore),
 * note (testo dopo la formula), cta_label, cta_link, id.
 *
 * Formula: stima = arrotonda ai 50 euro (tariffa x ore x settimane). La stessa funzione vive in
 * assets/js/earnings-calculator.js; qui serve solo per il valore iniziale, che e' anche quello mostrato senza
 * JavaScript. Senza JavaScript i comandi restano [hidden]: una stima che non calcola non deve mostrare comandi.
 * [ATTENZIONE] E' una stima lorda di esempio, non un dato di piattaforma: niente percentuale di commissione in pagina
 * (SPEC-pagina-diventa-cleaner, assunzione 3).
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'       => '',
	'title'         => '',
	'subtitle'      => '',
	'rate_min'      => 15,
	'rate_max'      => 35,
	'rate_default'  => 22,
	'hours'         => array( 10, 20, 30, 40 ),
	'hours_default' => 20,
	'weeks'         => 4.33,
	'rows'          => array(),
	'note'          => 'stima lorda, prima della commissione di piattaforma',
	'cta_label'     => '',
	'cta_link'      => '',
	'id'            => 'guadagni',
) );
if ( ! $args['title'] ) {
	return;
}
$rate  = (int) $args['rate_default'];
$hours = (int) $args['hours_default'];
$total = (int) ( round( $rate * $hours * (float) $args['weeks'] / 50 ) * 50 );
$fmt   = function ( $n ) {
	return number_format( (float) $n, 0, ',', '.' );
};
?>
<section class="dc-earn" id="<?php echo esc_attr( $args['id'] ); ?>" data-earnings data-weeks="<?php echo esc_attr( $args['weeks'] ); ?>" data-note="<?php echo esc_attr( $args['note'] ); ?>">
	<div class="dc-earn__inner">
		<div class="dc-earn__left">
			<div class="dc-earn__head">
				<?php if ( $args['eyebrow'] ) : ?><p class="dc-earn__eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p><?php endif; ?>
				<h2 class="dc-earn__title"><?php echo esc_html( $args['title'] ); ?></h2>
				<?php if ( $args['subtitle'] ) : ?><p class="dc-earn__sub"><?php echo esc_html( $args['subtitle'] ); ?></p><?php endif; ?>
			</div>
			<div class="dc-calc" data-earn-controls hidden>
				<div class="dc-calc__field">
					<div class="dc-calc__row">
						<label class="dc-calc__label" for="dc-rate">Tariffa oraria</label>
						<output class="dc-calc__pill" for="dc-rate" data-earn-rate-out><?php echo (int) $rate; ?> €/h</output>
					</div>
					<input class="dc-calc__range" type="range" id="dc-rate" min="<?php echo (int) $args['rate_min']; ?>" max="<?php echo (int) $args['rate_max']; ?>" step="1" value="<?php echo (int) $rate; ?>" data-earn-rate>
					<div class="dc-calc__scale" aria-hidden="true"><span><?php echo (int) $args['rate_min']; ?> €/h</span><span><?php echo (int) $args['rate_max']; ?> €/h</span></div>
				</div>
				<div class="dc-calc__field">
					<p class="dc-calc__label" id="dc-hours-label">Ore a settimana</p>
					<div class="dc-calc__chips" role="radiogroup" aria-labelledby="dc-hours-label">
						<?php foreach ( (array) $args['hours'] as $h ) : $on = (int) $h === $hours; ?>
							<button type="button" class="dc-calc__chip<?php echo $on ? ' is-on' : ''; ?>" role="radio" aria-checked="<?php echo $on ? 'true' : 'false'; ?>" data-earn-hours="<?php echo (int) $h; ?>"><?php echo (int) $h; ?> h</button>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
		<div class="dc-estimate">
			<p class="dc-estimate__eyebrow">STIMA MENSILE</p>
			<p class="dc-estimate__value"><span data-earn-total aria-live="polite">≈ <?php echo esc_html( $fmt( $total ) ); ?> €</span> <span class="dc-estimate__per">al mese</span></p>
			<p class="dc-estimate__formula" data-earn-formula>= <?php echo (int) $rate; ?> €/h × <?php echo (int) $hours; ?> h a settimana · <?php echo esc_html( $args['note'] ); ?></p>
			<?php if ( $args['rows'] ) : ?>
				<dl class="dc-estimate__rows">
					<?php foreach ( $args['rows'] as $r ) : ?>
						<div class="dc-estimate__row"><dt><?php echo esc_html( $r['title'] ); ?></dt><dd><?php echo esc_html( $r['text'] ); ?></dd></div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>
			<?php if ( $args['cta_label'] && $args['cta_link'] ) : ?>
				<a class="tp-btn tp-btn--primary dc-estimate__cta" href="<?php echo esc_url( $args['cta_link'] ); ?>" data-cta-position="guadagni"><?php echo esc_html( $args['cta_label'] ); ?> <span aria-hidden="true">&rarr;</span></a>
			<?php endif; ?>
		</div>
	</div>
</section>
