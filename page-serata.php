<?php
/**
 * Template Name: Survey serata 25/11
 *
 * Survey dopo la serata Wine Venture Club del 25/11 (vault: clients/trovapulizie/projects/sito-web/spec/SPEC-survey-serata-25-11.md),
 * pagina /serata-25-11/. Pagina autonoma: senza navbar ne' footer del sito, noindex, fuori dalla sitemap, senza tracking
 * (inc/serata.php). Le schermate le costruisce assets/js/serata.js dalla definizione passata in data-config; senza
 * JavaScript resta l'avviso del noscript. Pelle della Survey OnField (assets/css/survey.css) coi colori Trovapulizie.
 */
$config = tp_serata_page_config();
get_template_part( 'template-parts/head' );
?>
<main class="sv sv--tp" id="contenuto" data-serata data-config="<?php echo esc_attr( wp_json_encode( $config, JSON_UNESCAPED_UNICODE ) ); ?>">
	<div class="sv__frame">
		<header class="sv__top" data-sv-top hidden>
			<button type="button" class="sv__back" data-sv-back aria-label="Indietro"><span aria-hidden="true">←</span> Indietro</button>
			<span class="sv__count" data-sv-count aria-live="polite"></span>
			<div class="sv__bar" role="presentation"><span class="sv__bar-fill" data-sv-fill></span></div>
		</header>
		<div class="sv__screen" data-sv-screen tabindex="-1" aria-live="polite">
			<p class="sv__logo">Trovapulizie</p>
			<noscript><p class="sv__title"><?php echo esc_html( $config['def']['ui']['nojs'] ); ?></p></noscript>
		</div>
	</div>
</main>
<?php wp_footer(); ?>
</body>
</html>
