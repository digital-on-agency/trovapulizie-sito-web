<?php
/**
 * Footer — Figma 3564:167 (mobile 4026:1007). Contenuti da Theme Options > Footer (Carbon Fields, inc/carbon-fields.php).
 * Motion spec §10: reveal con colonne in stagger da 60 ms; hover link verde + 2 px a destra; social scale 1.1.
 */
$brand     = carbon_get_theme_option( 'footer_brand_text' );
$tagline   = carbon_get_theme_option( 'footer_tagline' );
$social    = array_filter( (array) carbon_get_theme_option( 'footer_social' ), function ( $s ) { return ! empty( $s['url'] ); } );
$columns   = (array) carbon_get_theme_option( 'footer_columns' );
$cta_text  = carbon_get_theme_option( 'footer_cta_text' );
$cta_label = carbon_get_theme_option( 'footer_cta_label' );
$cta_link  = carbon_get_theme_option( 'footer_cta_link' );
$copyright = carbon_get_theme_option( 'footer_copyright' );
$legal     = carbon_get_theme_option( 'footer_legal' );
$networks  = array( 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok', 'facebook' => 'Facebook' );
$phone_credit = is_page_template( 'page-home.php' ) ? carbon_get_the_post_meta( 'phone_model_credit' ) : '';
$col_index = 0;
?>
<footer class="site-footer" data-reveal>
	<div class="site-footer__inner">
		<div class="site-footer__top">
			<div class="site-footer__col site-footer__brand" style="--i:<?php echo $col_index++; ?>">
				<a class="site-footer__wordmark" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( $brand ?: 'trovapulizie' ); ?></a>
				<?php if ( $tagline ) : ?><p class="site-footer__tagline"><?php echo nl2br( esc_html( $tagline ) ); ?></p><?php endif; ?>
				<?php if ( $social ) : ?>
				<ul class="site-footer__social">
					<?php foreach ( $social as $s ) :
						$net  = $s['network'] ?? '';
						$icon = isset( $networks[ $net ] ) ? tp_icon_span( 'assets/icons/social-' . $net . '.svg', 'site-footer__social-icon' ) : '';
						// Senza URL l'icona non esce: un'icona social che non porta da nessuna parte (cutover-checklist A7).
						if ( ! $icon || empty( $s['url'] ) ) { continue; }
					?>
					<li>
						<a href="<?php echo esc_url( $s['url'] ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $networks[ $net ] ); ?>"><?php echo $icon; ?></a>
					</li>
					<?php endforeach; ?>
				</ul>
				<?php endif; ?>
			</div>
			<?php foreach ( $columns as $col ) : $links = tp_link_lines( $col['links'] ?? '' ); if ( empty( $col['title'] ) && ! $links ) { continue; } ?>
			<div class="site-footer__col" style="--i:<?php echo $col_index++; ?>">
				<?php if ( ! empty( $col['title'] ) ) : ?><p class="site-footer__heading"><?php echo esc_html( $col['title'] ); ?></p><?php endif; ?>
				<ul class="site-footer__links">
					<?php foreach ( $links as $l ) : ?><li><a href="<?php echo esc_url( $l['url'] ); ?>"><?php echo esc_html( $l['label'] ); ?></a></li><?php endforeach; ?>
				</ul>
			</div>
			<?php endforeach; ?>
			<?php if ( $cta_text || $cta_label ) : ?>
			<div class="site-footer__col site-footer__cta" style="--i:<?php echo $col_index++; ?>">
				<?php if ( $cta_text ) : ?><p class="site-footer__cta-text"><?php echo nl2br( esc_html( $cta_text ) ); ?></p><?php endif; ?>
				<?php if ( $cta_label && $cta_link ) : ?><a class="site-footer__cta-link" href="<?php echo esc_url( $cta_link ); ?>"><?php echo esc_html( $cta_label ); ?> <span aria-hidden="true">&rarr;</span></a><?php endif; ?>
			</div>
			<?php endif; ?>
		</div>
		<div class="site-footer__divider" aria-hidden="true"></div>
		<div class="site-footer__bottom">
			<p class="site-footer__copyright"><?php echo esc_html( $copyright ?: '© ' . gmdate( 'Y' ) . ' Trovapulizie S.R.L.' ); ?><?php if ( $phone_credit ) : ?> <span class="site-footer__credit"><?php echo esc_html( $phone_credit ); ?></span><?php endif; ?></p>
			<?php $legal_links = tp_link_lines( $legal ); if ( $legal_links ) : ?>
			<ul class="site-footer__legal">
				<?php foreach ( $legal_links as $l ) : ?><li><a href="<?php echo esc_url( $l['url'] ); ?>"><?php echo esc_html( $l['label'] ); ?></a></li><?php endforeach; ?>
			</ul>
			<?php endif; ?>
		</div>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
