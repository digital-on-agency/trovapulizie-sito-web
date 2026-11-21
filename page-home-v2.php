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
 * | 01-hero          | home-hero-v2 (TICKET-120)                   | 7903:5257 |
 * | 02-frasi         | home-services-v2, fascia (TICKET-121)       | 7903:6209 |
 * | 03-servizi       | home-services-v2 (TICKET-121)               | 7903:6228 |
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

// data-section => [ chiave Carbon, titolo di default ]. Il primo titolo e' l'unico h1 della pagina.
$hv2_sections = array(
	'01-hero'          => array( 'hero', 'Trovapulizie' ),
	'02-frasi'         => array( 'frasi', 'Fascia frasi' ),
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
		$hv2_title = trim( (string) carbon_get_the_post_meta( 'h2_' . $hv2_def[0] . '_title' ) );
		$hv2_title = '' !== $hv2_title ? $hv2_title : $hv2_def[1];
		$hv2_tag   = 0 === $hv2_i ? 'h1' : 'h2';
		?>
	<section class="hv2-section hv2-section--<?php echo esc_attr( $hv2_def[0] ); ?>" data-section="<?php echo esc_attr( $hv2_slug ); ?>" data-reveal>
		<<?php echo $hv2_tag; ?> class="hv2-section__title"><?php echo esc_html( $hv2_title ); ?></<?php echo $hv2_tag; ?>>
	</section>
		<?php
		++$hv2_i;
	endforeach;
	?>
</main>
<?php
get_footer();
