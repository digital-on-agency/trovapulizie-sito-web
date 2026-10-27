<?php
/**
 * Card di un articolo (Figma, componente "Blog / Card articolo" 6824:895; TICKET-70). Copertina 5:3, categoria,
 * titolo, estratto, data e minuti di lettura. Tutta la card e' cliccabile (::after del link sul titolo).
 * Usata nell'indice, nelle categorie e in "Leggi anche".
 *
 * Args: post (WP_Post o ID), featured (bool: card grande orizzontale in testa all'indice), heading ('h2' | 'h3').
 * Senza immagine in evidenza la copertina e' un riquadro menta con la mascotte.
 */
$args = wp_parse_args( $args ?? array(), array(
	'post'     => null,
	'featured' => false,
	'heading'  => 'h3',
) );
$p = get_post( $args['post'] );
if ( ! $p ) {
	return;
}
$cat   = tp_blog_category( $p );
$tag   = in_array( $args['heading'], array( 'h2', 'h3' ), true ) ? $args['heading'] : 'h3';
$feat  = (bool) $args['featured'];
$sizes = $feat ? '(min-width: 1024px) 600px, 100vw' : '(min-width: 1024px) 384px, (min-width: 640px) 50vw, 100vw';
?>
<article class="bl-card<?php echo $feat ? ' bl-card--featured' : ''; ?>">
	<div class="bl-card__cover">
		<?php if ( has_post_thumbnail( $p ) ) : ?>
			<?php echo get_the_post_thumbnail( $p, $feat ? 'large' : 'medium_large', array( 'sizes' => $sizes, 'loading' => $feat ? 'eager' : 'lazy', 'alt' => '' ) ); ?>
		<?php else : ?>
			<img class="bl-card__placeholder" src="<?php echo esc_url( tp_theme_asset( 'assets/img/sgrosso/sgrosso-polpo-mascotte.webp' ) ); ?>" alt="" loading="lazy">
		<?php endif; ?>
	</div>
	<div class="bl-card__body">
		<?php if ( $feat || $cat ) : ?>
			<p class="bl-card__tags">
				<?php if ( $feat ) : ?><span class="bl-tag bl-tag--dark">In evidenza</span><?php endif; ?>
				<?php if ( $cat ) : ?><span class="bl-tag"><?php echo esc_html( $cat->name ); ?></span><?php endif; ?>
			</p>
		<?php endif; ?>
		<<?php echo $tag; ?> class="bl-card__title"><a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a></<?php echo $tag; ?>>
		<p class="bl-card__excerpt"><?php echo esc_html( wp_strip_all_tags( get_the_excerpt( $p ) ) ); ?></p>
		<p class="bl-card__meta"><?php echo esc_html( tp_blog_meta_line( $p ) ); ?></p>
		<?php if ( $feat ) : ?><span class="bl-card__more" aria-hidden="true">Leggi l'articolo &rarr;</span><?php endif; ?>
	</div>
</article>
