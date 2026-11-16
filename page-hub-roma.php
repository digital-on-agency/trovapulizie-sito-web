<?php
/**
 * Template Name: Hub Roma
 *
 * Pagina hub di citta' /roma/ (Figma desktop 4422:921, mobile 3927:1280). PRD e ticket:
 * vault `clients/trovapulizie/projects/sito-web/spec/SPEC-pagina-hub-roma.md`.
 *
 * Legge i campi della scheda "Hub Roma — Sezioni" (inc/carbon-fields.php, prefisso roma_) e
 * renderizza nell'ordine: hero con ricerca, introduzione (cosa fa Trovapulizie), elenco "Servizi a Roma" con un
 * link per ogni pagina pubblicata, zone per CAP, servizi a tab, verificati, come funziona, recensioni (se
 * compilate), FAQ, CTA finale, barra fissa mobile.
 *
 * TICKET-98: l'hub risponde a "impresa di pulizie a Roma". H1, sottotitolo, introduzione e titoli di sezione hanno il
 * default in inc/hub-roma.php; un campo roma_* compilato sulla pagina vince. L'introduzione, prima in fondo come testo
 * SEO, sta ora sotto la hero (stesso campo roma_intro_text); l'elenco dei servizi sostituisce il blocco "Altri servizi
 * a Roma", che sull'hub linkava le stesse pagine senza controllare che fossero pubblicate.
 *
 * Le sezioni sono tutte template-part riusabili pilotati da $args: nessun accesso diretto ai
 * campi dentro le parti, nessuna copia divergente dei componenti della Home.
 *
 * [ATTENZIONE] Il nome del file non e' casuale. `page-roma.php` sarebbe entrato nella gerarchia
 * WordPress per lo slug `roma` applicandosi da solo, ma lasciando `_wp_page_template` a
 * `default`: la condizione `->where( 'post_template', ... )` dei campi Carbon non avrebbe
 * matchato e `is_page_template()` sarebbe stato falso, quindi nessun campo nell'editor e nessuno
 * script in pagina, in silenzio. Con questo nome il template va assegnato a mano sulla pagina.
 *
 * Non implementate, bloccate sui contenuti (vedi SPEC §6): recensioni vere (TICKET-35) e
 * barra Risk reversal (TICKET-36, claim da confermare).
 */
get_header();

$hub           = tp_hub_roma_testi();
$hero_eyebrow  = $hub['eyebrow'];
$hero_title    = $hub['title'];
$hero_subtitle = $hub['subtitle'];

$services      = carbon_get_the_post_meta( 'roma_services' );
$faq_items     = carbon_get_the_post_meta( 'roma_faq_items' );
$reviews       = carbon_get_the_post_meta( 'reviews' );
$servizi_roma  = tp_hub_roma_servizi();
?>
<main class="hub-roma" id="contenuto">

	<section class="hub-hero" id="cerca">
		<div class="hub-hero__content">
			<?php if ( $hero_eyebrow ) : ?>
				<p class="hub-hero__eyebrow"><?php echo esc_html( $hero_eyebrow ); ?></p>
			<?php endif; ?>

			<?php if ( $hero_title ) : ?>
				<h1><?php echo nl2br( esc_html( $hero_title ) ); ?></h1>
			<?php endif; ?>

			<?php if ( $hero_subtitle ) : ?>
				<p class="hub-hero__subtitle"><?php echo nl2br( esc_html( $hero_subtitle ) ); ?></p>
			<?php endif; ?>

			<?php
			// Stessa barra della Home, qui in modalita' form (TICKET-24): GET verso l'app, quindi
			// funzionante anche a JavaScript spento. Il contratto e' /home?address=<indirizzo>,
			// lo stesso che usa handleSearch nella SPA.
			get_template_part( 'template-parts/search-bar', null, array(
				'mode'         => 'input',
				'service'      => false,
				'action'       => tp_app_url( 'home' ),
				'input_id'     => 'hub-roma-indirizzo',
				'input_name'   => 'address',
				'placeholder'  => 'Inserisci il tuo indirizzo',
				'submit_label' => 'Cerca',
			) );
			?>
		</div>
	</section>

	<section class="hub-intro" id="cosa-facciamo" aria-labelledby="hub-intro-title">
		<div class="hub-intro__inner">
			<?php if ( $hub['intro_eyebrow'] ) : ?>
				<p class="hub-intro__eyebrow"><?php echo esc_html( $hub['intro_eyebrow'] ); ?></p>
			<?php endif; ?>
			<h2 class="hub-intro__title" id="hub-intro-title"><?php echo esc_html( $hub['intro_title'] ); ?></h2>
			<?php foreach ( preg_split( '/\R{2,}/', $hub['intro_text'] ) as $paragrafo ) : ?>
				<?php if ( '' !== trim( $paragrafo ) ) : ?>
					<p class="hub-intro__text"><?php echo nl2br( esc_html( trim( $paragrafo ) ) ); ?></p>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</section>

	<?php if ( $servizi_roma ) : ?>
		<section class="hub-services" id="servizi-roma" aria-labelledby="hub-services-title">
			<div class="hub-services__inner">
				<h2 class="hub-services__title" id="hub-services-title"><?php echo esc_html( $hub['services_title'] ); ?></h2>
				<?php if ( $hub['services_text'] ) : ?>
					<p class="hub-services__text"><?php echo esc_html( $hub['services_text'] ); ?></p>
				<?php endif; ?>
				<?php foreach ( $servizi_roma as $gruppo ) : ?>
					<div class="hub-services__group">
						<h3 class="hub-services__group-title"><?php echo esc_html( $gruppo['title'] ); ?></h3>
						<ul class="hub-services__list">
							<?php foreach ( $gruppo['voci'] as $voce ) : ?>
								<li>
									<a class="hub-services__link" href="<?php echo esc_url( home_url( $voce['path'] ) ); ?>" data-hub-service="<?php echo esc_attr( $voce['path'] ); ?>">
										<span class="hub-services__label"><?php echo esc_html( $voce['label'] ); ?></span>
										<?php if ( $voce['text'] ) : ?>
											<span class="hub-services__desc"><?php echo esc_html( $voce['text'] ); ?></span>
										<?php endif; ?>
										<span class="hub-services__arrow" aria-hidden="true">&rarr;</span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php
	// Zone: mappa per CAP (ADR-003 rev. 2), non la raster a 4 macro-zone del Figma, che e'
	// anteriore. Stessa sezione della Home, con i testi dell'hub.
	get_template_part( 'template-parts/zones-interactive', null, array(
		'title'        => carbon_get_the_post_meta( 'roma_zones_title' ),
		'description'  => carbon_get_the_post_meta( 'roma_zones_description' ),
		'cta_label'    => carbon_get_the_post_meta( 'roma_zones_cta_label' ) ?: 'Cerca in questa zona',
		'coverage_url' => tp_theme_asset( 'assets/data/coverage.fixture.json' ),
		'services'     => array(
			'pulizia_domestica'   => 'Pulizia casa',
			'pulizia_uffici'      => 'Uffici',
			'pulizia_scale'       => 'Scale e condomini',
			'pulizie_industriali' => 'Industriali',
			'sanificazioni'       => 'Sanificazioni',
			'pulizia_esterni'     => 'Esterni',
		),
	) );

	if ( $services ) {
		get_template_part( 'template-parts/services-popular', null, array(
			'title'    => carbon_get_the_post_meta( 'roma_services_title' ),
			'subtitle' => carbon_get_the_post_meta( 'roma_services_subtitle' ),
			'services' => $services,
		) );
	}

	get_template_part( 'template-parts/verified-cleaners', null, array(
		'eyebrow'   => carbon_get_the_post_meta( 'roma_verified_eyebrow' ),
		'title'     => carbon_get_the_post_meta( 'roma_verified_title' ),
		'text'      => carbon_get_the_post_meta( 'roma_verified_text' ),
		'steps'     => carbon_get_the_post_meta( 'roma_verified_steps' ),
		'cta_label' => carbon_get_the_post_meta( 'roma_verified_cta_label' ),
		'cta_link'  => carbon_get_the_post_meta( 'roma_verified_cta_link' ) ?: tp_app_url( 'home' ),
		'profile'   => array(
			'name'      => 'Maria G.',
			'zone'      => 'Cleaner - Roma Nord',
			'badge'     => 'Verificato',
			'checks'    => array(
				'Documenti d\'identità confermati',
				'Colloquio e prova superati',
				'Recensioni verificate dai clienti',
			),
			'cta_label' => 'Profilo di esempio: i profili reali si vedono nell\'app.',
		),
	) );

	get_template_part( 'template-parts/how-it-works-static', null, array(
		'eyebrow'  => carbon_get_the_post_meta( 'roma_hiw_eyebrow' ),
		'title'    => carbon_get_the_post_meta( 'roma_hiw_title' ),
		'subtitle' => carbon_get_the_post_meta( 'roma_hiw_subtitle' ),
		'steps'    => carbon_get_the_post_meta( 'roma_hiw_steps' ),
		'screen'   => carbon_get_the_post_meta( 'roma_hiw_screen' ),
	) );

	get_template_part( 'template-parts/price-estimator', null, array(
		'eyebrow'     => carbon_get_the_post_meta( 'roma_price_eyebrow' ),
		'title'       => carbon_get_the_post_meta( 'roma_price_title' ),
		'subtitle'    => carbon_get_the_post_meta( 'roma_price_subtitle' ),
		'note'        => carbon_get_the_post_meta( 'roma_price_note' ),
		'tariffa_min' => carbon_get_the_post_meta( 'roma_price_min' ) ?: 15,
		'tariffa_max' => carbon_get_the_post_meta( 'roma_price_max' ) ?: 20,
		'cta_label'   => carbon_get_the_post_meta( 'roma_price_cta_label' ),
		'cta_link'    => carbon_get_the_post_meta( 'roma_price_cta_link' ) ?: tp_app_url( 'home' ),
	) );

	// Recensioni: il componente esce da solo se l'array e' vuoto. Resta vuoto finche' non ci sono
	// recensioni vere (TICKET-35): i sei nomi del Figma sono segnaposto dichiarati nel design.
	if ( $reviews ) {
		get_template_part( 'template-parts/reviews-marquee', null, array(
			'title'   => carbon_get_the_post_meta( 'reviews_title' ),
			'reviews' => $reviews,
		) );
	}

	if ( $faq_items ) {
		get_template_part( 'template-parts/faq-accordion', null, array(
			'eyebrow'  => carbon_get_the_post_meta( 'roma_faq_eyebrow' ),
			'title'    => carbon_get_the_post_meta( 'roma_faq_title' ),
			'subtitle' => carbon_get_the_post_meta( 'roma_faq_subtitle' ),
			'items'    => $faq_items,
			'contact'  => array(
				'people'    => carbon_get_the_post_meta( 'roma_faq_contact_people' ),
				'role'      => carbon_get_the_post_meta( 'roma_faq_contact_role' ),
				'avatar_1'  => carbon_get_the_post_meta( 'roma_faq_contact_avatar_1' ),
				'avatar_2'  => carbon_get_the_post_meta( 'roma_faq_contact_avatar_2' ),
				'title'     => carbon_get_the_post_meta( 'roma_faq_contact_title' ),
				'text'      => carbon_get_the_post_meta( 'roma_faq_contact_text' ),
				'cta_label' => carbon_get_the_post_meta( 'roma_faq_contact_cta_label' ),
				'cta_link'  => carbon_get_the_post_meta( 'roma_faq_contact_cta_link' ),
			),
		) );
	}

	get_template_part( 'template-parts/cta-final', null, array(
		'title'       => carbon_get_the_post_meta( 'roma_cta_title' ),
		'subtitle'    => carbon_get_the_post_meta( 'roma_cta_subtitle' ),
		'button_text' => carbon_get_the_post_meta( 'roma_cta_button_text' ),
		'button_link' => carbon_get_the_post_meta( 'roma_cta_button_link' ) ?: tp_app_url( 'home' ),
		'image'       => carbon_get_the_post_meta( 'roma_cta_image' ),
	) );
	?>

	<?php get_template_part( 'template-parts/sticky-cta', null, array(
		'label'  => 'Trova pulizie nella tua zona',
		'target' => '#cerca',
	) ); ?>

</main>
<?php
get_footer();
