<?php
/**
 * Indice del blog (/blog/, pagina impostata come "pagina degli articoli"). Figma 6824:903, SPEC-blog TICKET-70.
 * Il markup sta in template-parts/blog-index.php, condiviso con category.php.
 */
get_header();
?>
<main class="bl" id="contenuto" data-landing="blog">
	<?php get_template_part( 'template-parts/blog-index' ); ?>
</main>
<?php
get_footer();
