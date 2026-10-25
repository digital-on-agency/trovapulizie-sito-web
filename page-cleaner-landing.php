<?php
/**
 * Template Name: Diventa cleaner
 *
 * Pagina /diventa-cleaner/: acquisizione dell'offerta (professionisti con partita IVA e imprese di pulizie).
 * Design: Figma 6752:895 (pagina "Sito Web"), rivisto in UI/UX il 25/10. PRD e ticket nel vault:
 * clients/trovapulizie/projects/sito-web/spec/SPEC-pagina-diventa-cleaner.md (TICKET-51..57).
 *
 * Ordine: hero con il telefono, trust strip, Come funziona con le schermate, bento dell'app, Quanto guadagni,
 * Per chi e', Requisiti, Partner (solo se compilato), FAQ, CTA finale, barra fissa mobile.
 * Ogni testo ha il default qui sotto; il campo della scheda "Diventa cleaner — Sezioni" (prefisso dc_), se
 * compilato, vince (tp_dc()). Le sezioni sono template-part pilotati da $args.
 *
 * [ATTENZIONE] Il file NON si chiama page-diventa-cleaner.php: col nome dello slug WordPress lo applicherebbe da
 * solo lasciando _wp_page_template a "default", e la scheda dei campi e gli script non comparirebbero (stessa
 * trappola di page-hub-roma.php). Il template va assegnato a mano sulla pagina.
 */

/** Testo di un campo della pagina se compilato, altrimenti il default. */
function tp_dc( $field, $fallback = '' ) {
	$v = carbon_get_the_post_meta( $field );
	return ( is_string( $v ) && trim( $v ) !== '' ) ? $v : $fallback;
}
/** Numero da un campo (accetta la virgola), altrimenti il default. */
function tp_dc_num( $field, $fallback ) {
	$v = str_replace( ',', '.', (string) carbon_get_the_post_meta( $field ) );
	return is_numeric( $v ) ? (float) $v : $fallback;
}

$signup   = tp_app_url( 'cleaner-signup' );
$wa_text  = "Salve, sono un'impresa di pulizie e vorrei informazioni su Trovapulizie.";
$whatsapp = 'https://wa.me/393200980144?text=' . rawurlencode( $wa_text );
$img      = function ( $name ) {
	return tp_theme_asset( 'assets/img/cleaner/' . $name . '.webp' );
};

get_header( '', array(
	'links'     => array(
		'#come-funziona' => 'Come funziona',
		'#app'           => 'Strumenti',
		'#guadagni'      => 'Guadagni',
		'#requisiti'     => 'Requisiti',
		'#faq'           => 'FAQ',
	),
	'cta_label' => 'Registrati gratis',
	'cta_href'  => $signup,
) );
?>
<main class="dc" id="contenuto" data-landing="diventa-cleaner">

	<?php if ( current_user_can( 'edit_posts' ) && ! carbon_get_the_post_meta( 'dc_hero_title' ) ) : ?>
		<div style="background:#fff3cd;border:1px solid #ffe69c;padding:12px 20px;margin:20px;border-radius:8px;">
			La pagina usa i testi di default del template. Per cambiarli: <strong>Pagine &rarr; Diventa Cleaner &rarr; Modifica</strong>, scheda <strong>Diventa cleaner &mdash; Sezioni</strong>. Questo avviso lo vedi solo da loggato.
		</div>
	<?php endif; ?>

	<section class="dc-hero" id="hero">
		<div class="dc-hero__panel">
			<div class="dc-hero__copy">
				<p class="dc-hero__eyebrow"><?php echo esc_html( tp_dc( 'dc_hero_eyebrow', 'PER PROFESSIONISTI E IMPRESE DI PULIZIE · ROMA' ) ); ?></p>
				<h1><?php echo nl2br( esc_html( tp_dc( 'dc_hero_title', "I clienti li portiamo noi.\nTu scegli quando,\ndove e a quanto." ) ) ); ?></h1>
				<p class="dc-hero__subtitle"><?php echo esc_html( tp_dc( 'dc_hero_subtitle', 'Trovapulizie ti porta richieste di pulizia nella tua zona. Tu fissi la tariffa, accetti solo i lavori che vuoi e ricevi il pagamento in automatico, senza inseguire nessuno.' ) ); ?></p>
				<div class="dc-hero__ctas">
					<a class="tp-btn tp-btn--primary tp-btn--lg dc-btn" href="<?php echo esc_url( $signup ); ?>" data-cta-position="hero"><?php echo esc_html( tp_dc( 'dc_hero_cta', 'Registrati gratis' ) ); ?> <span class="dc-arrow" aria-hidden="true">&rarr;</span></a>
					<a class="tp-btn tp-btn--lg dc-btn dc-btn--ghost" href="#come-funziona"><?php echo esc_html( tp_dc( 'dc_hero_cta2', 'Come funziona' ) ); ?></a>
				</div>
			</div>
			<div class="dc-hero__stage">
				<?php get_template_part( 'template-parts/phone-mockup', null, array(
					'screen' => carbon_get_the_post_meta( 'dc_hero_screen' ) ?: $img( 'dashboard-hero' ),
					'class'  => 'dc-hero__phone',
				) ); ?>
				<?php
				$floats = array(
					array( 'icon' => '!', 'title' => tp_dc( 'dc_float1_title', 'Nuova richiesta · Prati' ), 'sub' => tp_dc( 'dc_float1_sub', 'Pulizia casa · 90 m² · mar 14:00' ) ),
					array( 'icon' => '€', 'title' => tp_dc( 'dc_float2_title', 'Pagamento ricevuto' ), 'sub' => tp_dc( 'dc_float2_sub', '+ 96,00 € · accredito automatico' ) ),
				);
				foreach ( $floats as $i => $f ) : ?>
					<div class="dc-float dc-float--<?php echo (int) $i + 1; ?>" aria-hidden="true">
						<span class="dc-float__icon"><?php echo esc_html( $f['icon'] ); ?></span>
						<span class="dc-float__text"><strong><?php echo esc_html( $f['title'] ); ?></strong><span><?php echo esc_html( $f['sub'] ); ?></span></span>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<?php
	$trust = tp_lines( carbon_get_the_post_meta( 'dc_trust_items' ) );
	get_template_part( 'template-parts/trust-bar', null, array(
		'items' => $trust ?: array( 'Zero costi fissi', 'Tu fissi la tariffa', 'Pagamento automatico con carta', 'Zone che scegli tu' ),
	) );

	// Come funziona (TICKET-52): tre card con lo schermo dell'app.
	$steps = carbon_get_the_post_meta( 'dc_hiw_steps' );
	if ( ! $steps ) {
		$steps = array(
			array( 'title' => 'Crea il profilo', 'description' => 'Servizi che offri, zone in cui lavori, tariffa oraria. Documenti e partita IVA li verifichiamo noi.', 'screen' => $img( 'step-completa-profilo' ) ),
			array( 'title' => 'Ricevi le richieste', 'description' => 'Ti arriva una notifica per ogni lavoro nella tua zona. Vedi indirizzo, metri quadri e orario prima di accettare.', 'screen' => $img( 'step-richieste' ) ),
			array( 'title' => 'Lavora e incassa', 'description' => "Concludi il lavoro dall'app. Il cliente paga con carta, il tuo compenso arriva sul conto in automatico.", 'screen' => $img( 'step-guadagni' ) ),
		);
	}
	get_template_part( 'template-parts/steps-cards', null, array(
		'eyebrow'  => tp_dc( 'dc_hiw_eyebrow', 'COME FUNZIONA' ),
		'title'    => tp_dc( 'dc_hiw_title', 'Da zero a operativo in tre passi' ),
		'subtitle' => tp_dc( 'dc_hiw_subtitle', 'Nessun colloquio, nessuna attesa: il profilo si compila in pochi minuti.' ),
		'steps'    => $steps,
		'class'    => 'dc-steps',
	) );

	// Bento dell'app (TICKET-53).
	$tiles = carbon_get_the_post_meta( 'dc_app_tiles' );
	if ( ! $tiles ) {
		$tiles = array(
			array( 'title' => 'La tua agenda, sincronizzata', 'text' => 'Lavori in ordine di data, orari di lavoro che decidi tu, e sincronizzazione con Google, Apple e Outlook Calendar: zero sovrapposizioni con i tuoi impegni.', 'screen' => $img( 'tile-calendario' ), 'wide' => true ),
			array( 'title' => 'Richieste in tempo reale', 'text' => 'Notifica push per ogni lavoro nella tua zona. Accetti o rifiuti in un tocco.', 'screen' => $img( 'tile-notifiche' ), 'wide' => false ),
			array( 'title' => 'Guadagni sotto controllo', 'text' => 'Ogni lavoro, ogni accredito, il totale del mese. Niente contanti da rincorrere.', 'screen' => $img( 'tile-guadagni' ), 'wide' => false ),
			array( 'title' => 'Recensioni che portano lavoro', 'text' => 'Più stelle, più visibilità nella ricerca dei clienti.', 'screen' => $img( 'tile-recensioni' ), 'wide' => false ),
			array( 'title' => 'Zone che decidi tu', 'text' => 'Scegli i quartieri in cui vuoi lavorare. Le richieste arrivano solo da lì.', 'screen' => $img( 'tile-zone' ), 'wide' => false ),
		);
	}
	get_template_part( 'template-parts/app-bento', null, array(
		'eyebrow'  => tp_dc( 'dc_app_eyebrow', 'GLI STRUMENTI' ),
		'title'    => tp_dc( 'dc_app_title', "Un'app che lavora per te" ),
		'subtitle' => tp_dc( 'dc_app_subtitle', 'Agenda, richieste, pagamenti e recensioni in un posto solo. Niente fogli, niente messaggi persi.' ),
		'tiles'    => $tiles,
	) );

	// Quanto guadagni (TICKET-54).
	$hours = array_values( array_filter( array_map( 'intval', explode( ',', tp_dc( 'dc_calc_hours', '10,20,30,40' ) ) ) ) );
	$rows  = tp_pipe_lines( tp_dc( 'dc_calc_rows', "Costi fissi | 0 €\nQuota di iscrizione | 0 €\nCommissione | solo sui lavori completati\nAccredito | automatico, sul tuo conto" ) );
	get_template_part( 'template-parts/earnings-calculator', null, array(
		'eyebrow'       => tp_dc( 'dc_calc_eyebrow', 'QUANTO GUADAGNI' ),
		'title'         => tp_dc( 'dc_calc_title', "Il compenso lo decidi tu. Noi ti riempiamo l'agenda." ),
		'subtitle'      => tp_dc( 'dc_calc_subtitle', 'Imposta la tariffa oraria dal profilo e cambiala quando vuoi. Qui sotto un esempio: muovi i valori e vedi la stima.' ),
		'rate_min'      => (int) tp_dc_num( 'dc_calc_rate_min', 15 ),
		'rate_max'      => (int) tp_dc_num( 'dc_calc_rate_max', 35 ),
		'rate_default'  => (int) tp_dc_num( 'dc_calc_rate_default', 22 ),
		'hours'         => $hours ?: array( 10, 20, 30, 40 ),
		'hours_default' => (int) tp_dc_num( 'dc_calc_hours_default', 20 ),
		'rows'          => $rows,
		'note'          => tp_dc( 'dc_calc_note', 'stima lorda, prima della commissione di piattaforma' ),
		'cta_label'     => tp_dc( 'dc_calc_cta', 'Registrati e imposta la tua tariffa' ),
		'cta_link'      => $signup,
	) );

	// Per chi e' (TICKET-55): dopo la spiegazione, come bivio di conversione.
	$cards = carbon_get_the_post_meta( 'dc_who_cards' );
	if ( $cards ) {
		$cards = array_map( function ( $c ) {
			$c['bullets'] = tp_lines( $c['bullets'] ?? '' );
			$c['cta_position'] = 'per-chi';
			return $c;
		}, $cards );
	} else {
		$cards = array(
			array( 'badge' => 'P.IVA', 'title' => 'Sei un professionista', 'text' => "Lavori in autonomia e vuoi riempire l'agenda senza passaparola e volantini.", 'bullets' => array( 'Scegli zone, giorni e tariffa oraria dal profilo', 'Accetta solo le richieste che ti convengono', 'Incasso automatico sul tuo conto a lavoro fatto' ), 'cta_label' => 'Registrati gratis', 'cta_link' => $signup, 'cta_position' => 'per-chi', 'dark' => false ),
			array( 'badge' => 'SRL', 'title' => "Sei un'impresa di pulizie", 'text' => 'Hai una squadra e cerchi lavori continuativi: case, uffici, condomini, sgrossi.', 'bullets' => array( 'Richieste di preventivo da privati e aziende della tua zona', 'Un referente Trovapulizie che ti segue sui lavori grandi', 'Calendario condiviso con la squadra e pagamenti tracciati' ), 'cta_label' => 'Scrivici su WhatsApp', 'cta_link' => $whatsapp, 'cta_position' => 'per-chi', 'dark' => true ),
		);
	}
	get_template_part( 'template-parts/audience-cards', null, array(
		'eyebrow'  => tp_dc( 'dc_who_eyebrow', 'PER CHI È' ),
		'title'    => tp_dc( 'dc_who_title', 'Una piattaforma, due modi di lavorare' ),
		'subtitle' => tp_dc( 'dc_who_subtitle', "Che tu sia un professionista con partita IVA o un'impresa strutturata, i clienti arrivano dallo stesso posto." ),
		'cards'    => $cards,
	) );

	// Requisiti (TICKET-56).
	get_template_part( 'template-parts/requirements', null, array(
		'eyebrow'   => tp_dc( 'dc_req_eyebrow', 'REQUISITI' ),
		'title'     => tp_dc( 'dc_req_title', 'Cosa serve per iniziare. E cosa no.' ),
		'yes_title' => tp_dc( 'dc_req_yes_title', 'Cosa serve' ),
		'yes'       => tp_pipe_lines( tp_dc( 'dc_req_yes', "Esperienza nelle pulizie professionali | Casa, uffici o sgrossi: ci basta che tu sappia fare il lavoro.\nPartita IVA, o la disponibilità ad aprirla | Ti indichiamo come fare, il pagamento arriva solo con fattura.\nUno smartphone con internet | L'app funziona su iPhone e Android, senza installazioni.\nAlmeno 2 giorni a settimana di disponibilità | Li scegli tu dal calendario, li cambi quando vuoi." ) ),
		'no_title'  => tp_dc( 'dc_req_no_title', 'Cosa non serve' ),
		'no'        => tp_pipe_lines( tp_dc( 'dc_req_no', "Nessuna quota di iscrizione | Registrarsi è gratis, oggi e domani.\nNessuna esclusiva | Continui a lavorare con i tuoi clienti di sempre.\nNessun colloquio | Verifichiamo i documenti online e ti confermiamo l'attivazione via email.\nNessun minimo di lavori | Accetti quello che vuoi, quando vuoi." ) ),
	) );

	// Partner (TICKET-55): esiste solo se il campo e' compilato (serve l'ok dei partner ai nomi).
	get_template_part( 'template-parts/partners-strip', null, array(
		'title' => tp_dc( 'dc_partners_title', 'LAVORANO GIÀ CON NOI A ROMA' ),
		'names' => tp_lines( carbon_get_the_post_meta( 'dc_partners' ) ),
		'tail'  => tp_dc( 'dc_partners_tail', '+ professionisti indipendenti in tutta la città' ),
	) );

	// FAQ (TICKET-56): componente della Home, card di contatto dei fondatori con uscita WhatsApp.
	$faq = carbon_get_the_post_meta( 'dc_faq_items' );
	if ( ! $faq ) {
		$faq = array(
			array( 'question' => 'Quanto costa registrarsi?', 'answer' => "Niente. L'iscrizione è gratuita e non ci sono canoni: Trovapulizie trattiene una commissione solo sui lavori completati e pagati dal cliente." ),
			array( 'question' => 'Quando e come ricevo i pagamenti?', 'answer' => "Il cliente paga con carta quando prenota. A lavoro concluso il tuo compenso viene accreditato in automatico sul conto che hai collegato nel profilo. Niente contanti, niente fatture da inseguire." ),
			array( 'question' => 'Posso rifiutare una richiesta?', 'answer' => "Sì. Vedi indirizzo, tipo di pulizia, metri quadri e orario prima di decidere, e accetti solo i lavori che ti interessano." ),
			array( 'question' => 'Serve la partita IVA?', 'answer' => "Sì, per ricevere i pagamenti serve una partita IVA. Se non l'hai ancora puoi registrarti lo stesso: ti indichiamo come aprirla." ),
			array( 'question' => 'In quali zone siete attivi?', 'answer' => "Roma città e provincia. Nel profilo scegli i quartieri in cui vuoi lavorare e ricevi solo le richieste che arrivano da lì." ),
			array( 'question' => 'Cosa succede se il cliente annulla?', 'answer' => "Il lavoro annullato torna libero nella tua agenda e ricevi una notifica. Rimborsi e penali seguono le regole di cancellazione dei Termini e condizioni dell'app." ),
		);
	}
	get_template_part( 'template-parts/faq-accordion', null, array(
		'eyebrow'  => tp_dc( 'dc_faq_eyebrow', 'DOMANDE FREQUENTI' ),
		'title'    => tp_dc( 'dc_faq_title', 'Domande dei cleaner' ),
		'subtitle' => tp_dc( 'dc_faq_subtitle', 'Le risposte rapide. Per tutto il resto, scrivici.' ),
		'items'    => $faq,
		'contact'  => array(
			'people'    => 'Christian & Guido',
			'role'      => 'Fondatori',
			'title'     => tp_dc( 'dc_faq_contact_title', 'Rispondiamo di persona' ),
			'text'      => tp_dc( 'dc_faq_contact_text', 'Scrivici: ti risponde Guido o Christian, in base alla disponibilità. I fondatori, non un call center.' ),
			'cta_label' => tp_dc( 'dc_faq_contact_cta', 'Scrivici su WhatsApp' ),
			'cta_link'  => $whatsapp,
		),
	) );

	get_template_part( 'template-parts/cta-final', null, array(
		'title'       => tp_dc( 'dc_cta_title', "Pronto a riempire l'agenda?" ),
		'subtitle'    => tp_dc( 'dc_cta_subtitle', 'Registrati gratis, imposta zone e tariffa, ricevi la prima richiesta.' ),
		'button_text' => tp_dc( 'dc_cta_button', 'Registrati gratis' ),
		'button_link' => $signup,
		'note'        => tp_dc( 'dc_cta_note', 'Gratis · Nessun vincolo · Verifica documenti online' ),
		'cta_position' => 'final',
	) );

	get_template_part( 'template-parts/sticky-cta', null, array(
		'label'  => 'Registrati gratis',
		'target' => $signup,
		'watch'  => '.dc-hero',
		'cta_position' => 'sticky',
	) );
	?>
</main>
<?php
get_footer();
