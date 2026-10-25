<?php
/**
 * Sezione "Confrontiamo tutta Roma" — Figma 4364:6697 (desktop) e 4364:7154 (mobile), testi dal live.
 * Due colonne: a sinistra eyebrow a pillola, titolo, sottotitolo (varia per variante), pilastri con tile
 * verde e CTA; a destra la card "Preventivi a confronto" con tre offerte d'esempio, il riquadro del team,
 * il disclaimer e la nota sul prezzo. Su mobile l'ordine e' quello del Figma: testo, card, pilastri, CTA
 * (grid-template-areas in style.css).
 *
 * Le offerte sono un esempio illustrativo dichiarato in pagina (nomi non reali): il campo
 * `disclaimer` non va svuotato. Senza offerte la card non esce, i pilastri si'.
 *
 * Args: eyebrow, title, subtitle, pillars[] {title, description}, cta_label, cta_link, card_title,
 * card_disclaimer, card_chip, offers[] {logo, name, badge, price, highlighted}, team {image, eyebrow,
 * title, sub}, note.
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'         => '',
	'title'           => '',
	'subtitle'        => '',
	'pillars'         => array(),
	'cta_label'       => '',
	'cta_link'        => '#form',
	'card_title'      => '',
	'card_disclaimer' => '',
	'card_chip'       => '',
	'offers'          => array(),
	'team'            => array(),
	'note'            => '',
) );
$offers = array_values( array_filter( (array) $args['offers'], function ( $o ) {
	return ! empty( $o['name'] );
} ) );
$team   = wp_parse_args( (array) $args['team'], array( 'image' => '', 'eyebrow' => '', 'title' => '', 'sub' => '' ) );
?>
<section class="sg-compare" id="perche">
	<div class="sg-compare__inner">
		<div class="sg-compare__head" data-reveal>
			<?php if ( $args['eyebrow'] ) : ?>
				<p class="sg-eyebrow sg-eyebrow--pill"><?php echo esc_html( $args['eyebrow'] ); ?></p>
			<?php endif; ?>
			<?php if ( $args['title'] ) : ?>
				<h2><?php echo nl2br( esc_html( $args['title'] ) ); ?></h2>
			<?php endif; ?>
			<?php if ( $args['subtitle'] ) : ?>
				<p class="sg-compare__subtitle"><?php echo esc_html( $args['subtitle'] ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( $args['pillars'] || $args['cta_label'] ) : ?>
		<div class="sg-compare__pillars" data-reveal>
			<?php if ( $args['pillars'] ) : ?>
			<ul class="sg-pillars">
				<?php foreach ( $args['pillars'] as $i => $pillar ) : ?>
					<li class="sg-pillars__item" style="--i:<?php echo (int) $i; ?>">
						<span class="sg-pillars__tile"><?php echo tp_icon_span( 'assets/icons/ic-check.svg', 'sg-pillars__check' ); ?></span>
						<span class="sg-pillars__text">
							<span class="sg-pillars__title"><?php echo esc_html( $pillar['title'] ?? '' ); ?></span>
							<?php if ( ! empty( $pillar['description'] ) ) : ?>
								<span class="sg-pillars__desc"><?php echo esc_html( $pillar['description'] ); ?></span>
							<?php endif; ?>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>
			<?php if ( $args['cta_label'] ) : ?>
				<a class="tp-btn tp-btn--primary sg-cta" href="<?php echo esc_url( $args['cta_link'] ); ?>"><?php echo esc_html( $args['cta_label'] ); ?> <span class="sg-cta__arrow" aria-hidden="true">&rarr;</span></a>
			<?php endif; ?>
		</div>
		<?php endif; ?>

		<?php if ( $offers ) : ?>
		<div class="sg-compare__card" data-reveal>
			<div class="sg-compare__card-head">
				<div>
					<p class="sg-compare__card-title"><?php echo esc_html( $args['card_title'] ); ?></p>
					<?php if ( $args['card_disclaimer'] ) : ?>
						<p class="sg-compare__card-disclaimer"><?php echo esc_html( $args['card_disclaimer'] ); ?></p>
					<?php endif; ?>
				</div>
				<?php if ( $args['card_chip'] ) : ?>
					<span class="sg-chip"><?php echo esc_html( $args['card_chip'] ); ?></span>
				<?php endif; ?>
			</div>

			<ul class="sg-offers">
				<?php foreach ( $offers as $offer ) : ?>
					<li class="sg-offers__row<?php echo ! empty( $offer['highlighted'] ) ? ' is-best' : ''; ?>">
						<?php if ( ! empty( $offer['logo'] ) ) : ?>
							<img class="sg-offers__logo" src="<?php echo esc_url( $offer['logo'] ); ?>" alt="" width="36" height="36" loading="lazy" decoding="async">
						<?php endif; ?>
						<span class="sg-offers__text">
							<span class="sg-offers__name"><?php echo esc_html( $offer['name'] ); ?></span>
							<?php if ( ! empty( $offer['badge'] ) ) : ?>
								<span class="sg-offers__badge"><?php echo esc_html( $offer['badge'] ); ?></span>
							<?php endif; ?>
						</span>
						<span class="sg-offers__price"><?php echo esc_html( $offer['price'] ?? '' ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>

			<?php if ( $team['title'] ) : ?>
			<div class="sg-team">
				<?php if ( $team['image'] ) : ?>
					<img class="sg-team__img" src="<?php echo esc_url( $team['image'] ); ?>" alt="" width="74" height="52" loading="lazy" decoding="async">
				<?php endif; ?>
				<span class="sg-team__text">
					<?php if ( $team['eyebrow'] ) : ?><span class="sg-team__eyebrow"><?php echo esc_html( $team['eyebrow'] ); ?></span><?php endif; ?>
					<span class="sg-team__title"><?php echo esc_html( $team['title'] ); ?></span>
					<?php if ( $team['sub'] ) : ?><span class="sg-team__sub"><?php echo esc_html( $team['sub'] ); ?></span><?php endif; ?>
				</span>
			</div>
			<?php endif; ?>

			<?php if ( $args['note'] ) : ?>
				<p class="sg-compare__note"><?php echo tp_icon_span( 'assets/icons/ic-check.svg', 'sg-compare__note-check' ); ?><span><?php echo esc_html( $args['note'] ); ?></span></p>
			<?php endif; ?>
		</div>
		<?php endif; ?>
	</div>
</section>
