<?php
require_once __DIR__ . '/vendor/autoload.php';

add_action( 'after_setup_theme', function () {
	\Carbon_Fields\Carbon_Fields::boot();
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'trovapulizie-style', get_stylesheet_uri(), array(), wp_get_theme()->get( 'Version' ) );
} );

add_action( 'init', function () {
	remove_action( 'wp_head', 'wp_generator' );
} );
require_once __DIR__ . "/inc/carbon-fields.php";
