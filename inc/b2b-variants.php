<?php
/**
 * Testi di default per variante della landing B2B (TICKET-97).
 *
 * Un solo template (page-landing-b2b.php) serve tre pagine figlie di /roma/: la landing di partenza
 * /roma/sgrosso-commerciale/ e le due pagine di settore /roma/pulizie-uffici/ e /roma/pulizie-condomini/.
 * La variante si sceglie col campo b2b_variant (scheda "Landing B2B — Sezioni", tab Variante); senza valore vale
 * sgrosso-commerciale, quindi la pagina gia' in produzione non cambia. I campi Carbon della pagina, se compilati,
 * vincono su questi default (tp_b2b() nel template).
 *
 * sgrosso-commerciale: testi riportati verbatim dal template com'era prima del TICKET-97 (ridisegno 25/10 + live
 * React). Uffici e condomini: servizi del settore dalla SPEC-crescita-organica-fase-b US-02, testi approvati da
 * Guido prima della pubblicazione.
 *
 * [ATTENZIONE] Sulle due pagine nuove valgono i vincoli della SPEC §2: nessun prezzo, nessuna frequenza o tempo
 * garantito ("entro 1 ora"), nessuna certificazione (RC), nessun numero di ditte o clienti, nessuna recensione ne'
 * foto di lavori non reali. Per questo non hanno recensioni, lavori prima/dopo e riga con le stelle, e la
 * conferma del form non promette tempi.
 *
 * `landing` = identificatore negli eventi dataLayer (marketing_*) e in data-landing del form.
 * `show` = sezioni opzionali: sectors (Settori a tab), works (Lavori), plans (Formule), why (Perche' noi),
 * services (elenco servizi del settore), cross (link alle altre pagine), reviews (recensioni).
 */

/** Variante della pagina corrente (campo b2b_variant); una chiave sconosciuta o vuota ricade su sgrosso-commerciale. */
function tp_b2b_variant_key() {
	$k   = carbon_get_the_post_meta( 'b2b_variant' );
	$all = tp_b2b_variants();
	return ( is_string( $k ) && isset( $all[ $k ] ) ) ? $k : 'sgrosso-commerciale';
}

/** Default della variante richiesta (o della pagina corrente se $key e' null). */
function tp_b2b_defaults( $key = null ) {
	$all = tp_b2b_variants();
	$key = null === $key ? tp_b2b_variant_key() : $key;
	return isset( $all[ $key ] ) ? $all[ $key ] : $all['sgrosso-commerciale'];
}

/** Default della variante quando la pagina corrente usa page-landing-b2b.php, altrimenti null (per inc/seo.php). */
function tp_seo_b2b_defaults() {
	if ( ! is_singular() || ! is_page_template( 'page-landing-b2b.php' ) ) {
		return null;
	}
	return tp_b2b_defaults();
}

function tp_b2b_variants() {
	$img = function ( $name ) { return tp_theme_asset( 'assets/img/b2b/' . $name . '.webp' ); };

	$why_icons = array(
		'user-check'   => 'assets/icons/ic-user-check.svg',
		'clock'        => 'assets/icons/ic-clock.svg',
		'shield-check' => 'assets/icons/ic-shield-check.svg',
		'spray'        => 'assets/icons/ic-spray.svg',
		'check'        => 'assets/icons/ic-check.svg',
	);

	// Pagina di partenza: /roma/sgrosso-commerciale/ (post 12). Nessun cambiamento rispetto al template precedente.
	$commerciale = array(
		'landing'          => 'sgrosso-commerciale',
		'service_type'     => 'Pulizie commerciali per uffici, negozi e locali',
		'page_title'       => 'Pulizie commerciali Roma: uffici e negozi | Trovapulizie',
		'page_description' => 'Pulizie commerciali a Roma per uffici, negozi e locali: preventivo su misura, fattura, personale verificato. Sopralluogo gratuito, risposta in 1 ora.',
		'promo_desktop'    => 'Sopralluogo gratuito in sede · preventivo con fattura · ti richiamiamo entro 1 ora',
		'promo_mobile'     => 'Sopralluogo gratuito in sede · preventivo con fattura',
		'nav_links'        => array(
			'#settori'       => 'Settori',
			'#come-funziona' => 'Come funziona',
		),
		'nav_cta'          => 'Preventivo gratuito',
		'show'             => array( 'sectors' => true, 'works' => true, 'plans' => true, 'why' => true, 'services' => false, 'cross' => false, 'reviews' => true ),
		'hero'             => array(
			'eyebrow'  => 'PULIZIE COMMERCIALI · ROMA',
			'title'    => "Pulizie per uffici, negozi e locali.\nAnche quando sei chiuso.",
			'subtitle' => 'Pulizia a fondo e servizi ricorrenti per la tua attività. Sopralluogo gratuito in sede, preventivo su misura e fattura.',
			'bullets'  => array(
				'Orari flessibili, anche fuori apertura o notturni',
				'Fattura elettronica su ogni intervento',
				'Una-tantum o contratto ricorrente, come preferisci',
			),
			'social'   => 'Uffici, retail e locali serviti a Roma',
			// Foto di sfondo: file del tema con rendition mobile (picture) oppure un solo file.
			'image'    => array(
				'src'    => $img( 'hero-pavimento' ),
				'mobile' => $img( 'hero-pavimento-720' ),
				'alt'    => 'Lavasciuga professionale su un pavimento dopo i lavori, pulizie commerciali a Roma',
				'width'  => 1000,
				'height' => 1133,
			),
		),
		'form'             => array(
			'title'      => 'Richiedi il preventivo gratuito',
			'subtitle'   => 'Sopralluogo gratuito in sede. Ti richiamiamo entro 1 ora.',
			'cta'        => 'Richiedi il preventivo gratuito',
			'note'       => '★ Gratis · con fattura · risposta in 1h',
			'activities' => array( 'Ufficio', 'Negozio', 'Supermercato', 'Palestra', 'Altro' ),
			'extra'      => array(),
		),
		// Trust strip: le quattro promesse del ridisegno (il live diceva "Soddisfatti o ripuliamo" e "assicurazione RC",
		// claim ancora aperti: qui restano fuori, SPEC §6).
		'trust'            => array( 'Sopralluogo gratuito in sede', 'Fattura su ogni intervento', 'Anche di notte o nel weekend', 'Roma e provincia' ),
		// Settori (TICKET-59): testi di Uffici dal live; gli altri tre scritti sul live per il ridisegno (frame 6784:*),
		// da rileggere (SPEC §2.5).
		'sectors'          => array(
			'eyebrow'     => 'SETTORI SERVITI',
			'title'       => 'Puliamo ogni tipo di attività',
			'areas_title' => 'Cosa puliamo',
			'areas_text'  => 'Cambia settore per vedere cosa include. Il resto lo concordiamo al sopralluogo.',
			'cta'         => 'Richiedi il preventivo gratuito',
			'items'       => array(
				array( 'key' => 'uffici', 'label' => 'Uffici', 'title' => 'Uffici', 'text' => "Postazioni, sale riunioni, aree comuni e servizi. Interveniamo prima dell'apertura o dopo la chiusura, senza fermare il lavoro.", 'highlight' => 'Sempre lo stesso referente, fattura su ogni intervento', 'image' => $img( 'settore-uffici' ), 'image_alt' => 'Ufficio open space pulito', 'chips' => array( 'Postazioni', 'Sale riunioni', 'Aree comuni', 'Bagni', 'Cucinette', 'Vetrate' ) ),
				array( 'key' => 'negozi', 'label' => 'Negozi & retail', 'title' => 'Negozi & retail', 'text' => "Vetrine, camerini, magazzino e superfici vendita. Interveniamo prima dell'apertura: il negozio è pronto quando entra il primo cliente.", 'highlight' => "Vetrine pulite prima dell'apertura, fattura su ogni intervento", 'image' => $img( 'settore-retail' ), 'image_alt' => 'Negozio con vetrine pulite', 'chips' => array( 'Vetrine', 'Camerini', 'Magazzino', 'Superfici vendita', 'Bagni', 'Ingresso' ) ),
				array( 'key' => 'supermercati', 'label' => 'Supermercati', 'title' => 'Supermercati', 'text' => "Reparti, banchi, retro e pavimenti industriali. Lavasciuga professionale per le superfici grandi, fuori dall'orario di apertura.", 'highlight' => 'Interventi di notte, senza fermare la vendita', 'image' => $img( 'settore-supermercati' ), 'image_alt' => 'Corsia di supermercato pulita', 'chips' => array( 'Reparti', 'Banchi', 'Retro', 'Pavimenti', 'Bagni', 'Ingresso' ) ),
				array( 'key' => 'palestre', 'label' => 'Palestre & centri', 'title' => 'Palestre & centri', 'text' => 'Spogliatoi, attrezzi e aree umide sanificate. Igiene costante dove i clienti la notano di più.', 'highlight' => 'Aree umide sanificate, anche ogni giorno', 'image' => $img( 'settore-palestre' ), 'image_alt' => 'Sala attrezzi di una palestra pulita', 'chips' => array( 'Spogliatoi', 'Docce', 'Attrezzi', 'Sala corsi', 'Reception', 'Bagni' ) ),
			),
		),
		// Lavori a Roma (TICKET-59): prima/dopo trascinabile delle vetrate e galleria, foto reali del live.
		'works'            => array(
			'eyebrow'  => 'LAVORI A ROMA',
			'title'    => 'Fine cantiere: il prima e dopo reale',
			'subtitle' => 'Foto reali dei nostri interventi a Roma. Nessun ritocco: solo taglio ed esposizione.',
			'compare'  => array(
				'before'         => $img( 'vetrate-prima' ),
				'after'          => $img( 'vetrate-dopo' ),
				'before_alt'     => 'Vetrate prima della pulizia, velate dai residui di cantiere',
				'after_alt'      => 'Vetrate dopo la pulizia, riflesso limpido',
				'title'          => 'Vetrate di un locale commerciale',
				'text'           => 'Vetri velati dai residui di cantiere, riportati a un riflesso limpido prima della riapertura.',
				'before_caption' => 'Vetri velati da residui di cantiere',
				'after_caption'  => 'Vetri puliti, riflesso limpido',
			),
			'gallery'  => array(
				array( 'image' => $img( 'lavoro-pavimento' ), 'caption' => 'Lavasciuga professionale su pavimento post-cantiere' ),
				array( 'image' => $img( 'lavoro-spogliatoio' ), 'caption' => 'Spogliatoio consegnato pulito' ),
				array( 'image' => $img( 'lavoro-ufficio' ), 'caption' => 'Pavimento lucidato a specchio' ),
			),
		),
		// Una volta sola o tutto l'anno (TICKET-60): componente "Per chi e'" della pagina Diventa cleaner.
		'plans'            => array(
			'eyebrow'  => 'COME LAVORIAMO',
			'title'    => "Una volta sola o tutto l'anno",
			'subtitle' => 'Scegli tu la formula. Al sopralluogo gratuito definiamo servizi, orari e prezzo.',
			'items'    => array(
				array( 'badge' => 'UNA-TANTUM', 'title' => 'Uno sgrosso quando serve', 'text' => 'Per riaprire dopo un cantiere, cambiare sede o rimettere a nuovo gli spazi.', 'bullets' => array( 'Fine cantiere, riapertura, trasloco di sede', 'Preventivo dopo il sopralluogo gratuito', 'Fattura elettronica' ), 'cta_label' => 'Richiedi il preventivo', 'cta_link' => '#form-bottom', 'cta_position' => 'formule', 'cta_variant' => 'ghost', 'dark' => false ),
				array( 'badge' => 'CONTRATTO RICORRENTE', 'title' => 'Pulizie ricorrenti, stesso team', 'text' => 'Per chi vuole spazi sempre in ordine senza pensarci: frequenza e orari li decidi tu.', 'bullets' => array( 'Frequenza su misura: giornaliera, settimanale o mensile', 'Sempre lo stesso referente', 'Senza vincoli di durata' ), 'cta_label' => 'Richiedi il preventivo', 'cta_link' => '#form-bottom', 'cta_position' => 'formule', 'cta_variant' => 'primary', 'dark' => true ),
			),
		),
		// Perche' noi (TICKET-60): tre card del live e la quarta del ridisegno al posto di "Una-tantum o ricorrente",
		// gia' coperta dalla sezione sopra.
		'why'              => array(
			'eyebrow' => 'PERCHÉ NOI',
			'title'   => 'Un fornitore serio per la tua attività',
			'items'   => array(
				array( 'icon' => $why_icons['user-check'], 'title' => 'Un referente dedicato', 'text' => 'Una sola persona segue la tua attività, sempre la stessa. Zero passaggi di mano.' ),
				array( 'icon' => $why_icons['clock'], 'title' => 'Orari su misura', 'text' => "Interveniamo quando sei chiuso: notte, prima o dopo l'orario. Nessuno stop all'attività." ),
				array( 'icon' => $why_icons['shield-check'], 'title' => 'Tutto in regola', 'text' => 'Fattura, personale in regola e assicurazione RC. Prodotti professionali inclusi.' ),
				array( 'icon' => $why_icons['spray'], 'title' => 'Prodotti e macchinari professionali', 'text' => 'Prodotti professionali inclusi e lavasciuga per le superfici grandi. Tu non devi procurare niente.' ),
			),
		),
		// Come funziona (TICKET-61): tre card numerate, testi STEPS del live.
		'hiw'              => array(
			'eyebrow'  => 'COME FUNZIONA',
			'title'    => "Dal preventivo all'intervento",
			'subtitle' => 'Tre passi, senza fermare la tua attività.',
			'steps'    => array(
				array( 'title' => 'Richiedi il preventivo', 'description' => 'Compili il form o ci chiami. Fissiamo il sopralluogo gratuito in sede.' ),
				array( 'title' => 'Sopralluogo e preventivo', 'description' => 'Vediamo gli spazi, concordiamo orari, servizi e prezzo. Con fattura.' ),
				array( 'title' => 'Interveniamo', 'description' => 'Team verificato, fuori dai tuoi orari. Una-tantum o contratto ricorrente.' ),
			),
		),
		// Recensioni (TICKET-61): le tre del live, dichiarate fittizie nel sorgente (REVIEWS); iniziali al posto delle
		// foto di persone a caso (decisione Guido 25/10). Da sostituire con recensioni vere prima del cutover (SPEC §6).
		'reviews'          => array(
			'eyebrow' => 'DICONO DI NOI',
			'title'   => 'Attività che si affidano a noi',
			'items'   => array(
				array( 'author_name' => 'Marco C.', 'author_city' => 'Studio, Prati', 'review_text' => 'Puliscono il nostro studio ogni settimana, sempre fuori orario. Fattura puntuale e un referente unico. Zero pensieri.' ),
				array( 'author_name' => 'Laura R.', 'author_city' => 'Negozio, Centro', 'review_text' => 'Sgrosso completo prima della riapertura. Preventivo chiaro, lavoro serio. Ora abbiamo un contratto fisso.' ),
				array( 'author_name' => 'Andrea P.', 'author_city' => 'Palestra, EUR', 'review_text' => 'Spogliatoi e aree umide sempre impeccabili. Personale affidabile e in regola. Consigliatissimi.' ),
			),
		),
		// FAQ (TICKET-61): sei domande del live (tolta "Solo una-tantum o anche ricorrente?", coperta dalle formule).
		'faq'              => array(
			'eyebrow'  => 'DOMANDE FREQUENTI',
			'title'    => 'Prima di richiedere il preventivo',
			'subtitle' => 'Le risposte rapide. Per tutto il resto, il sopralluogo in sede è gratuito.',
			'items'    => array(
				array( 'question' => 'Intervenite fuori dagli orari di apertura?', 'answer' => "Sì: notte, prima o dopo l'orario, weekend. Concordiamo la fascia al sopralluogo, senza fermare l'attività." ),
				array( 'question' => 'Emettete fattura?', 'answer' => 'Sempre: fattura elettronica su ogni intervento, sia una-tantum sia ricorrente.' ),
				array( 'question' => 'Quanto costa?', 'answer' => 'Dipende da superficie, tipo di attività e frequenza. Il sopralluogo è gratuito: preventivo su misura, senza sorprese.' ),
				array( 'question' => 'In quanto tempo partite?', 'answer' => 'Dopo il sopralluogo definiamo tempi e prima data. In genere entro pochi giorni.' ),
				array( 'question' => 'In che zone operate?', 'answer' => 'Roma città e provincia. Indica la zona nel form e confermiamo la disponibilità.' ),
				array( 'question' => 'Siete assicurati?', 'answer' => 'Sì: copertura assicurativa RC su ogni intervento e personale in regola.' ),
			),
			'contact_title' => 'Rispondiamo di persona',
			'contact_text'  => 'Scrivici: ti risponde Guido o Christian, in base alla disponibilità. I fondatori, non un call center.',
			'contact_cta'   => 'Richiedi il preventivo gratuito',
		),
		// CTA finale con la seconda istanza del form (TICKET-61).
		'cta'              => array(
			'eyebrow'  => 'ULTIMO PASSO',
			'title'    => 'Un preventivo su misura per la tua attività',
			'subtitle' => 'Sopralluogo gratuito in sede, preventivo con fattura. Ti richiamiamo entro 1 ora.',
			'bullets'  => array( 'Sopralluogo gratuito in sede', 'Fattura su ogni intervento', 'Ti richiamiamo entro 1 ora', 'Anche di notte o nel weekend' ),
		),
	);

	// Le due pagine di settore: stessa struttura, servizi dalla SPEC-crescita-organica-fase-b US-02.
	$faq_contact = array(
		'contact_title' => 'Rispondiamo di persona',
		'contact_text'  => 'Scrivici: ti risponde Guido o Christian, in base alla disponibilità. I fondatori, non un call center.',
		'contact_cta'   => 'Richiedi il preventivo gratuito',
	);
	$done_text = 'Ti ricontattiamo al numero indicato per fissare il sopralluogo gratuito.';

	$uffici = array(
		'landing'          => 'pulizie-uffici',
		'service_type'     => 'Pulizie uffici',
		'page_title'       => 'Pulizie uffici Roma: preventivo gratuito | Trovapulizie',
		'page_description' => 'Pulizie uffici a Roma: ordinarie periodiche, di fondo, bagni, vetri e sanificazione. Sopralluogo gratuito in sede e preventivo su misura, senza impegno.',
		'promo_desktop'    => 'Sopralluogo gratuito in sede · preventivo su misura · senza impegno',
		'promo_mobile'     => 'Sopralluogo gratuito in sede · senza impegno',
		'nav_links'        => array(
			'#servizi'       => 'Servizi',
			'#come-funziona' => 'Come funziona',
		),
		'nav_cta'          => 'Preventivo gratuito',
		'show'             => array( 'sectors' => false, 'works' => false, 'plans' => false, 'why' => false, 'services' => true, 'cross' => true, 'reviews' => false ),
		'hero'             => array(
			'eyebrow'  => 'PULIZIE UFFICI · ROMA',
			'title'    => "Pulizie uffici a Roma.\nUn preventivo su misura per i tuoi spazi.",
			'subtitle' => "Pulizia ordinaria periodica, pulizie di fondo, bagni e aree ristoro, vetri, sanificazione e interventi dopo lavori o trasloco d'ufficio. Sopralluogo gratuito in sede, poi il preventivo.",
			'bullets'  => array(
				'Frequenza e orari da concordare al sopralluogo',
				'Pulizia periodica e interventi straordinari',
				'Sopralluogo gratuito, preventivo senza impegno',
			),
			'social'   => '',
			// Immagine decorativa generata (stessa del servizio Uffici nella Home), non una foto di un nostro lavoro.
			'image'    => array(
				'src'    => tp_theme_asset( 'assets/img/services-visual-uffici.jpg' ),
				'mobile' => '',
				'alt'    => "Mani con guanti verdi che puliscono con un panno una scrivania in un ufficio luminoso, con sedie e vetrate sullo sfondo",
				'width'  => 1600,
				'height' => 893,
			),
		),
		'form'             => array(
			'title'      => 'Preventivo per il tuo ufficio',
			'subtitle'   => 'Sopralluogo gratuito in sede. Ti ricontattiamo per fissarlo.',
			'cta'        => 'Richiedi il preventivo gratuito',
			'note'       => 'Gratis · senza impegno',
			'activities' => array( 'Ufficio', 'Studio professionale', 'Sede aziendale', 'Altro' ),
			'extra'      => array( 'done_text' => $done_text ),
		),
		'trust'            => array( 'Sopralluogo gratuito in sede', 'Preventivo su misura, senza impegno', 'Frequenza e orari da concordare', 'Roma e provincia' ),
		'services'         => array(
			'eyebrow'  => 'SERVIZI PER UFFICI',
			'title'    => 'Cosa puliamo nel tuo ufficio',
			'subtitle' => 'Scegli quello che ti serve: servizi, orari e frequenza si definiscono insieme al sopralluogo gratuito.',
			'items'    => array(
				array( 'icon' => $why_icons['check'], 'title' => 'Pulizia ordinaria periodica', 'text' => "Postazioni, sale riunioni e aree comuni tenute in ordine. La frequenza si concorda in base all'uso degli spazi." ),
				array( 'icon' => $why_icons['check'], 'title' => 'Pulizie straordinarie e di fondo', 'text' => 'Un intervento a fondo su tutti gli ambienti, per ripartire da spazi in ordine o recuperare lo sporco accumulato.' ),
				array( 'icon' => $why_icons['check'], 'title' => 'Bagni e aree ristoro', 'text' => "Servizi igienici, cucinette e zone caffè: le aree dove l'igiene si nota di più." ),
				array( 'icon' => $why_icons['check'], 'title' => 'Pavimenti e trattamenti specifici', 'text' => 'Lavaggio dei pavimenti e trattamenti adatti al tipo di superficie.' ),
				array( 'icon' => $why_icons['check'], 'title' => 'Vetri e infissi', 'text' => "Vetrate, finestre e infissi, da concordare in base all'accessibilità." ),
				array( 'icon' => $why_icons['check'], 'title' => 'Sanificazione degli ambienti', 'text' => 'Trattamenti di sanificazione per gli ambienti di lavoro, da valutare al sopralluogo.' ),
				array( 'icon' => $why_icons['check'], 'title' => "Dopo lavori o trasloco d'ufficio", 'text' => 'Pulizia di fine cantiere o dopo lo spostamento della sede, per riconsegnare gli spazi pronti all\'uso.' ),
			),
		),
		'hiw'              => array(
			'eyebrow'  => 'COME FUNZIONA IL PREVENTIVO',
			'title'    => 'Dal sopralluogo al preventivo',
			'subtitle' => 'Tre passi, prima di ogni impegno.',
			'steps'    => array(
				array( 'title' => 'Richiedi il preventivo', 'description' => 'Compili il form o ci chiami. Fissiamo il sopralluogo gratuito nel tuo ufficio.' ),
				array( 'title' => 'Sopralluogo gratuito', 'description' => 'Vediamo gli spazi insieme a te e capiamo cosa serve: servizi, orari e frequenza.' ),
				array( 'title' => 'Preventivo su misura', 'description' => 'Ricevi il preventivo sul tuo caso. Se ti convince si decide la data di inizio, altrimenti non hai nessun impegno.' ),
			),
		),
		// Link ai casi vicini: l'altra pagina di settore e la landing di partenza.
		'cross'            => array(
			'eyebrow'  => 'ALTRI SPAZI',
			'title'    => 'Hai anche altri spazi da pulire?',
			'subtitle' => 'Ogni tipo di spazio ha le sue esigenze: scegli la pagina del tuo caso.',
			'items'    => array(
				array( 'badge' => 'CONDOMINI', 'title' => 'Pulizie condomini', 'text' => 'Scale, androni, cortili e parti comuni: servizi per amministratori e condòmini.', 'cta_label' => 'Vai alle pulizie condomini', 'cta_link' => '/roma/pulizie-condomini/', 'cta_position' => 'altri_spazi_condomini', 'cta_variant' => 'ghost', 'dark' => false ),
				array( 'badge' => 'NEGOZI E LOCALI', 'title' => 'Negozi, supermercati e palestre', 'text' => 'Pulizie commerciali per negozi, supermercati, palestre e locali aperti al pubblico.', 'cta_label' => 'Vai alle pulizie commerciali', 'cta_link' => '/roma/sgrosso-commerciale/', 'cta_position' => 'altri_spazi_commerciale', 'cta_variant' => 'primary', 'dark' => true ),
			),
		),
		'faq'              => array_merge( array(
			'eyebrow'  => 'DOMANDE FREQUENTI',
			'title'    => 'Prima di richiedere il preventivo',
			'subtitle' => 'Le risposte rapide. Per tutto il resto, il sopralluogo in sede è gratuito e senza impegno.',
			'items'    => array(
				array( 'question' => 'Quali servizi di pulizia posso richiedere per il mio ufficio?', 'answer' => "Puoi richiedere la pulizia ordinaria periodica, le pulizie straordinarie e di fondo, bagni e aree ristoro, pavimenti e trattamenti specifici, vetri e infissi, la sanificazione degli ambienti e la pulizia dopo lavori o trasloco d'ufficio. Cosa serve davvero al tuo ufficio lo definiamo al sopralluogo." ),
				array( 'question' => 'Come funziona il preventivo?', 'answer' => 'Compili il form o ci chiami. Facciamo un sopralluogo gratuito nel tuo ufficio, vediamo gli spazi e concordiamo servizi, orari e frequenza. Poi ricevi il preventivo sul tuo caso, senza impegno.' ),
				array( 'question' => 'Quanto costa la pulizia di un ufficio?', 'answer' => 'Dipende da superficie, numero di ambienti, servizi richiesti e frequenza. Per gli uffici non pubblichiamo prezzi fissi: dopo il sopralluogo gratuito ricevi un preventivo su misura.' ),
				array( 'question' => 'Il sopralluogo è davvero gratuito?', 'answer' => 'Sì, è gratuito e senza impegno: serve a capire cosa serve all\'ufficio prima di fare il preventivo.' ),
				array( 'question' => 'Posso scegliere la frequenza delle pulizie?', 'answer' => "La frequenza della pulizia ordinaria si concorda al sopralluogo, in base a come vengono usati gli spazi. Gli interventi straordinari, come la pulizia di fondo, si organizzano a parte." ),
				array( 'question' => "Fate anche pulizie dopo lavori o trasloco d'ufficio?", 'answer' => "Sì, è tra i servizi che puoi richiedere: pulizia di fine cantiere o dopo lo spostamento della sede. Per valutarla serve vedere gli spazi, per questo si parte dal sopralluogo gratuito." ),
			),
		), $faq_contact ),
		'cta'              => array(
			'eyebrow'  => 'ULTIMO PASSO',
			'title'    => 'Un preventivo su misura per il tuo ufficio',
			'subtitle' => 'Sopralluogo gratuito in sede, poi il preventivo sul tuo caso. Senza impegno.',
			'bullets'  => array( 'Sopralluogo gratuito in sede', 'Preventivo su misura', 'Frequenza e orari da concordare', 'Senza impegno' ),
		),
	);

	$condomini = array(
		'landing'          => 'pulizie-condomini',
		'service_type'     => 'Pulizie condomini e parti comuni',
		'page_title'       => 'Pulizie condomini Roma: preventivo gratuito | Trovapulizie',
		'page_description' => 'Pulizie condomini a Roma: scale, androni, ascensore, cortili, garage e parti comuni. Sopralluogo gratuito nello stabile e preventivo su misura, senza impegno.',
		'promo_desktop'    => 'Sopralluogo gratuito nello stabile · preventivo su misura · senza impegno',
		'promo_mobile'     => 'Sopralluogo gratuito · senza impegno',
		'nav_links'        => array(
			'#servizi'       => 'Servizi',
			'#come-funziona' => 'Come funziona',
		),
		'nav_cta'          => 'Preventivo gratuito',
		'show'             => array( 'sectors' => false, 'works' => false, 'plans' => false, 'why' => false, 'services' => true, 'cross' => true, 'reviews' => false ),
		'hero'             => array(
			'eyebrow'  => 'PULIZIE CONDOMINI · ROMA',
			'title'    => "Pulizie condomini a Roma.\nUn preventivo su misura per il tuo stabile.",
			'subtitle' => 'Scale, androni e pianerottoli, ascensore, cortili, garage e bidoni della raccolta. Sopralluogo gratuito nello stabile, poi il preventivo.',
			'bullets'  => array(
				'Per amministratori di condominio e condòmini',
				'Frequenza da concordare al sopralluogo',
				'Sopralluogo gratuito, preventivo senza impegno',
			),
			'social'   => '',
			// Immagine decorativa generata (stessa del servizio Condomini nella Home), non una foto di un nostro lavoro.
			'image'    => array(
				'src'    => tp_theme_asset( 'assets/img/services-visual-condomini.jpg' ),
				'mobile' => '',
				'alt'    => "Persona con guanti verdi che lava con il mocio i gradini di marmo di una scala in un palazzo d'epoca, con secchio giallo e androne luminoso sullo sfondo",
				'width'  => 1600,
				'height' => 893,
			),
		),
		'form'             => array(
			'title'      => 'Preventivo per il condominio',
			'subtitle'   => 'Sopralluogo gratuito nello stabile. Ti ricontattiamo per fissarlo.',
			'cta'        => 'Richiedi il preventivo gratuito',
			'note'       => 'Gratis · senza impegno',
			'activities' => array( 'Condominio', 'Amministratore di condominio', 'Complesso residenziale' ),
			'extra'      => array(
				'chooser_label' => 'Tipo di richiesta *',
				'chooser_error' => 'Scegli il tipo di richiesta',
				'show_mq'       => false,
				'done_text'     => $done_text,
			),
		),
		'trust'            => array( 'Sopralluogo gratuito nello stabile', 'Preventivo su misura, senza impegno', 'Frequenza da concordare', 'Roma e provincia' ),
		'services'         => array(
			'eyebrow'  => 'SERVIZI PER CONDOMINI',
			'title'    => 'Cosa puliamo nel tuo condominio',
			'subtitle' => 'Scegli quello che ti serve: servizi, accessi e frequenza si definiscono insieme al sopralluogo gratuito.',
			'items'    => array(
				array( 'icon' => $why_icons['check'], 'title' => 'Scale, androni e pianerottoli', 'text' => 'Lavaggio e pulizia delle parti di passaggio del palazzo, con frequenza da concordare.' ),
				array( 'icon' => $why_icons['check'], 'title' => 'Ascensore', 'text' => "Cabina, pulsantiere e specchi dell'ascensore." ),
				array( 'icon' => $why_icons['check'], 'title' => 'Vetri, portoni e citofoni', 'text' => "Vetri dell'androne e delle scale, portoni e citofoni." ),
				array( 'icon' => $why_icons['check'], 'title' => 'Cortili, aree esterne e posti auto', 'text' => 'Pulizia di cortili, spazi esterni e posti auto del condominio.' ),
				array( 'icon' => $why_icons['check'], 'title' => 'Garage e cantine', 'text' => 'Pulizia di garage, box comuni e cantine.' ),
				array( 'icon' => $why_icons['check'], 'title' => 'Bidoni della raccolta', 'text' => "Gestione e lavaggio dei bidoni della raccolta, da concordare con l'amministratore." ),
				array( 'icon' => $why_icons['check'], 'title' => 'Sanificazione delle parti comuni', 'text' => 'Trattamenti di sanificazione per scale, androni e altre parti comuni, da valutare al sopralluogo.' ),
				array( 'icon' => $why_icons['check'], 'title' => 'Dopo lavori condominiali', 'text' => 'Pulizia di fine cantiere dopo lavori nelle parti comuni: via polvere e residui prima di riaprire gli spazi.' ),
			),
		),
		'hiw'              => array(
			'eyebrow'  => 'COME FUNZIONA IL PREVENTIVO',
			'title'    => 'Dal sopralluogo al preventivo',
			'subtitle' => 'Tre passi, prima di ogni impegno.',
			'steps'    => array(
				array( 'title' => 'Richiedi il preventivo', 'description' => 'Compili il form o ci chiami. Fissiamo il sopralluogo gratuito nello stabile.' ),
				array( 'title' => 'Sopralluogo gratuito', 'description' => 'Vediamo le parti comuni insieme a te e capiamo cosa serve: servizi, accessi e frequenza.' ),
				array( 'title' => 'Preventivo su misura', 'description' => 'Ricevi il preventivo sul tuo caso, pronto da condividere con amministratore e condòmini. Nessun impegno.' ),
			),
		),
		'cross'            => array(
			'eyebrow'  => 'ALTRI SPAZI',
			'title'    => 'Hai anche altri spazi da pulire?',
			'subtitle' => 'Ogni tipo di spazio ha le sue esigenze: scegli la pagina del tuo caso.',
			'items'    => array(
				array( 'badge' => 'UFFICI', 'title' => 'Pulizie uffici', 'text' => 'Postazioni, bagni, aree ristoro e vetri: servizi per uffici e studi professionali.', 'cta_label' => 'Vai alle pulizie uffici', 'cta_link' => '/roma/pulizie-uffici/', 'cta_position' => 'altri_spazi_uffici', 'cta_variant' => 'ghost', 'dark' => false ),
				array( 'badge' => 'NEGOZI E LOCALI', 'title' => 'Negozi, supermercati e palestre', 'text' => 'Pulizie commerciali per negozi, supermercati, palestre e locali aperti al pubblico.', 'cta_label' => 'Vai alle pulizie commerciali', 'cta_link' => '/roma/sgrosso-commerciale/', 'cta_position' => 'altri_spazi_commerciale', 'cta_variant' => 'primary', 'dark' => true ),
			),
		),
		'faq'              => array_merge( array(
			'eyebrow'  => 'DOMANDE FREQUENTI',
			'title'    => 'Prima di richiedere il preventivo',
			'subtitle' => 'Le risposte rapide. Per tutto il resto, il sopralluogo nello stabile è gratuito e senza impegno.',
			'items'    => array(
				array( 'question' => 'Quali servizi di pulizia posso richiedere per un condominio?', 'answer' => "Puoi richiedere la pulizia di scale, androni e pianerottoli, dell'ascensore, di vetri, portoni e citofoni, di cortili, aree esterne e posti auto, di garage e cantine, la gestione e il lavaggio dei bidoni della raccolta, la sanificazione delle parti comuni e la pulizia dopo lavori condominiali. Cosa serve al tuo stabile lo definiamo al sopralluogo." ),
				array( 'question' => 'Come funziona il preventivo?', 'answer' => 'Compili il form o ci chiami. Facciamo un sopralluogo gratuito nello stabile, vediamo le parti comuni e concordiamo servizi e frequenza. Poi ricevi il preventivo sul tuo caso, senza impegno.' ),
				array( 'question' => 'Quanto costa la pulizia di un condominio?', 'answer' => 'Dipende da numero di scale e piani, metratura delle parti comuni, servizi richiesti e frequenza. Non pubblichiamo prezzi fissi: dopo il sopralluogo gratuito ricevi un preventivo su misura.' ),
				array( 'question' => "Posso chiedere il preventivo se non sono l'amministratore?", 'answer' => "Sì, puoi richiederlo anche come condomino e poi condividerlo con l'amministratore." ),
				array( 'question' => 'Il sopralluogo è davvero gratuito?', 'answer' => 'Sì, è gratuito e senza impegno: serve a capire cosa serve allo stabile prima di fare il preventivo.' ),
				array( 'question' => 'Con che frequenza si puliscono le parti comuni?', 'answer' => 'La frequenza della pulizia ordinaria si concorda al sopralluogo, in base alle dimensioni dello stabile e a come vengono usati gli spazi. Gli interventi straordinari si organizzano a parte.' ),
			),
		), $faq_contact ),
		'cta'              => array(
			'eyebrow'  => 'ULTIMO PASSO',
			'title'    => 'Un preventivo su misura per il tuo condominio',
			'subtitle' => 'Sopralluogo gratuito nello stabile, poi il preventivo sul tuo caso. Senza impegno.',
			'bullets'  => array( 'Sopralluogo gratuito nello stabile', 'Preventivo su misura', 'Frequenza da concordare', 'Senza impegno' ),
		),
	);

	return array(
		'sgrosso-commerciale' => $commerciale,
		'pulizie-uffici'      => $uffici,
		'pulizie-condomini'   => $condomini,
	);
}
