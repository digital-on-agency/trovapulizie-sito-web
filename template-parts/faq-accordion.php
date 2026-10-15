<?php
/**
 * Sezione "FAQ" — Figma 3564:134 (mobile 4024:1007). Args: title, items (campo complex `faq_items`: question, answer),
 * theme_uri. Accordion: una domanda aperta per volta (la prima all'avvio), apertura con grid-template-rows 0fr -> 1fr e
 * chevron che ruota (assets/js/faq-accordion.js); reveal delle righe in stagger da 60 ms. Emette anche il JSON-LD
 * FAQPage per i motori di ricerca. Riutilizzabile nelle landing.
 */
$args = wp_parse_args( $args ?? array(), array(
	'title'     => '',
	'items'     => array(),
	'theme_uri' => get_stylesheet_directory_uri(),
) );
$items = array_values( array_filter( (array) $args['items'], function ( $f ) {
	return ! empty( $f['question'] ) && ! empty( $f['answer'] );
} ) );
if ( ! $items ) {
	return;
}
$uid    = 'faq-' . wp_unique_id();
$schema = array(
	'@context'   => 'https://schema.org',
	'@type'      => 'FAQPage',
	'mainEntity' => array_map( function ( $f ) {
		return array(
			'@type'          => 'Question',
			'name'           => wp_strip_all_tags( $f['question'] ),
			'acceptedAnswer' => array( '@type' => 'Answer', 'text' => wp_strip_all_tags( $f['answer'] ) ),
		);
	}, $items ),
);
?>
<section class="faq" id="faq" data-reveal>
	<div class="faq__inner">
		<?php if ( $args['title'] ) : ?><h2 class="faq__title"><?php echo esc_html( $args['title'] ); ?></h2><?php endif; ?>
		<div class="faq__list">
			<?php foreach ( $items as $i => $f ) : $open = 0 === $i; ?>
			<div class="faq__item<?php echo $open ? ' is-open' : ''; ?>" style="--i:<?php echo (int) $i; ?>">
				<h3 class="faq__q-wrap">
					<button type="button" class="faq__q" id="<?php echo $uid; ?>-q-<?php echo $i; ?>" aria-expanded="<?php echo $open ? 'true' : 'false'; ?>" aria-controls="<?php echo $uid; ?>-a-<?php echo $i; ?>">
						<span class="faq__q-text"><?php echo esc_html( $f['question'] ); ?></span>
						<span class="faq__chevron" aria-hidden="true"></span>
					</button>
				</h3>
				<div class="faq__a" id="<?php echo $uid; ?>-a-<?php echo $i; ?>" role="region" aria-labelledby="<?php echo $uid; ?>-q-<?php echo $i; ?>">
					<div class="faq__a-inner">
						<p><?php echo nl2br( esc_html( $f['answer'] ) ); ?></p>
					</div>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
	<script type="application/ld+json"><?php echo wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); ?></script>
</section>
