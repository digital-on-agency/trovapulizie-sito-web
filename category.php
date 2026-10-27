<?php
/**
 * Categoria del blog (/blog/categoria/<slug>/). Stesso layout dell'indice con il chip della categoria attivo e il
 * nome della categoria nel titolo. SPEC-blog TICKET-70.
 */
$cat  = get_queried_object();
$desc = trim( wp_strip_all_tags( category_description() ) );
get_header();
?>
<main class="bl" id="contenuto" data-landing="blog">
	<?php get_template_part( 'template-parts/blog-index', null, array(
		'eyebrow'  => 'BLOG · ' . mb_strtoupper( $cat->name ),
		'title'    => $cat->name,
		'subtitle' => $desc ?: 'Le guide del blog di Trovapulizie su questo argomento.',
	) ); ?>
</main>
<?php
get_footer();
