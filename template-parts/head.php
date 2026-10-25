<?php
/**
 * Apertura del documento, condivisa da header.php (sito) e header-landing.php (landing).
 * Primo elemento del body: il link di salto al contenuto, visibile solo quando prende il focus.
 * wp_body_open() e' il punto in cui i plugin e il tema (tracking, TICKET-38) inseriscono il noscript GTM.
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
<?php wp_body_open(); ?>

<a class="skip-link" href="#contenuto">Vai al contenuto</a>
