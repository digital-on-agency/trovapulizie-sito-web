<?php
// Args: screen (URL immagine), video (URL mp4), poster (URL), class, id
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
