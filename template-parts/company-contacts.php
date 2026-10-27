<?php
/**
 * Canali diretti e dati aziendali — pagina Contatti (Figma 6803:953; TICKET-64). Due card affiancate: a sinistra i
 * canali (fondo menta: WhatsApp, email, orari, bottone), a destra la card bianca coi dati della societa'. Sotto
 * 1024px impilate, canali sopra. Stesso disegno del blocco "Dati aziendali" di Chi siamo (6795:1136).
 *
 * Args:
 *   channels {eyebrow, title, text, whatsapp (numero in qualunque formato, vuoto = riga e bottone nascosti),
 *             email (vuoto = riga nascosta), cta_label}
 *   company  {eyebrow, name, rows[] {title, text}}
 * Il link WhatsApp esce da tp_whatsapp_parts(): stesso numero per testo, wa.me e JSON-LD (inc/seo.php).
 * Il click sul wa.me e' tracciato da tp-track.js (marketing_click_whatsapp, cta_position).
 */
$args = wp_parse_args( $args ?? array(), array(
	'channels' => array(),
	'company'  => array(),
) );
$ch = wp_parse_args( (array) $args['channels'], array(
	'eyebrow'   => '',
	'title'     => '',
	'text'      => '',
	'whatsapp'  => '',
	'email'     => '',
	'cta_label' => '',
) );
$co = wp_parse_args( (array) $args['company'], array(
	'eyebrow' => '',
	'name'    => '',
	'rows'    => array(),
) );
$wa    = tp_whatsapp_parts( $ch['whatsapp'] );
$email = sanitize_email( $ch['email'] );
$rows  = array_values( array_filter( (array) $co['rows'], function ( $r ) {
	return ! empty( $r['title'] ) && ! empty( $r['text'] );
} ) );
if ( ! $wa && ! $email && ! $rows ) {
	return;
}
?>
<section class="ct-info" id="canali" data-reveal>
	<div class="ct-info__inner">
		<?php if ( $wa || $email ) : ?>
		<div class="ct-info__card ct-info__card--channels">
			<?php if ( $ch['eyebrow'] ) : ?><p class="ct-info__eyebrow"><?php echo esc_html( $ch['eyebrow'] ); ?></p><?php endif; ?>
			<?php if ( $ch['title'] ) : ?><h2 class="ct-info__title"><?php echo esc_html( $ch['title'] ); ?></h2><?php endif; ?>
			<?php if ( $ch['text'] ) : ?><p class="ct-info__text"><?php echo esc_html( $ch['text'] ); ?></p><?php endif; ?>
			<ul class="ct-info__channels">
				<?php if ( $wa ) : ?>
				<li class="ct-info__channel">
					<span class="ct-info__channel-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a8.5 8.5 0 0 1-12.6 7.4L3 21l1.6-5.2A8.5 8.5 0 1 1 21 12z"/></svg></span>
					<span class="ct-info__channel-text"><span>WhatsApp</span><a href="<?php echo esc_url( $wa['link'] ); ?>" target="_blank" rel="noopener" data-cta-position="contatti_canali_numero"><?php echo esc_html( $wa['display'] ); ?></a></span>
				</li>
				<?php endif; ?>
				<?php if ( $email ) : ?>
				<li class="ct-info__channel">
					<span class="ct-info__channel-icon" aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></span>
					<span class="ct-info__channel-text"><span>Email</span><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></span>
				</li>
				<?php endif; ?>
			</ul>
			<?php if ( $wa && $ch['cta_label'] ) : ?>
				<a class="ct-info__cta" href="<?php echo esc_url( $wa['link'] ); ?>" target="_blank" rel="noopener" data-cta-position="contatti_canali"><?php echo esc_html( $ch['cta_label'] ); ?></a>
			<?php endif; ?>
		</div>
		<?php endif; ?>
		<?php if ( $rows ) : ?>
		<div class="ct-info__card ct-info__card--company">
			<?php if ( $co['eyebrow'] ) : ?><p class="ct-info__eyebrow"><?php echo esc_html( $co['eyebrow'] ); ?></p><?php endif; ?>
			<?php if ( $co['name'] ) : ?><h2 class="ct-info__title"><?php echo esc_html( $co['name'] ); ?></h2><?php endif; ?>
			<dl class="ct-info__rows">
				<?php foreach ( $rows as $r ) : ?>
				<div class="ct-info__row"><dt><?php echo esc_html( $r['title'] ); ?></dt><dd><?php echo esc_html( $r['text'] ); ?></dd></div>
				<?php endforeach; ?>
			</dl>
		</div>
		<?php endif; ?>
	</div>
</section>
