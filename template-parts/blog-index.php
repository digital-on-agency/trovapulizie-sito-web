<?php
/**
 * Indice del blog e delle categorie (Figma 6824:903; TICKET-70). Usato da home.php (/blog/) e category.php
 * (/blog/categoria/<slug>/) sulla query principale di WordPress.
 *
 * Hero menta con i chip delle categorie che hanno almeno un articolo pubblicato (il chip della pagina corrente e'
 * attivo); in pagina 1 il primo articolo in evidenza e gli altri in griglia, dalla pagina 2 tutti in griglia;
 * paginazione numerata; senza articoli uno stato vuoto. In fondo la CTA finale.
 *
 * Args: eyebrow, title (una riga per invio), subtitle, empty_title, empty_text.
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'     => 'BLOG',
	'title'       => "Guide pratiche\nsulle pulizie a Roma",
	'subtitle'    => 'Prezzi, checklist e consigli da chi pulisce per mestiere. Un articolo al mese sui dubbi che riceviamo più spesso.',
	'empty_title' => 'Il primo articolo arriva a breve.',
	'empty_text'  => 'Nel frattempo, se ti serve una pulizia a Roma, cerca il servizio e scopri se copriamo la tua zona.',
) );
$attuale = is_category() ? get_queried_object_id() : 0;
$cats    = get_categories( array( 'hide_empty' => true, 'exclude' => array( (int) get_option( 'default_category' ) ) ) );
// Ordine dei chip come nel Figma; una categoria nuova va in coda.
$ordine  = array_flip( array( 'casa', 'sgrosso', 'aziende-condomini', 'prezzi', 'professionisti' ) );
usort( $cats, function ( $a, $b ) use ( $ordine ) {
	return ( $ordine[ $a->slug ] ?? 99 ) <=> ( $ordine[ $b->slug ] ?? 99 );
} );
$prima   = ! is_paged();
$posts   = $GLOBALS['wp_query']->posts;
$evid    = ( $prima && $posts ) ? array_shift( $posts ) : null;
?>
<section class="bl-hero" id="hero">
	<div class="bl-hero__inner">
		<p class="bl-hero__eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p>
		<h1 class="bl-hero__title"><?php echo implode( ' <br>', array_map( 'esc_html', tp_lines( $args['title'] ) ) ); // phpcs:ignore -- righe gia' passate da esc_html ?></h1>
		<?php if ( $args['subtitle'] ) : ?><p class="bl-hero__sub"><?php echo esc_html( $args['subtitle'] ); ?></p><?php endif; ?>
		<?php if ( $cats ) : ?>
			<nav class="bl-chips" aria-label="Categorie del blog">
				<a class="bl-chip<?php echo $attuale ? '' : ' is-active'; ?>" href="<?php echo esc_url( tp_blog_url() ); ?>"<?php echo $attuale ? '' : ' aria-current="page"'; ?>>Tutti</a>
				<?php foreach ( $cats as $c ) : $on = (int) $c->term_id === $attuale; ?>
					<a class="bl-chip<?php echo $on ? ' is-active' : ''; ?>" href="<?php echo esc_url( get_category_link( $c ) ); ?>"<?php echo $on ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $c->name ); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
	</div>
</section>

<?php if ( ! $evid && ! $posts ) : ?>
	<section class="bl-empty">
		<p class="bl-empty__title"><?php echo esc_html( $args['empty_title'] ); ?></p>
		<p class="bl-empty__text"><?php echo esc_html( $args['empty_text'] ); ?></p>
		<a class="tp-btn tp-btn--primary bl-empty__cta" href="<?php echo esc_url( home_url( '/#hero' ) ); ?>" data-cta-position="blog_vuoto">Cerca un servizio <span class="dc-arrow" aria-hidden="true">&rarr;</span></a>
	</section>
<?php else : ?>
	<?php if ( $evid ) : ?>
		<section class="bl-featured" aria-label="In evidenza">
			<?php get_template_part( 'template-parts/post-card', null, array( 'post' => $evid, 'featured' => true, 'heading' => 'h2' ) ); ?>
		</section>
	<?php endif; ?>
	<?php if ( $posts ) : ?>
		<section class="bl-list">
			<h2 class="bl-list__title"><?php echo $prima ? 'Ultimi articoli' : 'Altri articoli'; ?></h2>
			<div class="bl-grid">
				<?php foreach ( $posts as $p ) {
					get_template_part( 'template-parts/post-card', null, array( 'post' => $p ) );
				} ?>
			</div>
		</section>
	<?php endif; ?>
	<?php
	$pag = paginate_links( array( 'type' => 'array', 'prev_text' => '&larr; <span>Precedenti</span>', 'next_text' => '<span>Successivi</span> &rarr;', 'mid_size' => 1 ) );
	if ( $pag ) : ?>
		<nav class="bl-pagination" aria-label="Pagine del blog"><?php echo implode( '', $pag ); // phpcs:ignore -- markup di WordPress ?></nav>
	<?php endif; ?>
<?php endif; ?>

<?php
get_template_part( 'template-parts/cta-final', null, array(
	'title'        => 'Ti serve una pulizia a Roma?',
	'subtitle'     => "Inserisci l'indirizzo, scegli il servizio e confronta le imprese disponibili nella tua zona.",
	'button_text'  => 'Cerca un servizio',
	'button_link'  => home_url( '/#hero' ),
	'note'         => 'Prezzo chiaro · Pagamento protetto · Imprese verificate',
	'cta_position' => 'final',
) );
