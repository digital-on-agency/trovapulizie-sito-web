<?php
/**
 * Articolo del blog (/blog/<slug>/). Figma 6827:937, SPEC-blog TICKET-71.
 *
 * Testata (breadcrumb, categoria, H1, estratto come sommario, autore Redazione, data e minuti), copertina
 * dall'immagine in evidenza, corpo su due colonne: indice generato dagli H2 (solo con almeno 3, fisso allo
 * scorrimento da 1024px) e testo. Il box del servizio collegato va dove c'e' [tp_servizio], altrimenti dopo il
 * testo. Poi box autore, "Leggi anche" (stessa categoria, poi i piu' recenti) e CTA finale.
 * Stili del testo: .bl-prose (h2, h3, liste, tabelle, blocchi con classe tp-checklist e tp-consiglio).
 */
get_header();
while ( have_posts() ) :
	the_post();
	$cat = tp_blog_category();
	$GLOBALS['tp_blog_service_used'] = false;
	$html = apply_filters( 'the_content', get_the_content() );
	list( $html, $voci ) = tp_blog_toc( $html );
	$box   = $GLOBALS['tp_blog_service_used'] ? '' : tp_blog_service_box();
	$mascotte = tp_theme_asset( 'assets/img/sgrosso/sgrosso-polpo-mascotte.webp' );
	?>
	<main class="bl bl--post" id="contenuto" data-landing="articolo">
		<header class="bl-post__head">
			<nav class="bl-crumbs" aria-label="Percorso">
				<a href="<?php echo esc_url( tp_blog_url() ); ?>">Blog</a>
				<?php if ( $cat ) : ?><span aria-hidden="true">/</span><a href="<?php echo esc_url( get_category_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a><?php endif; ?>
			</nav>
			<?php if ( $cat ) : ?><p class="bl-card__tags"><span class="bl-tag"><?php echo esc_html( $cat->name ); ?></span></p><?php endif; ?>
			<h1 class="bl-post__title"><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?><p class="bl-post__lead"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
			<div class="bl-author bl-author--small">
				<img class="bl-author__avatar" src="<?php echo esc_url( $mascotte ); ?>" alt="" width="40" height="40">
				<p><strong>Redazione Trovapulizie</strong><span><?php echo esc_html( tp_blog_meta_line() ); ?></span></p>
			</div>
		</header>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="bl-post__cover"><?php the_post_thumbnail( 'full', array( 'sizes' => '(min-width: 1440px) 1040px, 100vw', 'loading' => 'eager', 'fetchpriority' => 'high', 'alt' => '' ) ); ?></figure>
		<?php endif; ?>

		<div class="bl-post__body<?php echo count( $voci ) >= 3 ? '' : ' bl-post__body--single'; ?>">
			<?php if ( count( $voci ) >= 3 ) : ?>
				<nav class="bl-toc" aria-label="In questo articolo">
					<p class="bl-toc__title">IN QUESTO ARTICOLO</p>
					<ol>
						<?php foreach ( $voci as $v ) : ?><li><a href="#<?php echo esc_attr( $v['id'] ); ?>"><?php echo esc_html( $v['title'] ); ?></a></li><?php endforeach; ?>
					</ol>
				</nav>
			<?php endif; ?>
			<div class="bl-post__text">
				<div class="bl-prose"><?php echo $html; // phpcs:ignore -- contenuto filtrato da the_content ?></div>
				<?php echo $box; // phpcs:ignore -- markup costruito ed escapato in tp_blog_service_box ?>
				<aside class="bl-author">
					<img class="bl-author__avatar" src="<?php echo esc_url( $mascotte ); ?>" alt="" width="56" height="56">
					<p><strong>Scritto dalla Redazione Trovapulizie</strong><span>Con le imprese partner che lavorano ogni giorno a Roma. Hai un dubbio che vorresti vedere qui? <a href="<?php echo esc_url( home_url( '/contatti/' ) ); ?>">Scrivici</a>.</span></p>
				</aside>
			</div>
		</div>

		<?php
		$correlati = $cat ? get_posts( array( 'numberposts' => 3, 'category' => $cat->term_id, 'exclude' => array( get_the_ID() ) ) ) : array();
		if ( count( $correlati ) < 3 ) {
			$altri     = get_posts( array( 'numberposts' => 3 - count( $correlati ), 'exclude' => array_merge( array( get_the_ID() ), wp_list_pluck( $correlati, 'ID' ) ) ) );
			$correlati = array_merge( $correlati, $altri );
		}
		if ( $correlati ) : ?>
			<section class="bl-related">
				<h2 class="bl-list__title">Leggi anche</h2>
				<div class="bl-grid">
					<?php foreach ( $correlati as $p ) {
						get_template_part( 'template-parts/post-card', null, array( 'post' => $p ) );
					} ?>
				</div>
			</section>
		<?php endif; ?>

		<?php get_template_part( 'template-parts/cta-final', null, array(
			'title'        => 'Ti serve una pulizia a Roma?',
			'subtitle'     => "Inserisci l'indirizzo, scegli il servizio e confronta le imprese disponibili nella tua zona.",
			'button_text'  => 'Cerca un servizio',
			'button_link'  => home_url( '/#hero' ),
			'note'         => 'Prezzo chiaro · Pagamento protetto · Imprese verificate',
			'cta_position' => 'final',
		) ); ?>
	</main>
	<?php
endwhile;
get_footer();
