<?php
/**
 * Sezione "FAQ" — Figma 6375:1291 (layout B2B adottato per la Home il 15/10; versione precedente 3564:134).
 * Args: eyebrow, title, subtitle, items (campo complex `faq_items`: question, answer), contact (array: people, role,
 * avatar_1, avatar_2, title, text, cta_label, cta_link), theme_uri.
 * Colonna sinistra: pillola, titolo, testo, card di contatto con i fondatori. Colonna destra: accordion, una domanda
 * aperta per volta (la prima all'avvio), apertura con grid-template-rows 0fr -> 1fr, segno + / - (faq-accordion.js);
 * reveal in stagger da 60 ms. JSON-LD FAQPage. Riutilizzabile nelle landing.
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'   => '',
	'title'     => '',
	'subtitle'  => '',
	'items'     => array(),
	'contact'   => array(),
) );
$items = array_values( array_filter( (array) $args['items'], function ( $f ) {
	return ! empty( $f['question'] ) && ! empty( $f['answer'] );
} ) );
if ( ! $items ) {
	return;
}
$contact   = wp_parse_args( (array) $args['contact'], array(
	'people'    => '',
	'role'      => '',
	'avatar_1'  => '',
	'avatar_2'  => '',
	'title'     => '',
	'text'      => '',
	'cta_label' => '',
	'cta_link'  => '',
) );
$has_contact = $contact['title'] || $contact['text'] || $contact['cta_label'];
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
		<div class="faq__aside">
			<?php if ( $args['eyebrow'] ) : ?><span class="faq__eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></span><?php endif; ?>
			<?php if ( $args['title'] ) : ?><h2 class="faq__title"><?php echo esc_html( $args['title'] ); ?></h2><?php endif; ?>
			<?php if ( $args['subtitle'] ) : ?><p class="faq__subtitle"><?php echo esc_html( $args['subtitle'] ); ?></p><?php endif; ?>
			<?php if ( $has_contact ) : ?>
			<aside class="faq__contact">
				<?php if ( $contact['people'] || $contact['role'] ) : ?>
				<div class="faq__founders">
					<div class="faq__avatars" aria-hidden="true">
						<img class="faq__avatar" src="<?php echo esc_url( $contact['avatar_1'] ?: tp_theme_asset( 'assets/img/founder-guido.png' ) ); ?>" alt="" width="46" height="46" loading="lazy">
						<img class="faq__avatar" src="<?php echo esc_url( $contact['avatar_2'] ?: tp_theme_asset( 'assets/img/founder-christian.png' ) ); ?>" alt="" width="46" height="46" loading="lazy">
					</div>
					<p class="faq__founders-text"><?php if ( $contact['people'] ) : ?><strong><?php echo esc_html( $contact['people'] ); ?></strong><?php endif; ?><?php if ( $contact['role'] ) : ?><span><?php echo esc_html( $contact['role'] ); ?></span><?php endif; ?></p>
				</div>
				<?php endif; ?>
				<?php if ( $contact['title'] ) : ?><p class="faq__contact-title"><?php echo esc_html( $contact['title'] ); ?></p><?php endif; ?>
				<?php if ( $contact['text'] ) : ?><p class="faq__contact-text"><?php echo esc_html( $contact['text'] ); ?></p><?php endif; ?>
				<?php if ( $contact['cta_label'] && $contact['cta_link'] ) : ?><a class="faq__contact-cta" href="<?php echo esc_url( $contact['cta_link'] ); ?>"><span><?php echo esc_html( $contact['cta_label'] ); ?></span><span aria-hidden="true">&rarr;</span></a><?php endif; ?>
			</aside>
			<?php endif; ?>
		</div>
		<div class="faq__list">
			<?php foreach ( $items as $i => $f ) : $open = 0 === $i; ?>
			<div class="faq__item<?php echo $open ? ' is-open' : ''; ?>" style="--i:<?php echo (int) $i; ?>">
				<h3 class="faq__q-wrap">
					<button type="button" class="faq__q" id="<?php echo $uid; ?>-q-<?php echo $i; ?>" aria-expanded="<?php echo $open ? 'true' : 'false'; ?>" aria-controls="<?php echo $uid; ?>-a-<?php echo $i; ?>">
						<span class="faq__q-text"><?php echo esc_html( $f['question'] ); ?></span>
						<span class="faq__toggle" aria-hidden="true"></span>
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
