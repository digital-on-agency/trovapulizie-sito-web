<?php
/**
 * Sezione video "Una luce" della Home v2 (TICKET-157). Figma file UiW4TPgvqCODW5bhyFV5eK, pagina 30: desktop 8211:3486,
 * mobile 8211:3778, note 8211:4067. Va dopo la fascia frasi, al posto di Servizi (che resta nel codice, spenta da
 * WordPress con la casella h2_services_hide: si torna indietro senza rilascio).
 *
 * Quando compare:
 * - la sezione video compare solo se h2_video_file e' compilato (tp_home_v2_video_args() ritorna null altrimenti);
 *   con il campo vuoto la pagina e' identica a prima: nessun markup video, nessuno script, nessun JSON-LD VideoObject;
 * - Servizi sparisce solo con h2_services_hide acceso (tp_home_v2_services_hidden()), indipendentemente dal video.
 *
 * Il file del video sta nei media di WordPress (mai nel repository). La copertina e' h2_video_poster; se vuota si usa
 * quella del tema, assets/img/home-v2/video-una-luce-poster.webp (1920x1080) con la versione da 960 per lo srcset.
 */

/**
 * Valore di un campo file/immagine di Carbon (ID di allegato, di default) come URL. Accetta anche un URL gia' scritto.
 *
 * @param mixed $valore ID di allegato o URL.
 * @return string URL o stringa vuota.
 */
function tp_home_v2_video_url( $valore ) {
	if ( is_numeric( $valore ) && (int) $valore > 0 ) {
		$url = wp_get_attachment_url( (int) $valore );
		return $url ? (string) $url : '';
	}
	$valore = trim( (string) $valore );
	return '' !== $valore ? $valore : '';
}

/**
 * True se la sezione Servizi e' spenta da WordPress (casella h2_services_hide della pagina).
 *
 * @param int $post_id Pagina col template page-home-v2.php.
 * @return bool
 */
function tp_home_v2_services_hidden( $post_id ) {
	return (bool) carbon_get_post_meta( (int) $post_id, 'h2_services_hide' );
}

/**
 * Durata "m:ss" in secondi, 0 se il formato non e' valido.
 *
 * @param string $durata Es. "1:26".
 * @return int
 */
function tp_home_v2_video_seconds( $durata ) {
	if ( preg_match( '/^\s*(\d{1,3}):([0-5]\d)\s*$/', (string) $durata, $m ) ) {
		return (int) $m[1] * 60 + (int) $m[2];
	}
	return 0;
}

/**
 * Argomenti del template-part home-video-v2 dai campi Carbon, o null se il video non e' configurato.
 *
 * Chiavi: src, mime, poster (URL), poster_srcset (stringa, puo' essere vuota), poster_w, poster_h, eyebrow, title, subtitle,
 * duration (etichetta), seconds (0 se l'etichetta non e' m:ss). I testi vuoti restano vuoti: i default stanno nel template.
 *
 * @param int $post_id Pagina col template page-home-v2.php.
 * @return array|null
 */
function tp_home_v2_video_args( $post_id ) {
	$post_id = (int) $post_id;
	$src     = tp_home_v2_video_url( carbon_get_post_meta( $post_id, 'h2_video_file' ) );
	if ( '' === $src ) {
		return null;
	}
	$tipo   = wp_check_filetype( wp_parse_url( $src, PHP_URL_PATH ) ?: $src );
	$poster = tp_home_v2_video_url( carbon_get_post_meta( $post_id, 'h2_video_poster' ) );
	$set    = '';
	$w      = 1920;
	$h      = 1080;
	if ( '' === $poster ) {
		$poster = tp_theme_asset( 'assets/img/home-v2/video-una-luce-poster.webp' );
		$set    = tp_theme_asset( 'assets/img/home-v2/video-una-luce-poster-960.webp' ) . ' 960w, ' . $poster . ' 1920w';
	} else {
		$w = 0;
		$h = 0;
		$p = carbon_get_post_meta( $post_id, 'h2_video_poster' );
		if ( is_numeric( $p ) ) {
			$meta = wp_get_attachment_metadata( (int) $p );
			$w    = (int) ( $meta['width'] ?? 0 );
			$h    = (int) ( $meta['height'] ?? 0 );
		}
	}
	$durata = trim( (string) carbon_get_post_meta( $post_id, 'h2_video_duration' ) );
	$durata = '' !== $durata ? $durata : '1:26';
	return array(
		'src'           => $src,
		'mime'          => ! empty( $tipo['type'] ) ? $tipo['type'] : 'video/mp4',
		'poster'        => $poster,
		'poster_srcset' => $set,
		'poster_w'      => $w,
		'poster_h'      => $h,
		'eyebrow'       => (string) carbon_get_post_meta( $post_id, 'h2_video_eyebrow' ),
		'title'         => (string) carbon_get_post_meta( $post_id, 'h2_video_title' ),
		'subtitle'      => (string) carbon_get_post_meta( $post_id, 'h2_video_subtitle' ),
		'duration'      => $durata,
		'seconds'       => tp_home_v2_video_seconds( $durata ),
	);
}

/* Script del tasto play: solo sul template v2 e solo se il video e' configurato (altrimenti la pagina non cambia di un byte).
   Il <video> non esiste nell'HTML iniziale: lo crea il clic (assets/js/home-video.js). */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_page_template( 'page-home-v2.php' ) || ! tp_home_v2_video_args( get_queried_object_id() ) ) {
		return;
	}
	wp_enqueue_script( 'trovapulizie-home-video', get_stylesheet_directory_uri() . '/assets/js/home-video.js', array(), tp_theme_version(), array( 'strategy' => 'defer', 'in_footer' => true ) );
} );

/* Dati strutturati VideoObject nel JSON-LD della Home: sulla pagina iniziale (come Organization e WebSite, inc/seo.php), solo
   se il video e' configurato. L'anteprima non e' la pagina iniziale e non li ha. */
add_action( 'wp_head', function () {
	if ( ! is_singular() || ! is_front_page() || ! is_page_template( 'page-home-v2.php' ) ) {
		return;
	}
	$v = tp_home_v2_video_args( get_queried_object_id() );
	if ( ! $v ) {
		return;
	}
	$sub   = trim( $v['subtitle'] );
	$dati  = array(
		'@context'     => 'https://schema.org',
		'@type'        => 'VideoObject',
		'name'         => 'Trovapulizie - Una luce',
		'description'  => '' !== $sub ? $sub : 'Chiedi una pulizia dal telefono. Al resto pensiamo noi.',
		'thumbnailUrl' => array( remove_query_arg( 'ver', $v['poster'] ) ), // senza ?ver= di tp_theme_asset: URL stabile per i motori di ricerca
		'uploadDate'   => '2026-11-25',
		'contentUrl'   => $v['src'],
		'inLanguage'   => 'it',
	);
	if ( $v['seconds'] > 0 ) {
		$dati['duration'] = sprintf( 'PT%dM%dS', intdiv( $v['seconds'], 60 ), $v['seconds'] % 60 );
	}
	tp_seo_print_jsonld( $dati );
}, 6 );
