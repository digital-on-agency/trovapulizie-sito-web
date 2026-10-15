<?php
/**
 * Sezione "Servizi popolari" — Figma 3828:1079 (stati tab: 6306:890, 6306:958, 6306:1026).
 * Args: title, subtitle, proof_avatar, proof_title, proof_sub, proof_rating, services (array del campo
 * complex `popular_services`), theme_uri. Riutilizzabile nelle pagine quartiere/landing.
 * Comportamento (spec motion §5): reveal in stagger all'ingresso, indicatore che scivola fra le tab,
 * contenuto card in crossfade+rise con chip in stagger — assets/js/services-tabs.js + style.css.
 */
$args = wp_parse_args( $args ?? array(), array(
	'title'        => '',
	'subtitle'     => '',
	'proof_avatar' => '',
	'proof_title'  => '',
	'proof_sub'    => '',
	'proof_rating' => '',
	'services'     => array(),
	'video_first'  => '', // URL mp4: nella prima card, sotto la foto, il video dello zoom-through (desktop con motion)
) );
$services = array_values( array_filter( (array) $args['services'], function ( $s ) {
	return ! empty( $s['tab_label'] );
} ) );
if ( ! $services ) {
	return;
}
$icon_keys = array( 'casa', 'uffici', 'cantieri', 'condomini', 'sanificazione', 'affitti' );
?>
<section class="services" id="servizi" data-reveal>
	<div class="services__inner">
		<header class="services__head">
			<div class="services__titles">
				<?php if ( $args['title'] ) : ?><h2 class="services__title"><?php echo esc_html( $args['title'] ); ?></h2><?php endif; ?>
				<?php if ( $args['subtitle'] ) : ?><p class="services__subtitle"><?php echo esc_html( $args['subtitle'] ); ?></p><?php endif; ?>
			</div>
			<?php if ( $args['proof_title'] || $args['proof_rating'] ) : ?>
			<div class="services__proof">
				<div class="services__proof-user">
					<img class="services__proof-avatar" src="<?php echo esc_url( $args['proof_avatar'] ?: tp_theme_asset( 'assets/img/services-proof-avatar.jpg' ) ); ?>" alt="" width="40" height="40" loading="lazy">
					<div class="services__proof-text">
						<strong><?php echo esc_html( $args['proof_title'] ); ?></strong>
						<span><?php echo esc_html( $args['proof_sub'] ); ?></span>
					</div>
				</div>
				<span class="services__proof-divider" aria-hidden="true"></span>
				<div class="services__proof-rating"><span class="services__star" aria-hidden="true">&#9733;</span><?php echo esc_html( $args['proof_rating'] ); ?></div>
			</div>
			<?php endif; ?>
		</header>

		<div class="services__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Servizi' ); ?>">
			<span class="services__tab-indicator" aria-hidden="true"></span>
			<?php foreach ( $services as $i => $s ) :
				$icon = in_array( $s['tab_icon'] ?? '', $icon_keys, true ) ? $s['tab_icon'] : 'casa';
			?>
			<button type="button" class="services__tab<?php echo 0 === $i ? ' is-active' : ''; ?>" role="tab" id="services-tab-<?php echo $i; ?>" aria-controls="services-panel-<?php echo $i; ?>" aria-selected="<?php echo 0 === $i ? 'true' : 'false'; ?>" tabindex="<?php echo 0 === $i ? '0' : '-1'; ?>" style="--i:<?php echo $i; ?>">
				<?php echo tp_icon_span( 'assets/icons/tab-' . $icon . '.svg', 'services__tab-icon' ); ?>
				<?php echo esc_html( $s['tab_label'] ); ?>
			</button>
			<?php endforeach; ?>
		</div>

		<div class="services__cards">
			<?php foreach ( $services as $i => $s ) :
				$chips = tp_lines( $s['includes'] ?? '' );
				$image = ! empty( $s['card_image'] ) ? $s['card_image'] : tp_theme_asset( 'assets/img/services-visual-pulizia-casa.jpg' );
			?>
			<article class="tp-card services__card<?php echo 0 === $i ? ' is-active' : ''; ?>" role="tabpanel" id="services-panel-<?php echo $i; ?>" aria-labelledby="services-tab-<?php echo $i; ?>"<?php echo 0 === $i ? '' : ' aria-hidden="true"'; ?>>
				<div class="tp-card__panel services__includes">
					<h3 class="services__panel-title"><?php esc_html_e( 'Cosa include' ); ?></h3>
					<?php if ( $chips ) : ?>
					<ul class="services__chips">
						<?php foreach ( $chips as $k => $chip ) : ?><li class="tp-chip" style="--i:<?php echo (int) $k; ?>"><?php echo esc_html( $chip ); ?></li><?php endforeach; ?>
					</ul>
					<?php endif; ?>
				</div>
				<div class="services__visual">
					<img src="<?php echo esc_url( $image ); ?>" alt="" loading="lazy">
					<?php if ( 0 === $i && $args['video_first'] ) : ?><video class="services__video" muted loop playsinline preload="none" src="<?php echo esc_url( $args['video_first'] ); ?>" aria-hidden="true" tabindex="-1"></video><?php endif; ?>
					<?php if ( ! empty( $s['visual_label'] ) ) : ?><span class="services__visual-label"><?php echo esc_html( $s['visual_label'] ); ?></span><?php endif; ?>
				</div>
				<div class="tp-card__panel services__desc">
					<?php if ( ! empty( $s['card_title'] ) ) : ?><h3 class="services__card-title"><?php echo esc_html( $s['card_title'] ); ?></h3><?php endif; ?>
					<?php if ( ! empty( $s['card_description'] ) ) : ?><p class="services__card-text"><?php echo esc_html( $s['card_description'] ); ?></p><?php endif; ?>
					<?php if ( ! empty( $s['highlight'] ) ) : ?><p class="services__highlight"><?php echo esc_html( $s['highlight'] ); ?></p><?php endif; ?>
					<?php if ( ! empty( $s['link'] ) ) : ?><a class="tp-btn tp-btn--primary services__cta" href="<?php echo esc_url( $s['link'] ); ?>"><?php echo esc_html( ! empty( $s['link_text'] ) ? $s['link_text'] : 'Prenota ora' ); ?></a><?php endif; ?>
				</div>
			</article>
			<?php endforeach; ?>
		</div>

		<img class="services__mascot" src="<?php echo esc_url( tp_theme_asset( 'assets/img/mascotte.png' ) ); ?>" alt="" aria-hidden="true" width="218" height="218" loading="lazy">
	</div>
</section>
