<?php
/**
 * Template Name: Home
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
$zoom_video_url   = $zoom_video ? add_query_arg( 'ver', wp_get_theme()->get( 'Version' ), $zoom_video ) : '';
$theme_uri        = get_stylesheet_directory_uri();
$step_fallbacks   = array( 'screen-step1-servizio.png', 'screen-step2-cleaner.png', 'screen-step3-conferma.png' );
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
$zones_title      = carbon_get_the_post_meta( 'zones_title' );
$zones_desc       = carbon_get_the_post_meta( 'zones_description' );
$zones_map        = carbon_get_the_post_meta( 'zones_map_image' );
$zones_args       = array(
	'title'        => $zones_title,
	'description'  => $zones_desc,
	'map'          => $zones_map,
	'coming_label' => carbon_get_the_post_meta( 'zones_coming_label' ),
	'cities'       => preg_split( '/\r\n|\r|\n/', (string) carbon_get_the_post_meta( 'zones_coming_cities' ) ),
);
$faq_items        = carbon_get_the_post_meta( 'faq_items' );
$faq_title        = carbon_get_the_post_meta( 'faq_title' );
$cta_title        = carbon_get_the_post_meta( 'cta_title' );
$cta_subtitle     = carbon_get_the_post_meta( 'cta_subtitle' );
$cta_button_text  = carbon_get_the_post_meta( 'cta_button_text' );
$cta_button_link  = carbon_get_the_post_meta( 'cta_button_link' );
?>
<main class="home">

	<?php if ( current_user_can( 'edit_posts' ) && ! $hero_title && ! $steps && ! $services ) : ?>
		<div style="background:#fff3cd;border:1px solid #ffe69c;padding:12px 20px;margin:20px;border-radius:8px;">
			Contenuti non ancora inseriti — vai su <strong>Pagine → Home → Modifica</strong> e compila i campi Carbon Fields (Hero, Come funziona, Servizi Popolari, ecc.). Questo avviso lo vedi solo da loggato.
		</div>
	<?php endif; ?>

	<section class="hero">
		<div class="hero__box">
			<?php if ( $hero_video ) : ?>
				<!-- Timelapse disordine -> ordine: parte da JS (hero-video.js) solo senza "riduci movimento", sfuma sopra la foto e resta sull'ultimo frame -->
				<video class="hero__video" muted playsinline preload="auto" src="<?php echo esc_url( add_query_arg( 'ver', wp_get_theme()->get( 'Version' ), $hero_video ) ); ?>" aria-hidden="true" tabindex="-1"></video>
			<?php endif; ?>
		</div>
		<div class="hero__content">
			<?php if ( $hero_trust_badge ) : ?>
				<div class="trust-badge">
					<span class="trust-badge__stars">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
					<span class="trust-badge__text"><?php echo esc_html( $hero_trust_badge ); ?></span>
				</div>
			<?php endif; ?>

			<?php if ( $hero_title ) : ?>
				<h1><?php echo nl2br( esc_html( $hero_title ) ); ?></h1>
			<?php endif; ?>

			<?php if ( $hero_subtitle ) : ?>
				<p class="hero__subtitle"><?php echo nl2br( esc_html( $hero_subtitle ) ); ?></p>
			<?php endif; ?>

			<!-- Search bar: markup statico, widget JS reale da collegare — vedi ADR-001 -->
			<div class="hero__search-bar">
				<div class="hero__search-field hero__search-field--service">
					<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/icons/ic-servizio.svg' ); ?>" alt="" class="hero__search-icon">
					<span>Pulizia casa</span>
					<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/icons/chevron.svg' ); ?>" alt="" class="hero__search-chevron">
				</div>
				<div class="hero__search-divider"></div>
				<div class="hero__search-field hero__search-field--address">
					<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/icons/ic-pin.svg' ); ?>" alt="" class="hero__search-icon">
					<span class="hero__search-placeholder">Inserisci il tuo indirizzo</span>
				</div>
				<button type="button" class="tp-btn tp-btn--primary tp-btn--lg hero__search-submit">
					<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/icons/ic-search.svg' ); ?>" alt="" class="hero__search-icon hero__search-icon--white">
					Cerca
				</button>
			</div>
		</div>
		<div class="hero__phone" id="hiw-phone-pin" data-theme="<?php echo esc_attr( $theme_uri ); ?>" data-ver="<?php echo esc_attr( wp_get_theme()->get( 'Version' ) ); ?>" data-model="<?php echo esc_attr( carbon_get_the_post_meta( 'phone_model_url' ) ); ?>">
			<?php get_template_part( 'template-parts/phone-mockup', null, array(
				'screen' => $hiw_poster ?: $theme_uri . '/assets/img/hero-phone-screen.png',
				'video'  => $hiw_video ? add_query_arg( 'ver', wp_get_theme()->get( 'Version' ), $hiw_video ) : '', // il video e' cachato 7 giorni dall'hosting: la versione del tema lo invalida
				'poster' => $hiw_poster,
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
			<ol class="how-it-works__steps">
				<?php foreach ( array_slice( $steps, 0, 3 ) as $i => $step ) :
					$n      = $i + 1;
					$screen = ! empty( $step['image'] ) ? wp_get_attachment_url( $step['image'] ) : $theme_uri . '/assets/img/' . $step_fallbacks[ $i ];
				?>
				<li class="how-it-works__step how-it-works__step--<?php echo $n; ?><?php echo 1 === $n ? ' is-active' : ''; ?>" data-step="<?php echo $n; ?>">
					<span class="how-it-works__badge"><?php echo $n; ?></span>
					<div class="how-it-works__text">
						<h3><?php echo esc_html( $step['title'] ?? '' ); ?></h3>
						<p><?php echo esc_html( $step['description'] ?? '' ); ?></p>
					</div>
					<?php get_template_part( 'template-parts/phone-mockup', null, array(
						'screen' => $screen,
						'class'  => 'how-it-works__phone-static',
					) ); ?>
				</li>
				<?php endforeach; ?>
			</ol>
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

		<?php get_template_part( 'template-parts/zones-map', null, $zones_args ); ?>

	<?php if ( $faq_items ) : ?>
		<?php get_template_part( 'template-parts/faq-accordion', null, array( 'title' => $faq_title, 'items' => $faq_items ) ); ?>
	<?php endif; ?>

	<section class="cta-final">
		<?php if ( $cta_title ) : ?><h2><?php echo esc_html( $cta_title ); ?></h2><?php endif; ?>
		<?php if ( $cta_subtitle ) : ?><p><?php echo esc_html( $cta_subtitle ); ?></p><?php endif; ?>
		<?php if ( $cta_button_text && $cta_button_link ) : ?>
			<a class="button" href="<?php echo esc_url( $cta_button_link ); ?>"><?php echo esc_html( $cta_button_text ); ?></a>
		<?php endif; ?>
	</section>

</main>
<?php
get_footer();
