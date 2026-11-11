<?php
/**
 * Template Name: Pagina legale
 *
 * Pagine legali del sito (/privacy/, /cookie-policy/, /terms/): titolo della pagina, testo dall'editor di WordPress in una colonna
 * leggibile e, se il campo "Policy iubenda" e' compilato, la policy incorporata in un iframe come sulla SPA di
 * produzione (PrivacyPolicyPage.tsx). Nessuna sezione del tema, nessuno script proprio.
 *
 * - Privacy e cookie policy (dall'11/11/2026): testo della pagina, generato dal vault
 *   (clients/trovapulizie/projects/portale-transazionale/legale/); campo "Policy iubenda" vuoto.
 * - Termini: testo copiato dall'app (front-end/src/content/terms.ts, versione italiana del 14/06/2026) nel
 *   contenuto della pagina. [ATTENZIONE] Il testo vive anche nell'app: una modifica va fatta in entrambi i posti
 *   finche' l'app non punta a questa pagina.
 * - Il footer porta a /cookie-policy/ e a "#preferenze-cookie", che riapre il banner (assets/js/tp-consent.js).
 */
$iubenda = trim( (string) carbon_get_the_post_meta( 'lg_iubenda_url' ) );
get_header();
?>
<main class="lg" id="contenuto">
	<article class="lg__inner">
		<h1 class="lg__title"><?php echo esc_html( get_the_title() ); ?></h1>
		<?php if ( $iubenda ) : ?>
			<iframe class="lg__iubenda" src="<?php echo esc_url( $iubenda ); ?>" title="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy"></iframe>
			<p class="lg__fallback">Se il testo non si carica, <a href="<?php echo esc_url( $iubenda ); ?>" target="_blank" rel="noopener">apri la policy su iubenda</a>.</p>
		<?php endif; ?>
		<div class="lg__content">
			<?php
			while ( have_posts() ) {
				the_post();
				the_content();
			}
			?>
		</div>
	</article>
</main>
<?php
get_footer();
