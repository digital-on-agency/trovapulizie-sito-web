<?php
/**
 * Manifesto "Quattro niente" della Home v2, `05-manifesto` (SPEC-homepage-v2, TICKET-123). Figma file
 * UiW4TPgvqCODW5bhyFV5eK, pagina 30: desktop 7903:7379 (nodo "Perche' fidarti · manifesto" dentro 7903:5256), mobile
 * 7946:1823 (nel frame bianco 7946:2, quello con le pillole delle prove e l'icona), note di animazione 7882:4118.
 * Fondo #F3FAF7, titolo, timbro rotondo di testo circolare e quattro promesse: numero, "NIENTE" in #008866, parola
 * chiave in #0F1F1B, frase e una pillola di prova con icona.
 *
 * Regola di contenuto: nelle prove vanno dati reali dell'app, mai numeri o nomi inventati. Le quattro prove del Figma
 * ("Documento verificato · Laura B.", "Totale 76 € · 4 ore", "Addebito dopo l'accettazione", "Ti proponiamo Marco,
 * stesse condizioni") sono esempi in attesa di conferma (SPEC §6): stanno come valori precompilati del campo Carbon
 * `h2_manifesto_items` e in tp_home_v2_manifesto_defaults() (inc/home-v2-manifesto.php), modificabili senza deploy.
 *
 * Argomenti (le letture Carbon stanno in page-home-v2.php; tutti con default):
 * - eyebrow (string): scritta sopra il titolo. Default "Perche' fidarti".
 * - title (string): H2, in maiuscolo da CSS; l'a capo e' naturale. Default "Quattro «niente»."
 * - subtitle (string): sottotitolo. Default "Le regole che valgono per ogni pulizia. Senza asterischi."
 * - stamp (string): frase del timbro, maiuscolo, separatori " · " e spazio finale (gestita da tp-orbit-text).
 *   Default "PROFESSIONISTI VERIFICATI · TROVAPULIZIE · ROMA · ". Il timbro e' decorativo: aria-hidden.
 * - items (array|null): righe { lead, keyword, text, proof, icon }. null = le quattro del Figma; un array senza righe
 *   utili (nessuna promessa compilata) = la sezione non viene stampata. Una riga e' utile se ha lead o keyword.
 *   `icon` e' una chiave di tp_home_v2_manifesto_icons(); non riconosciuta o vuota = quella della riga con lo stesso
 *   numero d'ordine fra le quattro di default, poi la spunta. Una riga senza `proof` non stampa la pillola.
 *
 * Griglia: due colonne da 1024 px, una sotto 768 px; con meno di quattro promesse (o un numero dispari) l'ultima
 * occupa tutta la riga, senza buchi. Semantica: un solo h2, promesse in una lista <ol>, numeri e timbro aria-hidden.
 * Ingressi: data-reveal sulla testata e su ogni promessa (--i). Effetto timbro e pillole: TICKET-129.
 * CSS: blocco `TICKET-123` di style.css. JS: nessuno.
 *
 * Esempio: get_template_part( 'template-parts/home-manifesto', null, array( 'title' => 'Quattro «niente».' ) );
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'  => '',
	'title'    => '',
	'subtitle' => '',
	'stamp'    => '',
	'items'    => null,
) );
$hvm_pick = static function ( $valore, $default ) {
	$valore = trim( (string) $valore );
	return '' !== $valore ? $valore : $default;
};
$hvm_defaults = tp_home_v2_manifesto_defaults();
$hvm_icons    = tp_home_v2_manifesto_icons();
$hvm_in       = null === $args['items'] ? $hvm_defaults : (array) $args['items'];
$hvm_rows     = array();
foreach ( array_values( $hvm_in ) as $i => $r ) {
	if ( ! is_array( $r ) ) {
		continue;
	}
	$lead    = trim( (string) ( $r['lead'] ?? '' ) );
	$keyword = trim( (string) ( $r['keyword'] ?? '' ) );
	if ( '' === $lead && '' === $keyword ) {
		continue;
	}
	$chiave = (string) ( $r['icon'] ?? '' );
	if ( ! isset( $hvm_icons[ $chiave ] ) ) {
		$chiave = $hvm_defaults[ $i ]['icon'] ?? 'spunta';
	}
	$hvm_rows[] = array(
		'lead'    => $lead,
		'keyword' => $keyword,
		'text'    => trim( (string) ( $r['text'] ?? '' ) ),
		'proof'   => trim( (string) ( $r['proof'] ?? '' ) ),
		'icon'    => $hvm_icons[ $chiave ],
	);
}
if ( ! $hvm_rows ) {
	return; // zero promesse: sezione non stampata
}
$hvm_eyebrow  = $hvm_pick( $args['eyebrow'], 'Perché fidarti' );
$hvm_title    = $hvm_pick( $args['title'], 'Quattro «niente».' );
$hvm_subtitle = $hvm_pick( $args['subtitle'], 'Le regole che valgono per ogni pulizia. Senza asterischi.' );
$hvm_stamp    = $hvm_pick( $args['stamp'], 'PROFESSIONISTI VERIFICATI · TROVAPULIZIE · ROMA' ) . ' · ';
$hvm_stamp    = preg_replace( '/(?:\s*·\s*)+$/u', ' · ', $hvm_stamp ); // un solo separatore finale, anche se il campo lo contiene gia'
$hvm_stamp    = mb_strtoupper( ltrim( $hvm_stamp ) );
?>
<section class="hv2-man" data-section="05-manifesto" aria-labelledby="hv2-man-titolo">
	<div class="hv2-man__inner">
		<div class="hv2-man__stamp" aria-hidden="true">
			<?php
			get_template_part( 'template-parts/tp-orbit-text', null, array(
				'text'      => $hvm_stamp,
				'id'        => 'hv2-man-orbit',
				'size'      => 300,
				'radius'    => 114,
				'font_size' => 18.2,
				'class'     => 'hv2-man__orbit',
			) );
			?>
			<span class="hv2-man__ring"></span>
			<span class="hv2-man__disc"></span>
			<img class="hv2-man__mark" src="<?php echo esc_url( tp_theme_asset( 'assets/img/home-v2/man-stamp-icon.svg' ) ); ?>" alt="" width="84" height="76" decoding="async">
		</div>
		<div class="hv2-man__head" data-reveal>
			<p class="hv2-man__eyebrow"><?php echo esc_html( $hvm_eyebrow ); ?></p>
			<h2 class="hv2-man__title" id="hv2-man-titolo"><?php echo esc_html( $hvm_title ); ?></h2>
			<p class="hv2-man__subtitle"><?php echo esc_html( $hvm_subtitle ); ?></p>
		</div>
		<ol class="hv2-man__list">
			<?php foreach ( $hvm_rows as $i => $r ) : ?>
			<li class="hv2-man__item" data-reveal style="--i:<?php echo (int) ( $i + 1 ); ?>">
				<p class="hv2-man__promise">
					<span class="hv2-man__num" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span>
					<span class="hv2-man__title-promise"><?php if ( '' !== $r['lead'] ) : ?><span class="hv2-man__lead"><?php echo esc_html( $r['lead'] ); ?></span> <?php endif; ?><span class="hv2-man__key"><?php echo esc_html( $r['keyword'] ); ?></span></span>
				</p>
				<?php if ( '' !== $r['text'] ) : ?>
				<p class="hv2-man__text"><?php echo esc_html( $r['text'] ); ?></p>
				<?php endif; ?>
				<?php if ( '' !== $r['proof'] ) : ?>
				<p class="hv2-man__proof">
					<span class="hv2-man__ico" aria-hidden="true"><?php echo tp_icon_span( $r['icon'][0], 'hv2-man__ico-glyph hv2-man__ico-glyph--' . $r['icon'][2] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
					<span class="hv2-man__proof-text"><?php echo esc_html( $r['proof'] ); ?></span>
				</p>
				<?php endif; ?>
			</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
