<?php
/**
 * Testo circolare decorativo: una frase distribuita su una circonferenza con <textPath> (Figma 7903:5506 desktop,
 * 7946:40 mobile). Componente generico a nome neutro: lo riusano la hero (TICKET-120), il manifesto (TICKET-123)
 * e la chiusura (TICKET-127).
 *
 * Markup: <svg class="tp-orbit-text" aria-hidden="true" focusable="false"> con un <path> a cerchio (parte dall'alto
 * e gira in senso orario) e un <text> con <textPath> lungo quanto la circonferenza. Il testo e' decorativo: la stessa
 * informazione sta nel testo della pagina, quindi l'SVG e' nascosto agli screen reader.
 *
 * Argomenti (tutti facoltativi, default fra parentesi):
 * - text (string, ''): la frase. Vuota = il componente non stampa nulla. I separatori li scrive chi chiama (es. " · ").
 * - id (string, 'tp-orbit'): prefisso dell'id del path; deve essere unico nella pagina se il componente compare piu' volte.
 * - size (int, 820): lato del viewBox quadrato; il centro e' (size/2, size/2).
 * - radius (float, 339): raggio della linea di base del testo, in unita' del viewBox.
 * - font_size (float, 28): corpo massimo, in unita' del viewBox. Se la frase non ci sta, il corpo scende (vedi sotto).
 * - class (string, ''): classi extra sull'<svg>. Colore e peso del carattere stanno nel CSS del chiamante
 *   (.tp-orbit-text text { fill, font-weight }), non qui.
 *
 * Frase troppo lunga: PHP non misura i caratteri, quindi stima la larghezza naturale della frase (0,70 em per
 * lettera maiuscola ExtraBold, 0,30 em per spazio e punto medio) e, se supera il 94% della circonferenza, riduce il
 * corpo fino a farla stare. Poi textLength porta la frase esattamente sulla circonferenza (lengthAdjust="spacing"):
 * lo spazio in piu' o in meno si ripartisce fra le lettere, le lettere non vengono deformate. Il CSS del chiamante
 * deve mettere white-space: pre su `.tp-orbit-text text` per conservare lo spazio finale della frase. Con la frase di
 * default lo spazio in piu' e' di circa 10 unita' fra una lettera e l'altra (come nel Figma).
 * La rotazione si applica con transform sull'<svg> dal CSS del chiamante (hero e timbro del manifesto), non qui.
 *
 * Esempio:
 *   get_template_part( 'template-parts/tp-orbit-text', null, array(
 *       'text' => 'PROFESSIONISTI VERIFICATI · PREZZO CHIARO · ', 'id' => 'hero-orbit', 'size' => 820, 'radius' => 339, 'font_size' => 28,
 *   ) );
 */
$args = wp_parse_args( $args ?? array(), array(
	'text'      => '',
	'id'        => 'tp-orbit',
	'size'      => 820,
	'radius'    => 339,
	'font_size' => 28,
	'class'     => '',
) );
$tp_orbit_text = (string) $args['text']; // lo spazio finale e' voluto: separa l'ultima parola dalla prima (white-space: pre nel CSS)
if ( '' === trim( $tp_orbit_text ) ) {
	return;
}
$tp_orbit_size   = max( 1, (float) $args['size'] );
$tp_orbit_r      = max( 1, (float) $args['radius'] );
$tp_orbit_c      = $tp_orbit_size / 2;
$tp_orbit_circ   = 2 * M_PI * $tp_orbit_r;
$tp_orbit_font   = max( 1, (float) $args['font_size'] );

// Stima della larghezza naturale, in em: maiuscole 0,70, spazi e punti medi 0,30, il resto 0,62.
$tp_orbit_em = 0.0;
foreach ( preg_split( '//u', $tp_orbit_text, -1, PREG_SPLIT_NO_EMPTY ) as $tp_orbit_ch ) {
	if ( ' ' === $tp_orbit_ch || '·' === $tp_orbit_ch ) {
		$tp_orbit_em += 0.30;
	} elseif ( mb_strtoupper( $tp_orbit_ch ) === $tp_orbit_ch && mb_strtolower( $tp_orbit_ch ) !== $tp_orbit_ch ) {
		$tp_orbit_em += 0.70;
	} else {
		$tp_orbit_em += 0.62;
	}
}
if ( $tp_orbit_em * $tp_orbit_font > $tp_orbit_circ * 0.94 ) {
	$tp_orbit_font = $tp_orbit_circ * 0.94 / $tp_orbit_em;
}
$tp_orbit_path_id = sanitize_html_class( $args['id'] ) . '-path';
$tp_orbit_d       = sprintf(
	'M %1$s %2$s A %3$s %3$s 0 1 1 %1$s %4$s A %3$s %3$s 0 1 1 %1$s %2$s',
	round( $tp_orbit_c, 2 ),
	round( $tp_orbit_c - $tp_orbit_r, 2 ),
	round( $tp_orbit_r, 2 ),
	round( $tp_orbit_c + $tp_orbit_r, 2 )
);
?>
<svg class="tp-orbit-text <?php echo esc_attr( $args['class'] ); ?>" viewBox="0 0 <?php echo esc_attr( round( $tp_orbit_size, 2 ) ); ?> <?php echo esc_attr( round( $tp_orbit_size, 2 ) ); ?>" aria-hidden="true" focusable="false">
	<defs><path id="<?php echo esc_attr( $tp_orbit_path_id ); ?>" d="<?php echo esc_attr( $tp_orbit_d ); ?>" fill="none"/></defs>
	<text font-size="<?php echo esc_attr( round( $tp_orbit_font, 2 ) ); ?>"><textPath href="#<?php echo esc_attr( $tp_orbit_path_id ); ?>" textLength="<?php echo esc_attr( round( $tp_orbit_circ, 2 ) ); ?>" lengthAdjust="spacing"><?php echo esc_html( $tp_orbit_text ); ?></textPath></text>
</svg>
