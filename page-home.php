<?php
/**
 * Template Name: Home
 */
get_header();

$hero_title       = carbon_get_the_post_meta( 'hero_title' );
$hero_subtitle    = carbon_get_the_post_meta( 'hero_subtitle' );
$hero_trust_badge = carbon_get_the_post_meta( 'hero_trust_badge' );
$hiw_title        = carbon_get_the_post_meta( 'how_it_works_title' );
$hiw_subtitle     = carbon_get_the_post_meta( 'how_it_works_subtitle' );
$hiw_video        = carbon_get_the_post_meta( 'how_it_works_video' );
$hiw_poster       = carbon_get_the_post_meta( 'how_it_works_poster' );
$steps            = carbon_get_the_post_meta( 'how_it_works_steps' );
$visual_image     = carbon_get_the_post_meta( 'visual_band_image' );
$visual_label     = carbon_get_the_post_meta( 'visual_band_label' );
$theme_uri        = get_stylesheet_directory_uri();
$step_fallbacks   = array( 'screen-step1-servizio.png', 'screen-step2-cleaner.png', 'screen-step3-conferma.png' );
$services         = carbon_get_the_post_meta( 'popular_services' );
$reviews          = carbon_get_the_post_meta( 'reviews' );
$zones_title      = carbon_get_the_post_meta( 'zones_title' );
$zones_desc       = carbon_get_the_post_meta( 'zones_description' );
$zones_map        = carbon_get_the_post_meta( 'zones_map_image' );
$faq_items        = carbon_get_the_post_meta( 'faq_items' );
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
		<div class="hero__box"></div>
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
				'video'  => $hiw_video,
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

	<?php if ( $visual_image ) : ?>
	<section class="visual-band" id="visual-band">
		<div class="visual-band__media">
			<img src="<?php echo esc_url( $visual_image ); ?>" alt="">
			<?php if ( $visual_label ) : ?><span class="visual-band__label"><?php echo esc_html( $visual_label ); ?></span><?php endif; ?>
		</div>
	</section>
	<?php endif; ?>

	<?php if ( $services ) : ?>
	<section class="popular-services">
		<?php foreach ( $services as $service ) : ?>
			<div class="service-card" data-tab="<?php echo esc_attr( $service['tab_label'] ?? '' ); ?>">
				<?php if ( ! empty( $service['card_image'] ) ) : ?><img src="<?php echo esc_url( wp_get_attachment_url( $service['card_image'] ) ); ?>" alt=""><?php endif; ?>
				<h3><?php echo esc_html( $service['card_title'] ?? '' ); ?></h3>
				<p><?php echo esc_html( $service['card_description'] ?? '' ); ?></p>
				<?php if ( ! empty( $service['link'] ) ) : ?><a href="<?php echo esc_url( $service['link'] ); ?>">Scopri di più</a><?php endif; ?>
			</div>
		<?php endforeach; ?>
	</section>
	<?php endif; ?>

	<?php if ( $reviews ) : ?>
	<section class="social-proof">
		<?php foreach ( $reviews as $review ) : ?>
			<blockquote>
				<p><?php echo esc_html( $review['review_text'] ?? '' ); ?></p>
				<cite><?php echo esc_html( $review['author_name'] ?? '' ); ?></cite>
			</blockquote>
		<?php endforeach; ?>
	</section>
	<?php endif; ?>

	<section class="zones">
		<?php if ( $zones_title ) : ?><h2><?php echo esc_html( $zones_title ); ?></h2><?php endif; ?>
		<?php if ( $zones_desc ) : ?><p><?php echo esc_html( $zones_desc ); ?></p><?php endif; ?>
		<?php if ( $zones_map ) : ?><img src="<?php echo esc_url( wp_get_attachment_url( $zones_map ) ); ?>" alt="Mappa zone coperte"><?php endif; ?>
	</section>

	<?php if ( $faq_items ) : ?>
	<section class="faq">
		<?php foreach ( $faq_items as $item ) : ?>
			<div class="faq-item">
				<h4><?php echo esc_html( $item['question'] ?? '' ); ?></h4>
				<p><?php echo esc_html( $item['answer'] ?? '' ); ?></p>
			</div>
		<?php endforeach; ?>
	</section>
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
