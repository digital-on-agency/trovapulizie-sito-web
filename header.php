<?php
/**
 * Head + navbar (Figma 3564:5, mobile 4018:965). Navbar sticky alta --tp-navbar-h (style.css): da quel token
 * derivano il box hero e l'offset di Come funziona. Link statici ad ancore di sezione (#come-funziona, #servizi,
 * #registrati); #prezzi, #diventa-cleaner, #accedi non esistono ancora.
 * Sotto --tp-bp-desktop la barra mostra logo, Registrati e l'hamburger (Figma 4018:971): le voci di menu e
 * Accedi stanno nel pannello, aperto da assets/js/nav-mobile.js. Le voci hanno un'unica sorgente
 * ($tp_nav_links) e vengono rese sia nella barra desktop sia nel pannello.
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

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
		<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/logo.png' ); ?>" alt="<?php bloginfo( 'name' ); ?>">
	</a>
	<nav class="navbar__links">
		<?php foreach ( $tp_nav_links as $href => $label ) : ?>
			<a href="<?php echo esc_attr( $href ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</nav>
	<div class="navbar__cta">
		<a href="#accedi" class="tp-btn tp-btn--secondary">Accedi</a>
		<a href="#registrati" class="tp-btn tp-btn--primary">Registrati</a>
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
	<a href="#accedi" class="tp-btn tp-btn--secondary navbar__panel-login">Accedi</a>
</nav>
