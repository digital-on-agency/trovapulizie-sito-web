<?php
/**
 * Smistamento a card — pagina Contatti (Figma 6803:909; TICKET-63). Testa di sezione centrata e quattro card
 * affiancate: icona, titolo, testo e un link in fondo, allineato fra le card. La card `dark` e' quella del
 * contatto vero (cliente che ha gia' prenotato). Sotto 1024px due colonne, sotto 640px una.
 *
 * Args: eyebrow, title (H1 della pagina), subtitle, cards[] {icon ('home' | 'building' | 'user-plus' | 'chat'),
 * title, text, link_label, link, cta_position, dark (bool)}.
 * L'area cliccabile e' l'intera card (::after del link), il testo resta selezionabile solo via tastiera/lettore.
 * Il click e' tracciato da tp-track.js: marketing_cta_click con cta_position, marketing_click_whatsapp sui wa.me.
 * Senza card la sezione non esce.
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'  => '',
	'title'    => '',
	'subtitle' => '',
	'cards'    => array(),
) );
$cards = array_values( array_filter( (array) $args['cards'], function ( $c ) {
	return ! empty( $c['title'] ) && ! empty( $c['link'] );
} ) );
if ( ! $cards ) {
	return;
}
// Icone a tratto 24x24 dal Figma (stesso disegno delle icone lucide), colore da currentColor.
$icons = array(
	'home'      => '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z"/>',
	'building'  => '<rect x="4" y="3" width="16" height="18" rx="1"/><path d="M9 21v-4h6v4M8 7h2M14 7h2M8 11h2M14 11h2"/>',
	'user-plus' => '<circle cx="9" cy="8" r="4"/><path d="M2 21v-1a6 6 0 0 1 6-6h2a6 6 0 0 1 6 6v1M19 8v6M16 11h6"/>',
	'chat'      => '<path d="M21 12a8.5 8.5 0 0 1-12.6 7.4L3 21l1.6-5.2A8.5 8.5 0 1 1 21 12z"/>',
);
?>
<section class="ct-route" id="hero" data-reveal>
	<div class="ct-route__inner">
		<div class="ct-route__head">
			<?php if ( $args['eyebrow'] ) : ?><p class="ct-route__eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p><?php endif; ?>
			<?php if ( $args['title'] ) : ?><h1 class="ct-route__title"><?php echo esc_html( $args['title'] ); ?></h1><?php endif; ?>
			<?php if ( $args['subtitle'] ) : ?><p class="ct-route__sub"><?php echo esc_html( $args['subtitle'] ); ?></p><?php endif; ?>
		</div>
		<ul class="ct-route__cards">
			<?php foreach ( $cards as $i => $c ) :
				$dark   = ! empty( $c['dark'] );
				$icon   = $icons[ $c['icon'] ?? '' ] ?? $icons['chat'];
				$link   = (string) $c['link'];
				$is_ext = 0 === strpos( $link, 'http' ) && false === strpos( $link, home_url() );
				?>
				<li class="ct-route__card<?php echo $dark ? ' ct-route__card--dark' : ''; ?>" style="--i:<?php echo (int) $i; ?>">
					<span class="ct-route__icon" aria-hidden="true">
						<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?php echo $icon; // phpcs:ignore -- markup fisso definito sopra ?></svg>
					</span>
					<h2 class="ct-route__card-title"><?php echo esc_html( $c['title'] ); ?></h2>
					<?php if ( ! empty( $c['text'] ) ) : ?><p class="ct-route__text"><?php echo esc_html( $c['text'] ); ?></p><?php endif; ?>
					<a class="ct-route__link" href="<?php echo esc_url( $link ); ?>"<?php if ( ! empty( $c['cta_position'] ) ) : ?> data-cta-position="<?php echo esc_attr( $c['cta_position'] ); ?>"<?php endif; ?><?php if ( $is_ext ) : ?> target="_blank" rel="noopener"<?php endif; ?>><?php echo esc_html( $c['link_label'] ?: 'Vai' ); ?> <span class="ct-route__arrow" aria-hidden="true">&rarr;</span></a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
