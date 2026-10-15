<?php
/**
 * Head + navbar (Figma 3564:5, mobile 4018:965). Navbar sticky alta --tp-navbar-h (style.css): da quel token
 * derivano il box hero e l'offset di Come funziona. Link statici ad ancore di sezione (#come-funziona, #servizi,
 * #registrati); #prezzi, #diventa-cleaner, #accedi non esistono ancora. Sotto 960px i link sono nascosti e non
 * c'e' un menu mobile (adattamento pianificato, vedi docs/homepage.md §10).
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

<header class="navbar">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="navbar__logo">
		<img src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/logo.png' ); ?>" alt="<?php bloginfo( 'name' ); ?>">
	</a>
	<nav class="navbar__links">
		<a href="#come-funziona">Come funziona</a>
		<a href="#servizi">Servizi</a>
		<a href="#prezzi">Prezzi</a>
		<a href="#diventa-cleaner">Diventa cleaner</a>
	</nav>
	<div class="navbar__cta">
		<a href="#accedi" class="tp-btn tp-btn--secondary">Accedi</a>
		<a href="#registrati" class="tp-btn tp-btn--primary">Registrati</a>
	</div>
</header>
