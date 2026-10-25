<?php
/**
 * Testata delle landing paid (Figma 4364:6629 promo bar + 4364:6631 header; mobile 4364:7088/7090).
 * Caricata con get_header( 'landing', $args ) dai template landing (page-sgrosso.php, page-thank-you.php).
 * Niente menu del sito, niente Accedi/Registrati: logo verso la Home, due ancore di sezione, telefono e
 * una sola CTA verso il form. Sotto --tp-bp-desktop restano logo e CTA (TICKET-39).
 *
 * Args: promo_desktop, promo_mobile (testi della promo bar, vuoti = niente barra), links (href => label),
 * phone_label, phone_href, cta_label, cta_href.
 */
get_template_part( 'template-parts/head' );

$args = wp_parse_args( $args ?? array(), array(
	'promo_desktop' => '',
	'promo_mobile'  => '',
	'links'         => array(),
	'phone_label'   => '',
	'phone_href'    => '',
	'cta_label'     => '',
	'cta_short'     => '', // testo sotto --tp-bp-desktop: accanto al logo la CTA lunga non entra (Figma 4364:7095)
	'cta_href'      => '#form',
) );
?>
<?php if ( $args['promo_desktop'] || $args['promo_mobile'] ) : ?>
<div class="landing-promo">
	<?php if ( $args['promo_desktop'] ) : ?>
		<p class="landing-promo__text landing-promo__text--desktop"><?php echo esc_html( $args['promo_desktop'] ); ?></p>
	<?php endif; ?>
	<?php if ( $args['promo_mobile'] ) : ?>
		<p class="landing-promo__text landing-promo__text--mobile"><?php echo esc_html( $args['promo_mobile'] ); ?></p>
	<?php endif; ?>
</div>
<?php endif; ?>

<header class="landing-nav">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="landing-nav__logo">
		<img src="<?php echo esc_url( tp_theme_asset( 'assets/img/logo.png' ) ); ?>" alt="<?php bloginfo( 'name' ); ?>" width="1356" height="212">
	</a>
	<?php if ( $args['links'] ) : ?>
	<nav class="landing-nav__links" aria-label="Sezioni della pagina">
		<?php foreach ( $args['links'] as $href => $label ) : ?>
			<a href="<?php echo esc_attr( $href ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</nav>
	<?php endif; ?>
	<div class="landing-nav__actions">
		<?php if ( $args['phone_label'] && $args['phone_href'] ) : ?>
			<a class="landing-nav__phone" href="<?php echo esc_url( $args['phone_href'] ); ?>">
				<?php echo tp_icon_span( 'assets/icons/ic-phone.svg', 'landing-nav__phone-icon' ); ?>
				<span><?php echo esc_html( $args['phone_label'] ); ?></span>
			</a>
		<?php endif; ?>
		<?php if ( $args['cta_label'] ) : ?>
			<a class="tp-btn tp-btn--primary landing-nav__cta" href="<?php echo esc_url( $args['cta_href'] ); ?>">
				<?php // Il testo corto precede quello lungo: sotto la soglia desktop il CSS nasconde il fratello successivo. ?>
				<?php if ( $args['cta_short'] ) : ?><span class="landing-nav__cta-short"><?php echo esc_html( $args['cta_short'] ); ?></span><?php endif; ?>
				<span class="landing-nav__cta-full"><?php echo esc_html( $args['cta_label'] ); ?></span>
			</a>
		<?php endif; ?>
	</div>
</header>
