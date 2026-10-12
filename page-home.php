<?php
/**
 * Template Name: Home
 */
get_header();

$hero_title       = carbon_get_the_post_meta( 'hero_title' );
$hero_subtitle    = carbon_get_the_post_meta( 'hero_subtitle' );
$hero_trust_badge = carbon_get_the_post_meta( 'hero_trust_badge' );
$steps            = carbon_get_the_post_meta( 'how_it_works_steps' );
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
		<?php if ( $hero_title ) : ?><h1><?php echo esc_html( $hero_title ); ?></h1><?php endif; ?>
		<?php if ( $hero_subtitle ) : ?><p><?php echo esc_html( $hero_subtitle ); ?></p><?php endif; ?>
		<?php if ( $hero_trust_badge ) : ?><span class="trust-badge"><?php echo esc_html( $hero_trust_badge ); ?></span><?php endif; ?>
		<!-- widget lead/ricerca va qui, vedi ADR-001 -->
	</section>

	<?php if ( $steps ) : ?>
	<section class="how-it-works">
		<?php foreach ( $steps as $step ) : ?>
			<div class="step">
				<?php if ( ! empty( $step['image'] ) ) : ?><img src="<?php echo esc_url( wp_get_attachment_url( $step['image'] ) ); ?>" alt=""><?php endif; ?>
				<h3><?php echo esc_html( $step['title'] ?? '' ); ?></h3>
				<p><?php echo esc_html( $step['description'] ?? '' ); ?></p>
			</div>
		<?php endforeach; ?>
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
