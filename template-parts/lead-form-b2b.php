<?php
/**
 * Form lead B2B a un passo (SPEC-landing-sgrosso-commerciale, TICKET-58). Qui il markup; il comportamento vive in
 * assets/js/lead-form.js (initB2B, [data-lead-b2b]): una sola POST /api/leads con tipo b2b e conferma al posto dei
 * campi, nessuna thank-you. Senza JavaScript il bottone non fa nulla: il contratto e' JSON con honeypot e non e'
 * esprimibile come submit HTML (stessa scelta di lead-form.php).
 *
 * Contratto: SPEC-landing-sgrosso-commerciale §2. Il tipo di attivita' (chip, o testo libero con "Altro") finisce
 * nel campo note, come il testo libero del live; superficieMq e' facoltativa; telefono fisso o cellulare.
 *
 * Args: id (prefisso degli id interni, obbligatorio e unico in pagina), cta_position (hero|bottom), landing, title,
 * subtitle, cta_label, note, activities[] (chip; la voce uguale a other_label apre il campo di testo), other_label,
 * other_field_label, phone_label, phone_href (nella conferma), privacy_url.
 *
 * Landing zanzare (SPEC-landing-zanzare, TICKET-73), tutti facoltativi, i default lasciano la B2B com'era:
 * chooser_label (etichetta dei chip), chooser_types {voce => 'b2c'|'b2b'} (tipo del lead per voce, default b2b),
 * chooser_error, note_prefix (prima della voce nel campo note), show_mq, show_email + email_label, coupon (riga sopra
 * i campi), show_marketing, phone_field_label, call_label + whatsapp_href (bottoni sotto l'invio), urgency (riga
 * finale), done_title, done_text.
 */
$args = wp_parse_args( $args ?? array(), array(
	'id'                => 'lead-b2b',
	'cta_position'      => 'hero',
	'landing'           => 'sgrosso-commerciale',
	'title'             => 'Richiedi il preventivo gratuito',
	'subtitle'          => 'Sopralluogo gratuito in sede. Ti richiamiamo entro 1 ora.',
	'cta_label'         => 'Richiedi il preventivo gratuito',
	'note'              => '★ Gratis · con fattura · risposta in 1h',
	'activities'        => array( 'Ufficio', 'Negozio', 'Supermercato', 'Palestra', 'Altro' ),
	'other_label'       => 'Altro',
	'other_field_label' => 'Che attività è? *',
	'phone_label'       => '06 9151 6075',
	'phone_href'        => 'tel:0691516075',
	'privacy_url'       => home_url( '/privacy/' ),
	'chooser_label'     => 'Tipo di attività *',
	'chooser_types'     => array(),
	'chooser_error'     => 'Scegli il tipo di attività',
	'note_prefix'       => '',
	'show_mq'           => true,
	'show_email'        => false,
	'email_label'       => 'Email (facoltativa)',
	'coupon'            => '',
	'show_marketing'    => true,
	'phone_field_label' => 'Telefono (fisso o cellulare) *',
	'call_label'        => '',
	'whatsapp_href'     => '',
	'urgency'           => '',
	'done_title'        => 'Richiesta inviata.',
	'done_text'         => 'Ti richiamiamo entro 1 ora al numero indicato per fissare il sopralluogo gratuito.',
) );
$p          = $args['id'];
$prefissi   = array( '+39' => 'IT +39', '+41' => 'CH +41', '+33' => 'FR +33', '+34' => 'ES +34', '+44' => 'UK +44', '+49' => 'DE +49', '+40' => 'RO +40' );
$activities = array_values( array_filter( array_map( 'trim', (array) $args['activities'] ) ) );
?>
<form class="lead-form lead-form--b2b" id="<?php echo esc_attr( $p ); ?>" data-lead-b2b data-form="b2b" data-api="<?php echo esc_attr( tp_api_base() ); ?>" data-cta-position="<?php echo esc_attr( $args['cta_position'] ); ?>" data-landing="<?php echo esc_attr( $args['landing'] ); ?>" data-note-prefix="<?php echo esc_attr( $args['note_prefix'] ); ?>" data-chooser-error="<?php echo esc_attr( $args['chooser_error'] ); ?>" novalidate>
	<input class="lead-form__hp" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">

	<fieldset class="lead-form__step" data-step="1">
		<legend class="lead-form__title"><?php echo esc_html( $args['title'] ); ?></legend>
		<?php if ( $args['subtitle'] ) : ?><p class="lead-form__sub"><?php echo esc_html( $args['subtitle'] ); ?></p><?php endif; ?>
		<?php if ( $args['coupon'] ) : ?><p class="lead-form__coupon"><?php echo esc_html( $args['coupon'] ); ?></p><?php endif; ?>

		<?php if ( $activities ) : ?>
		<div class="lead-form__group" data-field="tipoAttivita">
			<span class="lead-form__label" id="<?php echo esc_attr( $p ); ?>-tipo-label"><?php echo esc_html( $args['chooser_label'] ); ?></span>
			<div class="tp-pills lead-form__chips" role="radiogroup" aria-labelledby="<?php echo esc_attr( $p ); ?>-tipo-label">
				<?php foreach ( $activities as $i => $a ) : $other = 0 === strcasecmp( $a, $args['other_label'] ); ?>
					<label class="tp-pill"><input type="radio" name="tipoAttivita" value="<?php echo esc_attr( $a ); ?>" data-other="<?php echo $other ? '1' : '0'; ?>" data-tipo="<?php echo esc_attr( $args['chooser_types'][ $a ] ?? 'b2b' ); ?>"<?php checked( 0, $i ); ?>><span><?php echo esc_html( $a ); ?></span></label>
				<?php endforeach; ?>
			</div>
			<p class="tp-field__error" data-error-for="tipoAttivita" aria-live="polite"></p>
		</div>
		<div class="tp-field" data-field="tipoAttivitaAltro" data-other-field hidden>
			<div class="tp-field__box">
				<input id="<?php echo esc_attr( $p ); ?>-altro" name="tipoAttivitaAltro" type="text" placeholder=" " autocomplete="organization" maxlength="80">
				<label for="<?php echo esc_attr( $p ); ?>-altro"><?php echo esc_html( $args['other_field_label'] ); ?></label>
			</div>
			<p class="tp-field__error" data-error-for="tipoAttivitaAltro" aria-live="polite"></p>
		</div>
		<?php endif; ?>

		<div class="lead-form__row<?php echo $args['show_mq'] ? '' : ' lead-form__row--single'; ?>">
			<?php if ( $args['show_mq'] ) : ?>
			<div class="tp-field" data-field="superficieMq">
				<div class="tp-field__box">
					<input id="<?php echo esc_attr( $p ); ?>-mq" name="superficieMq" type="number" inputmode="numeric" min="10" max="100000" placeholder=" ">
					<label for="<?php echo esc_attr( $p ); ?>-mq">Superficie (mq)</label>
				</div>
				<p class="tp-field__error" data-error-for="superficieMq" aria-live="polite"></p>
			</div>
			<?php endif; ?>
			<div class="tp-field" data-field="zona">
				<div class="tp-field__box">
					<input id="<?php echo esc_attr( $p ); ?>-zona" name="zona" type="text" placeholder=" " autocomplete="address-level2" required>
					<label for="<?php echo esc_attr( $p ); ?>-zona">Zona o CAP *</label>
				</div>
				<p class="tp-field__error" data-error-for="zona" aria-live="polite"></p>
			</div>
		</div>

		<div class="tp-field tp-field--phone" data-field="telefono">
			<div class="tp-field__box">
				<span class="tp-field__prefix">
					<select name="prefisso" aria-label="Prefisso internazionale">
						<?php foreach ( $prefissi as $val => $label ) : ?>
							<option value="<?php echo esc_attr( $val ); ?>"<?php selected( $val, '+39' ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php echo tp_icon_span( 'assets/icons/chevron.svg', 'tp-field__chevron' ); ?>
				</span>
				<span class="tp-field__sep" aria-hidden="true"></span>
				<span class="tp-field__num">
					<input id="<?php echo esc_attr( $p ); ?>-telefono" name="telefono" type="tel" inputmode="tel" placeholder=" " autocomplete="tel-national" required>
					<label for="<?php echo esc_attr( $p ); ?>-telefono"><?php echo esc_html( $args['phone_field_label'] ); ?></label>
				</span>
			</div>
			<p class="tp-field__error" data-error-for="telefono" aria-live="polite"></p>
		</div>

		<?php if ( $args['show_email'] ) : ?>
		<div class="tp-field" data-field="email">
			<div class="tp-field__box">
				<input id="<?php echo esc_attr( $p ); ?>-email" name="email" type="email" inputmode="email" placeholder=" " autocomplete="email">
				<label for="<?php echo esc_attr( $p ); ?>-email"><?php echo esc_html( $args['email_label'] ); ?></label>
			</div>
			<p class="tp-field__error" data-error-for="email" aria-live="polite"></p>
		</div>
		<?php endif; ?>

		<label class="tp-check" data-field="consentPrivacy">
			<input type="checkbox" name="consentPrivacy" value="1" required>
			<span class="tp-check__box" aria-hidden="true"></span>
			<span>Ho letto e accetto la <a href="<?php echo esc_url( $args['privacy_url'] ); ?>" target="_blank" rel="noopener">Privacy Policy</a> e acconsento al trattamento dei dati.</span>
		</label>
		<p class="tp-field__error" data-error-for="consentPrivacy" aria-live="polite"></p>
		<?php if ( $args['show_marketing'] ) : ?>
		<label class="tp-check">
			<input type="checkbox" name="consentMarketing" value="1">
			<span class="tp-check__box" aria-hidden="true"></span>
			<span>Acconsento a ricevere comunicazioni promozionali (facoltativo).</span>
		</label>
		<?php endif; ?>
	</fieldset>

	<p class="lead-form__api-error" role="alert" hidden></p>

	<div class="lead-form__actions">
		<button type="submit" class="tp-btn tp-btn--primary lead-form__submit" data-submit><?php echo esc_html( $args['cta_label'] ); ?></button>
		<?php if ( $args['call_label'] || $args['whatsapp_href'] ) : ?>
		<div class="lead-form__contact">
			<?php if ( $args['call_label'] && $args['phone_href'] ) : ?><a class="tp-btn lead-form__call" href="<?php echo esc_url( $args['phone_href'] ); ?>" data-cta-position="<?php echo esc_attr( $args['cta_position'] ); ?>"><?php echo tp_icon_span( 'assets/icons/ic-phone.svg', 'lead-form__contact-icon' ); ?><?php echo esc_html( $args['call_label'] ); ?></a><?php endif; ?>
			<?php if ( $args['whatsapp_href'] ) : ?><a class="tp-btn lead-form__wa" href="<?php echo esc_url( $args['whatsapp_href'] ); ?>" target="_blank" rel="noopener" data-cta-position="<?php echo esc_attr( $args['cta_position'] ); ?>">WhatsApp</a><?php endif; ?>
		</div>
		<?php endif; ?>
	</div>
	<?php if ( $args['urgency'] ) : ?><p class="lead-form__urgency" data-note><?php echo esc_html( $args['urgency'] ); ?></p><?php endif; ?>
	<?php if ( $args['note'] ) : ?><p class="lead-form__note" data-note><?php echo esc_html( $args['note'] ); ?></p><?php endif; ?>

	<div class="lead-form__done" data-done role="status" aria-live="polite" tabindex="-1" hidden>
		<span class="lead-form__done-tile"><?php echo tp_icon_span( 'assets/icons/ic-check.svg', 'lead-form__done-check' ); ?></span>
		<p class="lead-form__done-title"><?php echo esc_html( $args['done_title'] ); ?></p>
		<p class="lead-form__done-text"><?php echo esc_html( $args['done_text'] ); ?><?php if ( $args['phone_label'] && $args['phone_href'] ) : ?> Se hai fretta: <a href="<?php echo esc_url( $args['phone_href'] ); ?>"><?php echo esc_html( $args['phone_label'] ); ?></a>.<?php endif; ?></p>
	</div>
</form>
