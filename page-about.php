<?php
/**
 * Template Name: Chi siamo
 *
 * Pagina /chi-siamo/: chi c'e' dietro Trovapulizie, come lavora, il team, la storia e i dati della societa'.
 * Design: Figma 6795:895 (pagina "Sito Web") con la hero rifatta il 27/10 (6814:895, manifesto chiaro con mappa).
 * PRD e ticket nel vault: clients/trovapulizie/projects/sito-web/spec/SPEC-pagina-chi-siamo.md (TICKET-66..69).
 *
 * Ordine: hero con la mappa dei CAP, Perche' esistiamo, Cosa facciamo, Come lavoriamo, Il team, La nostra storia,
 * Dati aziendali e contatti, CTA finale. Navbar e footer globali.
 * Ogni testo ha il default qui sotto; il campo della scheda "Chi siamo — Sezioni" (prefisso ab_), se compilato,
 * vince (tp_ab()). Le sezioni sono template-part pilotati da $args.
 *
 * [ATTENZIONE] Il file NON si chiama page-chi-siamo.php: col nome dello slug WordPress lo applicherebbe da solo
 * lasciando _wp_page_template a "default", e la scheda dei campi e gli script non comparirebbero (stessa trappola
 * di page-hub-roma.php). Il template va assegnato a mano sulla pagina.
 */

/** Testo di un campo della pagina se compilato, altrimenti il default. */
function tp_ab( $field, $fallback = '' ) {
	$v = carbon_get_the_post_meta( $field );
	return ( is_string( $v ) && trim( $v ) !== '' ) ? $v : $fallback;
}
/** Campo complesso se compilato, altrimenti il default. */
function tp_ab_list( $field, $fallback ) {
	$v = carbon_get_the_post_meta( $field );
	return $v ? $v : $fallback;
}

$cerca     = home_url( '/#hero' );
$contatti  = home_url( '/contatti/' );
$partner   = home_url( '/diventa-cleaner/' );
$img       = function ( $name ) {
	return tp_theme_asset( 'assets/img/about/' . $name . '.webp' );
};

get_header();
?>
<main class="ab" id="contenuto" data-landing="chi-siamo">

	<?php if ( current_user_can( 'edit_posts' ) && ! carbon_get_the_post_meta( 'ab_hero_title' ) ) : ?>
		<div style="background:#fff3cd;border:1px solid #ffe69c;padding:12px 20px;margin:20px;border-radius:8px;">
			La pagina usa i testi di default del template. Per cambiarli: <strong>Pagine &rarr; Chi siamo &rarr; Modifica</strong>, scheda <strong>Chi siamo &mdash; Sezioni</strong>. Questo avviso lo vedi solo da loggato.
		</div>
	<?php endif; ?>

	<?php
	// Hero (TICKET-66): mappa dei CAP colorata dalla stessa copertura della Home.
	get_template_part( 'template-parts/about-hero', null, array(
		'eyebrow'       => tp_ab( 'ab_hero_eyebrow', 'CHI SIAMO · STARTUP INNOVATIVA DI ROMA' ),
		'title'         => tp_ab( 'ab_hero_title', "Mettiamo ordine\nnelle pulizie di Roma." ),
		'accent'        => tp_ab( 'ab_hero_accent', 'ordine' ),
		'text'          => tp_ab( 'ab_hero_text', "Trovapulizie è nata a Roma nel 2026 perché trovare un'impresa di pulizie affidabile non dipendesse più dal passaparola. Imprese verificate, prezzo chiaro prima di prenotare, pagamento protetto." ),
		'cta_primary'   => array( 'label' => tp_ab( 'ab_hero_cta', 'Cerca un servizio' ), 'link' => $cerca, 'position' => 'hero' ),
		'cta_secondary' => array( 'label' => tp_ab( 'ab_hero_cta2', 'Contattaci' ), 'link' => $contatti, 'position' => 'hero_contatti' ),
		'floats'        => array(
			array( 'icon' => 'shield', 'title' => tp_ab( 'ab_float1_title', 'Imprese verificate' ), 'text' => tp_ab( 'ab_float1_text', 'Documenti e partita IVA controllati' ) ),
			array( 'icon' => 'pin', 'title' => tp_ab( 'ab_float2_title', 'Roma, quartiere per quartiere' ), 'text' => tp_ab( 'ab_float2_text', "Scrivi l'indirizzo e vedi se ci siamo" ) ),
		),
		'stats'         => array(
			array( 'value' => tp_ab( 'ab_stat1_value', '26' ), 'label' => tp_ab( 'ab_stat1_label', 'quartieri attivi' ) ),
			array( 'value' => tp_ab( 'ab_stat2_value', '5' ), 'label' => tp_ab( 'ab_stat2_label', 'zone di Roma' ) ),
		),
		'coverage'      => get_stylesheet_directory() . '/assets/data/coverage.fixture.json',
	) );

	// Perche' esistiamo (TICKET-67).
	get_template_part( 'template-parts/about-why', null, array(
		'eyebrow'    => tp_ab( 'ab_why_eyebrow', 'PERCHÉ ESISTIAMO' ),
		'title'      => tp_ab( 'ab_why_title', 'A Roma le pulizie si trovano ancora col passaparola.' ),
		'paragraphs' => tp_lines( tp_ab( 'ab_why_text', "Chi cerca un'impresa di pulizie chiede a un vicino, scorre i gruppi WhatsApp, telefona a tre numeri e spera. Non sa in anticipo quanto spenderà, chi entrerà in casa o in ufficio, cosa succede se il lavoro non va bene.\nChi pulisce per mestiere ha il problema opposto: sa fare bene il lavoro, ma passa le serate a rispondere ai messaggi, a rincorrere i pagamenti e a cercare il cliente successivo." ) ),
		'closing'    => tp_ab( 'ab_why_closing', 'Trovapulizie mette ordine fra le due parti.' ),
		'items'      => array_map( function ( $r, $icon ) {
			return array( 'icon' => $icon, 'title' => $r['title'] ?? '', 'text' => $r['text'] ?? '' );
		}, tp_pipe_lines( tp_ab( 'ab_why_items', "Imprese e professionisti verificati | Controlliamo documenti e partita IVA prima che un profilo vada online.\nPrezzo chiaro prima di prenotare | Sui servizi standard il prezzo è fisso. Per gli interventi grandi arriva un preventivo, dopo un sopralluogo o qualche foto.\nPagamento protetto | Paghi online sulla piattaforma. L'impresa riceve il pagamento a lavoro concluso." ) ), array( 'shield', 'tag', 'card' ) ),
	) );

	// Cosa facciamo (TICKET-67): componente "Per chi e'" di Diventa cleaner, badge come eyebrow.
	get_template_part( 'template-parts/audience-cards', null, array(
		'eyebrow'  => tp_ab( 'ab_what_eyebrow', 'COSA FACCIAMO' ),
		'title'    => tp_ab( 'ab_what_title', 'Una piattaforma, due lati dello stesso lavoro.' ),
		'subtitle' => tp_ab( 'ab_what_subtitle', "Da una parte chi ha bisogno di un servizio di pulizia, dall'altra chi lo fa per mestiere. Noi teniamo insieme le due cose." ),
		'id'       => 'cosa-facciamo',
		'cards'    => array(
			array( 'badge' => 'PER CHI CERCA', 'title' => tp_ab( 'ab_what_left_title', 'Privati e aziende che cercano un servizio a Roma.' ), 'bullets' => tp_lines( tp_ab( 'ab_what_left_items', "Pulizie di casa e interventi di sgrosso: trasloco, fine affitto, post-ristrutturazione, prima pulizia\nPulizie per uffici, negozi, condomini, palestre e strutture ricettive\nProfili con recensioni, prezzo chiaro e pagamento online" ) ), 'cta_label' => 'Cerca un servizio', 'cta_link' => $cerca, 'cta_position' => 'cosa_facciamo_cerca', 'dark' => false, 'cta_variant' => 'primary' ),
			array( 'badge' => 'PER CHI PULISCE', 'title' => tp_ab( 'ab_what_right_title', 'Imprese di pulizie e professionisti con partita IVA.' ), 'bullets' => tp_lines( tp_ab( 'ab_what_right_items', "Richieste di lavoro nelle zone che scegli tu\nTariffe e agenda le decidi tu\nPagamenti automatici sulla piattaforma, senza rincorrere nessuno" ) ), 'cta_label' => 'Diventa partner', 'cta_link' => $partner, 'cta_position' => 'cosa_facciamo_partner', 'dark' => true, 'cta_variant' => 'primary' ),
		),
	) );

	// Come lavoriamo (TICKET-67).
	get_template_part( 'template-parts/about-rules', null, array(
		'eyebrow' => tp_ab( 'ab_rules_eyebrow', 'COME LAVORIAMO' ),
		'title'   => tp_ab( 'ab_rules_title', 'Quattro regole che non cambiamo.' ),
		'items'   => array_map( function ( $r, $icon ) {
			return array( 'icon' => $icon, 'title' => $r['title'] ?? '', 'text' => $r['text'] ?? '' );
		}, tp_pipe_lines( tp_ab( 'ab_rules_items', "Solo recensioni vere | Pubblichiamo solo recensioni di clienti che hanno ricevuto il servizio. Nessuna testimonianza inventata.\nIl prezzo prima, non dopo | Sai quanto spendi prima di confermare. Le sorprese a fine lavoro non fanno parte del servizio.\nPersone, non un call center | Se ci scrivi ti risponde il team, spesso uno dei fondatori.\nRoma, quartiere per quartiere | Lavoriamo su una sola città per conoscerla bene. Oggi 5 zone e 26 quartieri." ) ), array( 'star', 'receipt', 'chat', 'map' ) ),
	) );

	// Il team (TICKET-68): chi ha la foto ha la card grande.
	get_template_part( 'template-parts/about-team', null, array(
		'eyebrow'  => tp_ab( 'ab_team_eyebrow', 'IL TEAM' ),
		'title'    => tp_ab( 'ab_team_title', 'Le persone dietro Trovapulizie.' ),
		'subtitle' => tp_ab( 'ab_team_subtitle', 'Due fondatori e un team di prodotto. Quando ci scrivi, parli con loro.' ),
		'people'   => tp_ab_list( 'ab_team_people', array(
			array( 'name' => 'Christian Pascolini', 'role' => 'Co-fondatore e Amministratore Unico', 'bio' => "Segue la visione dell'azienda, la governance e i rapporti con istituzioni e investitori. Viene dalla comunicazione e dalla cybersecurity e ha fondato Opera S.r.l.", 'photo' => $img( 'founder-christian' ) ),
			array( 'name' => 'Guido Spanu', 'role' => 'Co-fondatore, COO e CFO', 'bio' => 'Segue operazioni, prodotto e lancio sul mercato. Ha fondato AcquisitionHub, che porta le imprese sul digitale con piattaforme su misura.', 'photo' => $img( 'founder-guido' ) ),
			array( 'name' => 'Niccolò', 'role' => 'CTO', 'bio' => 'Sviluppa la piattaforma: prenotazioni, pagamenti, app per le imprese.', 'photo' => '' ),
			array( 'name' => 'Mattia', 'role' => 'CPO', 'bio' => 'Guida il prodotto e il gestionale per le imprese di pulizie.', 'photo' => '' ),
		) ),
	) );

	// La nostra storia (TICKET-68).
	$steps = array_map( function ( $line ) {
		$p = array_map( 'trim', explode( '|', $line, 3 ) );
		return array( 'date' => $p[0], 'title' => $p[1] ?? '', 'text' => $p[2] ?? '' );
	}, tp_lines( tp_ab( 'ab_story_steps', "Febbraio 2026 | Nasce Trovapulizie S.R.L. | Christian e Guido fondano la società a Roma.\nMarzo 2026 | Startup innovativa | Iscrizione alla sezione speciale del Registro delle Imprese.\nGiugno 2026 | La piattaforma va online | Prenotazioni e pagamenti attivi a Roma.\nAgosto 2026 | I primi lavori | Primi interventi per privati e aziende.\nOttobre 2026 | Il nuovo sito | Pagine per zona, per servizio e per le imprese partner." ) ) );
	get_template_part( 'template-parts/about-timeline', null, array(
		'eyebrow' => tp_ab( 'ab_story_eyebrow', 'LA NOSTRA STORIA' ),
		'title'   => tp_ab( 'ab_story_title', 'Dal 2026, a Roma.' ),
		'steps'   => $steps,
	) );

	// Dati aziendali e contatti (TICKET-69): blocco di Contatti, dati a sinistra. Numero ed email dalla pagina
	// Contatti se compilati, cosi' i due posti non divergono.
	$contatti_id = (int) url_to_postid( $contatti );
	$da_contatti = function ( $k, $d ) use ( $contatti_id ) {
		$v = $contatti_id ? trim( (string) carbon_get_post_meta( $contatti_id, $k ) ) : '';
		return '' !== $v ? $v : $d;
	};
	$rows = tp_pipe_lines( tp_ab( 'ab_co_rows', "Sede legale | Via Paolo Albera 63, 00181 Roma\nPartita IVA e C.F. | 18445521000\nREA | RM-1785357\nIscrizione | Startup innovativa, sezione speciale del Registro delle Imprese di Roma" ) );
	get_template_part( 'template-parts/company-contacts', null, array(
		'order'    => 'company',
		'position' => 'chi_siamo_contatti',
		'id'       => 'dati-aziendali',
		'channels' => array(
			'eyebrow'   => tp_ab( 'ab_ch_eyebrow', 'CONTATTI' ),
			'title'     => tp_ab( 'ab_ch_title', 'Parla con noi' ),
			'text'      => tp_ab( 'ab_ch_text', 'Per una richiesta, un dubbio su un lavoro o una collaborazione. Rispondiamo nei giorni lavorativi.' ),
			'whatsapp'  => $da_contatti( 'ct_whatsapp', TP_CT_WHATSAPP ),
			'email'     => $da_contatti( 'ct_email', TP_CT_EMAIL ),
			'cta_label' => tp_ab( 'ab_ch_cta', 'Scrivici su WhatsApp' ),
		),
		'company'  => array(
			'eyebrow' => tp_ab( 'ab_co_eyebrow', 'DATI AZIENDALI' ),
			'name'    => tp_ab( 'ab_co_name', 'Trovapulizie S.R.L.' ),
			'rows'    => $rows,
		),
	) );

	get_template_part( 'template-parts/cta-final', null, array(
		'title'        => tp_ab( 'ab_cta_title', 'Ti serve una pulizia a Roma?' ),
		'subtitle'     => tp_ab( 'ab_cta_subtitle', "Inserisci l'indirizzo, scegli il servizio e confronta le imprese disponibili nella tua zona." ),
		'button_text'  => tp_ab( 'ab_cta_button', 'Cerca un servizio' ),
		'button_link'  => $cerca,
		'note'         => tp_ab( 'ab_cta_note', 'Prezzo chiaro · Pagamento protetto · Imprese verificate' ),
		'cta_position' => 'final',
	) );
	?>
</main>
<?php
get_footer();
