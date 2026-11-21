<?php
/**
 * Barra in alto, variante chiara della Home v2 (SPEC-homepage-v2, TICKET-120). Figma 7903:5261 (desktop) e 7946:6
 * (mobile, frame bianco 7946:2). La chiama header.php quando l'argomento `variant` e' 'v2' o tp_header_v2_on() e' vero;
 * i due punti di ingresso sono documentati in header.php. Il documento (<head>, <body>, link di salto) lo apre
 * header.php prima di arrivare qui: questo file stampa solo <header> e il pannello del menu.
 *
 * Argomenti (gli stessi di header.php, passati cosi' come sono):
 * - links (array href => etichetta, []): voci della barra e del pannello. Vuoto = Servizi (/roma/), Come funziona
 *   (ancora #come-funziona: sulla pagina d'anteprima resta sulla pagina, altrove porta alla Home), Per le aziende
 *   (/roma/pulizie-uffici/), Diventa professionista (/diventa-cleaner/).
 * - cta_label (string, 'Registrati' = default di header.php): con il default esce "Apri l'app"; un'etichetta diversa
 *   passata da chi chiama (es. il signup cleaner) viene rispettata.
 * - cta_href (string, ''): vuoto = tp_app_url().
 * - variant: ignorato qui.
 *
 * Tracciamento: il bottone porta data-cta-position="navbar" (valore gia' in uso, SPEC §2) e apre l'app nella stessa
 * scheda. "Accedi" porta a tp_app_url( 'login' ).
 * Mobile (max-width: 1023px): marchio, "Apri l'app" e hamburger da 44 px; le voci e Accedi stanno in #nav-panel,
 * aperto da assets/js/nav-mobile.js (ids #nav-burger e #nav-panel, invariati).
 * CSS: blocco `.navbar--v2` in style.css, solo selettori nuovi. Il token --tp-navbar-h passa a 103 px (desktop) e 64 px
 * (mobile) sotto la classe di body `tp-v2-header` (inc/home-v2.php), cosi' seguono scroll-margin e top del pannello.
 */
$args = wp_parse_args( $args ?? array(), array(
	'links'     => array(),
	'cta_label' => 'Registrati',
	'cta_href'  => '',
	'variant'   => '',
) );
$tp_v2_anchor = function_exists( 'tp_is_home_v2_preview' ) && tp_is_home_v2_preview() ? '#come-funziona' : home_url( '/#come-funziona' );
$tp_v2_links  = $args['links'] ?: array(
	home_url( '/roma/' )                  => 'Servizi',
	$tp_v2_anchor                         => 'Come funziona',
	home_url( '/roma/pulizie-uffici/' )   => 'Per le aziende',
	home_url( '/diventa-cleaner/' )       => 'Diventa professionista',
);
$tp_v2_cta_label = 'Registrati' === $args['cta_label'] ? 'Apri l’app' : $args['cta_label'];
$tp_v2_cta_href  = $args['cta_href'] ?: tp_app_url();
?>
<header class="navbar navbar--v2">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="navbar__brand" aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>">
		<img class="navbar__brand-icon" src="<?php echo esc_url( tp_theme_asset( 'assets/img/home-v2/logo-icon.svg' ) ); ?>" alt="" width="40" height="37">
		<span class="navbar__brand-name" aria-hidden="true">Trovapulizie</span>
	</a>
	<nav class="navbar__links" aria-label="Menu principale">
		<?php foreach ( $tp_v2_links as $href => $label ) : ?>
			<a href="<?php echo esc_attr( $href ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</nav>
	<div class="navbar__cta">
		<a href="<?php echo esc_url( tp_app_url( 'login' ) ); ?>" class="navbar__login">Accedi</a>
		<a href="<?php echo esc_url( $tp_v2_cta_href ); ?>" class="navbar__app" data-cta-position="navbar"><?php echo esc_html( $tp_v2_cta_label ); ?></a>
	</div>
	<button type="button" class="navbar__burger" id="nav-burger" aria-expanded="false" aria-controls="nav-panel" aria-label="Apri il menu">
		<span aria-hidden="true"></span>
		<span aria-hidden="true"></span>
		<span aria-hidden="true"></span>
	</button>
</header>
<nav class="navbar__panel navbar__panel--v2" id="nav-panel" aria-labelledby="nav-burger" hidden>
	<?php foreach ( $tp_v2_links as $href => $label ) : ?>
		<a href="<?php echo esc_attr( $href ); ?>"><?php echo esc_html( $label ); ?></a>
	<?php endforeach; ?>
	<a href="<?php echo esc_url( tp_app_url( 'login' ) ); ?>" class="navbar__panel-login">Accedi</a>
</nav>
