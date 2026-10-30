<?php
/**
 * Template Name: Survey OnField
 *
 * Survey Dubai v2 (vault: clients/trovapulizie/projects/gestionale-saas/survey-dubai-v2.md), pagina /survey-dubai/.
 * Pagina autonoma: senza navbar ne' footer del sito, noindex, fuori dalla sitemap, senza tracking (inc/survey.php).
 * Le schermate le costruisce assets/js/survey.js dalla definizione passata in data-config; senza JavaScript resta
 * l'avviso del noscript. Pelle del Figma della v1 (UiW4TPgvqCODW5bhyFV5eK, pagina 2781:9): Inter, verde #00C896.
 *
 * [ATTENZIONE] Il nome del file non e' page-survey-dubai.php di proposito: WordPress lo applicherebbe da solo allo slug.
 */
$config = tp_survey_page_config();
$brand  = TP_SURVEY_BRAND;
get_template_part( 'template-parts/head' );
?>
<main class="sv" id="contenuto" data-survey data-config="<?php echo esc_attr( wp_json_encode( $config, JSON_UNESCAPED_UNICODE ) ); ?>">
	<div class="sv__frame">
		<header class="sv__top" data-sv-top hidden>
			<button type="button" class="sv__back" data-sv-back aria-label="Back"><span aria-hidden="true">←</span> Back</button>
			<span class="sv__count" data-sv-count aria-live="polite"></span>
			<div class="sv__bar" role="presentation"><span class="sv__bar-fill" data-sv-fill></span></div>
		</header>
		<div class="sv__screen" data-sv-screen tabindex="-1" aria-live="polite">
			<p class="sv__logo"><?php echo esc_html( $brand ); ?></p>
			<noscript><p class="sv__title">Please enable JavaScript to take this survey.</p></noscript>
		</div>
	</div>
</main>
<?php wp_footer(); ?>
</body>
</html>
