<?php
/**
 * Fascia aziende della Home v2, `08-aziende` (SPEC-homepage-v2, TICKET-126). Figma file UiW4TPgvqCODW5bhyFV5eK, pagina 30:
 * desktop 7903:7601 (dentro 7903:5256), mobile 7946:2048 (dentro 7946:2). Riquadro scuro con foto d'ufficio e velo verde:
 * etichetta, titolo, frase, garanzie, bottone "Richiedi un preventivo" e link "Scopri le pulizie per aziende".
 *
 * Argomenti (tutti con default; le letture Carbon e la risoluzione degli URL stanno in tp_home_v2_b2b_args(),
 * inc/home-v2-b2b.php):
 * - eyebrow (string): scritta sopra il titolo. Default "Aziende e condomini".
 * - title (string): H2, maiuscolo da CSS. Default "Hai un ufficio, un condominio o un B&B?".
 * - text (string): frase sotto il titolo. Default "Ci dici cosa serve, ti arrivano i preventivi di imprese verificate.
 *   Senza rincorrere nessuno."
 * - badges (array|null): garanzie, una stringa per voce. null = "Sopralluogo gratuito" e "Imprese verificate"; array vuoto =
 *   nessuna garanzia. "Preventivo entro 24 ore" non e' un default (non confermato).
 * - cta_label (string) e cta_url (string): bottone verde. Senza cta_url il bottone non viene stampato.
 *   data-cta-position="home_b2b". Default etichetta "Richiedi un preventivo".
 * - link_label (string) e link_url (string): link secondario. Senza link_url il link non viene stampato.
 *   Default etichetta "Scopri le pulizie per aziende".
 * - image (string): URL della foto di fondo; vuoto = foto d'ufficio del tema (assets/img/home-v2/aziende-ufficio*.webp).
 *
 * Semantica: un H2, le garanzie in una lista, la foto e' decorativa (alt vuoto, lazy). Ingressi: data-reveal sul testo e
 * sulle azioni (--i). Ken burns della foto: TICKET-129 (non qui). Riduci movimento: nel blocco unico della v2.
 * CSS: blocco `TICKET-126` in fondo a style.css. JS: nessuno.
 *
 * Esempio: get_template_part( 'template-parts/home-b2b-band', null, array( 'cta_url' => home_url( '/roma/sgrosso-commerciale/' ) ) );
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'    => '',
	'title'      => '',
	'text'       => '',
	'badges'     => null,
	'cta_label'  => '',
	'cta_url'    => '',
	'link_label' => '',
	'link_url'   => '',
	'image'      => '',
) );
$hb_pick  = static function ( $valore, $default ) {
	$valore = trim( (string) $valore );
	return '' !== $valore ? $valore : $default;
};
$hb_badges = null === $args['badges'] ? tp_home_v2_b2b_default_badges() : array_values( array_filter( array_map( 'trim', array_map( 'strval', (array) $args['badges'] ) ) ) );
$hb_img    = trim( (string) $args['image'] );
$hb_cta    = trim( (string) $args['cta_url'] );
$hb_link   = trim( (string) $args['link_url'] );
?>
<section class="hv2-b2b" data-section="08-aziende" aria-labelledby="hv2-b2b-titolo">
	<span class="hv2-b2b__light hv2-b2b__light--a" aria-hidden="true"></span>
	<span class="hv2-b2b__light hv2-b2b__light--b" aria-hidden="true"></span>
	<div class="hv2-b2b__box">
		<picture class="hv2-b2b__photo" aria-hidden="true">
			<?php if ( '' === $hb_img ) : ?>
				<source media="(max-width: 1023px)" srcset="<?php echo esc_url( tp_theme_asset( 'assets/img/home-v2/aziende-ufficio-960.webp' ) ); ?>">
				<img src="<?php echo esc_url( tp_theme_asset( 'assets/img/home-v2/aziende-ufficio.webp' ) ); ?>" alt="" width="1600" height="893" loading="lazy" decoding="async">
			<?php else : ?>
				<img src="<?php echo esc_url( $hb_img ); ?>" alt="" loading="lazy" decoding="async">
			<?php endif; ?>
		</picture>
		<div class="hv2-b2b__text" data-reveal>
			<p class="hv2-b2b__eyebrow"><?php echo esc_html( $hb_pick( $args['eyebrow'], 'Aziende e condomini' ) ); ?></p>
			<h2 class="hv2-b2b__title" id="hv2-b2b-titolo"><?php echo esc_html( $hb_pick( $args['title'], 'Hai un ufficio, un condominio o un B&B?' ) ); ?></h2>
			<p class="hv2-b2b__lead"><?php echo esc_html( $hb_pick( $args['text'], 'Ci dici cosa serve, ti arrivano i preventivi di imprese verificate. Senza rincorrere nessuno.' ) ); ?></p>
			<?php if ( $hb_badges ) : ?>
				<ul class="hv2-b2b__badges">
					<?php foreach ( $hb_badges as $hb_b ) : ?>
						<li class="hv2-b2b__badge"><svg class="hv2-b2b__check" width="10" height="8" viewBox="0 0 12 9.5" fill="none" aria-hidden="true" focusable="false"><path d="M1 5l3.5 3.5L11 1" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><span><?php echo esc_html( $hb_b ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php if ( '' !== $hb_cta || '' !== $hb_link ) : ?>
			<div class="hv2-b2b__actions" data-reveal style="--i: 2">
				<?php if ( '' !== $hb_cta ) : ?>
					<a class="hv2-b2b__cta" href="<?php echo esc_url( $hb_cta ); ?>" data-cta-position="home_b2b"><span><?php echo esc_html( $hb_pick( $args['cta_label'], 'Richiedi un preventivo' ) ); ?></span><span aria-hidden="true">&rarr;</span></a>
				<?php endif; ?>
				<?php if ( '' !== $hb_link ) : ?>
					<a class="hv2-b2b__link" href="<?php echo esc_url( $hb_link ); ?>"><span><?php echo esc_html( $hb_pick( $args['link_label'], 'Scopri le pulizie per aziende' ) ); ?></span> <span aria-hidden="true">&rarr;</span></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
