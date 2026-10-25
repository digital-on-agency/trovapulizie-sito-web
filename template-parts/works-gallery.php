<?php
/**
 * Sezione "Lavori a Roma" — landing B2B (Figma 6781:954, TICKET-59). Confronto prima/dopo trascinabile con lo
 * stesso markup .sg-compare-media di scenario-tabs.php (lo inizializza scenario-tabs.js anche fuori dai tab), una
 * card di testo accanto con i due tag PRIMA / DOPO, poi una galleria di foto con didascalia. Testi dal live.
 *
 * Args: eyebrow, title, subtitle, id, compare {before, after, before_alt, after_alt, pill, title, text,
 * before_caption, after_caption}, gallery[] {image, caption}. Senza foto "dopo" il confronto non esce; senza
 * galleria resta solo il confronto.
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'  => '',
	'title'    => '',
	'subtitle' => '',
	'id'       => 'lavori',
	'compare'  => array(),
	'gallery'  => array(),
) );
$cmp = wp_parse_args( (array) $args['compare'], array(
	'before'         => '',
	'after'          => '',
	'before_alt'     => 'Prima dell\'intervento',
	'after_alt'      => 'Dopo l\'intervento',
	'pill'           => 'Trascina per confrontare',
	'title'          => '',
	'text'           => '',
	'before_caption' => '',
	'after_caption'  => '',
) );
$gallery = array_values( array_filter( (array) $args['gallery'], function ( $g ) {
	return ! empty( $g['image'] );
} ) );
if ( ! $cmp['after'] && ! $gallery ) {
	return;
}
?>
<section class="b2b-works" id="<?php echo esc_attr( $args['id'] ); ?>" data-reveal>
	<div class="b2b-works__inner">
		<div class="sg-section-head">
			<?php if ( $args['eyebrow'] ) : ?><p class="sg-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p><?php endif; ?>
			<?php if ( $args['title'] ) : ?><h2><?php echo esc_html( $args['title'] ); ?></h2><?php endif; ?>
			<?php if ( $args['subtitle'] ) : ?><p class="sg-section-head__sub"><?php echo esc_html( $args['subtitle'] ); ?></p><?php endif; ?>
		</div>

		<?php if ( $cmp['after'] ) : ?>
		<div class="b2b-works__compare">
			<div class="sg-compare-media b2b-works__media" data-compare style="--pos: 50%">
				<img class="sg-compare-media__after" src="<?php echo esc_url( $cmp['after'] ); ?>" alt="<?php echo esc_attr( $cmp['after_alt'] ); ?>" width="1000" height="700" loading="lazy" decoding="async">
				<?php if ( $cmp['before'] ) : ?>
					<img class="sg-compare-media__before" src="<?php echo esc_url( $cmp['before'] ); ?>" alt="<?php echo esc_attr( $cmp['before_alt'] ); ?>" width="1000" height="700" loading="lazy" decoding="async">
					<span class="sg-compare-media__divider" aria-hidden="true"></span>
					<span class="sg-compare-media__handle" aria-hidden="true">&lsaquo;&rsaquo;</span>
					<span class="sg-compare-media__label sg-compare-media__label--before" aria-hidden="true">PRIMA</span>
					<span class="sg-compare-media__label sg-compare-media__label--after" aria-hidden="true">DOPO</span>
					<input class="sg-compare-media__range" type="range" min="0" max="100" value="50" aria-label="Confronta prima e dopo">
				<?php endif; ?>
			</div>
			<aside class="b2b-works__card">
				<?php if ( $cmp['pill'] && $cmp['before'] ) : ?><p class="b2b-works__pill"><span aria-hidden="true">&lsaquo;&rsaquo;</span> <?php echo esc_html( $cmp['pill'] ); ?></p><?php endif; ?>
				<?php if ( $cmp['title'] ) : ?><h3 class="b2b-works__title"><?php echo esc_html( $cmp['title'] ); ?></h3><?php endif; ?>
				<?php if ( $cmp['text'] ) : ?><p class="b2b-works__text"><?php echo esc_html( $cmp['text'] ); ?></p><?php endif; ?>
				<?php if ( $cmp['before_caption'] || $cmp['after_caption'] ) : ?>
				<ul class="b2b-works__tags">
					<?php if ( $cmp['before_caption'] ) : ?><li><span class="b2b-tag b2b-tag--before">PRIMA</span><span><?php echo esc_html( $cmp['before_caption'] ); ?></span></li><?php endif; ?>
					<?php if ( $cmp['after_caption'] ) : ?><li><span class="b2b-tag b2b-tag--after">DOPO</span><span><?php echo esc_html( $cmp['after_caption'] ); ?></span></li><?php endif; ?>
				</ul>
				<?php endif; ?>
			</aside>
		</div>
		<?php endif; ?>

		<?php if ( $gallery ) : ?>
		<ul class="b2b-works__gallery">
			<?php foreach ( $gallery as $i => $g ) : ?>
				<li class="b2b-works__item" style="--i:<?php echo (int) $i; ?>">
					<img src="<?php echo esc_url( $g['image'] ); ?>" alt="<?php echo esc_attr( $g['caption'] ?? '' ); ?>" width="800" height="500" loading="lazy" decoding="async">
					<?php if ( ! empty( $g['caption'] ) ) : ?><p><?php echo esc_html( $g['caption'] ); ?></p><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>
	</div>
</section>
