<?php
/**
 * Head + navbar (Figma 3564:5, mobile 4018:965). Primo elemento del body: il link di salto al contenuto,
 * visibile solo quando prende il focus. Navbar sticky alta --tp-navbar-h (style.css): da quel token
 * derivano il box hero e l'offset di Come funziona. Link statici ad ancore di sezione (#come-funziona, #servizi,
 * #servizi); #prezzi e #diventa-cleaner non esistono ancora. Accedi e Registrati portano all'app
 * (tp_app_url()), non ad ancore vuote.
 * Sotto --tp-bp-desktop la barra mostra logo, Registrati e l'hamburger (Figma 4018:971): le voci di menu e
 * Accedi stanno nel pannello, aperto da assets/js/nav-mobile.js. Le voci hanno un'unica sorgente
 * ($tp_nav_links) e vengono rese sia nella barra desktop sia nel pannello.
 */
?>
<?php get_template_part( 'template-parts/head' ); ?>

<?php
$tp_nav_links = array(
	'#come-funziona'   => 'Come funziona',
	'#servizi'         => 'Servizi',
	'#prezzi'          => 'Prezzi',
	'#diventa-cleaner' => 'Diventa cleaner',
);
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
		<a href="<?php echo esc_url( tp_app_url() ); ?>" class="tp-btn tp-btn--primary">Registrati</a>
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
