<?php
/**
 * Sezione "Social proof" — Figma 3564:96 (mobile 4023:1023).
 * Args: badge, badge_link, badge_logo (google|none), eyebrow, title, reviews (array del campo complex `reviews`:
 * author_name, author_city, avatar, review_text), layout ('marquee' | 'grid'), card ('side' | 'stars').
 * Desktop: marquee continuo (CSS, 40 s, pausa su hover, sul focus e col bottone; set duplicato per il loop); mobile: prime 3 card impilate
 * statiche con reveal in stagger; reduced-motion: griglia statica. Riutilizzabile nelle landing.
 * Landing Sgrosso (Figma 4364:6920): layout 'grid' = tre card ferme centrate, card 'stars' = stelle sopra, citazione,
 * avatar con nome e zona sotto (TICKET-47). Stesso componente, nessuna copia.
 */
$args = wp_parse_args( $args ?? array(), array(
	'badge'      => '',
	'badge_link' => '',
	'badge_logo' => 'google',
	'eyebrow'    => '',
	'title'      => '',
	'reviews'    => array(),
	'layout'     => 'marquee',
	'card'       => 'side',
) );
$reviews = array_values( array_filter( (array) $args['reviews'], function ( $r ) {
	return ! empty( $r['review_text'] );
} ) );
if ( ! $reviews ) {
	return;
}
$total     = count( $reviews );
$render_card = function ( $r, $i ) use ( $total, $args ) {
	$avatar = ! empty( $r['avatar'] ) ? $r['avatar'] : tp_theme_asset( 'assets/img/review-avatar-' . ( ( $i % 3 ) + 1 ) . '.png' );
	$author = trim( (string) ( $r['author_name'] ?? '' ) );
	$city   = trim( (string) ( $r['author_city'] ?? '' ) );
	if ( 'stars' === $args['card'] ) :
	?>
	<article class="review-card review-card--stars" style="--i:<?php echo (int) $i; ?>">
		<span class="review-card__stars" aria-label="5 stelle su 5">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
		<blockquote class="review-card__text"><?php echo esc_html( trim( (string) $r['review_text'], "“”\"" ) ); ?></blockquote>
		<div class="review-card__who">
			<img class="review-card__avatar" src="<?php echo esc_url( $avatar ); ?>" alt="" width="40" height="40" loading="lazy">
			<div>
				<?php if ( $author ) : ?><p class="review-card__name"><?php echo esc_html( $author ); ?></p><?php endif; ?>
				<?php if ( $city ) : ?><p class="review-card__city"><?php echo esc_html( $city ); ?></p><?php endif; ?>
			</div>
		</div>
	</article>
	<?php
	return;
	endif;
	?>
	<article class="review-card" style="--i:<?php echo (int) $i; ?>">
		<div class="review-card__side">
			<img class="review-card__avatar" src="<?php echo esc_url( $avatar ); ?>" alt="" width="56" height="56" loading="lazy">
			<span class="review-card__index"><?php echo esc_html( sprintf( '%02d / %02d', $i + 1, $total ) ); ?></span>
		</div>
		<div class="review-card__body">
			<?php if ( $author || $city ) : ?><p class="review-card__author"><?php echo esc_html( $author . ( $author && $city ? ' · ' : '' ) . $city ); ?></p><?php endif; ?>
			<blockquote class="review-card__text"><?php echo esc_html( '“' . trim( (string) $r['review_text'], "“”\"" ) . '”' ); ?></blockquote>
		</div>
	</article>
	<?php
};
?>
<section class="reviews<?php echo 'grid' === $args['layout'] ? ' reviews--grid' : ''; ?>" id="recensioni" data-reveal>
	<div class="reviews__inner">
		<?php if ( $args['eyebrow'] ) : ?><p class="sg-eyebrow reviews__eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p><?php endif; ?>
		<?php if ( $args['badge'] ) : ?>
			<?php $tag = $args['badge_link'] ? 'a' : 'div'; ?>
			<<?php echo $tag; ?> class="reviews__badge"<?php if ( $args['badge_link'] ) : ?> href="<?php echo esc_url( $args['badge_link'] ); ?>" target="_blank" rel="noopener"<?php endif; ?>>
				<?php if ( 'google' === $args['badge_logo'] ) : ?><img class="reviews__badge-logo" src="<?php echo esc_url( tp_theme_asset( 'assets/icons/google-g.svg' ) ); ?>" alt="Google" width="18" height="18"><?php endif; ?>
				<span class="reviews__stars" aria-hidden="true">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
				<span><?php echo esc_html( $args['badge'] ); ?></span>
			</<?php echo $tag; ?>>
		<?php endif; ?>
		<?php if ( $args['title'] ) : ?><h2 class="reviews__title"><?php echo esc_html( $args['title'] ); ?></h2><?php endif; ?>
	</div>
	<?php if ( 'grid' === $args['layout'] ) : ?>
	<div class="reviews__grid" aria-label="<?php esc_attr_e( 'Recensioni dei clienti' ); ?>">
		<?php foreach ( $reviews as $i => $r ) { $render_card( $r, $i ); } ?>
	</div>
	<?php else : ?>
	<div class="reviews__marquee" aria-label="<?php esc_attr_e( 'Recensioni dei clienti' ); ?>">
		<?php // Il nastro scorre da solo per piu' di 5 secondi: serve un comando per fermarlo (WCAG 2.2.2).
		// La pausa al passaggio del mouse non basta, da tastiera non esiste. Il bottone compare solo dove
		// il nastro si muove davvero, cioe' da --tp-bp-desktop in su. ?>
		<button type="button" class="reviews__pause" aria-pressed="false">
			<span class="reviews__pause-label"><?php esc_html_e( 'Metti in pausa' ); ?></span>
		</button>
		<div class="reviews__track">
			<div class="reviews__set">
				<?php foreach ( $reviews as $i => $r ) { $render_card( $r, $i ); } ?>
			</div>
			<div class="reviews__set" aria-hidden="true">
				<?php foreach ( $reviews as $i => $r ) { $render_card( $r, $i ); } ?>
			</div>
		</div>
	</div>
	<?php endif; ?>
</section>
