<?php
/**
 * Head + navbar (Figma 3564:5, mobile 4018:965). Primo elemento del body: il link di salto al contenuto,
 * visibile solo quando prende il focus. Navbar sticky alta --tp-navbar-h (style.css): da quel token
 * derivano il box hero e l'offset di Come funziona. Voci di default assolute: Come funziona e Servizi alle sezioni
 * della Home, Prezzi al calcolatore dell'hub /roma/, Diventa cleaner alla sua pagina. Accedi e Registrati portano all'app
 * (tp_app_url()), non ad ancore vuote.
 * Sotto --tp-bp-desktop la barra mostra logo, Registrati e l'hamburger (Figma 4018:971): le voci di menu e
 * Accedi stanno nel pannello, aperto da assets/js/nav-mobile.js. Le voci hanno un'unica sorgente
 * ($tp_nav_links) e vengono rese sia nella barra desktop sia nel pannello.
 */
?>
<?php get_template_part( 'template-parts/head' ); ?>

<?php
// Args (get_header( '', $args )): links = voci della barra (default: le ancore della Home), cta_label e
// cta_href = bottone primario. La pagina Diventa cleaner (TICKET-51) passa le proprie ancore e il signup cleaner.
$args = wp_parse_args( $args ?? array(), array(
	'links'     => array(),
	'cta_label' => 'Registrati',
	'cta_href'  => '',
) );
// Voci assolute: da qualunque pagina portano alla sezione giusta (prima erano ancore relative, vuote fuori dalla
// Home). Prezzi va al calcolatore dell'hub, l'unico posto del sito con i prezzi (cutover-checklist A6).
$tp_nav_links = $args['links'] ?: array(
	home_url( '/#come-funziona' )     => 'Come funziona',
	home_url( '/#servizi' )           => 'Servizi',
	home_url( '/roma/#quanto-costa' ) => 'Prezzi',
	home_url( '/diventa-cleaner/' )   => 'Diventa cleaner',
);
$tp_nav_cta_href = $args['cta_href'] ?: tp_app_url();
?>
<header class="navbar">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="navbar__logo">
		<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/logo.png' ); ?>" alt="<?php bloginfo( 'name' ); ?>" width="1356" height="212">
	</a>
	<nav class="navbar__links">
		<?php foreach ( $tp_nav_links as $href => $label ) : ?>
			<a href="<?php echo esc_attr( $href ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</nav>
	<div class="navbar__cta">
		<?php // Puntano all'app, non piu' ad ancore inesistenti: erano le uniche due azioni sopra la
		// piega e non facevano niente. La barra di ricerca della hero resta un segnaposto (ADR-001). ?>
		<a href="<?php echo esc_url( tp_app_url( 'login' ) ); ?>" class="tp-btn tp-btn--secondary">Accedi</a>
		<a href="<?php echo esc_url( $tp_nav_cta_href ); ?>" class="tp-btn tp-btn--primary" data-cta-position="navbar"><?php echo esc_html( $args['cta_label'] ); ?></a>
	</div>
	<button type="button" class="navbar__burger" id="nav-burger" aria-expanded="false" aria-controls="nav-panel" aria-label="Apri il menu">
		<span aria-hidden="true"></span>
		<span aria-hidden="true"></span>
		<span aria-hidden="true"></span>
	</button>
</header>
<nav class="navbar__panel" id="nav-panel" aria-labelledby="nav-burger" hidden>
	<?php foreach ( $tp_nav_links as $href => $label ) : ?>
		<a href="<?php echo esc_attr( $href ); ?>"><?php echo esc_html( $label ); ?></a>
	<?php endforeach; ?>
	<a href="<?php echo esc_url( tp_app_url( 'login' ) ); ?>" class="tp-btn tp-btn--secondary navbar__panel-login">Accedi</a>
</nav>
