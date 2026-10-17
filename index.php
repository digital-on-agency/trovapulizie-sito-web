<?php
/**
 * Template di fallback per tutte le pagine senza template dedicato: contenuto WP nudo dentro <main id="contenuto">.
 * La Home usa page-home.php; le pagine quartiere/landing avranno template propri (vedi CLAUDE.md del progetto).
 */
get_header();
?>
<main>
<?php
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		the_content();
	endwhile;
endif;
?>
</main>
<?php
get_footer();
