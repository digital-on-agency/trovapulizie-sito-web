<?php
/**
 * "Il team" — Chi siamo (Figma 6795:1065; TICKET-68). Testa centrata, due card grandi con la foto (fondatori) e due
 * card compatte su fondo menta con l'iniziale al posto della foto. Una card con foto diventa grande, una senza
 * resta compatta: aggiungendo la foto di un membro del team di prodotto la sua card cresce da sola.
 * Sotto 1024px le card grandi mettono la foto sopra il testo; sotto 640px tutto in colonna.
 *
 * Args: eyebrow, title, subtitle, people[] {name, role, bio, photo (URL), alt}. Senza persone non esce.
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'  => '',
	'title'    => '',
	'subtitle' => '',
	'people'   => array(),
) );
$people = array_values( array_filter( (array) $args['people'], function ( $p ) {
	return ! empty( $p['name'] );
} ) );
if ( ! $people ) {
	return;
}
$big   = array_values( array_filter( $people, function ( $p ) { return ! empty( $p['photo'] ); } ) );
$small = array_values( array_filter( $people, function ( $p ) { return empty( $p['photo'] ); } ) );
?>
<section class="ab-team" id="team" data-reveal>
	<div class="ab-team__inner">
		<div class="sg-section-head">
			<?php if ( $args['eyebrow'] ) : ?><p class="ab-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p><?php endif; ?>
			<?php if ( $args['title'] ) : ?><h2><?php echo esc_html( $args['title'] ); ?></h2><?php endif; ?>
			<?php if ( $args['subtitle'] ) : ?><p class="sg-section-head__sub"><?php echo esc_html( $args['subtitle'] ); ?></p><?php endif; ?>
		</div>
		<?php foreach ( array( 'big' => $big, 'small' => $small ) as $kind => $group ) :
			if ( ! $group ) {
				continue;
			} ?>
			<ul class="ab-team__row ab-team__row--<?php echo esc_attr( $kind ); ?>">
				<?php foreach ( $group as $i => $p ) : ?>
					<li class="ab-team__card ab-team__card--<?php echo esc_attr( $kind ); ?>" style="--i:<?php echo (int) $i; ?>">
						<?php if ( 'big' === $kind ) : ?>
							<img class="ab-team__photo" src="<?php echo esc_url( $p['photo'] ); ?>" alt="<?php echo esc_attr( $p['alt'] ?? $p['name'] ); ?>" width="220" height="264" loading="lazy" decoding="async">
						<?php else : ?>
							<span class="ab-team__initial" aria-hidden="true"><?php echo esc_html( mb_substr( $p['name'], 0, 1 ) ); ?></span>
						<?php endif; ?>
						<div class="ab-team__body">
							<h3 class="ab-team__name"><?php echo esc_html( $p['name'] ); ?></h3>
							<?php if ( ! empty( $p['role'] ) ) : ?><p class="ab-team__role"><?php echo esc_html( $p['role'] ); ?></p><?php endif; ?>
							<?php if ( ! empty( $p['bio'] ) ) : ?><p class="ab-team__bio"><?php echo esc_html( $p['bio'] ); ?></p><?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endforeach; ?>
	</div>
</section>
