<?php
/**
 * Bezel CSS del telefono (.tp-phone, Figma 3743:3095): faccia frontale + 10 strati translateZ per lo spessore.
 * Args: screen (URL immagine dello schermo), video (URL mp4: se presente lo schermo e' un <video> muto con
 * preload="none" e poster = poster o screen), poster (URL), class (classi extra), id.
 * Usi: hero (#hiw-phone, con il video scrubbed da home-motion.js) e step di Come funziona
 * (how-it-works__phone-static, solo immagine, visibili senza motion). Su desktop con WebGL2 il bezel della hero
 * resta nel DOM ma invisibile (.is-3d sul contenitore): il telefono reale e' il canvas di phone-3d.js.
 * Misura fissa 294x608 px (style.css .tp-phone): le versioni piccole usano transform: scale.
 */
$args = wp_parse_args( $args ?? array(), array( 'screen' => '', 'video' => '', 'poster' => '', 'class' => '', 'id' => '' ) );
?>
<div class="tp-phone <?php echo esc_attr( $args['class'] ); ?>"<?php if ( $args['id'] ) : ?> id="<?php echo esc_attr( $args['id'] ); ?>"<?php endif; ?>>
	<?php for ( $i = 10; $i >= 1; $i-- ) : ?>
		<span class="tp-phone__layer" style="--z:-<?php echo $i; ?>px" aria-hidden="true"></span>
	<?php endfor; ?>
	<div class="tp-phone__face">
		<div class="tp-phone__screen">
			<?php if ( $args['video'] ) : ?>
				<video class="tp-phone__video" muted playsinline preload="none" poster="<?php echo esc_url( $args['poster'] ?: $args['screen'] ); ?>" src="<?php echo esc_url( $args['video'] ); ?>"></video>
			<?php elseif ( $args['screen'] ) : ?>
				<img class="tp-phone__img" src="<?php echo esc_url( $args['screen'] ); ?>" alt="">
			<?php endif; ?>
		</div>
	</div>
</div>
