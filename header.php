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
