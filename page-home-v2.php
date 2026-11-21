<?php
/**
 * Template Name: Home v2
 *
 * Home app-first (SPEC-homepage-v2, ADR-005, variante bianca). Vive su una pagina pubblicata con slug
 * /anteprima-pagina-iniziale/: noindex e fuori sitemap finche' non e' la pagina iniziale (inc/home-v2.php,
 * tp_is_home_v2_preview()). Non e' linkata da nessuna parte. Scheletro di TICKET-119: dieci sezioni con
 * data-section, titolo da Carbon (container "Home v2 - Sezioni", prefisso h2_) o default; il contenuto arriva coi
 * ticket delle singole sezioni.
 *
 * Figma (file UiW4TPgvqCODW5bhyFV5eK, pagina 30): desktop 7903:5256, mobile 7946:2 (hero e manifesto) e layout
 * 7883:2776 con i colori del desktop per le altre sezioni, note di animazione 7882:4118.
 *
 * | data-section     | Template-part (ticket)                      | Nodo Figma desktop |
 * | 01-hero          | home-hero-v2 (TICKET-120, fatto)            | 7903:5257, mobile 7946:3 |
 * | 02-frasi         | tp-marquee (TICKET-121, fatto)              | 7903:6209, mobile 7946:662 |
 * | 03-servizi       | home-services-v2 (TICKET-121, fatto)        | 7903:6228, mobile 7946:668 |
 * | 04-come-funziona | home-steps-v2 (TICKET-122)                  | 7903:6508 |
 * | 05-manifesto     | home-manifesto (TICKET-123)                 | 7903:7379 |
 * | 06-recensioni    | home-reviews-notes (TICKET-124)             | 7903:7496 |
 * | 07-zone          | home-zones-v2 (TICKET-125)                  | 7903:7567 |
 * | 08-aziende       | home-b2b-band (TICKET-126)                  | 7903:7601 |
 * | 09-faq           | faq-accordion con fondatori (TICKET-126)    | 7903:7623 |
 * | 10-chiusura      | home-cta-v2 (TICKET-127)                    | 7903:7666 |
 * Footer 7904:2856: TICKET-128.
 *
 * <main> porta data-landing="home": tp-track.js emette marketing_cta_click solo con [data-landing] (o per ogni
 * data-cta-position dopo TICKET-130). Ogni sezione ha data-reveal: reveal.js aggiunge .is-in (e la classe tp-reveal
 * su <html>, da cui dipende l'opacita' 0: senza JavaScript tutto e' visibile).
 */
get_header();

// data-section => [ chiave Carbon, titolo di default ]. L'unico h1 della pagina e' nella hero (home-hero-v2); le altre sezioni hanno un h2.
$hv2_sections = array(
	'01-hero'          => array( 'hero', 'Trovapulizie' ),
	'02-frasi'         => array( 'frasi', 'In breve' ),
	'03-servizi'       => array( 'servizi', 'Servizi' ),
	'04-come-funziona' => array( 'come_funziona', 'Come funziona' ),
	'05-manifesto'     => array( 'manifesto', 'Manifesto' ),
	'06-recensioni'    => array( 'recensioni', 'Recensioni' ),
	'07-zone'          => array( 'zone', 'Zone' ),
	'08-aziende'       => array( 'aziende', 'Aziende' ),
	'09-faq'           => array( 'faq', 'Domande frequenti' ),
	'10-chiusura'      => array( 'chiusura', 'Apri l\'app' ),
);
?>
<main class="hv2" id="contenuto" data-landing="home">
	<?php
	$hv2_i = 0;
	foreach ( $hv2_sections as $hv2_slug => $hv2_def ) :
		if ( 0 === $hv2_i ) :
			// Hero (TICKET-120): template-part dedicato, con il proprio H1 e senza data-reveal (contiene l'LCP).
			get_template_part( 'template-parts/home-hero-v2', null, array(
				'title'         => carbon_get_the_post_meta( 'h2_hero_title' ),
				'accent'        => carbon_get_the_post_meta( 'h2_hero_title_accent' ),
				'label'         => carbon_get_the_post_meta( 'h2_hero_label' ),
				'subtitle'      => carbon_get_the_post_meta( 'h2_hero_subtitle' ),
				'orbit'         => carbon_get_the_post_meta( 'h2_hero_orbit' ),
				'google_badge'  => (bool) carbon_get_the_post_meta( 'h2_google_badge' ),
				'google_rating' => carbon_get_the_post_meta( 'h2_google_rating' ),
			) );
			++$hv2_i;
			continue;
		endif;
		if ( 'frasi' === $hv2_def[0] ) :
			// Fascia frasi (TICKET-121): le frasi vengono da un campo complex; senza righe valgono i tre default del Figma.
			$hv2_frasi = array();
			foreach ( (array) carbon_get_the_post_meta( 'h2_frasi_items' ) as $hv2_riga ) {
				$hv2_frasi[] = is_array( $hv2_riga ) ? ( $hv2_riga['text'] ?? '' ) : '';
			}
			$hv2_frasi = array_values( array_filter( array_map( 'trim', $hv2_frasi ) ) );
			if ( ! $hv2_frasi ) {
				$hv2_frasi = array( 'La casa pulita. Il weekend libero.', 'Il divano ringrazia.', 'Lo sgrosso non è un hobby.' );
			}
			$hv2_vel = (float) carbon_get_the_post_meta( 'h2_frasi_speed' );
			$hv2_ft  = trim( (string) carbon_get_the_post_meta( 'h2_frasi_title' ) );
			?>
	<section class="hv2-frasi" data-section="02-frasi" aria-labelledby="hv2-frasi-titolo">
		<h2 class="screen-reader-text" id="hv2-frasi-titolo"><?php echo esc_html( '' !== $hv2_ft ? $hv2_ft : $hv2_def[1] ); ?></h2>
		<?php
		get_template_part( 'template-parts/tp-marquee', null, array(
			'items'    => $hv2_frasi,
			'duration' => $hv2_vel > 0 ? $hv2_vel : 30,
			'class'    => 'hv2-frasi__marquee',
		) );
		?>
	</section>
			<?php
			++$hv2_i;
			continue;
		endif;
		if ( 'servizi' === $hv2_def[0] ) :
			// Servizi (TICKET-121): template-part dedicato; senza righe in Carbon valgono le sei icone di default.
			$hv2_righe = array();
			foreach ( (array) carbon_get_the_post_meta( 'h2_servizi_items' ) as $hv2_riga ) {
				if ( is_array( $hv2_riga ) ) {
					$hv2_righe[] = array(
						'name' => $hv2_riga['name'] ?? '',
						'icon' => $hv2_riga['icon'] ?? '',
						'url'  => $hv2_riga['url'] ?? '',
						'soon' => ! empty( $hv2_riga['soon'] ),
					);
				}
			}
			get_template_part( 'template-parts/home-services-v2', null, array(
				'title'      => carbon_get_the_post_meta( 'h2_servizi_title' ),
				'eyebrow'    => carbon_get_the_post_meta( 'h2_servizi_eyebrow' ),
				'subtitle'   => carbon_get_the_post_meta( 'h2_servizi_subtitle' ),
				'more_label' => carbon_get_the_post_meta( 'h2_servizi_more_label' ),
				'cta_label'  => carbon_get_the_post_meta( 'h2_servizi_cta' ),
				'items'      => $hv2_righe,
			) );
			++$hv2_i;
			continue;
		endif;
		$hv2_title = trim( (string) carbon_get_the_post_meta( 'h2_' . $hv2_def[0] . '_title' ) );
		$hv2_title = '' !== $hv2_title ? $hv2_title : $hv2_def[1];
		$hv2_tag   = 'h2'; // l'unico h1 e' nella hero
		?>
	<section class="hv2-section hv2-section--<?php echo esc_attr( $hv2_def[0] ); ?>"<?php if ( 'come_funziona' === $hv2_def[0] ) : ?> id="come-funziona"<?php endif; ?> data-section="<?php echo esc_attr( $hv2_slug ); ?>" data-reveal>
		<<?php echo $hv2_tag; ?> class="hv2-section__title"><?php echo esc_html( $hv2_title ); ?></<?php echo $hv2_tag; ?>>
	</section>
		<?php
		++$hv2_i;
	endforeach;
	?>
</main>
<?php
get_footer();
