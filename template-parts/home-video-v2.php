<?php
/**
 * Sezione video della Home v2, `03-video` (SPEC-homepage-v2, TICKET-157). Figma file UiW4TPgvqCODW5bhyFV5eK, pagina 30:
 * desktop 8211:3486, mobile 8211:3778, note 8211:4067. Scritta, titolo, sottotitolo, riquadro 16:9 con copertina e tasto
 * play, riga "Servizi a Roma" e link all'app. Impaginazione centrata (da telefono allineata a sinistra).
 *
 * Il video si scarica solo al clic: nell'HTML non c'e' nessun <video>. Il tasto e' un link al file mp4 (senza JavaScript
 * il clic apre il video); assets/js/home-video.js lo sostituisce con un <button> e al clic crea il <video> (src, controls,
 * playsinline, preload="metadata"), lo avvia con audio, sposta il fuoco sul video e al primo avvio emette
 * tpTrack('video_play', { video: 'una-luce' }). Nessun autoplay.
 *
 * Riga "Servizi a Roma": tp_home_v2_altri_servizi() (solo pagine pubblicate, percorsi e anchor da tp_service_pages()), la
 * stessa della sezione Servizi. Link "Scopri i servizi nell'app": tp_app_url(), data-cta-position="video".
 *
 * Argomenti (le letture Carbon stanno in inc/home-v2-video.php; i testi vuoti prendono i default qui sotto):
 * - src (string, obbligatorio): URL del file mp4. mime (string): default video/mp4.
 * - poster (string, obbligatorio): URL della copertina. poster_srcset (string): srcset della copertina. poster_w, poster_h (int):
 *   dimensioni della copertina per width e height.
 * - eyebrow (string): default "Il video". title (string): default "Trova. Prenota. Sorridi." (maiuscolo da CSS).
 *   subtitle (string): default "Chiedi una pulizia dal telefono. Al resto pensiamo noi.".
 * - duration (string): etichetta sulla copertina, default "1:26". seconds (int): durata in secondi, per l'etichetta del tasto
 *   ("1 minuto e 26"); 0 o assente = quella di default.
 *
 * Ingressi: data-reveal sul testo, sul riquadro e sulla riga dei link (--i 0, 1, 2), come le altre sezioni v2; con "riduci
 * movimento" nessuna animazione (regole generali di data-reveal). CSS: blocco `.hv2-video` di style.css.
 *
 * Esempio: get_template_part( 'template-parts/home-video-v2', null, array( 'src' => $mp4, 'poster' => $webp ) );
 */
$args = wp_parse_args( $args ?? array(), array(
	'src'           => '',
	'mime'          => 'video/mp4',
	'poster'        => '',
	'poster_srcset' => '',
	'poster_w'      => 1920,
	'poster_h'      => 1080,
	'eyebrow'       => '',
	'title'         => '',
	'subtitle'      => '',
	'duration'      => '',
	'seconds'       => 0,
) );
if ( '' === trim( (string) $args['src'] ) ) {
	return;
}
$hv2v_pick = static function ( $valore, $default ) {
	$valore = trim( (string) $valore );
	return '' !== $valore ? $valore : $default;
};
// Sotto il video solo tre link (Guido 25/11: le sei della sezione Servizi erano troppe): sgrosso casa, pulizie
// commerciali, guida ai prezzi. Le altre landing restano raggiungibili da hub, menu e piede.
$hv2v_more     = array_intersect_key( tp_home_v2_altri_servizi(), array_flip( array( '/roma/sgrosso-casa/', '/roma/sgrosso-commerciale/', '/roma/prezzi-pulizie/' ) ) );
$hv2v_duration = $hv2v_pick( $args['duration'], '1:26' );
$hv2v_sec      = (int) $args['seconds'] > 0 ? (int) $args['seconds'] : 86;
$hv2v_label    = sprintf( 'Guarda il video di Trovapulizie, %d %s e %d', intdiv( $hv2v_sec, 60 ), 1 === intdiv( $hv2v_sec, 60 ) ? 'minuto' : 'minuti', $hv2v_sec % 60 );
?>
<section class="hv2-section hv2-video" data-section="03-video" aria-labelledby="hv2-video-titolo">
	<span class="hv2-video__light hv2-video__light--a" aria-hidden="true"></span>
	<span class="hv2-video__light hv2-video__light--b" aria-hidden="true"></span>
	<div class="hv2-video__inner">
		<div class="hv2-video__head" data-reveal>
			<p class="hv2-video__eyebrow"><?php echo esc_html( $hv2v_pick( $args['eyebrow'], 'Il video' ) ); ?></p>
			<h2 class="hv2-video__title" id="hv2-video-titolo"><?php echo esc_html( $hv2v_pick( $args['title'], 'Trova. Prenota. Sorridi.' ) ); ?></h2>
			<p class="hv2-video__subtitle"><?php echo esc_html( $hv2v_pick( $args['subtitle'], 'Chiedi una pulizia dal telefono. Al resto pensiamo noi.' ) ); ?></p>
		</div>
		<div class="hv2-video__frame" data-reveal style="--i: 1" data-video-src="<?php echo esc_url( $args['src'] ); ?>" data-video-type="<?php echo esc_attr( $args['mime'] ); ?>">
			<img class="hv2-video__poster" src="<?php echo esc_url( $args['poster'] ); ?>"<?php echo $args['poster_srcset'] ? ' srcset="' . esc_attr( $args['poster_srcset'] ) . '" sizes="(min-width: 1024px) 960px, calc(100vw - 32px)"' : ''; ?><?php echo (int) $args['poster_w'] > 0 && (int) $args['poster_h'] > 0 ? ' width="' . (int) $args['poster_w'] . '" height="' . (int) $args['poster_h'] . '"' : ''; ?> alt="" loading="lazy" decoding="async">
			<a class="hv2-video__play" href="<?php echo esc_url( $args['src'] ); ?>" aria-label="<?php echo esc_attr( $hv2v_label ); ?>">
				<span class="hv2-video__veil" aria-hidden="true"></span>
				<span class="hv2-video__disc" aria-hidden="true">
					<svg viewBox="0 0 96 96" width="96" height="96" focusable="false"><circle cx="48" cy="48" r="48" fill="#fff"/><path d="M40.3 31.7 66.9 48 40.3 64.3z" fill="#062A22"/></svg>
				</span>
				<span class="hv2-video__caption" aria-hidden="true"><span class="hv2-video__duration"><?php echo esc_html( $hv2v_duration ); ?></span><span class="hv2-video__cta-label">Guarda il video</span></span>
			</a>
		</div>
		<div class="hv2-video__more" data-reveal style="--i: 2">
			<?php if ( $hv2v_more ) : ?>
				<p class="hv2-video__more-label" id="hv2-video-more">Servizi a Roma</p>
				<div class="hv2-video__chips-wrap">
					<ul class="hv2-video__chips" aria-labelledby="hv2-video-more">
						<?php foreach ( $hv2v_more as $hv2v_path => $hv2v_anchor ) : ?>
							<li><a class="hv2-video__chip" href="<?php echo esc_url( home_url( $hv2v_path ) ); ?>"><?php echo esc_html( $hv2v_anchor ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
			<a class="hv2-video__app" href="<?php echo esc_url( tp_app_url() ); ?>" data-cta-position="video"><span>Scopri i servizi nell’app</span><span aria-hidden="true">→</span></a>
		</div>
	</div>
</section>
