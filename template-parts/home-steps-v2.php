<?php
/**
 * Sezione Come funziona della Home v2, `04-come-funziona` (SPEC-homepage-v2, TICKET-122). Figma file
 * UiW4TPgvqCODW5bhyFV5eK, pagina 30: desktop 7903:6508 (schede 7903:6513, 7903:7059, 7903:7170, 7903:7327), mobile
 * 7946:947 (nel frame bianco 7946:2). Scritta, titolo, sottotitolo, quattro schede (una per passo: foto a tutta scheda,
 * telefono con la schermata del passo, "N DI 4", titolo grande e frase) e un link di approfondimento.
 *
 * Comportamento: da 1024 px le schede si impilano (position: sticky con `top` crescente, la scheda sotto scorre sopra
 * la precedente, che si rimpicciolisce a 0,96: assets/js/home-steps-stack.js); sotto 1024 px sono un carosello a scorrimento
 * (template-parts/tp-carousel.php + assets/js/tp-carousel.js) con indicatore "N di M" annunciato. Senza JavaScript le
 * schede restano leggibili: pila sticky da 1024 px (solo CSS), scroll-snap sotto.
 *
 * L'id="come-funziona" sta sulla <section>: lo usano il bottone della hero e la voce della barra (#come-funziona) e il
 * CSS gli da' scroll-margin-top per non finire sotto la barra.
 *
 * Argomenti (le letture Carbon stanno in page-home-v2.php; tutti con default):
 * - title (string): titolo H2, in maiuscolo da CSS. Default "SPOILER: DUE MINUTI."
 * - eyebrow (string): scritta sopra il titolo. Default "Come funziona".
 * - subtitle (string): sottotitolo desktop. Default "Quattro passi. L’ultimo lo fai sul divano." (uguale al mobile)
 * - subtitle_mobile (string): sottotitolo sotto 1024 px (il frame mobile dice un'altra frase). Default "Quattro passi. L'ultimo lo fai sul divano."
 * - link_label (string) e link_url (string): link in fondo. Default etichetta "Vedi come funziona nel dettaglio"; senza URL il link
 *   non esce (nessuna destinazione e' decisa: SPEC §6) e, se l'URL e' una pagina interna, solo se pubblicata (tp_home_v2_service_href()).
 *   Porta data-cta-position="steps".
 * - items (array|null): righe { title, text, photo (URL), screen (URL) }. null (nessuna riga in Carbon) = i quattro passi di
 *   default. Un array e' l'elenco voluto: le righe senza titolo sono scartate e, se non ne resta nessuna, la sezione non
 *   viene stampata (zero passi). Foto o schermata vuote prendono l'immagine di default del passo con lo stesso numero
 *   d'ordine, se esiste; altrimenti la scheda esce senza.
 *
 * Immagini: WebP in assets/img/home-v2/step-N-bg.webp (foto a tutta scheda, con la variante -720 per il mobile) e
 * step-N-screen.webp (schermata dell'app ritagliata come nel Figma, 376 px = 2x) e step-N-screen-full.webp (la stessa schermata
 * intera con la barra in basso, 520x1125, per il telefono fermo da 1024 px; il passo 4 e' -v2, con i margini
 * bilanciati: la CDN di Hostinger tiene l'immagine vecchia se il nome non cambia), con width e height e loading="lazy"; `sizes` esplicito (da WordPress
 * 6.7 un `auto` davanti ridurrebbe la rendition scelta su un'immagine ritagliata con object-fit).
 * Il telefono e' disegnato in CSS (cornice, isola, angoli); la schermata e' un'immagine estratta dal Figma, ritagliata
 * dove il disegno la ritaglia (la scheda taglia il telefono in basso).
 *
 * Tracciamento: solo il link in fondo (data-cta-position="steps"); le schede non hanno CTA. CSS: blocco `.hv2-steps` di style.css. Ingressi: data-reveal su testata e link
 * (le schede si muovono da sole con la pila sticky; l'ingresso da scheda oltre a data-reveal e' di TICKET-129).
 *
 * Esempio: get_template_part( 'template-parts/home-steps-v2', null, array( 'title' => 'Spoiler: due minuti.' ) );
 */
$args = wp_parse_args( $args ?? array(), array(
	'title'           => '',
	'eyebrow'         => '',
	'subtitle'        => '',
	'subtitle_mobile' => '',
	'link_label'      => '',
	'link_url'        => '',
	'items'           => null,
) );
$hv2c_pick = static function ( $valore, $default ) {
	$valore = trim( (string) $valore );
	return '' !== $valore ? $valore : $default;
};
$hv2c_img = static function ( $file ) {
	return tp_theme_asset( 'assets/img/home-v2/' . $file );
};
// Default: titolo, frase, dimensioni della foto grande (la variante -720 ha la stessa proporzione).
$hv2c_default = array(
	array( 'title' => 'Scegli.', 'text' => 'Dici cosa ti serve e dove. Il resto lo cerca l’app.', 'bg_w' => 1200, 'bg_h' => 630, 'alt' => 'Una professionista passa l’aspirapolvere sul divano di un salotto luminoso' ),
	array( 'title' => 'Confronta.', 'text' => 'Vedi chi è libero vicino a te e scegli chi ti piace.', 'bg_w' => 1440, 'bg_h' => 804, 'alt' => 'Una spugna gialla su una superficie in lavorazione, accanto a una pianta' ),
	array( 'title' => 'Prenota.', 'text' => 'Scegli giorno e ora, in due tocchi. Il professionista conferma.', 'bg_w' => 1440, 'bg_h' => 804, 'alt' => 'Una camera con letto rifatto e asciugamani piegati' ),
	array( 'title' => 'Fatto.', 'text' => 'Torni a casa ed è tutto a posto. Il resto della giornata è tuo.', 'bg_w' => 1376, 'bg_h' => 768, 'alt' => 'Un salotto in ordine, pieno di luce' ),
);
$hv2c_in = is_array( $args['items'] ) ? array_values( array_filter( $args['items'], static function ( $r ) {
	return is_array( $r ) && '' !== trim( (string) ( $r['title'] ?? '' ) );
} ) ) : null;
if ( is_array( $hv2c_in ) && ! $hv2c_in ) {
	return;
}
$hv2c_rows = array();
if ( $hv2c_in ) {
	foreach ( $hv2c_in as $i => $r ) {
		$d  = $hv2c_default[ $i ] ?? null;
		$bg = trim( (string) ( $r['photo'] ?? '' ) );
		$sc = trim( (string) ( $r['screen'] ?? '' ) );
		$hv2c_rows[] = array(
			'title'  => trim( (string) $r['title'] ),
			'text'   => trim( (string) ( $r['text'] ?? '' ) ),
			'bg'     => '' !== $bg ? $bg : ( $d ? $hv2c_img( 'step-' . ( $i + 1 ) . '-bg.webp' ) : '' ),
			'bg_720' => '' !== $bg ? '' : ( $d ? $hv2c_img( 'step-' . ( $i + 1 ) . '-bg-720.webp' ) : '' ),
			'bg_w'   => '' !== $bg ? 0 : ( $d ? $d['bg_w'] : 0 ),
			'bg_h'   => '' !== $bg ? 0 : ( $d ? $d['bg_h'] : 0 ),
			'screen' => '' !== $sc ? $sc : ( $d ? $hv2c_img( 'step-' . ( $i + 1 ) . '-screen.webp' ) : '' ),
			// una schermata propria da Carbon vale anche per il telefono fermo; altrimenti la versione intera del passo
			'screen_full' => '' !== $sc ? $sc : ( $d ? $hv2c_img( 'step-' . ( $i + 1 ) . ( 3 === $i ? '-screen-full-v2.webp' : '-screen-full.webp' ) ) : '' ),
			'alt'    => $d ? $d['alt'] : '',
		);
	}
} else {
	foreach ( $hv2c_default as $i => $d ) {
		$hv2c_rows[] = array(
			'title'  => $d['title'],
			'text'   => $d['text'],
			'bg'     => $hv2c_img( 'step-' . ( $i + 1 ) . '-bg.webp' ),
			'bg_720' => $hv2c_img( 'step-' . ( $i + 1 ) . '-bg-720.webp' ),
			'bg_w'   => $d['bg_w'],
			'bg_h'   => $d['bg_h'],
			'screen' => $hv2c_img( 'step-' . ( $i + 1 ) . '-screen.webp' ),
			'screen_full' => $hv2c_img( 'step-' . ( $i + 1 ) . ( 3 === $i ? '-screen-full-v2.webp' : '-screen-full.webp' ) ),
			'alt'    => $d['alt'],
		);
	}
}
$hv2c_total  = count( $hv2c_rows );
$hv2c_slides = array();
foreach ( $hv2c_rows as $hv2c_i => $hv2c_r ) {
	ob_start();
	?>
	<article class="hv2-steps__card" data-step="<?php echo (int) ( $hv2c_i + 1 ); ?>" style="--i: <?php echo (int) $hv2c_i; ?>">
		<?php if ( '' !== $hv2c_r['bg'] ) : ?>
			<img class="hv2-steps__photo" src="<?php echo esc_url( $hv2c_r['bg'] ); ?>"<?php echo '' !== $hv2c_r['bg_720'] ? ' srcset="' . esc_url( $hv2c_r['bg_720'] ) . ' 720w, ' . esc_url( $hv2c_r['bg'] ) . ' ' . (int) $hv2c_r['bg_w'] . 'w" sizes="(max-width: 1023px) 760px, 1248px"' : ''; ?><?php echo $hv2c_r['bg_w'] ? ' width="' . (int) $hv2c_r['bg_w'] . '" height="' . (int) $hv2c_r['bg_h'] . '"' : ''; ?> alt="" loading="lazy" decoding="async">
		<?php endif; ?>
		<span class="hv2-steps__shade" aria-hidden="true"></span>
		<span class="hv2-steps__badge"><span class="screen-reader-text">Passo </span><?php echo (int) ( $hv2c_i + 1 ); ?> di <?php echo (int) $hv2c_total; ?></span>
		<?php if ( '' !== $hv2c_r['screen'] ) : ?>
			<span class="hv2-steps__phone" aria-hidden="true">
				<span class="hv2-steps__screen"><img src="<?php echo esc_url( $hv2c_r['screen'] ); ?>" alt="" loading="lazy" decoding="async"<?php echo 0 === $hv2c_i ? ' width="376" height="706"' : ' width="376" height="711"'; ?>></span>
				<span class="hv2-steps__island"></span>
			</span>
		<?php endif; ?>
		<div class="hv2-steps__copy">
			<h3 class="hv2-steps__step<?php echo mb_strlen( $hv2c_r['title'] ) > 8 ? ' hv2-steps__step--long' : ''; ?>"><?php echo esc_html( $hv2c_r['title'] ); ?></h3>
			<?php if ( '' !== $hv2c_r['text'] ) : ?>
				<p class="hv2-steps__text"><?php echo esc_html( $hv2c_r['text'] ); ?></p>
			<?php endif; ?>
		</div>
	</article>
	<?php
	$hv2c_slides[] = ob_get_clean();
}
$hv2c_link = $hv2c_pick( $args['link_url'], '' ) ? tp_home_v2_service_href( $args['link_url'] ) : '';
$hv2c_title = $hv2c_pick( $args['title'], 'Spoiler: due minuti.' );
// Le ultime due parole non vanno a capo separate (giro 2, P3-9): "DUE MINUTI." non lascia "MINUTI." da solo su una riga.
$hv2c_title = preg_replace( '/\s+(\S+)$/u', "\u{00A0}\$1", $hv2c_title );
?>
<section class="hv2-section hv2-steps" id="come-funziona" data-section="04-come-funziona">
	<span class="hv2-steps__light hv2-steps__light--a" aria-hidden="true"></span>
	<span class="hv2-steps__light hv2-steps__light--b" aria-hidden="true"></span>
	<span class="hv2-steps__light hv2-steps__light--c" aria-hidden="true"></span>
	<div class="hv2-steps__inner">
		<div class="hv2-steps__head" data-reveal>
			<p class="hv2-steps__eyebrow"><?php echo esc_html( $hv2c_pick( $args['eyebrow'], 'Come funziona' ) ); ?></p>
			<h2 class="hv2-steps__title"><?php echo esc_html( $hv2c_title ); ?></h2>
			<p class="hv2-steps__subtitle hv2-steps__subtitle--desktop"><?php echo esc_html( $hv2c_pick( $args['subtitle'], 'Quattro passi. L’ultimo lo fai sul divano.' ) ); ?></p>
			<p class="hv2-steps__subtitle hv2-steps__subtitle--mobile"><?php echo esc_html( $hv2c_pick( $args['subtitle_mobile'], 'Quattro passi. L’ultimo lo fai sul divano.' ) ); ?></p>
		</div>
		<?php
		// Telefono fermo (24/11, richiesta di Guido): da 1024 px un solo telefono resta fermo sopra la pila delle schede e
		// cambia schermata mentre le schede scorrono (home-steps-stack.js mette .hv2-steps--pin sulla sezione e dissolve
		// le schermate con lo scroll, con un contatore "N di M" fermo accanto). Senza JavaScript, sotto 1024 px o prima dello script restano i telefoni dentro le schede.
		$hv2c_screens = array_values( array_filter( array_column( $hv2c_rows, 'screen_full' ) ) );
		?>
		<div class="hv2-steps__stack">
		<?php
		get_template_part( 'template-parts/tp-carousel', null, array(
			'slides'     => $hv2c_slides,
			'label'      => 'I passi per prenotare',
			'class'      => 'hv2-steps__carousel',
			'dot_label'  => 'Vai al passo %d',
			'prev_label' => 'Passo precedente',
			'next_label' => 'Passo successivo',
		) );
		?>
		<?php if ( count( $hv2c_screens ) === $hv2c_total ) : ?>
			<div class="hv2-steps__pin" aria-hidden="true">
				<div class="hv2-steps__pinbox">
					<?php // Contatore unico (24/11): sta fermo col telefono al posto di quello delle schede, che il telefono coprirebbe. ?>
					<span class="hv2-steps__badge hv2-steps__pincount"><span class="hv2-steps__pinnum">1</span> di <?php echo (int) $hv2c_total; ?>
						<span class="hv2-steps__ticks"><?php echo str_repeat( '<i></i>', $hv2c_total ); ?></span>
					</span>
					<span class="hv2-steps__phone hv2-steps__phone--pin">
						<span class="hv2-steps__screen">
							<?php foreach ( $hv2c_screens as $hv2c_k => $hv2c_src ) : ?>
								<img class="hv2-steps__pinshot" src="<?php echo esc_url( $hv2c_src ); ?>" alt="" loading="lazy" decoding="async" width="520" height="1125" style="--k: <?php echo (int) $hv2c_k; ?>">
							<?php endforeach; ?>
						</span>
						<span class="hv2-steps__island"></span>
					</span>
				</div>
			</div>
		<?php endif; ?>
		</div>

		<?php if ( $hv2c_link ) : ?>
			<p class="hv2-steps__more" data-reveal>
				<a class="hv2-steps__link" href="<?php echo esc_url( $hv2c_link ); ?>" data-cta-position="steps"><span><?php echo esc_html( $hv2c_pick( $args['link_label'], 'Vedi come funziona nel dettaglio' ) ); ?></span><span aria-hidden="true">→</span></a>
			</p>
		<?php endif; ?>
	</div>
</section>
