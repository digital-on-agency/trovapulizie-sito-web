<?php
/**
 * Barra di ricerca (Figma hero 3564:17). Un solo componente per tutta la Home: la hero e la sezione
 * "Dove siamo attivi" mostrano la stessa barra, non due copie che poi divergono.
 *
 * Args:
 * - mode: 'static' (hero: il campo indirizzo e' un segnaposto, il widget vero arriva con ADR-001)
 *         oppure 'input' (campo reale, con datalist facoltativa)
 * - service: true/false, mostra il campo del servizio con il chevron (nella mappa e' rumore)
 * - service_label, placeholder, submit_label
 * - input_id, input_name, suggestions (array di stringhe per la datalist)
 * - action: URL di destinazione. Se valorizzato la barra diventa un <form method="get"> e il
 *           bottone un submit: la ricerca funziona anche a JavaScript spento (TICKET-24).
 *           Vuoto = contenitore inerte, il comportamento lo da' il JS della sezione
 *           (es. zones-map.js nella sezione mappa).
 * - class: classi extra sul contenitore
 */
$args = wp_parse_args( $args ?? array(), array(
	'mode'          => 'static',
	'service'       => true,
	'service_label' => 'Pulizia casa',
	'placeholder'   => 'Inserisci il tuo indirizzo',
	'submit_label'  => 'Cerca',
	'input_id'      => '',
	'input_name'    => 'indirizzo',
	'action'        => '',
	'suggestions'   => array(),
	'class'         => '',
) );
$icona       = get_stylesheet_directory_uri() . '/assets/icons/';
$datalist_id = $args['suggestions'] && $args['input_id'] ? $args['input_id'] . '-list' : '';
$e_form      = (bool) $args['action'];
?>
<?php if ( $e_form ) : ?>
<form class="tp-search <?php echo esc_attr( $args['class'] ); ?>" method="get" action="<?php echo esc_url( $args['action'] ); ?>" role="search">
<?php else : ?>
<div class="tp-search <?php echo esc_attr( $args['class'] ); ?>">
<?php endif; ?>
	<?php if ( $args['service'] ) : ?>
		<div class="tp-search__field tp-search__field--service">
			<img src="<?php echo esc_url( $icona . 'ic-servizio.svg' ); ?>" alt="" class="tp-search__icon" width="20" height="20">
			<span><?php echo esc_html( $args['service_label'] ); ?></span>
			<img src="<?php echo esc_url( $icona . 'chevron.svg' ); ?>" alt="" class="tp-search__chevron" width="18" height="18">
		</div>
		<div class="tp-search__divider"></div>
	<?php endif; ?>

	<div class="tp-search__field tp-search__field--address">
		<img src="<?php echo esc_url( $icona . 'ic-pin.svg' ); ?>" alt="" class="tp-search__icon" width="20" height="20">
		<?php if ( 'input' === $args['mode'] ) : ?>
			<label class="screen-reader-text" for="<?php echo esc_attr( $args['input_id'] ); ?>"><?php echo esc_html( $args['placeholder'] ); ?></label>
			<input type="text" class="tp-search__input" id="<?php echo esc_attr( $args['input_id'] ); ?>"
				name="<?php echo esc_attr( $args['input_name'] ); ?>"
				placeholder="<?php echo esc_attr( $args['placeholder'] ); ?>"
				autocomplete="off"
				<?php if ( $datalist_id ) : ?>list="<?php echo esc_attr( $datalist_id ); ?>"<?php endif; ?>>
			<?php if ( $datalist_id ) : ?>
				<?php // La datalist e' anche il percorso da tastiera: sostituisce il select senza perdere l'accessibilita'. ?>
				<datalist id="<?php echo esc_attr( $datalist_id ); ?>">
					<?php foreach ( $args['suggestions'] as $voce ) : ?>
						<option value="<?php echo esc_attr( $voce ); ?>"></option>
					<?php endforeach; ?>
				</datalist>
			<?php endif; ?>
		<?php else : ?>
			<span class="tp-search__placeholder"><?php echo esc_html( $args['placeholder'] ); ?></span>
		<?php endif; ?>
	</div>

	<button type="<?php echo $e_form ? 'submit' : 'button'; ?>" class="tp-btn tp-btn--primary tp-btn--lg tp-search__submit">
		<img src="<?php echo esc_url( $icona . 'ic-search.svg' ); ?>" alt="" class="tp-search__icon tp-search__icon--white" width="20" height="20">
		<?php echo esc_html( $args['submit_label'] ); ?>
	</button>
<?php if ( $e_form ) : ?>
</form>
<?php else : ?>
</div>
<?php endif; ?>
