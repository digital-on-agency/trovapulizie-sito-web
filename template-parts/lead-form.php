<?php
/**
 * Form lead in tre passi (ADR-004, TICKET-40). Markup dei tre passi; il comportamento (passaggio di
 * passo, validazioni, chiamate alle rotte /api/leads, eventi dataLayer) vive in assets/js/lead-form.js.
 * Senza JavaScript resta visibile il solo passo 1 e il bottone non fa nulla: e' voluto, il contratto
 * dell'API richiede tre chiamate con token e non e' esprimibile come submit HTML.
 *
 * Valori delle opzioni = opzioni Airtable esatte (typecast:false lato backend) e chiavi del motore
 * preventivo: SPEC-landing-sgrosso §2 "Vincoli verificati" punto 1. Non cambiarli senza il backend.
 *
 * Args: id (prefisso degli id interni, obbligatorio e unico in pagina), cta_position (hero|bottom),
 * landing, default_tipo (chiave preselezionata del tipo di pulizia), title, subtitle, cta_label, note,
 * privacy_url.
 */
$args = wp_parse_args( $args ?? array(), array(
	'id'           => 'lead',
	'cta_position' => 'hero',
	'landing'      => 'sgrosso-casa',
	'default_tipo' => 'post-ristrutturazione',
	'title'        => 'I tuoi dati di contatto',
	'subtitle'     => "Ti risponderemo entro un'ora con una prima valutazione.",
	'cta_label'    => 'Prenota sopralluogo gratuito',
	'note'         => 'Gratis · nessun impegno · risposta in 1h',
	'privacy_url'  => home_url( '/privacy/' ),
) );
$p = $args['id'];

$prefissi = array( '+39' => 'IT +39', '+41' => 'CH +41', '+33' => 'FR +33', '+34' => 'ES +34', '+44' => 'UK +44', '+49' => 'DE +49', '+40' => 'RO +40' );
$tipi_appartamento = array( 'Monolocale', 'Bilocale', 'Trilocale', '4+' );
$pavimenti = array( 'Marmo', 'Parquet', 'Gres / ceramica', 'Cotto', 'Moquette', 'Altro / misto' );
$arredata  = array( 'Vuota', 'In parte', 'Arredata' );
$sporco    = array(
	'molto_pulita'       => array( 'Già molto pulita', 'Poca polvere, niente grasso visibile, appena pulita di recente.' ),
	'leggermente_sporca' => array( 'Leggermente sporca', 'Polvere accumulata, calcare leggero, uso quotidiano normale.' ),
	'normale'            => array( 'Condizione normale', "Sporco normale accumulato, un po' di grasso in cucina, calcare leggero." ),
	'pesante'            => array( 'Sporco pesante', 'Grasso stratificato, calcare vecchio, aloni.' ),
	'molto_pesante'      => array( 'Molto pesante / abbandono', 'Sporco vecchio, muffa/incrostazioni, casa abbandonata o cantiere appena chiuso.' ),
);
$tipi_pulizia = array(
	'post-ristrutturazione' => array( 'Post-ristrutturazione', 'Polveri e residui di cantiere' ),
	'trasloco'              => array( 'Trasloco', 'Casa che lasci o dove entri' ),
	'fine-affitto'          => array( 'Fine affitto', 'Riconsegna e cauzione' ),
	'prima-pulizia-a-fondo' => array( 'Prima pulizia a fondo', 'Sporco accumulato nel tempo' ),
);
$urgenze = array( 'Nessuna fretta' => 'Senza fretta', 'Entro una settimana' => '1 settimana', 'Entro 48h' => '48h', 'Entro 24h' => '24h' );
?>
<form class="lead-form" id="<?php echo esc_attr( $p ); ?>" data-lead-form data-api="<?php echo esc_attr( tp_api_base() ); ?>" data-cta-position="<?php echo esc_attr( $args['cta_position'] ); ?>" data-landing="<?php echo esc_attr( $args['landing'] ); ?>" novalidate>
	<input class="lead-form__hp" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">

	<div class="lead-form__progress">
		<div class="lead-form__progress-row">
			<span>Passo <b data-step-n>1</b> di 3</span>
			<span class="lead-form__step-name" data-step-name>Contatto</span>
		</div>
		<div class="lead-form__bars" aria-hidden="true"><i class="is-done"></i><i></i><i></i></div>
	</div>

	<fieldset class="lead-form__step" data-step="1">
		<legend class="lead-form__title"><?php echo esc_html( $args['title'] ); ?></legend>
		<?php if ( $args['subtitle'] ) : ?><p class="lead-form__sub"><?php echo esc_html( $args['subtitle'] ); ?></p><?php endif; ?>

		<div class="tp-field" data-field="nome">
			<div class="tp-field__box">
				<input id="<?php echo esc_attr( $p ); ?>-nome" name="nome" type="text" placeholder=" " autocomplete="name" required>
				<label for="<?php echo esc_attr( $p ); ?>-nome">Nome e Cognome *</label>
			</div>
			<p class="tp-field__error" data-error-for="nome" aria-live="polite"></p>
		</div>

		<div class="tp-field tp-field--phone" data-field="cellulare">
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
					<input id="<?php echo esc_attr( $p ); ?>-cellulare" name="cellulare" type="tel" inputmode="tel" placeholder=" " autocomplete="tel-national" required>
					<label for="<?php echo esc_attr( $p ); ?>-cellulare">Telefono *</label>
				</span>
			</div>
			<p class="tp-field__error" data-error-for="cellulare" aria-live="polite"></p>
		</div>

		<div class="tp-field" data-field="email">
			<div class="tp-field__box">
				<input id="<?php echo esc_attr( $p ); ?>-email" name="email" type="email" placeholder=" " autocomplete="email" inputmode="email" required>
				<label for="<?php echo esc_attr( $p ); ?>-email">Email *</label>
			</div>
			<p class="tp-field__error" data-error-for="email" aria-live="polite"></p>
		</div>

		<div class="tp-field" data-field="indirizzo">
			<div class="tp-field__box">
				<input id="<?php echo esc_attr( $p ); ?>-indirizzo" name="indirizzo" type="text" placeholder=" " autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="<?php echo esc_attr( $p ); ?>-indirizzo-list" data-here-key="<?php echo esc_attr( tp_here_key() ); ?>" required>
				<label for="<?php echo esc_attr( $p ); ?>-indirizzo">Indirizzo *</label>
				<ul class="tp-field__suggest" id="<?php echo esc_attr( $p ); ?>-indirizzo-list" role="listbox" hidden></ul>
			</div>
			<p class="tp-field__error" data-error-for="indirizzo" aria-live="polite"></p>
		</div>

		<label class="tp-check" data-field="consentPrivacy">
			<input type="checkbox" name="consentPrivacy" value="1" required>
			<span class="tp-check__box" aria-hidden="true"></span>
			<span>Ho letto e accetto la <a href="<?php echo esc_url( $args['privacy_url'] ); ?>" target="_blank" rel="noopener">Privacy Policy</a> e acconsento al trattamento dei dati.</span>
		</label>
		<p class="tp-field__error" data-error-for="consentPrivacy" aria-live="polite"></p>
		<label class="tp-check">
			<input type="checkbox" name="consentMarketing" value="1">
			<span class="tp-check__box" aria-hidden="true"></span>
			<span>Acconsento a ricevere comunicazioni promozionali (facoltativo).</span>
		</label>
	</fieldset>

	<fieldset class="lead-form__step" data-step="2" hidden>
		<legend class="lead-form__title">Dettagli dell'abitazione</legend>

		<div class="lead-form__row">
			<div class="tp-field" data-field="mq">
				<div class="tp-field__box">
					<input id="<?php echo esc_attr( $p ); ?>-mq" name="mq" type="number" inputmode="numeric" min="10" max="500" placeholder=" " required>
					<label for="<?php echo esc_attr( $p ); ?>-mq">Mq *</label>
				</div>
				<p class="tp-field__error" data-error-for="mq" aria-live="polite"></p>
			</div>
			<div class="tp-field" data-field="bagni">
				<div class="tp-field__box">
					<input id="<?php echo esc_attr( $p ); ?>-bagni" name="bagni" type="number" inputmode="numeric" min="1" max="6" placeholder=" " required>
					<label for="<?php echo esc_attr( $p ); ?>-bagni">Bagni *</label>
				</div>
				<p class="tp-field__error" data-error-for="bagni" aria-live="polite"></p>
			</div>
		</div>

		<div class="lead-form__group">
			<span class="lead-form__label">Tipo di appartamento</span>
			<div class="tp-pills tp-pills--row">
				<?php foreach ( $tipi_appartamento as $t ) : ?>
					<label class="tp-pill"><input type="radio" name="tipoAppartamento" value="<?php echo esc_attr( $t ); ?>"><span><?php echo esc_html( $t ); ?></span></label>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="lead-form__group">
			<span class="lead-form__label">Pavimento prevalente</span>
			<div class="tp-pills">
				<?php foreach ( $pavimenti as $t ) : ?>
					<label class="tp-pill"><input type="radio" name="pavimento" value="<?php echo esc_attr( $t ); ?>"><span><?php echo esc_html( $t ); ?></span></label>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="lead-form__group" data-field="arredata">
			<span class="lead-form__label">La casa è arredata? *</span>
			<div class="tp-pills tp-pills--row">
				<?php foreach ( $arredata as $t ) : ?>
					<label class="tp-pill"><input type="radio" name="arredata" value="<?php echo esc_attr( $t ); ?>" required><span><?php echo esc_html( $t ); ?></span></label>
				<?php endforeach; ?>
			</div>
			<p class="tp-field__error" data-error-for="arredata" aria-live="polite"></p>
		</div>

		<div class="lead-form__group lead-form__range">
			<label class="lead-form__label" for="<?php echo esc_attr( $p ); ?>-sporco">Livello di sporco</label>
			<input id="<?php echo esc_attr( $p ); ?>-sporco" name="livelloSporcoIdx" type="range" min="0" max="4" step="1" value="2" aria-valuetext="Condizione normale">
			<input type="hidden" name="livelloSporco" value="normale">
			<p class="lead-form__range-desc"><b data-sporco-title>Condizione normale</b> <span data-sporco-sub>Sporco normale accumulato, un po' di grasso in cucina, calcare leggero.</span></p>
			<script type="application/json" data-sporco-levels><?php echo wp_json_encode( $sporco, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
		</div>
	</fieldset>

	<fieldset class="lead-form__step" data-step="3" hidden>
		<legend class="lead-form__title">Il servizio</legend>

		<div class="lead-form__group">
			<span class="lead-form__label">Tipo di pulizia</span>
			<div class="lead-form__choices">
				<?php foreach ( $tipi_pulizia as $key => $t ) : ?>
					<label class="tp-choice">
						<input type="radio" name="tipoPulizia" value="<?php echo esc_attr( $key ); ?>" data-label="<?php echo esc_attr( $t[0] ); ?>"<?php checked( $key, $args['default_tipo'] ); ?>>
						<span class="tp-choice__title"><?php echo esc_html( $t[0] ); ?></span>
						<span class="tp-choice__sub"><?php echo esc_html( $t[1] ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="lead-form__group">
			<span class="lead-form__label">Entro quando ti serve?</span>
			<div class="tp-pills tp-pills--row">
				<?php foreach ( $urgenze as $val => $label ) : ?>
					<label class="tp-pill"><input type="radio" name="urgenza" value="<?php echo esc_attr( $val ); ?>"><span><?php echo esc_html( $label ); ?></span></label>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="lead-form__group lead-form__photos" data-field="foto">
			<label class="lead-form__label" for="<?php echo esc_attr( $p ); ?>-foto">Foto della casa (facoltative, max 5)</label>
			<input id="<?php echo esc_attr( $p ); ?>-foto" name="foto" type="file" accept="image/*" multiple>
			<ul class="lead-form__photo-list" data-photo-list aria-live="polite"></ul>
			<p class="tp-field__error" data-error-for="foto" aria-live="polite"></p>
		</div>
	</fieldset>

	<p class="lead-form__api-error" role="alert" hidden></p>

	<div class="lead-form__actions">
		<button type="button" class="lead-form__back" data-back hidden>Indietro</button>
		<button type="submit" class="tp-btn tp-btn--primary lead-form__submit" data-submit data-labels='["<?php echo esc_attr( $args['cta_label'] ); ?>","Continua","Invia richiesta"]'><?php echo esc_html( $args['cta_label'] ); ?></button>
	</div>
	<?php if ( $args['note'] ) : ?><p class="lead-form__note" data-note><?php echo esc_html( $args['note'] ); ?></p><?php endif; ?>
</form>
