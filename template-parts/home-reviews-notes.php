<?php
/**
 * Recensioni a foglietti e fascia loghi della Home v2, `06-recensioni` (SPEC-homepage-v2 US-03, TICKET-124). Figma file
 * UiW4TPgvqCODW5bhyFV5eK, pagina 30: desktop "Chi ci ha già scelto · foglietti" 7903:7496 (1440 x ~1056), mobile 7946:1871
 * (nel frame bianco 7946:2). Scritta, titolo, foglietti (foto prima e dopo facoltative, stelle, testo, autore con zona e
 * servizio) e, sotto, la fascia "Lavoriamo con imprese e cleaner verificati" che scorre.
 *
 * [BLOCCO] REGOLA DI CONTENUTO. Questo template non ha nessun contenuto di default: ne' recensioni ne' loghi. Le
 * recensioni sono solo vere, scritte da clienti reali e con il loro consenso; le foto prima e dopo solo vere e con
 * consenso; le immagini del Figma sono segnaposto e non vanno nel tema. I loghi di Igea Clean Service, Ready Solution e
 * Disinfestazione Veloce si caricano solo con autorizzazione scritta dell'impresa (SPEC §6); le foto dei cleaner solo con
 * il loro consenso. Nessun dato strutturato: ne' JSON-LD Review ne' AggregateRating, in nessun caso.
 *
 * COMPORTAMENTO A CAMPI VUOTI (nessun buco, nessun segnaposto in pagina):
 * - nessuna recensione valida e nessun logo valido: il template non stampa nulla (`return;`);
 * - recensioni valide, nessun logo: solo la sezione coi foglietti, senza fascia;
 * - nessuna recensione, loghi validi: solo la fascia (un <section> a se', data-section="06-loghi", senza titolo ne' foglietti);
 * - entrambi: sezione `06-recensioni` con la fascia in fondo.
 * Una recensione e' valida con testo e nome; una voce della fascia con un'immagine. Le foto prima e dopo escono solo
 * insieme; senza foto il foglietto mostra testo, nome, zona e servizio (l'altezza e' uguale a quella degli altri: la griglia
 * li stira). Zona e servizio, se vuoti, non lasciano il separatore.
 *
 * Argomenti (le letture Carbon stanno in page-home-v2.php; tutti con default):
 * - title (string): titolo H2, maiuscolo da CSS. Default "Non lo diciamo solo noi."
 * - eyebrow (string): scritta sopra il titolo. Default "Chi ci ha già scelto".
 * - items (array, array()): righe { text, name, zone, service, stars (1-5, default 5), photo_before, photo_after, avatar } (URL).
 * - logos_label (string): scritta a sinistra della fascia. Default "Lavoriamo con imprese e cleaner verificati".
 * - logos (array, array()): righe { name, image (URL), kind ('logo' | 'cleaner': il cleaner ha la spunta verde) }.
 *
 * Componenti riusati, nessuno nuovo: tp-carousel (foglietti: griglia da 1024 px, carosello con indicatore "N di M" sotto
 * 1024 px, come Come funziona) e tp-marquee (fascia loghi, con `items_html`). Loop in pausa fuori viewport: tp-loops.js.
 * Movimento: ingresso della testata e della fascia con data-reveal; i foglietti scendono con rimbalzo quando entra la testata (CSS, blocco TICKET-129). Al passaggio del mouse il foglietto si raddrizza e si alza di 6 px (250 ms, solo transform, ferma con riduci movimento).
 * CSS: blocco "TICKET-124" in fondo a style.css. Nessuna CTA: nessun data-cta-position.
 *
 * Esempio: get_template_part( 'template-parts/home-reviews-notes', null, array( 'items' => array( array( 'text' => '...', 'name' => 'Laura B.' ) ) ) );
 */
$args = wp_parse_args( $args ?? array(), array(
	'title'       => '',
	'eyebrow'     => '',
	'items'       => array(),
	'logos_label' => '',
	'logos'       => array(),
) );
$hv2r_pick = static function ( $valore, $default ) {
	$valore = trim( (string) $valore );
	return '' !== $valore ? $valore : $default;
};

// Recensioni valide: testo e nome compilati.
$hv2r_notes = array();
foreach ( (array) $args['items'] as $hv2r_row ) {
	if ( ! is_array( $hv2r_row ) ) {
		continue;
	}
	$hv2r_text = trim( (string) ( $hv2r_row['text'] ?? '' ) );
	$hv2r_name = trim( (string) ( $hv2r_row['name'] ?? '' ) );
	if ( '' === $hv2r_text || '' === $hv2r_name ) {
		continue;
	}
	$hv2r_before = trim( (string) ( $hv2r_row['photo_before'] ?? '' ) );
	$hv2r_after  = trim( (string) ( $hv2r_row['photo_after'] ?? '' ) );
	$hv2r_stars  = (int) ( $hv2r_row['stars'] ?? 5 );
	$hv2r_notes[] = array(
		'text'    => $hv2r_text,
		'name'    => $hv2r_name,
		'meta'    => implode( ' · ', array_filter( array( trim( (string) ( $hv2r_row['zone'] ?? '' ) ), trim( (string) ( $hv2r_row['service'] ?? '' ) ) ), 'strlen' ) ),
		'stars'   => $hv2r_stars >= 1 && $hv2r_stars <= 5 ? $hv2r_stars : 5,
		'before'  => '' !== $hv2r_before && '' !== $hv2r_after ? $hv2r_before : '',
		'after'   => '' !== $hv2r_before && '' !== $hv2r_after ? $hv2r_after : '',
		'avatar'  => trim( (string) ( $hv2r_row['avatar'] ?? '' ) ),
	);
}

// Voci della fascia: HTML gia' escluso, una per riga con immagine. Prima la scritta, che si ripete a ogni giro.
$hv2r_label = $hv2r_pick( $args['logos_label'], 'Lavoriamo con imprese e cleaner verificati' );
$hv2r_logos = array();
foreach ( (array) $args['logos'] as $hv2r_row ) {
	$hv2r_img = is_array( $hv2r_row ) ? trim( (string) ( $hv2r_row['image'] ?? '' ) ) : '';
	if ( '' === $hv2r_img ) {
		continue;
	}
	$hv2r_alt = tp_img_alt( $hv2r_img, trim( (string) ( $hv2r_row['name'] ?? '' ) ) );
	if ( 'cleaner' === ( $hv2r_row['kind'] ?? 'logo' ) ) {
		$hv2r_logos[] = '<span class="hv2-rev__cleaner"><img src="' . esc_url( $hv2r_img ) . '" alt="' . esc_attr( $hv2r_alt ) . '" width="52" height="52" loading="lazy" decoding="async"><span class="hv2-rev__verified">' . tp_icon_span( 'assets/icons/ic-check.svg', 'hv2-rev__check' ) . '<span class="screen-reader-text"> cleaner verificato</span></span></span>';
	} else {
		$hv2r_logos[] = '<span class="hv2-rev__logo"><img src="' . esc_url( $hv2r_img ) . '" alt="' . esc_attr( $hv2r_alt ) . '" height="44" loading="lazy" decoding="async"></span>';
	}
}

if ( ! $hv2r_notes && ! $hv2r_logos ) {
	return;
}

// Fascia: stessa markup per i due casi (con o senza foglietti). Larghezza stimata del gruppo e velocita' costante (~45 px/s).
$hv2r_band = '';
if ( $hv2r_logos ) {
	$hv2r_est    = 400 + count( $hv2r_logos ) * 160;
	$hv2r_items  = array_merge( array( '<span class="hv2-rev__band-label">' . esc_html( $hv2r_label ) . '</span>' ), $hv2r_logos );
	ob_start();
	?>
	<div class="hv2-rev__band" data-reveal>
		<?php
		get_template_part( 'template-parts/tp-marquee', null, array(
			'items_html' => $hv2r_items,
			'dots'       => false,
			'duration'   => max( 12, round( $hv2r_est / 45 ) ),
			'repeat'     => max( 3, min( 8, (int) ceil( 2560 / $hv2r_est ) + 1 ) ),
			'class'      => 'hv2-rev__marquee',
		) );
		?>
	</div>
	<?php
	$hv2r_band = ob_get_clean();
}

if ( ! $hv2r_notes ) :
	// Solo la fascia loghi: una sezione a parte, senza titolo.
	?>
<section class="hv2-section hv2-section--recensioni hv2-rev hv2-rev--solo-loghi" data-section="06-loghi" aria-label="<?php echo esc_attr( $hv2r_label ); ?>">
	<?php echo $hv2r_band; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML costruito sopra con escape ?>
</section>
	<?php
	return;
endif;

$hv2r_rot    = array( '3deg', '-2deg', '1.5deg' );
$hv2r_slides = array();
foreach ( $hv2r_notes as $hv2r_i => $hv2r_n ) {
	$hv2r_parts = preg_split( '/\s+/u', $hv2r_n['name'] );
	$hv2r_init  = '';
	foreach ( array_slice( $hv2r_parts, 0, 2 ) as $hv2r_p ) {
		$hv2r_init .= mb_strtoupper( mb_substr( $hv2r_p, 0, 1 ) );
	}
	ob_start();
	?>
	<article class="hv2-rev__note<?php echo '' !== $hv2r_n['before'] ? ' has-photos' : ''; ?>" style="--hv2-rev-rot: <?php echo esc_attr( $hv2r_rot[ $hv2r_i % 3 ] ); ?>">
		<?php if ( '' !== $hv2r_n['before'] ) : ?>
			<div class="hv2-rev__photos">
				<figure class="hv2-rev__photo">
					<img src="<?php echo esc_url( $hv2r_n['before'] ); ?>" alt="<?php echo esc_attr( tp_img_alt( $hv2r_n['before'], 'Prima della pulizia' ) ); ?>" width="190" height="150" loading="lazy" decoding="async" sizes="(max-width: 1023px) 140px, 190px">
					<figcaption class="hv2-rev__tag hv2-rev__tag--before" aria-hidden="true">Prima</figcaption>
				</figure>
				<figure class="hv2-rev__photo">
					<img src="<?php echo esc_url( $hv2r_n['after'] ); ?>" alt="<?php echo esc_attr( tp_img_alt( $hv2r_n['after'], 'Dopo la pulizia' ) ); ?>" width="190" height="150" loading="lazy" decoding="async" sizes="(max-width: 1023px) 140px, 190px">
					<figcaption class="hv2-rev__tag hv2-rev__tag--after" aria-hidden="true">Dopo</figcaption>
				</figure>
			</div>
		<?php endif; ?>
		<p class="hv2-rev__stars" role="img" aria-label="<?php echo esc_attr( sprintf( '%d stelle su 5', $hv2r_n['stars'] ) ); ?>"><?php echo esc_html( str_repeat( '★', $hv2r_n['stars'] ) ); ?><?php echo $hv2r_n['stars'] < 5 ? '<span class="hv2-rev__stars-off">' . esc_html( str_repeat( '★', 5 - $hv2r_n['stars'] ) ) . '</span>' : ''; ?></p>
		<blockquote class="hv2-rev__text"><p>“<?php echo esc_html( $hv2r_n['text'] ); ?>”</p></blockquote>
		<div class="hv2-rev__author">
			<?php if ( '' !== $hv2r_n['avatar'] ) : ?>
				<img class="hv2-rev__avatar" src="<?php echo esc_url( $hv2r_n['avatar'] ); ?>" alt="" width="40" height="40" loading="lazy" decoding="async">
			<?php else : ?>
				<span class="hv2-rev__avatar hv2-rev__avatar--initials" aria-hidden="true"><?php echo esc_html( $hv2r_init ); ?></span>
			<?php endif; ?>
			<p class="hv2-rev__who">
				<span class="hv2-rev__name"><?php echo esc_html( $hv2r_n['name'] ); ?></span>
				<?php if ( '' !== $hv2r_n['meta'] ) : ?>
					<span class="hv2-rev__meta"><?php echo esc_html( $hv2r_n['meta'] ); ?></span>
				<?php endif; ?>
			</p>
		</div>
	</article>
	<?php
	$hv2r_slides[] = ob_get_clean();
}
?>
<section class="hv2-section hv2-section--recensioni hv2-rev" data-section="06-recensioni">
	<span class="hv2-rev__light hv2-rev__light--a" aria-hidden="true"></span>
	<span class="hv2-rev__light hv2-rev__light--b" aria-hidden="true"></span>
	<div class="hv2-rev__inner">
		<div class="hv2-rev__head" data-reveal>
			<p class="hv2-rev__eyebrow"><?php echo esc_html( $hv2r_pick( $args['eyebrow'], 'Chi ci ha già scelto' ) ); ?></p>
			<h2 class="hv2-rev__title"><?php echo esc_html( $hv2r_pick( $args['title'], 'Non lo diciamo solo noi.' ) ); ?></h2>
		</div>
		<?php
		get_template_part( 'template-parts/tp-carousel', null, array(
			'slides'       => $hv2r_slides,
			'label'        => 'Recensioni dei clienti',
			'class'        => 'hv2-rev__carousel',
			'count_format' => '%1$d di %2$d',
			'dot_label'    => 'Vai alla recensione %d',
			'prev_label'   => 'Recensione precedente',
			'next_label'   => 'Recensione successiva',
		) );
		?>
	</div>
	<?php echo $hv2r_band; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML costruito sopra con escape ?>
</section>
