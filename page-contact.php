<?php
/**
 * Template Name: Contatti
 *
 * Pagina /contatti/: smistamento di chi cerca un contatto verso la pagina giusta, piu' i canali diretti per chi
 * ha bisogno del team. Nessun form (decisione Guido 27/10). Design: Figma 6803:895 (pagina "Sito Web").
 * PRD e ticket nel vault: clients/trovapulizie/projects/sito-web/spec/SPEC-pagina-contatti.md (TICKET-63..65).
 *
 * Ordine: hero con le quattro card di smistamento, canali diretti e dati aziendali, FAQ. Navbar e footer globali.
 * Ogni testo ha il default qui sotto; il campo della scheda "Contatti — Sezioni" (prefisso ct_), se compilato,
 * vince (tp_ct()). Le sezioni sono template-part pilotati da $args.
 *
 * [ATTENZIONE] Il file NON si chiama page-contatti.php: col nome dello slug WordPress lo applicherebbe da solo
 * lasciando _wp_page_template a "default", e la scheda dei campi e gli script non comparirebbero (stessa trappola
 * di page-hub-roma.php). Il template va assegnato a mano sulla pagina.
 */

/** Testo di un campo della pagina se compilato, altrimenti il default. */
function tp_ct( $field, $fallback = '' ) {
	$v = carbon_get_the_post_meta( $field );
	return ( is_string( $v ) && trim( $v ) !== '' ) ? $v : $fallback;
}

$numero   = tp_ct( 'ct_whatsapp', TP_CT_WHATSAPP );
$wa_card  = tp_whatsapp_parts( $numero, 'Salve, ho una prenotazione su Trovapulizie e ho bisogno di aiuto.' );
// Posizione negli eventi per icona: resta stabile anche se Guido riordina o riscrive le card.
$posizioni = array(
	'home'      => 'contatti_card_casa',
	'building'  => 'contatti_card_azienda',
	'user-plus' => 'contatti_card_lavora',
	'chat'      => 'contatti_card_prenotato',
);

get_header();
?>
<main class="ct" id="contenuto" data-landing="contatti">

	<?php if ( current_user_can( 'edit_posts' ) && ! carbon_get_the_post_meta( 'ct_hero_title' ) ) : ?>
		<div style="background:#fff3cd;border:1px solid #ffe69c;padding:12px 20px;margin:20px;border-radius:8px;">
			La pagina usa i testi di default del template. Per cambiarli: <strong>Pagine &rarr; Contatti &rarr; Modifica</strong>, scheda <strong>Contatti &mdash; Sezioni</strong>. Questo avviso lo vedi solo da loggato.
		</div>
	<?php endif; ?>

	<?php
	// Smistamento (TICKET-63).
	$cards = carbon_get_the_post_meta( 'ct_cards' );
	if ( ! $cards ) {
		$cards = array(
			array( 'icon' => 'home', 'title' => 'Cerchi una pulizia per casa', 'text' => "Scrivi l'indirizzo, scegli il servizio e vedi subito se copriamo la tua zona.", 'link_label' => 'Cerca un servizio', 'link' => home_url( '/#hero' ), 'dark' => false ),
			array( 'icon' => 'building', 'title' => "Hai un'azienda o un negozio", 'text' => 'Uffici, negozi, palestre, condomini: preventivo per una pulizia una tantum o continuativa.', 'link_label' => 'Pulizie commerciali', 'link' => home_url( '/roma/sgrosso-commerciale/' ), 'dark' => false ),
			array( 'icon' => 'user-plus', 'title' => 'Vuoi lavorare con noi', 'text' => "Sei un professionista con partita IVA o un'impresa di pulizie: ricevi richieste dalla tua zona.", 'link_label' => 'Diventa partner', 'link' => home_url( '/diventa-cleaner/' ), 'dark' => false ),
			array( 'icon' => 'chat', 'title' => 'Hai già prenotato', 'text' => 'Un problema con un lavoro, un pagamento o il tuo account: scrivici e ti risponde una persona del team.', 'link_label' => 'Scrivici su WhatsApp', 'link' => $wa_card ? $wa_card['link'] : '', 'dark' => true ),
		);
	}
	$cards = array_map( function ( $c ) use ( $posizioni ) {
		$c['cta_position'] = $posizioni[ $c['icon'] ?? '' ] ?? 'contatti_card';
		return $c;
	}, $cards );
	get_template_part( 'template-parts/route-cards', null, array(
		'eyebrow'  => tp_ct( 'ct_hero_eyebrow', 'CONTATTI' ),
		'title'    => tp_ct( 'ct_hero_title', 'Come possiamo aiutarti?' ),
		'subtitle' => tp_ct( 'ct_hero_subtitle', 'Scegli il tuo caso e ti portiamo subito al posto giusto. Per tutto il resto ti risponde una persona del team, su WhatsApp o per email.' ),
		'cards'    => $cards,
	) );

	// Canali diretti e dati aziendali (TICKET-64): stessi dati del CLAUDE.md cliente, sezione Identita'.
	$rows = tp_pipe_lines( tp_ct( 'ct_co_rows', "Sede legale | Via Paolo Albera 63, 00181 Roma\nPartita IVA e C.F. | 18445521000\nREA | RM-1785357\nIscrizione | Startup innovativa, sezione speciale del Registro delle Imprese di Roma" ) );
	$rows = array_map( function ( $r ) {
		return array( 'title' => $r['title'], 'text' => $r['text'] );
	}, $rows );
	get_template_part( 'template-parts/company-contacts', null, array(
		'channels' => array(
			'eyebrow'   => tp_ct( 'ct_ch_eyebrow', 'CANALI DIRETTI' ),
			'title'     => tp_ct( 'ct_ch_title', 'Scrivici' ),
			'text'      => tp_ct( 'ct_ch_text', 'Per qualsiasi altra domanda. Rispondiamo nei giorni lavorativi.' ),
			'whatsapp'  => $numero,
			'email'     => tp_ct( 'ct_email', TP_CT_EMAIL ),
			'cta_label' => tp_ct( 'ct_ch_cta', 'Scrivici su WhatsApp' ),
		),
		'company'  => array(
			'eyebrow' => tp_ct( 'ct_co_eyebrow', 'DATI AZIENDALI' ),
			'name'    => tp_ct( 'ct_co_name', 'Trovapulizie S.R.L.' ),
			'rows'    => $rows,
		),
	) );

	// FAQ (TICKET-65): componente della Home senza card di contatto (i canali sono gia' sopra).
	$faq = carbon_get_the_post_meta( 'ct_faq_items' );
	if ( ! $faq ) {
		$faq = array(
			array( 'question' => 'In quanto tempo rispondete?', 'answer' => 'Nei giorni lavorativi, su WhatsApp e per email. Per un problema su un lavoro in corso scrivi su WhatsApp: è il canale più veloce.' ),
			array( 'question' => 'Come modifico o annullo una prenotazione?', 'answer' => "Dall'app: apri la prenotazione e trovi Modifica e Annulla. Se hai un dubbio prima di farlo, scrivici su WhatsApp." ),
			array( 'question' => 'Ho un problema con un lavoro già fatto: cosa faccio?', 'answer' => "Scrivici su WhatsApp il prima possibile con il riferimento della prenotazione e, se puoi, qualche foto. Verifichiamo con l'impresa e ti rispondiamo noi." ),
			array( 'question' => 'Lavorate anche fuori Roma?', 'answer' => "Per ora siamo attivi a Roma, quartiere per quartiere. Scrivi l'indirizzo nella barra di ricerca della Home e vedi subito se copriamo la tua zona." ),
		);
	}
	get_template_part( 'template-parts/faq-accordion', null, array(
		'eyebrow'  => tp_ct( 'ct_faq_eyebrow', 'DOMANDE FREQUENTI' ),
		'title'    => tp_ct( 'ct_faq_title', 'Prima di scriverci' ),
		'subtitle' => tp_ct( 'ct_faq_subtitle', 'Le risposte alle domande che riceviamo più spesso.' ),
		'items'    => $faq,
	) );
	?>
</main>
<?php
get_footer();
