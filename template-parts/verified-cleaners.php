<?php
/**
 * Sezione "Verificati sul serio" — Figma desktop 4441:965, mobile 4451:965 (TICKET-27).
 * Due colonne su desktop: a sinistra pillola, titolo, testo, elenco degli step di verifica e CTA;
 * a destra la card di un profilo cleaner di esempio. Sotto 1024px le due colonne si impilano.
 *
 * Args: eyebrow, title, text, steps (array di title/description), cta_label, cta_link,
 *       profile (array: name, zone, badge, checks = array di stringhe, cta_label).
 *
 * [ATTENZIONE] La card profilo e' un esempio, non una persona reale: niente valutazioni medie
 * ne' numeri di lavori svolti (nel Figma "4,9 - 128 lavori a Roma"). Vale la decisione del 17/10
 * sui numeri dell'offerta: con la base attuale racconterebbero la debolezza, non la copertura.
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'   => '',
	'title'     => '',
	'text'      => '',
	'steps'     => array(),
	'cta_label' => '',
	'cta_link'  => '',
	'profile'   => array(),
) );
$steps = array_values( array_filter( (array) $args['steps'], function ( $s ) {
	return ! empty( $s['title'] );
} ) );
if ( ! $args['title'] && ! $steps ) {
	return;
}
$profile = wp_parse_args( (array) $args['profile'], array(
	'name'      => '',
	'zone'      => '',
	'badge'     => '',
	'checks'    => array(),
	'cta_label' => '',
) );
$iniziale = $profile['name'] ? mb_substr( trim( $profile['name'] ), 0, 1 ) : '';
?>
<section class="verified" id="verificati" data-reveal>
	<div class="verified__inner">

		<div class="verified__left">
			<?php if ( $args['eyebrow'] ) : ?>
				<p class="verified__eyebrow"><span class="verified__dot" aria-hidden="true"></span><?php echo esc_html( $args['eyebrow'] ); ?></p>
			<?php endif; ?>

			<?php if ( $args['title'] ) : ?>
				<h2 class="verified__title"><?php echo esc_html( $args['title'] ); ?></h2>
			<?php endif; ?>

			<?php if ( $args['text'] ) : ?>
				<p class="verified__text"><?php echo esc_html( $args['text'] ); ?></p>
			<?php endif; ?>

			<?php if ( $steps ) : ?>
				<ul class="verified__steps">
					<?php foreach ( $steps as $i => $step ) : ?>
						<li class="verified__step" style="--i:<?php echo (int) $i; ?>">
							<span class="verified__check" aria-hidden="true">&#10003;</span>
							<span class="verified__step-text">
								<strong><?php echo esc_html( $step['title'] ); ?></strong>
								<?php if ( ! empty( $step['description'] ) ) : ?>
									<span><?php echo esc_html( $step['description'] ); ?></span>
								<?php endif; ?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( $args['cta_label'] && $args['cta_link'] ) : ?>
				<a class="verified__cta" href="<?php echo esc_url( $args['cta_link'] ); ?>">
					<span><?php echo esc_html( $args['cta_label'] ); ?></span>
					<span class="verified__arrow" aria-hidden="true">&rarr;</span>
				</a>
			<?php endif; ?>
		</div>

		<?php if ( $profile['name'] ) : ?>
			<article class="verified__card">
				<header class="verified__card-head">
					<span class="verified__avatar" aria-hidden="true"><?php echo esc_html( $iniziale ); ?></span>
					<span class="verified__identity">
						<strong><?php echo esc_html( $profile['name'] ); ?></strong>
						<?php if ( $profile['zone'] ) : ?><span><?php echo esc_html( $profile['zone'] ); ?></span><?php endif; ?>
					</span>
					<?php if ( $profile['badge'] ) : ?>
						<span class="verified__badge"><?php echo esc_html( $profile['badge'] ); ?></span>
					<?php endif; ?>
				</header>

				<?php if ( $profile['checks'] ) : ?>
					<ul class="verified__card-checks">
						<?php foreach ( (array) $profile['checks'] as $voce ) : ?>
							<li><span aria-hidden="true">&#10003;</span><?php echo esc_html( $voce ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( $profile['cta_label'] ) : ?>
					<p class="verified__card-note"><?php echo esc_html( $profile['cta_label'] ); ?></p>
				<?php endif; ?>
			</article>
		<?php endif; ?>

	</div>
</section>
