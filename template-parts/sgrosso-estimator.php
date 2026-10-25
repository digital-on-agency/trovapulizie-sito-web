<?php
/**
 * "Quanto costa": calcolatore della stima e lead via email (Figma 4364:6853, mobile 4364:7316; testi e formula
 * dal live). A sinistra la card degli input (metri quadri, tipo di intervento, extra), a destra la card verde con la
 * fascia di prezzo che si aggiorna in tempo reale e il form email con coupon.
 *
 * Formula del live, coefficienti editabili: base = fisso + mq x euro/mq; min = round10(base + extra x extra_min);
 * max = round10(base x moltiplicatore + extra x extra_max). Calcolo in assets/js/sgrosso-estimator.js, che tiene
 * aggiornato anche il campo nascosto `note` ("Calcolatore: 100mq · Tipo · extra: ... · stima 420-570€").
 *
 * Il form porta data-lead-atomic: l'invio (POST /api/leads atomico, evento marketing_form_submit {form:'calcolatore'})
 * lo gestisce il modulo lead (assets/js/lead-form.js, TICKET-40). Senza quel modulo il bottone non fa nulla.
 *
 * Args: eyebrow, title, subtitle, types[], default_type, extras[], mq_default, coef {base, per_mq, extra_min,
 * extra_max, max_mult}, result_label, cta_title, coupon_amount, coupon_text, cta_label, note, landing, privacy_url.
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'       => '',
	'title'         => '',
	'subtitle'      => '',
	'types'         => array(),
	'default_type'  => '',
	'extras'        => array(),
	'mq_default'    => 100,
	'coef'          => array(),
	'result_label'  => 'STIMA INDICATIVA',
	'cta_title'     => 'Ricevi il preventivo esatto',
	'coupon_amount' => '-30€',
	'coupon_text'   => "sul primo lavoro, con l'email",
	'cta_label'     => 'Ricevi il preventivo',
	'note'          => '',
	'landing'       => 'sgrosso-casa',
	'privacy_url'   => home_url( '/privacy/' ),
) );
$types = array_values( array_filter( array_map( 'trim', (array) $args['types'] ) ) );
if ( ! $types ) {
	return;
}
$default_type = in_array( $args['default_type'], $types, true ) ? $args['default_type'] : $types[0];
$extras       = array_values( array_filter( array_map( 'trim', (array) $args['extras'] ) ) );
$coef         = wp_parse_args( (array) $args['coef'], array( 'base' => 200, 'per_mq' => 2.2, 'extra_min' => 25, 'extra_max' => 40, 'max_mult' => 1.35 ) );
$mq           = max( 30, min( 300, (int) $args['mq_default'] ) );
// Stima iniziale calcolata lato server: la card non parte vuota se il JS tarda.
$base    = (float) $coef['base'] + $mq * (float) $coef['per_mq'];
$min_ini = (int) ( round( $base / 10 ) * 10 );
$max_ini = (int) ( round( $base * (float) $coef['max_mult'] / 10 ) * 10 );
$uid     = 'calc-' . wp_unique_id();
?>
<section class="sg-calc" id="quanto-costa" data-estimator
	data-base="<?php echo esc_attr( $coef['base'] ); ?>" data-per-mq="<?php echo esc_attr( $coef['per_mq'] ); ?>"
	data-extra-min="<?php echo esc_attr( $coef['extra_min'] ); ?>" data-extra-max="<?php echo esc_attr( $coef['extra_max'] ); ?>"
	data-max-mult="<?php echo esc_attr( $coef['max_mult'] ); ?>">
	<div class="sg-calc__inner">
		<div class="sg-section-head" data-reveal>
			<?php if ( $args['eyebrow'] ) : ?><p class="sg-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p><?php endif; ?>
			<?php if ( $args['title'] ) : ?><h2><?php echo esc_html( $args['title'] ); ?></h2><?php endif; ?>
			<?php if ( $args['subtitle'] ) : ?><p class="sg-section-head__sub"><?php echo esc_html( $args['subtitle'] ); ?></p><?php endif; ?>
		</div>

		<div class="sg-calc__grid">
			<div class="sg-calc__inputs" data-reveal>
				<div class="sg-calc__group">
					<div class="sg-calc__row">
						<label class="sg-calc__label" for="<?php echo esc_attr( $uid ); ?>-mq">Metri quadri</label>
						<span class="sg-chip sg-chip--plain" aria-live="polite"><span data-mq-out><?php echo (int) $mq; ?></span> m²</span>
					</div>
					<input class="sg-calc__range" id="<?php echo esc_attr( $uid ); ?>-mq" type="range" name="mq" min="30" max="300" step="5" value="<?php echo (int) $mq; ?>">
					<div class="sg-calc__scale" aria-hidden="true"><span>30 m²</span><span>300 m²</span></div>
				</div>

				<div class="sg-calc__group">
					<span class="sg-calc__label">Tipo di intervento</span>
					<div class="tp-pills" role="radiogroup" aria-label="Tipo di intervento">
						<?php foreach ( $types as $t ) : ?>
							<label class="tp-pill tp-pill--solid"><input type="radio" name="tipo" value="<?php echo esc_attr( $t ); ?>"<?php checked( $t, $default_type ); ?>><span><?php echo esc_html( $t ); ?></span></label>
						<?php endforeach; ?>
					</div>
				</div>

				<?php if ( $extras ) : ?>
				<div class="sg-calc__group">
					<span class="sg-calc__label">Extra (opzionali)</span>
					<div class="sg-calc__extras">
						<?php foreach ( $extras as $x ) : ?>
							<label class="tp-check tp-check--dark"><input type="checkbox" name="extra" value="<?php echo esc_attr( $x ); ?>"><span class="tp-check__box" aria-hidden="true"></span><span><?php echo esc_html( $x ); ?></span></label>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>
			</div>

			<form class="sg-calc__result" data-lead-atomic data-form="calcolatore" data-landing="<?php echo esc_attr( $args['landing'] ); ?>" novalidate data-reveal>
				<p class="sg-calc__result-label"><?php echo esc_html( $args['result_label'] ); ?></p>
				<p class="sg-calc__price" aria-live="polite"><span data-price-min><?php echo $min_ini; ?></span>€ – <span data-price-max><?php echo $max_ini; ?></span>€</p>
				<p class="sg-calc__meta">≈ <span data-meta-mq><?php echo (int) $mq; ?></span> m² · <span data-meta-tipo><?php echo esc_html( $default_type ); ?></span> · <span data-meta-extra>0</span> extra</p>
				<hr class="sg-calc__divider">
				<?php if ( $args['cta_title'] ) : ?><p class="sg-calc__cta-title"><?php echo esc_html( $args['cta_title'] ); ?></p><?php endif; ?>
				<?php if ( $args['coupon_amount'] ) : ?>
					<p class="sg-calc__coupon"><b><?php echo esc_html( $args['coupon_amount'] ); ?></b> <span><?php echo esc_html( $args['coupon_text'] ); ?></span></p>
				<?php endif; ?>

				<input class="lead-form__hp" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">
				<input type="hidden" name="zona" value="Non specificata (calcolatore)">
				<input type="hidden" name="note" data-calc-note value="Calcolatore: <?php echo (int) $mq; ?>mq · <?php echo esc_attr( $default_type ); ?> · extra: nessuno · stima <?php echo $min_ini; ?>-<?php echo $max_ini; ?>€">

				<div class="tp-field tp-field--onwhite" data-field="email">
					<div class="tp-field__box">
						<input id="<?php echo esc_attr( $uid ); ?>-email" name="email" type="email" placeholder=" " autocomplete="email" inputmode="email" required>
						<label for="<?php echo esc_attr( $uid ); ?>-email">Email *</label>
					</div>
					<p class="tp-field__error tp-field__error--onlight" data-error-for="email" aria-live="polite"></p>
				</div>
				<label class="tp-check tp-check--onlight" data-field="consentPrivacy">
					<input type="checkbox" name="consentPrivacy" value="1" required>
					<span class="tp-check__box" aria-hidden="true"></span>
					<span>Ho letto e accetto la <a href="<?php echo esc_url( $args['privacy_url'] ); ?>" target="_blank" rel="noopener">Privacy Policy</a> e acconsento al trattamento dei dati.</span>
				</label>
				<p class="tp-field__error tp-field__error--onlight" data-error-for="consentPrivacy" aria-live="polite"></p>
				<p class="lead-form__api-error" role="alert" hidden></p>
				<button type="submit" class="sg-btn-dark sg-calc__submit" data-submit><?php echo esc_html( $args['cta_label'] ); ?> <span class="sg-cta__arrow" aria-hidden="true">&rarr;</span></button>
				<?php if ( $args['note'] ) : ?><p class="sg-calc__note"><?php echo esc_html( $args['note'] ); ?></p><?php endif; ?>
			</form>
		</div>
	</div>
</section>
