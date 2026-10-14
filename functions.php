<?php
require_once __DIR__ . '/vendor/autoload.php';

add_action( 'after_setup_theme', function () {
	\Carbon_Fields\Carbon_Fields::boot();
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'trovapulizie-fonts', 'https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap', array(), null );
	wp_enqueue_style( 'trovapulizie-style', get_stylesheet_uri(), array( 'trovapulizie-fonts' ), wp_get_theme()->get( 'Version' ) );
	if ( is_page_template( 'page-home.php' ) ) {
		wp_enqueue_script( 'trovapulizie-home-motion', get_stylesheet_directory_uri() . '/assets/js/home-motion.js', array(), wp_get_theme()->get( 'Version' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
	}
} );

add_action( 'init', function () {
	remove_action( 'wp_head', 'wp_generator' );
} );
require_once __DIR__ . "/inc/carbon-fields.php";
