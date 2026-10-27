<?php
/**
 * Template Name: Home
 *
 * Pagina Home (Figma desktop 3564:4, mobile 4016:965). Legge i campi Carbon della tab "Home — Sezioni"
 * (inc/carbon-fields.php), prepara un array di argomenti per sezione e renderizza nell'ordine:
 * hero, come-funziona, overlay #hiw-zoom, servizi, recensioni, zone, faq, cta (footer da footer.php).
 *
 * Hero e Come funziona sono inline perche' condividono il telefono (#hiw-phone-pin, pinnato dal JS
 * home-motion.js su desktop); le altre sezioni sono template-part riutilizzabili con $args.
 * Ogni asset del tema passa da tp_theme_asset()/tp_versioned_url() (cache Hostinger 7 giorni).
 * Dettagli e vincoli: docs/homepage.md.
 */
get_header();

$hero_title       = carbon_get_the_post_meta( 'hero_title' );
$hero_subtitle    = carbon_get_the_post_meta( 'hero_subtitle' );
$hero_trust_badge = carbon_get_the_post_meta( 'hero_trust_badge' );
$hero_video       = carbon_get_the_post_meta( 'hero_video' );
$hiw_title        = carbon_get_the_post_meta( 'how_it_works_title' );
$hiw_subtitle     = carbon_get_the_post_meta( 'how_it_works_subtitle' );
$hiw_video        = carbon_get_the_post_meta( 'how_it_works_video' );
$hiw_poster       = carbon_get_the_post_meta( 'how_it_works_poster' );
$steps            = carbon_get_the_post_meta( 'how_it_works_steps' );
$zoom_video       = carbon_get_the_post_meta( 'how_it_works_zoom_video' );
$zoom_video_url   = tp_versioned_url( $zoom_video );
// Poster dei tre passi: fotogrammi dei clip mobile (tools/step-clips-encode.sh), non piu' i PNG a piena
// risoluzione del Figma, che pesavano 86-97 KB l'uno dentro uno schermo largo 146 px.
$step_fallbacks   = array( 'come-funziona-step1-poster.jpg', 'come-funziona-step2-poster.jpg', 'come-funziona-step3-poster.jpg' );
$services         = carbon_get_the_post_meta( 'popular_services' );
$services_args    = array(
	'title'        => carbon_get_the_post_meta( 'services_title' ),
	'subtitle'     => carbon_get_the_post_meta( 'services_subtitle' ),
	'proof_avatar' => carbon_get_the_post_meta( 'services_proof_avatar' ),
	'proof_title'  => carbon_get_the_post_meta( 'services_proof_title' ),
	'proof_sub'    => carbon_get_the_post_meta( 'services_proof_sub' ),
	'proof_rating' => carbon_get_the_post_meta( 'services_proof_rating' ),
	'services'     => $services,
	'video_first'  => $zoom_video_url, // la prima card ospita il video dello zoom-through dopo l'atterraggio
);
$reviews          = carbon_get_the_post_meta( 'reviews' );
$reviews_args     = array(
	'badge'      => carbon_get_the_post_meta( 'reviews_badge' ),
	'badge_link' => carbon_get_the_post_meta( 'reviews_badge_link' ),
	'badge_logo' => carbon_get_the_post_meta( 'reviews_badge_source' ),
	'title'      => carbon_get_the_post_meta( 'reviews_title' ),
	'reviews'    => $reviews,
);
$zones_args       = array(
	'title'        => carbon_get_the_post_meta( 'zones_title' ),
	'description'  => carbon_get_the_post_meta( 'zones_description' ),
	'map'          => carbon_get_the_post_meta( 'zones_map_image' ),
	'coming_label' => carbon_get_the_post_meta( 'zones_coming_label' ),
	'cities'       => tp_lines( carbon_get_the_post_meta( 'zones_coming_cities' ) ),
);
$faq_items        = carbon_get_the_post_meta( 'faq_items' );
$faq_args         = array(
	'eyebrow'  => carbon_get_the_post_meta( 'faq_eyebrow' ),
	'title'    => carbon_get_the_post_meta( 'faq_title' ),
	'subtitle' => carbon_get_the_post_meta( 'faq_subtitle' ),
	'items'    => $faq_items,
	'contact'  => array(
		'people'    => carbon_get_the_post_meta( 'faq_contact_people' ),
		'role'      => carbon_get_the_post_meta( 'faq_contact_role' ),
		'avatar_1'  => carbon_get_the_post_meta( 'faq_contact_avatar_1' ),
		'avatar_2'  => carbon_get_the_post_meta( 'faq_contact_avatar_2' ),
		'title'     => carbon_get_the_post_meta( 'faq_contact_title' ),
		'text'      => carbon_get_the_post_meta( 'faq_contact_text' ),
		'cta_label' => carbon_get_the_post_meta( 'faq_contact_cta_label' ),
		'cta_link'  => carbon_get_the_post_meta( 'faq_contact_cta_link' ),
	),
);
$cta_args         = array(
	'title'       => carbon_get_the_post_meta( 'cta_title' ),
	'subtitle'    => carbon_get_the_post_meta( 'cta_subtitle' ),
	'button_text' => carbon_get_the_post_meta( 'cta_button_text' ),
	'button_link' => carbon_get_the_post_meta( 'cta_button_link' ),
	'note'        => carbon_get_the_post_meta( 'cta_note' ),
	'image'       => carbon_get_the_post_meta( 'cta_image' ),
);
?>
<main class="home" id="contenuto">

	<?php if ( current_user_can( 'edit_posts' ) && ! $hero_title && ! $steps && ! $services ) : ?>
		<div style="background:#fff3cd;border:1px solid #ffe69c;padding:12px 20px;margin:20px;border-radius:8px;">
			Contenuti non ancora inseriti — vai su <strong>Pagine → Home → Modifica</strong> e compila i campi Carbon Fields (Hero, Come funziona, Servizi Popolari, ecc.). Questo avviso lo vedi solo da loggato.
		</div>
	<?php endif; ?>

	<section class="hero" id="hero">
		<div class="hero__box">
			<?php if ( $hero_video ) : ?>
				<!-- Timelapse disordine -> ordine: parte da JS (hero-video.js) solo senza "riduci movimento", sfuma sopra la foto e resta sull'ultimo frame -->
				<video class="hero__video" muted playsinline preload="none" src="<?php echo esc_url( tp_versioned_url( $hero_video ) ); ?>" aria-hidden="true" tabindex="-1"></video>
			<?php endif; ?>
		</div>
		<div class="hero__content">
			<?php if ( $hero_trust_badge ) : ?>
				<div class="trust-badge">
					<span class="trust-badge__stars" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
					<span class="trust-badge__text"><?php echo esc_html( $hero_trust_badge ); ?></span>
				</div>
			<?php endif; ?>

			<?php if ( $hero_title ) : ?>
				<h1><?php echo nl2br( esc_html( $hero_title ) ); ?></h1>
			<?php endif; ?>

			<?php if ( $hero_subtitle ) : ?>
				<p class="hero__subtitle"><?php echo nl2br( esc_html( $hero_subtitle ) ); ?></p>
			<?php endif; ?>

			<?php
			// Ricerca vera (era un segnaposto): GET verso l'app con address e service, come la Home React
			// (MarketingHome.handleSearch); i servizi solo aziende vanno al preventivo B2B (assets/js/search-form.js).
			// [ATTENZIONE] Destinazione dei privati da Theme Options > Integrazioni (tp_search_target). Default /home:
			// al 25/10/2026 in produzione /results non legge address dall'URL (ripiego solo su staging, 947de5d8) e resta
			// bloccata, mentre /home precompila l'indirizzo (il servizio si riconferma nell'app).
			$servizi = tp_service_types();
			$default = 0;
			foreach ( $servizi as $i => $svc ) {
				if ( empty( $svc['b2b'] ) ) { $default = $i; break; }
			}
			if ( $default ) {
				array_unshift( $servizi, array_splice( $servizi, $default, 1 )[0] );
			}
			get_template_part( 'template-parts/search-bar', null, array(
				'mode'         => 'input',
				'action'       => tp_app_url( 'results' === carbon_get_theme_option( 'tp_search_target' ) ? 'results' : 'home' ),
				'b2b_action'   => tp_app_url( 'richiedi-preventivo' ),
				'services'     => $servizi,
				'input_id'     => 'hero-indirizzo',
				'input_name'   => 'address',
				'placeholder'  => 'Inserisci il tuo indirizzo',
				'required'     => true,
			) ); ?>
		</div>
		<div class="hero__phone" id="hiw-phone-pin" data-theme="<?php echo esc_attr( get_stylesheet_directory_uri() ); ?>" data-ver="<?php echo esc_attr( tp_theme_version() ); ?>" data-model="<?php echo esc_attr( carbon_get_the_post_meta( 'phone_model_url' ) ); ?>">
			<?php get_template_part( 'template-parts/phone-mockup', null, array(
				// Poster/schermo del telefono: va caricato gia' ridimensionato (~360px di larghezza).
				// Lo schermo e' largo 146px su mobile e 294 su desktop: i PNG a piena risoluzione del
				// Figma (1170px, 86-97 KB serviti) sono fuori misura su ogni viewport (TICKET-20).
				'screen' => tp_versioned_url( $hiw_poster ) ?: tp_theme_asset( 'assets/img/' . $step_fallbacks[0] ),
				'video'  => tp_versioned_url( $hiw_video ), // il video e' cachato 7 giorni dall'hosting: la versione del tema lo invalida
				// Stesso URL dell'<img> della sequenza (tp_versioned_url su entrambi): senza il ?ver
				// il poster e l'immagine sono due richieste per lo stesso file (TICKET-20).
				'poster' => tp_versioned_url( $hiw_poster ),
				'id'     => 'hiw-phone',
			) ); ?>
			<div class="hero__phone-3d" aria-hidden="true"></div>
		</div>
	</section>

	<?php if ( $steps ) : ?>
	<section class="how-it-works" id="come-funziona">
		<div class="how-it-works__sticky">
			<?php if ( $hiw_title ) : ?><h2 class="how-it-works__title"><?php echo esc_html( $hiw_title ); ?></h2><?php endif; ?>
			<?php if ( $hiw_subtitle ) : ?><p class="how-it-works__subtitle"><?php echo esc_html( $hiw_subtitle ); ?></p><?php endif; ?>
			<?php // Indicatore 1-2-3 (solo coreografia desktop): dice quanti passi sono e a che punto si e'.
			// Lo stato attivo arriva da data-step sulla sezione, scritto da home-motion.js. ?>
			<ol class="hiw-progress" aria-hidden="true">
				<li class="hiw-progress__item"><span class="hiw-progress__bar"></span><span class="hiw-progress__n">1</span></li>
				<li class="hiw-progress__item"><span class="hiw-progress__bar"></span><span class="hiw-progress__n">2</span></li>
				<li class="hiw-progress__item"><span class="hiw-progress__bar"></span><span class="hiw-progress__n">3</span></li>
			</ol>
			<ol class="how-it-works__steps">
				<?php foreach ( array_slice( $steps, 0, 3 ) as $i => $step ) :
					$n      = $i + 1;
					$screen = ! empty( $step['image'] ) ? tp_versioned_url( wp_get_attachment_url( $step['image'] ) ) : tp_theme_asset( 'assets/img/' . $step_fallbacks[ $i ] );
				?>
				<li class="how-it-works__step how-it-works__step--<?php echo $n; ?><?php echo 1 === $n ? ' is-active' : ''; ?>" data-step="<?php echo $n; ?>">
					<span class="how-it-works__badge"><?php echo $n; ?></span>
					<div class="how-it-works__text">
						<h3><?php echo esc_html( $step['title'] ?? '' ); ?></h3>
						<p><span><?php echo esc_html( $step['description'] ?? '' ); ?></span></p>
					</div>
					<?php
					// Telefono del singolo passo: si vede solo nel fallback impilato (niente JS o
					// reduced-motion). Nella sequenza mobile e su desktop resta nascosto. ADR-002 rev. 5.
					get_template_part( 'template-parts/phone-mockup', null, array(
						'screen' => $screen,
						'class'  => 'how-it-works__phone-static',
					) ); ?>
				</li>
				<?php endforeach; ?>
			</ol>
			<span class="hiw-scrollcue" aria-hidden="true"></span>
			<?php
			// Sequenza mobile (ADR-002 rev. 5): un solo telefono sticky, le tre schermate in crossfade
			// guidate dallo scroll (assets/js/hiw-sequence.js). Senza JS o con reduced-motion resta
			// nascosto e si vedono i tre blocchi impilati qui sopra.
			$seq_screens = array();
			foreach ( array_slice( $steps, 0, 3 ) as $i => $step ) {
				$seq_screens[] = ! empty( $step['image'] ) ? tp_versioned_url( wp_get_attachment_url( $step['image'] ) ) : tp_theme_asset( 'assets/img/' . $step_fallbacks[ $i ] );
			}
			?>
			<div class="hiw-seq" aria-hidden="true">
				<div class="tp-phone hiw-seq__phone">
					<?php for ( $i = 10; $i >= 1; $i-- ) : ?>
						<span class="tp-phone__layer" style="--z:-<?php echo $i; ?>px"></span>
					<?php endfor; ?>
					<div class="tp-phone__face">
						<div class="tp-phone__screen">
							<?php foreach ( $seq_screens as $i => $src ) : ?>
								<img class="tp-phone__img hiw-seq__screen" data-seq="<?php echo $i; ?>" src="<?php echo esc_url( $src ); ?>" alt="">
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
	<?php endif; ?>

	<?php if ( $zoom_video_url ) : ?>
		<!-- Zoom-through (ADR-002 rev. 3): overlay fisso che cresce dallo schermo del telefono a tutto il viewport e poi rientra nella foto della prima card di Servizi popolari (home-motion.js) -->
		<div id="hiw-zoom" class="hiw-zoom" aria-hidden="true">
			<video class="hiw-zoom__video" muted loop playsinline preload="none" src="<?php echo esc_url( $zoom_video_url ); ?>" tabindex="-1"></video>
			<span class="hiw-zoom__veil"></span>
		</div>
	<?php endif; ?>

	<?php if ( $services ) : ?>
		<?php get_template_part( 'template-parts/services-popular', null, $services_args ); ?>
	<?php endif; ?>

	<?php if ( $reviews ) : ?>
		<?php get_template_part( 'template-parts/reviews-marquee', null, $reviews_args ); ?>
	<?php endif; ?>

		<?php
		// Mappa interattiva per CAP (ADR-003). Finche' non c'e' il proxy (TICKET-05) legge la fixture
		// con i numeri veri; i testi diventano campi WP con TICKET-08, qui restano i default del tema
		// con titolo e descrizione presi dai campi della vecchia sezione raster.
		get_template_part( 'template-parts/zones-interactive', null, array(
			'title'        => $zones_args['title'],
			'description'  => $zones_args['description'],
			'coverage_url' => tp_theme_asset( 'assets/data/coverage.fixture.json' ),
			'services'     => array(
				'pulizia_domestica'   => 'Pulizia casa',
				'pulizia_uffici'      => 'Uffici',
				'pulizia_scale'       => 'Scale e condomini',
				'pulizie_industriali' => 'Industriali',
				'sanificazioni'       => 'Sanificazioni',
				'pulizia_esterni'     => 'Esterni',
			),
		) ); ?>

	<?php if ( $faq_items ) : ?>
		<?php get_template_part( 'template-parts/faq-accordion', null, $faq_args ); ?>
	<?php endif; ?>

		<?php get_template_part( 'template-parts/cta-final', null, $cta_args ); ?>

</main>
<?php
get_footer();
