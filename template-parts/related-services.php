<?php
/**
 * Blocco "Altri servizi a Roma" (SPEC-seo-post-cutover, TICKET-78): link interni alle pagine di servizio e
 * all'hub /roma/, con gli anchor fissi di tp_service_pages(). Esclude la pagina corrente. Va in fondo alla
 * pagina, dopo la CTA finale e prima del footer. Non linka mai app.trovapulizie.com.
 * Stile coerente con .cta-final: stessa larghezza, raggio e ombra della card, fondo chiaro.
 * Args: title (default "Altri servizi a Roma").
 */
$args = wp_parse_args( $args ?? array(), array( 'title' => 'Altri servizi a Roma' ) );
$corrente = tp_current_path();
$voci     = array();
foreach ( tp_service_pages() as $percorso => $anchor ) {
	if ( trailingslashit( $percorso ) === $corrente ) {
		continue;
	}
	$voci[ $percorso ] = $anchor;
}
if ( ! $voci ) {
	return;
}
?>
<section class="related-services" aria-labelledby="related-services-title">
	<div class="related-services__card">
		<h2 class="related-services__title" id="related-services-title"><?php echo esc_html( $args['title'] ); ?></h2>
		<ul class="related-services__list">
			<?php foreach ( $voci as $percorso => $anchor ) : ?>
				<li><a class="related-services__link" href="<?php echo esc_url( home_url( $percorso ) ); ?>"><span><?php echo esc_html( $anchor ); ?></span><span class="related-services__arrow" aria-hidden="true">&rarr;</span></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
