<?php
/**
 * Sezione "Dove siamo attivi" — Figma 3564:114 (mobile 4023:1007). Args: title, description, map (URL raster; vuoto =
 * mappa di Roma del tema con gli overlay), overlays (bool, null = solo con la mappa del tema), coming_label, cities.
 * La mappa e' un raster (spec motion §7, niente draw-in): sopra ci stanno i due overlay reali del Figma (Centro,
 * Prati) in coordinate percentuali, con fade + pulse all'ingresso e un alone radar discreto in loop su Roma.
 * Citta' "Prossimamente" come chip senza link. Reveal via reveal.js ([data-reveal]).
 */
$args = wp_parse_args( $args ?? array(), array(
	'title'        => '',
	'description'  => '',
	'map'          => '',
	'coming_label' => '',
	'cities'       => array(),
	'overlays'     => null, // gli overlay sono disegnati per la mappa di Roma del tema: null = solo con quella
) );
$map      = $args['map'] ?: tp_theme_asset( 'assets/img/zones-map-roma.jpg' );
$overlays = null === $args['overlays'] ? empty( $args['map'] ) : (bool) $args['overlays'];
$cities    = array_values( array_filter( array_map( 'trim', (array) $args['cities'] ) ) );
?>
<section class="zones" id="dove-siamo" data-reveal>
	<div class="zones__inner">
		<?php if ( $args['title'] ) : ?><h2 class="zones__title"><?php echo esc_html( $args['title'] ); ?></h2><?php endif; ?>
		<?php
		// Lo "stage" ha sempre il rapporto della mappa (1280x714) e porta img + overlay, che sono in
		// percentuale su di lui. Serve perche' su mobile il riquadro visibile e' 350x220 (rapporto diverso):
		// se gli overlay fossero figli del riquadro, al cambio di rapporto si staccherebbero dalla mappa.
		?>
		<div class="zones__map">
			<div class="zones__map-stage">
				<img class="zones__map-img" src="<?php echo esc_url( $map ); ?>" alt="<?php esc_attr_e( 'Mappa delle zone coperte' ); ?>" loading="lazy" width="1280" height="714">
				<?php if ( $overlays ) : ?>
				<div class="zones__overlay zones__overlay--centro" aria-hidden="true"><img src="<?php echo esc_url( tp_theme_asset( 'assets/img/zones-overlay-centro.png' ) ); ?>" alt="" loading="lazy"></div>
				<div class="zones__overlay zones__overlay--prati" aria-hidden="true"><img src="<?php echo esc_url( tp_theme_asset( 'assets/img/zones-overlay-prati.jpg' ) ); ?>" alt="" loading="lazy"></div>
				<span class="zones__radar" aria-hidden="true"></span>
				<?php endif; ?>
			</div>
		</div>
		<?php if ( $args['description'] ) : ?><p class="zones__desc"><?php echo esc_html( $args['description'] ); ?></p><?php endif; ?>
		<?php if ( $cities ) : ?>
		<div class="zones__coming">
			<?php if ( $args['coming_label'] ) : ?><p class="zones__coming-label"><?php echo esc_html( $args['coming_label'] ); ?></p><?php endif; ?>
			<ul class="zones__cities">
				<?php foreach ( $cities as $i => $city ) : ?><li class="zones__city" style="--i:<?php echo (int) $i; ?>"><?php echo esc_html( $city ); ?></li><?php endforeach; ?>
			</ul>
		</div>
		<?php endif; ?>
	</div>
</section>
