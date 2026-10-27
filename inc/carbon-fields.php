<?php
/**
 * Campi editoriali del tema (Carbon Fields).
 *
 * - Container post_meta "Home — Sezioni": solo sulle pagine con template page-home.php, un tab per sezione
 *   (Hero, Come funziona, Telefono 3D, Servizi Popolari, Social Proof, Dove Siamo Attivi, FAQ, CTA Finale).
 *   Letti da page-home.php con carbon_get_the_post_meta().
 * - Container theme_options "Footer" (menu wp-admin "Footer", page file tp-footer). Letto da footer.php con
 *   carbon_get_theme_option().
 * Tabella campi -> uso nei template: docs/homepage.md §3.
 *
 * [ATTENZIONE] Tutti i Container::make() devono stare dentro questo callback: un container registrato fuori
 * dal hook carbon_fields_register_fields manda in fatal l'intero sito (successo il 15/10 su staging).
 * I file e le immagini usano set_value_type('url'): i template ricevono URL, non ID di allegato
 * (eccezione: `image` degli step, ID risolto con wp_get_attachment_url in page-home.php).
 */
use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action( 'carbon_fields_register_fields', function () {

	// Scheda SEO su tutte le pagine (inc/seo.php). Title e description: campo compilato > default della
	// variante sulle landing Sgrosso > titolo WordPress / motto del sito.
	Container::make( 'post_meta', __( 'SEO' ) )
		->where( 'post_type', '=', 'page' )
		->set_context( 'side' )
		->add_fields( array(
			Field::make( 'text', 'seo_title', __( 'Title' ) )
				->set_help_text( __( 'Fino a 60 caratteri. Vuoto = sulle landing Sgrosso il title della variante, altrove "Titolo pagina – Trovapulizie".' ) ),
			Field::make( 'textarea', 'seo_description', __( 'Meta description' ) )
				->set_help_text( __( 'Da 120 a 160 caratteri. Vuota = sulle landing Sgrosso la description della variante, altrove il motto del sito.' ) ),
		) );

	Container::make( 'post_meta', __( 'Home — Sezioni' ) )
		->where( 'post_template', '=', 'page-home.php' )
		->add_tab( __( 'Hero' ), array(
			Field::make( 'textarea', 'hero_title', __( 'Titolo' ) )
				->set_help_text( __( 'Un&#39;invio a capo = una riga nel titolo (il Figma ha 3 righe: "Prenota pulizie" / "professionali" / "In 2 minuti").' ) ),
			Field::make( 'textarea', 'hero_subtitle', __( 'Sottotitolo' ) ),
			Field::make( 'text', 'hero_trust_badge', __( 'Trust badge' ) ),
			Field::make( 'file', 'hero_video', __( 'Video di sfondo (mp4 muto, ~3 s)' ) )
				->set_type( 'video' )
				->set_value_type( 'url' )
				->set_help_text( __( 'Timelapse da stanza in disordine a stanza in ordine. Parte da solo (muto), si ferma sull&#39;ultimo frame, sotto lo stesso scrim verde della foto. Se vuoto o con &quot;riduci movimento&quot; attivo resta la foto statica.' ) ),
		) )
		->add_tab( __( 'Come funziona' ), array(
			Field::make( 'text', 'how_it_works_title', __( 'Titolo sezione' ) )
				->set_default_value( 'Come funziona' ),
			Field::make( 'text', 'how_it_works_subtitle', __( 'Sottotitolo' ) )
				->set_default_value( 'Prenoti in 2 minuti. Guarda il flusso mentre scorri.' ),
			Field::make( 'file', 'how_it_works_video', __( 'Video schermo telefono (mp4 muto)' ) )
				->set_type( 'video' )
				->set_value_type( 'url' )
				->set_help_text( __( 'Desktop: scorre con lo scroll (3 step a terzi, ultimo frame = immagine Banda Visual). Vincoli completi in ADR-002.' ) ),
			Field::make( 'image', 'how_it_works_poster', __( 'Poster video (primo frame)' ) )
				->set_value_type( 'url' ),
			Field::make( 'file', 'how_it_works_zoom_video', __( 'Video zoom-through (mp4 muto, loop)' ) )
				->set_type( 'video' )
				->set_value_type( 'url' )
				->set_help_text( __( 'Desktop: a fine scroll esce dallo schermo del telefono a tutto schermo e poi rientra nella foto della prima card di Servizi popolari, dove resta in loop. Primo frame = ultimo frame del video schermo = foto della prima card. Mobile e &quot;riduci movimento&quot;: non usato.' ) ),
			Field::make( 'complex', 'how_it_works_steps', __( 'Step' ) )
				->set_layout( 'tabbed-vertical' )
				->set_max( 3 )
				->add_fields( array(
					Field::make( 'text', 'title', __( 'Titolo' ) ),
					Field::make( 'textarea', 'description', __( 'Descrizione' ) ),
					Field::make( 'image', 'image', __( 'Schermo telefono (mobile e reduced-motion)' ) ),
				) ),
		) )
		->add_tab( __( 'Telefono 3D' ), array(
			Field::make( 'file', 'phone_model_url', __( 'Modello GLB (opzionale)' ) )
				->set_value_type( 'url' )
				->set_help_text( __( 'Telefono generico senza marchio, formato .glb, consigliato < 2 MB. Vuoto = corpo procedurale.' ) ),
			Field::make( 'text', 'phone_model_credit', __( 'Credito autore (footer)' ) )
				->set_help_text( __( 'Obbligatorio per modelli CC-BY, es. "Modello 3D: Nome Autore (Sketchfab), CC BY 4.0".' ) ),
		) )
		->add_tab( __( 'Servizi Popolari' ), array(
			Field::make( 'text', 'services_title', __( 'Titolo sezione' ) )
				->set_default_value( 'Servizi popolari nella tua zona' ),
			Field::make( 'text', 'services_subtitle', __( 'Sottotitolo' ) )
				->set_default_value( 'Trova professionisti verificati vicino a te.' ),
			Field::make( 'image', 'services_proof_avatar', __( 'Social proof: avatar' ) )
				->set_value_type( 'url' )
				->set_help_text( __( 'Vuoto = avatar del Figma.' ) ),
			Field::make( 'text', 'services_proof_title', __( 'Social proof: riga 1' ) )
				->set_default_value( 'Ottimo servizio' ),
			Field::make( 'text', 'services_proof_sub', __( 'Social proof: riga 2' ) )
				->set_default_value( '100% soddisfatti' ),
			Field::make( 'text', 'services_proof_rating', __( 'Social proof: valutazione' ) )
				->set_default_value( '4,9 valutazione media' ),
			Field::make( 'complex', 'popular_services', __( 'Servizi (una tab ciascuno, max 6)' ) )
				->set_layout( 'tabbed-vertical' )
				->set_max( 6 )
				->add_fields( array(
					Field::make( 'text', 'tab_label', __( 'Etichetta tab' ) ),
					Field::make( 'select', 'tab_icon', __( 'Icona tab' ) )
						->set_options( array( 'casa' => 'Casa', 'uffici' => 'Uffici', 'cantieri' => 'Cantieri / a fondo', 'condomini' => 'Condomini', 'sanificazione' => 'Sanificazione', 'affitti' => 'Affitti brevi' ) ),
					Field::make( 'textarea', 'includes', __( 'Cosa include (una voce per riga)' ) ),
					Field::make( 'text', 'card_title', __( 'Titolo card' ) ),
					Field::make( 'textarea', 'card_description', __( 'Descrizione card' ) ),
					Field::make( 'text', 'highlight', __( 'Riga in evidenza (verde)' ) )
						->set_help_text( __( 'Es. "Più richiesto: pulizia settimanale ricorrente".' ) ),
					Field::make( 'image', 'card_image', __( 'Foto' ) )
						->set_value_type( 'url' )
						->set_help_text( __( 'Vuota = foto Pulizia casa del Figma. Sotto c&#39;e&#39; sempre il velo verde.' ) ),
					Field::make( 'text', 'visual_label', __( 'Etichetta sulla foto' ) ),
					Field::make( 'text', 'link', __( 'Link CTA' ) ),
					Field::make( 'text', 'link_text', __( 'Testo CTA' ) )
						->set_default_value( 'Prenota ora' ),
				) ),
		) )
		->add_tab( __( 'Social Proof' ), array(
			Field::make( 'text', 'reviews_badge', __( 'Badge (es. "5.0 stelle sulle recensioni Google")' ) )
				->set_help_text( __( 'Solo dati veri: la valutazione deve corrispondere al profilo. Vuoto = niente badge.' ) ),
			Field::make( 'select', 'reviews_badge_source', __( 'Logo nel badge' ) )
				->set_options( array( 'google' => 'Google', 'none' => 'Nessuno' ) ),
			Field::make( 'text', 'reviews_badge_link', __( 'Link del badge (es. pagina recensioni Google)' ) ),
			Field::make( 'text', 'reviews_title', __( 'Titolo' ) )
				->set_default_value( 'Scelto da chi vuole il meglio' ),
			Field::make( 'complex', 'reviews', __( 'Recensioni (desktop: scorrono in continuo; mobile: le prime 3)' ) )
				->set_layout( 'tabbed-vertical' )
				->add_fields( array(
					Field::make( 'text', 'author_name', __( 'Nome (es. Laura M.)' ) ),
					Field::make( 'text', 'author_city', __( 'Citta' ) ),
					Field::make( 'image', 'avatar', __( 'Foto' ) )
						->set_value_type( 'url' )
						->set_help_text( __( 'Vuota = avatar del Figma a rotazione.' ) ),
					Field::make( 'textarea', 'review_text', __( 'Testo recensione' ) ),
				) ),
		) )
		->add_tab( __( 'Dove Siamo Attivi' ), array(
			Field::make( 'text', 'zones_title', __( 'Titolo' ) )
				->set_default_value( 'Dove siamo attivi' ),
			Field::make( 'text', 'zones_description', __( 'Testo sotto la mappa' ) )
				->set_default_value( 'Trova professionisti delle pulizie nella tua città.' ),
			Field::make( 'image', 'zones_map_image', __( 'Mappa (raster)' ) )
				->set_value_type( 'url' )
				->set_help_text( __( 'Vuota = mappa di Roma del Figma, con le zone verdi (Centro e Prati) e l&#39;alone animato. Con una mappa diversa le zone verdi non vengono mostrate.' ) ),
			Field::make( 'text', 'zones_coming_label', __( 'Etichetta citta in arrivo' ) )
				->set_default_value( 'Prossimamente' ),
			Field::make( 'textarea', 'zones_coming_cities', __( 'Citta in arrivo (una per riga)' ) ),
		) )
		->add_tab( __( 'FAQ' ), array(
			Field::make( 'text', 'faq_eyebrow', __( 'Pillola sopra il titolo' ) )
				->set_default_value( 'SUPPORTO' ),
			Field::make( 'text', 'faq_title', __( 'Titolo' ) )
				->set_default_value( 'Domande frequenti' ),
			Field::make( 'textarea', 'faq_subtitle', __( 'Testo sotto il titolo' ) ),
			Field::make( 'separator', 'faq_contact_sep', __( 'Card di contatto (colonna sinistra)' ) ),
			Field::make( 'text', 'faq_contact_people', __( 'Nomi (es. Guido & Christian)' ) ),
			Field::make( 'text', 'faq_contact_role', __( 'Ruolo (es. Fondatori)' ) ),
			Field::make( 'image', 'faq_contact_avatar_1', __( 'Foto 1' ) )
				->set_value_type( 'url' )
				->set_help_text( __( 'Vuota = foto del Figma.' ) ),
			Field::make( 'image', 'faq_contact_avatar_2', __( 'Foto 2' ) )
				->set_value_type( 'url' ),
			Field::make( 'text', 'faq_contact_title', __( 'Titolo card' ) ),
			Field::make( 'textarea', 'faq_contact_text', __( 'Testo card' ) ),
			Field::make( 'text', 'faq_contact_cta_label', __( 'Bottone: testo' ) ),
			Field::make( 'text', 'faq_contact_cta_link', __( 'Bottone: URL' ) ),
			Field::make( 'separator', 'faq_items_sep', __( 'Domande' ) ),
			Field::make( 'complex', 'faq_items', __( 'FAQ (la prima parte aperta; una aperta per volta)' ) )
				->set_layout( 'tabbed-vertical' )
				->add_fields( array(
					Field::make( 'text', 'question', __( 'Domanda' ) ),
					Field::make( 'textarea', 'answer', __( 'Risposta' ) ),
				) ),
		) )
		->add_tab( __( 'CTA Finale' ), array(
			Field::make( 'text', 'cta_title', __( 'Titolo' ) )
				->set_default_value( 'Pronto a provare?' ),
			Field::make( 'textarea', 'cta_subtitle', __( 'Sottotitolo' ) )
				->set_default_value( 'Registrati gratis e prenota il tuo primo servizio di pulizia in meno di un minuto.' ),
			Field::make( 'text', 'cta_button_text', __( 'Testo bottone' ) )
				->set_default_value( 'Registrati gratis' ),
			Field::make( 'text', 'cta_button_link', __( 'Link bottone' ) ),
			Field::make( 'text', 'cta_note', __( 'Nota sotto il bottone' ) )
				->set_default_value( 'Gratis · Nessuna carta richiesta · Disdici quando vuoi' ),
			Field::make( 'image', 'cta_image', __( 'Illustrazione' ) )
				->set_value_type( 'url' )
				->set_help_text( __( 'Vuota = mascotte con il mocio del Figma.' ) ),
		) );


	// Hub Roma — Figma 4422:921 (desktop) / 3927:1280 (mobile). PRD: SPEC-pagina-hub-roma.
	// Prefisso roma_ su tutti i campi: la scheda convive con quella della Home e i nomi non
	// devono potersi confondere leggendo un template.
	Container::make( 'post_meta', __( 'Hub Roma — Sezioni' ) )
		->where( 'post_template', '=', 'page-hub-roma.php' )
		->add_tab( __( 'Hero' ), array(
			Field::make( 'text', 'roma_hero_eyebrow', __( 'Pillola sopra il titolo' ) ),
			Field::make( 'textarea', 'roma_hero_title', __( 'Titolo (H1)' ) )
				->set_help_text( __( 'Un invio a capo = una riga nel titolo.' ) ),
			Field::make( 'textarea', 'roma_hero_subtitle', __( 'Sottotitolo' ) ),
		) )
		->add_tab( __( 'Zone' ), array(
			Field::make( 'text', 'roma_zones_title', __( 'Titolo' ) )
				->set_default_value( 'Pulizie per zona a Roma' ),
			Field::make( 'textarea', 'roma_zones_description', __( 'Testo sotto il titolo' ) ),
			Field::make( 'text', 'roma_zones_cta_label', __( 'Testo della CTA del pannello' ) ),
		) )
		->add_tab( __( 'Servizi' ), array(
			Field::make( 'text', 'roma_services_title', __( 'Titolo sezione' ) ),
			Field::make( 'text', 'roma_services_subtitle', __( 'Sottotitolo' ) ),
			Field::make( 'complex', 'roma_services', __( 'Servizi (una tab ciascuno)' ) )
				->add_fields( array(
					Field::make( 'text', 'tab_label', __( 'Etichetta tab' ) ),
					// Stesso set di icone della Home: 'cantieri' e' gia' l'icona di "a fondo"
					// (lo dice la sua etichetta), non serve disegnarne una nuova.
					Field::make( 'select', 'tab_icon', __( 'Icona tab' ) )
						->set_options( array( 'casa' => 'Casa', 'uffici' => 'Uffici', 'cantieri' => 'Cantieri / a fondo', 'condomini' => 'Condomini', 'sanificazione' => 'Sanificazione', 'affitti' => 'Affitti brevi' ) ),
					Field::make( 'textarea', 'includes', __( 'Cosa include (una voce per riga)' ) ),
					Field::make( 'text', 'card_title', __( 'Titolo card' ) ),
					Field::make( 'textarea', 'card_description', __( 'Descrizione card' ) ),
					Field::make( 'text', 'highlight', __( 'Riga in evidenza' ) ),
					Field::make( 'image', 'card_image', __( 'Foto' ) )
						->set_value_type( 'url' ),
					Field::make( 'text', 'link_text', __( 'Testo CTA' ) ),
					Field::make( 'text', 'link', __( 'Link CTA' ) ),
				) ),
		) )
		->add_tab( __( 'Verificati' ), array(
			Field::make( 'text', 'roma_verified_eyebrow', __( 'Pillola sopra il titolo' ) ),
			Field::make( 'text', 'roma_verified_title', __( 'Titolo' ) ),
			Field::make( 'textarea', 'roma_verified_text', __( 'Testo' ) ),
			Field::make( 'complex', 'roma_verified_steps', __( 'Step di verifica' ) )
				->add_fields( array(
					Field::make( 'text', 'title', __( 'Titolo' ) ),
					Field::make( 'text', 'description', __( 'Descrizione' ) ),
				) ),
			Field::make( 'text', 'roma_verified_cta_label', __( 'Testo del bottone' ) ),
			Field::make( 'text', 'roma_verified_cta_link', __( 'Link del bottone' ) ),
		) )
		->add_tab( __( 'Come funziona' ), array(
			Field::make( 'text', 'roma_hiw_eyebrow', __( 'Pillola sopra il titolo' ) ),
			Field::make( 'text', 'roma_hiw_title', __( 'Titolo' ) ),
			Field::make( 'text', 'roma_hiw_subtitle', __( 'Sottotitolo' ) ),
			Field::make( 'complex', 'roma_hiw_steps', __( 'Passi (tre nel Figma)' ) )
				->add_fields( array(
					Field::make( 'text', 'title', __( 'Titolo' ) ),
					Field::make( 'textarea', 'description', __( 'Descrizione' ) ),
				) ),
			Field::make( 'image', 'roma_hiw_screen', __( 'Schermata nel telefono' ) )
				->set_value_type( 'url' )
				->set_help_text( __( 'Immagine gia ridimensionata: lo schermo e largo 294px su desktop.' ) ),
		) )
		->add_tab( __( 'Quanto costa' ), array(
			Field::make( 'text', 'roma_price_eyebrow', __( 'Pillola sopra il titolo' ) ),
			Field::make( 'text', 'roma_price_title', __( 'Titolo' ) ),
			Field::make( 'text', 'roma_price_subtitle', __( 'Sottotitolo' ) ),
			// [ATTENZIONE] Questi due numeri devono restare uguali a quelli dichiarati nella FAQ
			// sul prezzo: sono la stessa promessa detta due volte nella stessa pagina.
			Field::make( 'text', 'roma_price_min', __( 'Tariffa oraria minima (euro)' ) )
				->set_default_value( '15' ),
			Field::make( 'text', 'roma_price_max', __( 'Tariffa oraria massima (euro)' ) )
				->set_default_value( '20' ),
			Field::make( 'text', 'roma_price_cta_label', __( 'Bottone: testo' ) ),
			Field::make( 'text', 'roma_price_cta_link', __( 'Bottone: URL' ) ),
			Field::make( 'textarea', 'roma_price_note', __( 'Nota sotto il bottone' ) ),
		) )
		->add_tab( __( 'FAQ' ), array(
			Field::make( 'text', 'roma_faq_eyebrow', __( 'Pillola sopra il titolo' ) ),
			Field::make( 'text', 'roma_faq_title', __( 'Titolo' ) ),
			Field::make( 'textarea', 'roma_faq_subtitle', __( 'Testo sotto il titolo' ) ),
			Field::make( 'separator', 'roma_faq_contact_sep', __( 'Card di contatto (colonna sinistra)' ) ),
			Field::make( 'text', 'roma_faq_contact_people', __( 'Nomi' ) ),
			Field::make( 'text', 'roma_faq_contact_role', __( 'Ruolo' ) ),
			Field::make( 'image', 'roma_faq_contact_avatar_1', __( 'Foto 1' ) )
				->set_value_type( 'url' ),
			Field::make( 'image', 'roma_faq_contact_avatar_2', __( 'Foto 2' ) )
				->set_value_type( 'url' ),
			Field::make( 'text', 'roma_faq_contact_title', __( 'Titolo card' ) ),
			Field::make( 'textarea', 'roma_faq_contact_text', __( 'Testo card' ) ),
			Field::make( 'text', 'roma_faq_contact_cta_label', __( 'Bottone: testo' ) ),
			Field::make( 'text', 'roma_faq_contact_cta_link', __( 'Bottone: URL' ) ),
			Field::make( 'separator', 'roma_faq_items_sep', __( 'Domande' ) ),
			Field::make( 'complex', 'roma_faq_items', __( 'FAQ (la prima parte aperta)' ) )
				->add_fields( array(
					Field::make( 'text', 'question', __( 'Domanda' ) ),
					Field::make( 'textarea', 'answer', __( 'Risposta' ) ),
				) ),
		) )
		->add_tab( __( 'CTA e Intro' ), array(
			Field::make( 'text', 'roma_cta_title', __( 'CTA: titolo' ) ),
			Field::make( 'textarea', 'roma_cta_subtitle', __( 'CTA: sottotitolo' ) ),
			Field::make( 'text', 'roma_cta_button_text', __( 'CTA: testo bottone' ) ),
			Field::make( 'text', 'roma_cta_button_link', __( 'CTA: link bottone' ) ),
			Field::make( 'image', 'roma_cta_image', __( 'CTA: illustrazione' ) )
				->set_value_type( 'url' ),
			Field::make( 'separator', 'roma_intro_sep', __( 'Testo introduttivo in fondo' ) ),
			Field::make( 'text', 'roma_intro_eyebrow', __( 'Pillola' ) ),
			Field::make( 'textarea', 'roma_intro_text', __( 'Testo' ) ),
		) );


	// Landing Sgrosso — Figma 4364:6628 / 4364:7087, contenuti dal live. Solo sulle pagine col template
	// page-sgrosso.php (cinque: generica + quattro verticali). I testi che cambiano per variante hanno il
	// default in inc/sgrosso-variants.php: un campo vuoto = default della variante, un campo compilato vince.
	$sg_variant_help = __( 'Vuoto = testo della variante scelta nella tab Variante.' );
	Container::make( 'post_meta', __( 'Landing Sgrosso — Sezioni' ) )
		->where( 'post_template', '=', 'page-sgrosso.php' )
		->add_tab( __( 'Variante' ), array(
			Field::make( 'select', 'sg_variant', __( 'Variante della landing' ) )
				->set_options( array(
					'sgrosso-casa'          => 'Sgrosso casa (generica, /roma/sgrosso-casa)',
					'post-ristrutturazione' => 'Post-ristrutturazione',
					'trasloco'              => 'Trasloco',
					'fine-affitto'          => 'Fine affitto',
					'prima-pulizia-a-fondo' => 'Prima pulizia a fondo',
				) )
				->set_help_text( __( 'Decide i testi di default, la tab aperta in Quando serve, il tipo di pulizia preselezionato nel form e il nome della landing negli eventi di tracking.' ) ),
		) )
		->add_tab( __( 'Testata' ), array(
			Field::make( 'text', 'sg_promo_desktop', __( 'Promo bar, testo desktop' ) )
				->set_help_text( __( 'Vuoto = "Sopralluogo gratuito · -30€ sul primo lavoro · ti rispondiamo entro 1 ora".' ) ),
			Field::make( 'text', 'sg_promo_mobile', __( 'Promo bar, testo mobile' ) )
				->set_help_text( __( 'Vuoto = "Sopralluogo gratuito · -30€ sul primo lavoro".' ) ),
			Field::make( 'text', 'sg_nav_phone', __( 'Telefono in testata' ) )
				->set_help_text( __( 'Vuoto = 06 9151 6075. Il link tel: si ricava dalle sole cifre.' ) ),
			Field::make( 'text', 'sg_nav_cta', __( 'Testo della CTA in testata' ) )
				->set_help_text( __( 'Vuoto = "Calcola il preventivo gratis". Porta sempre al form.' ) ),
		) )
		->add_tab( __( 'Hero' ), array(
			Field::make( 'text', 'sg_hero_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( $sg_variant_help ),
			Field::make( 'textarea', 'sg_hero_title', __( 'Titolo (H1)' ) )
				->set_help_text( __( 'Un invio a capo = una riga nel titolo. Vuoto = titolo della variante.' ) ),
			Field::make( 'textarea', 'sg_hero_subtitle', __( 'Sottotitolo' ) )->set_help_text( $sg_variant_help ),
			Field::make( 'textarea', 'sg_hero_bullets', __( 'Punti di valore (uno per riga)' ) )
				->set_help_text( __( 'Vuoto = i tre punti del live.' ) ),
			Field::make( 'text', 'sg_hero_social', __( 'Riga sotto le stelle' ) )
				->set_help_text( __( 'Vuoto = "Famiglie a Parioli, Prati, EUR e Roma Nord".' ) ),
			Field::make( 'image', 'sg_hero_image', __( 'Foto di sfondo' ) )
				->set_value_type( 'url' )
				->set_help_text( __( 'Vuota = foto della variante nel tema (assets/img/sgrosso). Consigliati 1920x1071.' ) ),
		) )
		->add_tab( __( 'Form' ), array(
			Field::make( 'text', 'sg_form_title', __( 'Titolo del passo 1' ) )->set_help_text( __( 'Vuoto = "I tuoi dati di contatto".' ) ),
			Field::make( 'text', 'sg_form_subtitle', __( 'Testo sotto il titolo' ) )->set_help_text( __( "Vuoto = \"Ti risponderemo entro un'ora con una prima valutazione.\"" ) ),
			Field::make( 'text', 'sg_form_cta', __( 'Bottone del passo 1' ) )->set_help_text( __( 'Vuoto = "Prenota sopralluogo gratuito". I passi 2 e 3 dicono Continua e Invia richiesta.' ) ),
			Field::make( 'text', 'sg_form_note', __( 'Nota sotto il bottone' ) )->set_help_text( __( 'Vuoto = "Gratis · nessun impegno · risposta in 1h".' ) ),
		) )
		->add_tab( __( 'Trust bar' ), array(
			Field::make( 'textarea', 'sg_trust_items', __( 'Garanzie (una per riga)' ) )
				->set_help_text( __( 'Vuoto = Sopralluogo gratuito / Soddisfatti o ripuliamo / Verificati e assicurati / Roma e provincia. Sono promesse pubbliche: cambiarle qui vale anche come impegno.' ) ),
		) )
		->add_tab( __( 'Confrontiamo' ), array(
			Field::make( 'text', 'sg_cmp_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( __( 'Vuoto = PERCHE\' TROVAPULIZIE.' ) ),
			Field::make( 'textarea', 'sg_cmp_title', __( 'Titolo (H2)' ) )->set_help_text( __( 'Un invio a capo = una riga. Vuoto = "Confrontiamo tutta Roma / Tu paghi il prezzo migliore".' ) ),
			Field::make( 'textarea', 'sg_cmp_subtitle', __( 'Sottotitolo' ) )->set_help_text( $sg_variant_help ),
			Field::make( 'complex', 'sg_cmp_pillars', __( 'Pilastri (vuoto = i quattro del live)' ) )
				->add_fields( array(
					Field::make( 'text', 'title', __( 'Titolo' ) ),
					Field::make( 'text', 'description', __( 'Descrizione' ) ),
				) ),
			Field::make( 'text', 'sg_cmp_cta_label', __( 'Bottone: testo' ) )->set_help_text( __( 'Vuoto = "Calcola il preventivo gratis".' ) ),
			Field::make( 'text', 'sg_cmp_cta_link', __( 'Bottone: link' ) )->set_help_text( __( 'Vuoto = #form.' ) ),
			Field::make( 'separator', 'sg_cmp_card_sep', __( 'Card "Preventivi a confronto"' ) ),
			Field::make( 'text', 'sg_cmp_card_title', __( 'Titolo card' ) )->set_help_text( __( 'Vuoto = "Preventivi a confronto".' ) ),
			Field::make( 'text', 'sg_cmp_card_disclaimer', __( 'Riga sotto il titolo' ) )->set_help_text( __( 'Vuoto = "Esempio illustrativo di come funziona il confronto". Le offerte non sono imprese reali: la riga resta.' ) ),
			Field::make( 'text', 'sg_cmp_card_chip', __( 'Chip a destra' ) )->set_help_text( __( 'Vuoto = "Roma e provincia".' ) ),
			Field::make( 'complex', 'sg_cmp_offers', __( 'Offerte d\'esempio (vuoto = le tre del live)' ) )
				->add_fields( array(
					Field::make( 'image', 'logo', __( 'Logo (tondo, 36px)' ) )->set_value_type( 'url' ),
					Field::make( 'text', 'name', __( 'Nome' ) ),
					Field::make( 'text', 'badge', __( 'Riga sotto il nome' ) ),
					Field::make( 'text', 'price', __( 'Prezzo' ) ),
					Field::make( 'checkbox', 'highlighted', __( 'Evidenziata (miglior prezzo)' ) ),
				) ),
			Field::make( 'image', 'sg_cmp_team_image', __( 'Team: foto' ) )->set_value_type( 'url' )->set_help_text( __( 'Vuota = foto del tema. Resa 74x52.' ) ),
			Field::make( 'text', 'sg_cmp_team_eyebrow', __( 'Team: pillola' ) )->set_help_text( __( 'Vuoto = CHI VERRA\' A CASA TUA.' ) ),
			Field::make( 'text', 'sg_cmp_team_title', __( 'Team: titolo' ) )->set_help_text( __( 'Vuoto = "Il team dell\'impresa scelta".' ) ),
			Field::make( 'text', 'sg_cmp_team_sub', __( 'Team: riga sotto' ) )->set_help_text( __( 'Vuoto = "Squadra verificata e assicurata".' ) ),
			Field::make( 'textarea', 'sg_cmp_note', __( 'Nota in fondo alla card' ) )->set_help_text( __( 'Vuoto = "Il prezzo definitivo e\' confermato dopo il sopralluogo gratuito. Nessuna sorpresa."' ) ),
		) )
		->add_tab( __( 'Quando serve' ), array(
			Field::make( 'text', 'sg_when_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( __( 'Vuoto = QUANDO SERVE.' ) ),
			Field::make( 'text', 'sg_when_title', __( 'Titolo (H2)' ) )->set_help_text( $sg_variant_help ),
			Field::make( 'complex', 'sg_when_scenarios', __( 'Scenari (vuoto = i quattro del live)' ) )
				->set_help_text( __( 'Sulla landing generica sono tab; sulle verticali esce solo lo scenario della variante. Le chiavi devono coincidere con i tipi di pulizia del form.' ) )
				->add_fields( array(
					Field::make( 'select', 'key', __( 'Chiave' ) )
						->set_options( array( 'post-ristrutturazione' => 'Post-ristrutturazione', 'trasloco' => 'Trasloco', 'fine-affitto' => 'Fine affitto', 'prima-pulizia-a-fondo' => 'Prima pulizia a fondo' ) ),
					Field::make( 'text', 'label', __( 'Etichetta della tab' ) ),
					Field::make( 'text', 'title', __( 'Titolo del dettaglio' ) ),
					Field::make( 'textarea', 'text', __( 'Testo' ) ),
					Field::make( 'text', 'highlight', __( 'Riga in evidenza' ) ),
					Field::make( 'image', 'before', __( 'Foto prima' ) )->set_value_type( 'url' ),
					Field::make( 'image', 'after', __( 'Foto dopo' ) )->set_value_type( 'url' ),
					Field::make( 'textarea', 'areas', __( 'Aree evidenziate (una per riga, fra quelle della lista)' ) ),
				) ),
			Field::make( 'textarea', 'sg_when_areas', __( 'Lista delle aree (una per riga)' ) )->set_help_text( __( 'Vuoto = Cucina / Bagni e calcare / Dietro i mobili / Vetri e infissi / Soffitti / Pavimenti.' ) ),
			Field::make( 'text', 'sg_when_areas_title', __( 'Titolo del riquadro aree' ) )->set_help_text( __( 'Vuoto = "Tutte le aree, sempre".' ) ),
			Field::make( 'textarea', 'sg_when_areas_text', __( 'Testo sotto le aree' ) )->set_help_text( __( 'Vuoto = "Lo sgrosso copre tutte le stanze a fondo. Cambia il focus in base alla tua situazione."' ) ),
			Field::make( 'text', 'sg_when_cta_label', __( 'Bottone: testo' ) )->set_help_text( __( 'Vuoto = "Calcola il preventivo gratis".' ) ),
			Field::make( 'text', 'sg_when_cta_link', __( 'Bottone: link' ) )->set_help_text( __( 'Vuoto = #form.' ) ),
		) )
		->add_tab( __( 'Quanto costa' ), array(
			Field::make( 'text', 'sg_calc_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( __( 'Vuoto = CALCOLA LA STIMA.' ) ),
			Field::make( 'text', 'sg_calc_title', __( 'Titolo (H2)' ) )->set_help_text( $sg_variant_help ),
			Field::make( 'text', 'sg_calc_subtitle', __( 'Sottotitolo' ) )->set_help_text( __( 'Vuoto = "Fai una stima in 10 secondi. Senza registrarti."' ) ),
			Field::make( 'textarea', 'sg_calc_types', __( 'Tipi di intervento (uno per riga)' ) )->set_help_text( __( 'Vuoto = Post-ristrutturazione / Trasloco / Fine affitto / Prima pulizia a fondo. Il tipo preselezionato e\' quello della variante.' ) ),
			Field::make( 'textarea', 'sg_calc_extras', __( 'Extra (uno per riga)' ) )->set_help_text( __( 'Vuoto = Forno e cappa / Vetri e infissi / Balconi e terrazzi / Sanificazione a vapore.' ) ),
			Field::make( 'separator', 'sg_calc_coef_sep', __( 'Formula: base = fisso + mq x euro/mq; minimo = base + extra x costo minimo; massimo = base x moltiplicatore + extra x costo massimo; arrotondati alla decina' ) ),
			Field::make( 'text', 'sg_calc_base', __( 'Fisso (euro)' ) )->set_default_value( '200' ),
			Field::make( 'text', 'sg_calc_per_mq', __( 'Euro al metro quadro' ) )->set_default_value( '2.2' ),
			Field::make( 'text', 'sg_calc_extra_min', __( 'Costo minimo di un extra (euro)' ) )->set_default_value( '25' ),
			Field::make( 'text', 'sg_calc_extra_max', __( 'Costo massimo di un extra (euro)' ) )->set_default_value( '40' ),
			Field::make( 'text', 'sg_calc_max_mult', __( 'Moltiplicatore del massimo' ) )->set_default_value( '1.35' ),
			Field::make( 'text', 'sg_calc_mq_default', __( 'Metri quadri iniziali' ) )->set_default_value( '100' ),
			Field::make( 'separator', 'sg_calc_card_sep', __( 'Card del risultato' ) ),
			Field::make( 'text', 'sg_calc_result_label', __( 'Etichetta sopra il prezzo' ) )->set_help_text( __( 'Vuoto = STIMA INDICATIVA.' ) ),
			Field::make( 'text', 'sg_calc_cta_title', __( 'Titolo del form' ) )->set_help_text( __( 'Vuoto = "Ricevi il preventivo esatto".' ) ),
			Field::make( 'text', 'sg_calc_coupon_amount', __( 'Coupon: importo' ) )->set_help_text( __( 'Vuoto = -30€. Svuotare con uno spazio per nascondere il coupon.' ) ),
			Field::make( 'text', 'sg_calc_coupon_text', __( 'Coupon: testo' ) )->set_help_text( __( "Vuoto = \"sul primo lavoro, con l'email\"." ) ),
			Field::make( 'text', 'sg_calc_cta_label', __( 'Bottone: testo' ) )->set_help_text( __( 'Vuoto = "Ricevi il preventivo".' ) ),
			Field::make( 'textarea', 'sg_calc_note', __( 'Nota sotto il bottone' ) )->set_help_text( __( 'Vuoto = "Prezzo indicativo. Quello fisso, senza sorprese, lo confermiamo dopo il sopralluogo gratuito."' ) ),
		) )
		->add_tab( __( 'Come funziona' ), array(
			Field::make( 'text', 'sg_hiw_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( __( 'Vuoto = COME FUNZIONA.' ) ),
			Field::make( 'text', 'sg_hiw_title', __( 'Titolo (H2)' ) )->set_help_text( __( 'Vuoto = "Dal sopralluogo alla casa pulita".' ) ),
			Field::make( 'text', 'sg_hiw_subtitle', __( 'Sottotitolo' ) )->set_help_text( __( 'Vuoto = "Tre passaggi, senza sorprese."' ) ),
			Field::make( 'complex', 'sg_hiw_steps', __( 'Passi (vuoto = i tre del live)' ) )
				->add_fields( array(
					Field::make( 'text', 'title', __( 'Titolo' ) ),
					Field::make( 'textarea', 'description', __( 'Descrizione' ) ),
				) ),
		) )
		->add_tab( __( 'Recensioni e coupon' ), array(
			Field::make( 'text', 'sg_reviews_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( __( 'Vuoto = DICONO DI NOI.' ) ),
			Field::make( 'text', 'sg_reviews_title', __( 'Titolo (H2)' ) )->set_help_text( __( 'Vuoto = "Case rimesse a nuovo, a Roma".' ) ),
			Field::make( 'complex', 'sg_reviews', __( 'Recensioni (vuoto = le tre del live, dichiarate fittizie nel sorgente)' ) )
				->set_help_text( __( 'Tre card ferme su desktop. Scrivere uno spazio nel testo della prima per non mostrare la sezione.' ) )
				->add_fields( array(
					Field::make( 'text', 'author_name', __( 'Nome' ) ),
					Field::make( 'text', 'author_city', __( 'Zona' ) ),
					Field::make( 'image', 'avatar', __( 'Foto' ) )->set_value_type( 'url' ),
					Field::make( 'textarea', 'review_text', __( 'Testo' ) ),
				) ),
			Field::make( 'separator', 'sg_coupon_sep', __( 'Banner coupon' ) ),
			Field::make( 'text', 'sg_coupon_amount', __( 'Importo' ) )->set_help_text( __( 'Vuoto = -30€.' ) ),
			Field::make( 'text', 'sg_coupon_title', __( 'Titolo' ) )->set_help_text( __( 'Vuoto = "sul tuo primo sgrosso".' ) ),
			Field::make( 'textarea', 'sg_coupon_text', __( 'Testo' ) )->set_help_text( __( "Vuoto = \"Lascia l'email: ricevi il coupon e lo riscatti dopo il sopralluogo gratuito. Solo nuovi clienti.\"" ) ),
			Field::make( 'text', 'sg_coupon_cta_label', __( 'Bottone: testo' ) )->set_help_text( __( 'Vuoto = "Richiedi la stima gratis".' ) ),
			Field::make( 'text', 'sg_coupon_cta_link', __( 'Bottone: link' ) )->set_help_text( __( 'Vuoto = #form-bottom.' ) ),
		) )
		->add_tab( __( 'FAQ' ), array(
			Field::make( 'text', 'sg_faq_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( __( 'Vuoto = DOMANDE FREQUENTI.' ) ),
			Field::make( 'text', 'sg_faq_title', __( 'Titolo' ) )->set_help_text( __( 'Vuoto = "Prima di prenotare il sopralluogo".' ) ),
			Field::make( 'textarea', 'sg_faq_subtitle', __( 'Testo sotto il titolo' ) )->set_help_text( __( 'Vuoto = "Le risposte rapide. Per tutto il resto, il sopralluogo e\' gratuito e senza impegno."' ) ),
			Field::make( 'complex', 'sg_faq_items', __( 'Domande (vuoto = le sei del live; la prima cambia per variante)' ) )
				->add_fields( array(
					Field::make( 'text', 'question', __( 'Domanda' ) ),
					Field::make( 'textarea', 'answer', __( 'Risposta' ) ),
				) ),
			Field::make( 'separator', 'sg_faq_contact_sep', __( 'Card di contatto' ) ),
			Field::make( 'text', 'sg_faq_contact_people', __( 'Nomi' ) )->set_help_text( __( 'Vuoto = nomi, ruolo e foto della card di contatto della Home.' ) ),
			Field::make( 'text', 'sg_faq_contact_role', __( 'Ruolo' ) )->set_help_text( __( 'Vuoto = Fondatori.' ) ),
			Field::make( 'image', 'sg_faq_contact_avatar_1', __( 'Foto 1' ) )->set_value_type( 'url' ),
			Field::make( 'image', 'sg_faq_contact_avatar_2', __( 'Foto 2' ) )->set_value_type( 'url' ),
			Field::make( 'text', 'sg_faq_contact_title', __( 'Titolo card' ) )->set_help_text( __( 'Vuoto = "Rispondiamo di persona".' ) ),
			Field::make( 'textarea', 'sg_faq_contact_text', __( 'Testo card' ) ),
			Field::make( 'text', 'sg_faq_contact_cta_label', __( 'Bottone: testo' ) )->set_help_text( __( 'Vuoto = "Prenota la stima gratis".' ) ),
			Field::make( 'text', 'sg_faq_contact_cta_link', __( 'Bottone: link' ) )->set_help_text( __( 'Vuoto = #form-bottom.' ) ),
		) )
		->add_tab( __( 'CTA finale' ), array(
			Field::make( 'text', 'sg_cta_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( __( 'Vuoto = ULTIMO PASSO.' ) ),
			Field::make( 'text', 'sg_cta_title', __( 'Titolo (H2)' ) )->set_help_text( $sg_variant_help ),
			Field::make( 'textarea', 'sg_cta_subtitle', __( 'Sottotitolo' ) )->set_help_text( __( 'Vuoto = "Un sopralluogo gratuito, un prezzo fisso, una ditta verificata. Al resto pensa lei."' ) ),
			Field::make( 'textarea', 'sg_cta_bullets', __( 'Punti (uno per riga)' ) )->set_help_text( __( 'Vuoto = i quattro del live.' ) ),
			Field::make( 'image', 'sg_cta_image', __( 'Mascotte' ) )->set_value_type( 'url' )->set_help_text( __( 'Vuota = polpo del tema. Solo su desktop.' ) ),
		) );

	// Footer — Figma 3564:167. Theme Options > Footer, valido per tutte le pagine (footer.php)
	// Diventa cleaner — Figma 6752:895 (TICKET-51..56). Solo sulla pagina col template page-cleaner-landing.php.
	// Ogni campo vuoto = testo di default del template; un campo compilato vince.
	$dc_vuoto = __( 'Vuoto = testo di default del template.' );
	Container::make( 'post_meta', __( 'Diventa cleaner — Sezioni' ) )
		->where( 'post_template', '=', 'page-cleaner-landing.php' )
		->add_tab( __( 'Hero' ), array(
			Field::make( 'text', 'dc_hero_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( $dc_vuoto ),
			Field::make( 'textarea', 'dc_hero_title', __( 'Titolo (H1)' ) )->set_rows( 3 )->set_help_text( __( 'Un invio a capo = una riga. Vuoto = "I clienti li portiamo noi. / Tu scegli quando, / dove e a quanto."' ) ),
			Field::make( 'textarea', 'dc_hero_subtitle', __( 'Sottotitolo' ) )->set_help_text( $dc_vuoto ),
			Field::make( 'text', 'dc_hero_cta', __( 'Bottone principale' ) )->set_help_text( __( 'Vuoto = "Registrati gratis". Porta sempre al signup cleaner dell\'app.' ) ),
			Field::make( 'text', 'dc_hero_cta2', __( 'Bottone secondario' ) )->set_help_text( __( 'Vuoto = "Come funziona". Porta alla sezione Come funziona.' ) ),
			Field::make( 'image', 'dc_hero_screen', __( 'Schermo del telefono' ) )->set_value_type( 'url' )->set_help_text( __( 'Vuoto = dashboard dell\'app (assets/img/cleaner/dashboard-hero.webp). Proporzione 270x584.' ) ),
			Field::make( 'text', 'dc_float1_title', __( 'Card flottante 1: titolo' ) )->set_help_text( __( 'Vuoto = "Nuova richiesta · Prati".' ) ),
			Field::make( 'text', 'dc_float1_sub', __( 'Card flottante 1: riga sotto' ) ),
			Field::make( 'text', 'dc_float2_title', __( 'Card flottante 2: titolo' ) )->set_help_text( __( 'Vuoto = "Pagamento ricevuto".' ) ),
			Field::make( 'text', 'dc_float2_sub', __( 'Card flottante 2: riga sotto' ) ),
		) )
		->add_tab( __( 'Trust strip' ), array(
			Field::make( 'textarea', 'dc_trust_items', __( 'Garanzie (una per riga)' ) )->set_help_text( __( 'Vuoto = Zero costi fissi / Tu fissi la tariffa / Pagamento automatico con carta / Zone che scegli tu.' ) ),
		) )
		->add_tab( __( 'Come funziona' ), array(
			Field::make( 'text', 'dc_hiw_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( $dc_vuoto ),
			Field::make( 'text', 'dc_hiw_title', __( 'Titolo (H2)' ) )->set_help_text( $dc_vuoto ),
			Field::make( 'text', 'dc_hiw_subtitle', __( 'Sottotitolo' ) )->set_help_text( $dc_vuoto ),
			Field::make( 'complex', 'dc_hiw_steps', __( 'Passi (vuoto = i tre di default con le schermate)' ) )
				->add_fields( array(
					Field::make( 'text', 'title', __( 'Titolo' ) ),
					Field::make( 'textarea', 'description', __( 'Descrizione' ) ),
					Field::make( 'image', 'screen', __( 'Schermata (ritagliata dall\'alto)' ) )->set_value_type( 'url' ),
				) ),
		) )
		->add_tab( __( 'Strumenti (bento)' ), array(
			Field::make( 'text', 'dc_app_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( $dc_vuoto ),
			Field::make( 'text', 'dc_app_title', __( 'Titolo (H2)' ) )->set_help_text( $dc_vuoto ),
			Field::make( 'text', 'dc_app_subtitle', __( 'Sottotitolo' ) )->set_help_text( $dc_vuoto ),
			Field::make( 'complex', 'dc_app_tiles', __( 'Tile (vuoto = le cinque di default)' ) )
				->set_help_text( __( 'La prima tile "larga" occupa due colonne. Le schermate vanno caricate gia\' ritagliate sulla parte da mostrare.' ) )
				->add_fields( array(
					Field::make( 'text', 'title', __( 'Titolo' ) ),
					Field::make( 'textarea', 'text', __( 'Testo' ) ),
					Field::make( 'image', 'screen', __( 'Schermata' ) )->set_value_type( 'url' ),
					Field::make( 'checkbox', 'wide', __( 'Larga (due colonne)' ) ),
				) ),
		) )
		->add_tab( __( 'Quanto guadagni' ), array(
			Field::make( 'text', 'dc_calc_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( $dc_vuoto ),
			Field::make( 'text', 'dc_calc_title', __( 'Titolo (H2)' ) )->set_help_text( $dc_vuoto ),
			Field::make( 'textarea', 'dc_calc_subtitle', __( 'Sottotitolo' ) )->set_help_text( $dc_vuoto ),
			Field::make( 'text', 'dc_calc_rate_min', __( 'Tariffa minima dello slider (€/h)' ) )->set_help_text( __( 'Vuoto = 15. E\' un esempio, non un dato di piattaforma.' ) ),
			Field::make( 'text', 'dc_calc_rate_max', __( 'Tariffa massima (€/h)' ) )->set_help_text( __( 'Vuoto = 35.' ) ),
			Field::make( 'text', 'dc_calc_rate_default', __( 'Tariffa iniziale (€/h)' ) )->set_help_text( __( 'Vuoto = 22.' ) ),
			Field::make( 'text', 'dc_calc_hours', __( 'Ore a settimana proposte (separate da virgola)' ) )->set_help_text( __( 'Vuoto = 10,20,30,40.' ) ),
			Field::make( 'text', 'dc_calc_hours_default', __( 'Ore iniziali' ) )->set_help_text( __( 'Vuoto = 20. Deve essere una delle ore proposte.' ) ),
			Field::make( 'textarea', 'dc_calc_rows', __( 'Righe della card (Etichetta | Valore)' ) )->set_help_text( __( 'Vuoto = Costi fissi / Quota di iscrizione / Commissione / Accredito.' ) ),
			Field::make( 'text', 'dc_calc_note', __( 'Nota dopo la formula' ) )->set_help_text( __( 'Vuoto = "stima lorda, prima della commissione di piattaforma".' ) ),
			Field::make( 'text', 'dc_calc_cta', __( 'Bottone' ) )->set_help_text( __( 'Vuoto = "Registrati e imposta la tua tariffa".' ) ),
		) )
		->add_tab( __( 'Per chi è' ), array(
			Field::make( 'text', 'dc_who_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( $dc_vuoto ),
			Field::make( 'text', 'dc_who_title', __( 'Titolo (H2)' ) )->set_help_text( $dc_vuoto ),
			Field::make( 'textarea', 'dc_who_subtitle', __( 'Sottotitolo' ) )->set_help_text( $dc_vuoto ),
			Field::make( 'complex', 'dc_who_cards', __( 'Card (vuoto = professionista e impresa di default)' ) )
				->add_fields( array(
					Field::make( 'text', 'badge', __( 'Badge (es. P.IVA)' ) ),
					Field::make( 'text', 'title', __( 'Titolo' ) ),
					Field::make( 'textarea', 'text', __( 'Testo' ) ),
					Field::make( 'textarea', 'bullets', __( 'Punti (uno per riga)' ) ),
					Field::make( 'text', 'cta_label', __( 'Bottone: testo' ) ),
					Field::make( 'text', 'cta_link', __( 'Bottone: link' ) )->set_help_text( __( 'Signup: https://app.trovapulizie.com/cleaner-signup. WhatsApp: https://wa.me/393200980144?text=...' ) ),
					Field::make( 'checkbox', 'dark', __( 'Card scura' ) ),
				) ),
		) )
		->add_tab( __( 'Requisiti' ), array(
			Field::make( 'text', 'dc_req_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( $dc_vuoto ),
			Field::make( 'text', 'dc_req_title', __( 'Titolo (H2)' ) )->set_help_text( $dc_vuoto ),
			Field::make( 'text', 'dc_req_yes_title', __( 'Card 1: titolo' ) )->set_help_text( __( 'Vuoto = "Cosa serve".' ) ),
			Field::make( 'textarea', 'dc_req_yes', __( 'Card 1: voci (Titolo | testo, una per riga)' ) )->set_help_text( $dc_vuoto ),
			Field::make( 'text', 'dc_req_no_title', __( 'Card 2: titolo' ) )->set_help_text( __( 'Vuoto = "Cosa non serve".' ) ),
			Field::make( 'textarea', 'dc_req_no', __( 'Card 2: voci (Titolo | testo, una per riga)' ) )->set_help_text( $dc_vuoto ),
		) )
		->add_tab( __( 'Partner' ), array(
			Field::make( 'textarea', 'dc_partners', __( 'Nomi dei partner (uno per riga)' ) )->set_help_text( __( '[ATTENZIONE] Vuoto = sezione nascosta. Scrivere un nome solo dopo l\'ok del partner.' ) ),
			Field::make( 'text', 'dc_partners_title', __( 'Titolo' ) )->set_help_text( __( 'Vuoto = "LAVORANO GIÀ CON NOI A ROMA".' ) ),
			Field::make( 'text', 'dc_partners_tail', __( 'Riga di coda' ) )->set_help_text( __( 'Vuoto = "+ professionisti indipendenti in tutta la città".' ) ),
		) )
		->add_tab( __( 'FAQ' ), array(
			Field::make( 'text', 'dc_faq_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( $dc_vuoto ),
			Field::make( 'text', 'dc_faq_title', __( 'Titolo' ) )->set_help_text( __( 'Vuoto = "Domande dei cleaner".' ) ),
			Field::make( 'textarea', 'dc_faq_subtitle', __( 'Testo sotto il titolo' ) )->set_help_text( $dc_vuoto ),
			Field::make( 'complex', 'dc_faq_items', __( 'Domande (vuoto = le sei di default)' ) )
				->add_fields( array(
					Field::make( 'text', 'question', __( 'Domanda' ) ),
					Field::make( 'textarea', 'answer', __( 'Risposta' ) ),
				) ),
			Field::make( 'text', 'dc_faq_contact_title', __( 'Card contatto: titolo' ) )->set_help_text( __( 'Vuoto = "Rispondiamo di persona".' ) ),
			Field::make( 'textarea', 'dc_faq_contact_text', __( 'Card contatto: testo' ) )->set_help_text( $dc_vuoto ),
			Field::make( 'text', 'dc_faq_contact_cta', __( 'Card contatto: bottone' ) )->set_help_text( __( 'Vuoto = "Scrivici su WhatsApp". Porta sempre a WhatsApp.' ) ),
		) )
		->add_tab( __( 'CTA finale' ), array(
			Field::make( 'text', 'dc_cta_title', __( 'Titolo' ) )->set_help_text( __( 'Vuoto = "Pronto a riempire l\'agenda?".' ) ),
			Field::make( 'textarea', 'dc_cta_subtitle', __( 'Sottotitolo' ) )->set_help_text( $dc_vuoto ),
			Field::make( 'text', 'dc_cta_button', __( 'Bottone' ) )->set_help_text( __( 'Vuoto = "Registrati gratis". Porta al signup cleaner.' ) ),
			Field::make( 'text', 'dc_cta_note', __( 'Nota sotto il bottone' ) )->set_help_text( $dc_vuoto ),
		) );

	// Landing B2B — pagina col template page-landing-b2b.php (SPEC-landing-sgrosso-commerciale, TICKET-58..61).
	// Ogni testo ha il default nel template (ridisegno 25/10 + testi del live): un campo compilato vince.
	$b2b_vuoto = __( 'Vuoto = testo di default del template.' );
	Container::make( 'post_meta', __( 'Landing B2B — Sezioni' ) )
		->where( 'post_template', '=', 'page-landing-b2b.php' )
		->add_tab( __( 'Testata' ), array(
			Field::make( 'text', 'b2b_promo_desktop', __( 'Promo bar, testo desktop' ) )->set_help_text( $b2b_vuoto ),
			Field::make( 'text', 'b2b_promo_mobile', __( 'Promo bar, testo mobile' ) )->set_help_text( $b2b_vuoto ),
			Field::make( 'text', 'b2b_nav_phone', __( 'Telefono in testata' ) )->set_help_text( __( 'Vuoto = 06 9151 6075. Vale anche per Chiama nella barra fissa e nella conferma del form.' ) ),
			Field::make( 'text', 'b2b_nav_cta', __( 'Testo della CTA in testata' ) )->set_help_text( __( 'Vuoto = "Preventivo gratuito". Porta sempre al form.' ) ),
		) )
		->add_tab( __( 'Hero' ), array(
			Field::make( 'text', 'b2b_hero_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( $b2b_vuoto ),
			Field::make( 'textarea', 'b2b_hero_title', __( 'Titolo (H1)' ) )->set_rows( 2 )->set_help_text( __( 'Un invio a capo = una riga. Vuoto = "Pulizie per uffici, negozi e locali. / Anche quando sei chiuso."' ) ),
			Field::make( 'textarea', 'b2b_hero_subtitle', __( 'Sottotitolo' ) )->set_help_text( $b2b_vuoto ),
			Field::make( 'textarea', 'b2b_hero_bullets', __( 'Punti di valore (uno per riga)' ) )->set_help_text( $b2b_vuoto ),
			Field::make( 'text', 'b2b_hero_social', __( 'Riga sotto le stelle' ) )->set_help_text( __( 'Vuoto = "Uffici, retail e locali serviti a Roma".' ) ),
			Field::make( 'image', 'b2b_hero_image', __( 'Foto di sfondo' ) )->set_value_type( 'url' )->set_help_text( __( 'Vuota = lavasciuga su pavimento post-cantiere (assets/img/b2b/hero-pavimento.webp, con rendition mobile).' ) ),
		) )
		->add_tab( __( 'Form' ), array(
			Field::make( 'text', 'b2b_form_title', __( 'Titolo della card' ) )->set_help_text( __( 'Vuoto = "Richiedi il preventivo gratuito".' ) ),
			Field::make( 'text', 'b2b_form_subtitle', __( 'Testo sotto il titolo' ) )->set_help_text( __( 'Vuoto = "Sopralluogo gratuito in sede. Ti richiamiamo entro 1 ora."' ) ),
			Field::make( 'textarea', 'b2b_form_activities', __( 'Tipi di attivita\' (uno per riga)' ) )->set_help_text( __( 'Vuoto = Ufficio / Negozio / Supermercato / Palestra / Altro. La voce "Altro" apre un campo di testo. Il valore scelto finisce nel campo note del lead.' ) ),
			Field::make( 'text', 'b2b_form_cta', __( 'Bottone' ) )->set_help_text( __( 'Vuoto = "Richiedi il preventivo gratuito".' ) ),
			Field::make( 'text', 'b2b_form_note', __( 'Nota sotto il bottone' ) )->set_help_text( __( 'Vuoto = "★ Gratis · con fattura · risposta in 1h".' ) ),
		) )
		->add_tab( __( 'Trust strip' ), array(
			Field::make( 'textarea', 'b2b_trust_items', __( 'Garanzie (una per riga)' ) )->set_help_text( __( 'Vuoto = Sopralluogo gratuito in sede / Fattura su ogni intervento / Anche di notte o nel weekend / Roma e provincia.' ) ),
		) )
		->add_tab( __( 'Settori' ), array(
			Field::make( 'text', 'b2b_sectors_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( __( 'Vuoto = SETTORI SERVITI.' ) ),
			Field::make( 'text', 'b2b_sectors_title', __( 'Titolo (H2)' ) )->set_help_text( __( 'Vuoto = "Puliamo ogni tipo di attività".' ) ),
			Field::make( 'text', 'b2b_sectors_areas_title', __( 'Titolo della colonna dei chip' ) )->set_help_text( __( 'Vuoto = "Cosa puliamo".' ) ),
			Field::make( 'textarea', 'b2b_sectors_areas_text', __( 'Testo sotto i chip' ) )->set_help_text( $b2b_vuoto ),
			Field::make( 'text', 'b2b_sectors_cta', __( 'Bottone del pannello' ) )->set_help_text( __( 'Vuoto = "Richiedi il preventivo gratuito". Porta al form.' ) ),
			Field::make( 'complex', 'b2b_sectors', __( 'Settori (vuoto = i quattro di default con le foto del live)' ) )
				->add_fields( array(
					Field::make( 'text', 'key', __( 'Chiave (senza spazi, es. uffici)' ) ),
					Field::make( 'text', 'label', __( 'Testo del tab' ) ),
					Field::make( 'text', 'title', __( 'Titolo del pannello' ) ),
					Field::make( 'textarea', 'text', __( 'Testo' ) ),
					Field::make( 'text', 'highlight', __( 'Riga in evidenza' ) ),
					Field::make( 'image', 'image', __( 'Foto' ) )->set_value_type( 'url' ),
					Field::make( 'text', 'image_alt', __( 'Testo alternativo della foto' ) ),
					Field::make( 'textarea', 'chips', __( 'Cosa puliamo (una voce per riga)' ) ),
				) ),
		) )
		->add_tab( __( 'Lavori' ), array(
			Field::make( 'text', 'b2b_works_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( __( 'Vuoto = LAVORI A ROMA.' ) ),
			Field::make( 'text', 'b2b_works_title', __( 'Titolo (H2)' ) )->set_help_text( __( 'Vuoto = "Fine cantiere: il prima e dopo reale".' ) ),
			Field::make( 'textarea', 'b2b_works_subtitle', __( 'Sottotitolo' ) )->set_help_text( $b2b_vuoto ),
			Field::make( 'image', 'b2b_works_before', __( 'Confronto: foto prima' ) )->set_value_type( 'url' )->set_help_text( __( 'Vuota = vetrate prima della pulizia (live).' ) ),
			Field::make( 'image', 'b2b_works_after', __( 'Confronto: foto dopo' ) )->set_value_type( 'url' )->set_help_text( __( 'Vuota = vetrate dopo la pulizia (live).' ) ),
			Field::make( 'text', 'b2b_works_compare_title', __( 'Confronto: titolo della card' ) )->set_help_text( __( 'Vuoto = "Vetrate di un locale commerciale".' ) ),
			Field::make( 'textarea', 'b2b_works_compare_text', __( 'Confronto: testo della card' ) )->set_help_text( $b2b_vuoto ),
			Field::make( 'text', 'b2b_works_before_caption', __( 'Didascalia PRIMA' ) )->set_help_text( __( 'Vuoto = "Vetri velati da residui di cantiere".' ) ),
			Field::make( 'text', 'b2b_works_after_caption', __( 'Didascalia DOPO' ) )->set_help_text( __( 'Vuoto = "Vetri puliti, riflesso limpido".' ) ),
			Field::make( 'complex', 'b2b_works_gallery', __( 'Galleria (vuoto = le tre foto del live)' ) )
				->add_fields( array(
					Field::make( 'image', 'image', __( 'Foto' ) )->set_value_type( 'url' ),
					Field::make( 'text', 'caption', __( 'Didascalia' ) ),
				) ),
		) )
		->add_tab( __( 'Formule' ), array(
			Field::make( 'text', 'b2b_plans_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( __( 'Vuoto = COME LAVORIAMO.' ) ),
			Field::make( 'text', 'b2b_plans_title', __( 'Titolo (H2)' ) )->set_help_text( __( 'Vuoto = "Una volta sola o tutto l\'anno".' ) ),
			Field::make( 'textarea', 'b2b_plans_subtitle', __( 'Sottotitolo' ) )->set_help_text( $b2b_vuoto ),
			Field::make( 'complex', 'b2b_plans', __( 'Card (vuoto = una-tantum e contratto ricorrente di default)' ) )
				->set_help_text( __( 'I bottoni portano sempre al form in fondo alla pagina.' ) )
				->add_fields( array(
					Field::make( 'text', 'badge', __( 'Etichetta (es. UNA-TANTUM)' ) ),
					Field::make( 'text', 'title', __( 'Titolo' ) ),
					Field::make( 'textarea', 'text', __( 'Testo' ) ),
					Field::make( 'textarea', 'bullets', __( 'Punti (uno per riga)' ) ),
					Field::make( 'text', 'cta_label', __( 'Bottone: testo' ) ),
					Field::make( 'checkbox', 'dark', __( 'Card scura' ) ),
				) ),
		) )
		->add_tab( __( 'Perché noi' ), array(
			Field::make( 'text', 'b2b_why_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( __( 'Vuoto = PERCHÉ NOI.' ) ),
			Field::make( 'text', 'b2b_why_title', __( 'Titolo (H2)' ) )->set_help_text( __( 'Vuoto = "Un fornitore serio per la tua attività".' ) ),
			Field::make( 'complex', 'b2b_why_items', __( 'Card (vuoto = le quattro di default)' ) )
				->add_fields( array(
					Field::make( 'select', 'icon', __( 'Icona' ) )->set_options( array( 'user-check' => 'Referente', 'clock' => 'Orologio', 'shield-check' => 'Scudo', 'spray' => 'Prodotti', 'check' => 'Spunta' ) ),
					Field::make( 'text', 'title', __( 'Titolo' ) ),
					Field::make( 'textarea', 'text', __( 'Testo' ) ),
				) ),
		) )
		->add_tab( __( 'Come funziona' ), array(
			Field::make( 'text', 'b2b_hiw_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( __( 'Vuoto = COME FUNZIONA.' ) ),
			Field::make( 'text', 'b2b_hiw_title', __( 'Titolo (H2)' ) )->set_help_text( __( 'Vuoto = "Dal preventivo all\'intervento".' ) ),
			Field::make( 'text', 'b2b_hiw_subtitle', __( 'Sottotitolo' ) )->set_help_text( $b2b_vuoto ),
			Field::make( 'complex', 'b2b_hiw_steps', __( 'Passi (vuoto = i tre del live)' ) )
				->add_fields( array(
					Field::make( 'text', 'title', __( 'Titolo' ) ),
					Field::make( 'textarea', 'description', __( 'Descrizione' ) ),
				) ),
		) )
		->add_tab( __( 'Recensioni' ), array(
			Field::make( 'text', 'b2b_reviews_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( __( 'Vuoto = DICONO DI NOI.' ) ),
			Field::make( 'text', 'b2b_reviews_title', __( 'Titolo (H2)' ) )->set_help_text( __( 'Vuoto = "Attività che si affidano a noi".' ) ),
			Field::make( 'complex', 'b2b_reviews', __( 'Recensioni (vuoto = le tre del live, fittizie: da sostituire prima del cutover)' ) )
				->set_help_text( __( 'Le card mostrano le iniziali del nome, non una foto.' ) )
				->add_fields( array(
					Field::make( 'text', 'author_name', __( 'Nome (es. Marco C.)' ) ),
					Field::make( 'text', 'author_city', __( 'Attivita\' e zona (es. Studio, Prati)' ) ),
					Field::make( 'textarea', 'review_text', __( 'Testo' ) ),
				) ),
		) )
		->add_tab( __( 'FAQ' ), array(
			Field::make( 'text', 'b2b_faq_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( __( 'Vuoto = DOMANDE FREQUENTI.' ) ),
			Field::make( 'text', 'b2b_faq_title', __( 'Titolo' ) )->set_help_text( __( 'Vuoto = "Prima di richiedere il preventivo".' ) ),
			Field::make( 'textarea', 'b2b_faq_subtitle', __( 'Testo sotto il titolo' ) )->set_help_text( $b2b_vuoto ),
			Field::make( 'complex', 'b2b_faq_items', __( 'Domande (vuoto = le sei del live)' ) )
				->add_fields( array(
					Field::make( 'text', 'question', __( 'Domanda' ) ),
					Field::make( 'textarea', 'answer', __( 'Risposta' ) ),
				) ),
			Field::make( 'text', 'b2b_faq_contact_title', __( 'Card contatto: titolo' ) )->set_help_text( __( 'Vuoto = "Rispondiamo di persona". Le foto vengono dalla card di contatto della Home.' ) ),
			Field::make( 'textarea', 'b2b_faq_contact_text', __( 'Card contatto: testo' ) )->set_help_text( $b2b_vuoto ),
			Field::make( 'text', 'b2b_faq_contact_cta', __( 'Card contatto: bottone' ) )->set_help_text( __( 'Vuoto = "Richiedi il preventivo gratuito". Porta al form in fondo.' ) ),
		) )
		->add_tab( __( 'CTA finale' ), array(
			Field::make( 'text', 'b2b_cta_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( __( 'Vuoto = ULTIMO PASSO.' ) ),
			Field::make( 'text', 'b2b_cta_title', __( 'Titolo (H2)' ) )->set_help_text( __( 'Vuoto = "Un preventivo su misura per la tua attività".' ) ),
			Field::make( 'textarea', 'b2b_cta_subtitle', __( 'Sottotitolo' ) )->set_help_text( $b2b_vuoto ),
			Field::make( 'textarea', 'b2b_cta_bullets', __( 'Punti (uno per riga)' ) )->set_help_text( __( 'Vuoto = i quattro del ridisegno. Il form ripete titolo, testi e bottone della card della hero.' ) ),
		) );

	// Contatti — pagina col template page-contact.php (SPEC-pagina-contatti, TICKET-63..65).
	// Ogni testo ha il default nel template (Figma 6803:895): un campo compilato vince.
	$ct_vuoto = __( 'Vuoto = testo di default del template.' );
	Container::make( 'post_meta', __( 'Contatti — Sezioni' ) )
		->where( 'post_template', '=', 'page-contact.php' )
		->add_tab( __( 'Smistamento' ), array(
			Field::make( 'text', 'ct_hero_eyebrow', __( 'Pillola sopra il titolo' ) )->set_help_text( __( 'Vuoto = CONTATTI.' ) ),
			Field::make( 'text', 'ct_hero_title', __( 'Titolo (H1)' ) )->set_help_text( __( 'Vuoto = "Come possiamo aiutarti?"' ) ),
			Field::make( 'textarea', 'ct_hero_subtitle', __( 'Sottotitolo' ) )->set_help_text( $ct_vuoto ),
			Field::make( 'complex', 'ct_cards', __( 'Card (vuoto = le quattro del Figma)' ) )
				->set_help_text( __( "L'icona decide anche la posizione negli eventi di tracking (casa, azienda, lavora, prenotato). La card scura e' quella del contatto vero." ) )
				->add_fields( array(
					Field::make( 'select', 'icon', __( 'Icona' ) )->set_options( array(
						'home'      => 'Casa',
						'building'  => 'Azienda',
						'user-plus' => 'Lavora con noi',
						'chat'      => 'Messaggio',
					) ),
					Field::make( 'text', 'title', __( 'Titolo' ) ),
					Field::make( 'textarea', 'text', __( 'Testo' ) )->set_rows( 3 ),
					Field::make( 'text', 'link_label', __( 'Testo del link' ) ),
					Field::make( 'text', 'link', __( 'Link' ) )->set_help_text( __( 'URL completo o percorso (es. /diventa-cleaner/). WhatsApp: https://wa.me/393200980144' ) ),
					Field::make( 'checkbox', 'dark', __( 'Card scura' ) ),
				) ),
		) )
		->add_tab( __( 'Canali' ), array(
			Field::make( 'text', 'ct_ch_eyebrow', __( 'Pillola' ) )->set_help_text( __( 'Vuoto = CANALI DIRETTI.' ) ),
			Field::make( 'text', 'ct_ch_title', __( 'Titolo' ) )->set_help_text( __( 'Vuoto = "Scrivici".' ) ),
			Field::make( 'textarea', 'ct_ch_text', __( 'Testo (orari)' ) )->set_help_text( __( 'Vuoto = "Per qualsiasi altra domanda. Rispondiamo nei giorni lavorativi."' ) ),
			Field::make( 'text', 'ct_whatsapp', __( 'Numero WhatsApp' ) )->set_help_text( __( 'Vuoto = 320 098 0144. Vale per la card "Hai gia\' prenotato", il bottone e i dati strutturati.' ) ),
			Field::make( 'text', 'ct_email', __( 'Email' ) )->set_help_text( __( 'Vuoto = help@trovapulizie.it.' ) ),
			Field::make( 'text', 'ct_ch_cta', __( 'Bottone' ) )->set_help_text( __( 'Vuoto = "Scrivici su WhatsApp".' ) ),
		) )
		->add_tab( __( 'Dati aziendali' ), array(
			Field::make( 'text', 'ct_co_eyebrow', __( 'Pillola' ) )->set_help_text( __( 'Vuoto = DATI AZIENDALI.' ) ),
			Field::make( 'text', 'ct_co_name', __( 'Ragione sociale' ) )->set_help_text( __( 'Vuoto = "Trovapulizie S.R.L."' ) ),
			Field::make( 'textarea', 'ct_co_rows', __( 'Righe (Voce | valore, una per riga)' ) )->set_help_text( __( 'Vuoto = sede legale, partita IVA, REA, iscrizione.' ) ),
		) )
		->add_tab( __( 'FAQ' ), array(
			Field::make( 'text', 'ct_faq_eyebrow', __( 'Pillola' ) )->set_help_text( __( 'Vuoto = DOMANDE FREQUENTI.' ) ),
			Field::make( 'text', 'ct_faq_title', __( 'Titolo' ) )->set_help_text( __( 'Vuoto = "Prima di scriverci".' ) ),
			Field::make( 'textarea', 'ct_faq_subtitle', __( 'Testo sotto il titolo' ) )->set_help_text( $ct_vuoto ),
			Field::make( 'complex', 'ct_faq_items', __( 'Domande (vuoto = le quattro di default)' ) )
				->set_help_text( __( 'Una domanda senza risposta non esce, ne\' in pagina ne\' nei dati strutturati.' ) )
				->add_fields( array(
					Field::make( 'text', 'question', __( 'Domanda' ) ),
					Field::make( 'textarea', 'answer', __( 'Risposta' ) ),
				) ),
		) );

	// Thank-you — pagina col template page-thank-you.php (TICKET-42). Riepilogo e passi sono fissi nel template:
	// i dati della richiesta arrivano dal browser (sessionStorage), non da WordPress.
	Container::make( 'post_meta', __( 'Thank-you — Testi' ) )
		->where( 'post_template', '=', 'page-thank-you.php' )
		->add_fields( array(
			Field::make( 'text', 'ty_badge', __( 'Pillola sopra il titolo' ) )->set_help_text( __( 'Vuoto = RICHIESTA RICEVUTA.' ) ),
			Field::make( 'textarea', 'ty_title', __( 'Titolo (H1)' ) )->set_help_text( __( 'Un invio a capo = una riga. Vuoto = "Richiesta ricevuta / Ci pensiamo noi".' ) ),
			Field::make( 'textarea', 'ty_text', __( 'Testo sotto il titolo' ) ),
			Field::make( 'text', 'ty_coupon', __( 'Coupon mostrato nel riepilogo' ) )->set_help_text( __( 'Vuoto = "-30€ sul primo lavoro". Il form puo\' sovrascriverlo.' ) ),
			Field::make( 'text', 'ty_promo_desktop', __( 'Promo bar, testo desktop' ) ),
			Field::make( 'text', 'ty_promo_mobile', __( 'Promo bar, testo mobile' ) ),
			Field::make( 'text', 'ty_phone', __( 'Telefono in testata' ) )->set_help_text( __( 'Vuoto = 06 9151 6075.' ) ),
			Field::make( 'text', 'ty_nav_cta', __( 'CTA in testata' ) )->set_help_text( __( 'Vuoto = "Sopralluogo gratuito", verso /roma/sgrosso-casa/#form.' ) ),
		) );

	// Integrazioni — Theme Options > Integrazioni: API del portale per il form lead (ADR-004) e chiave HERE.
	// La chiave HERE e' pubblica nel browser (va limitata per referrer sul pannello HERE), non e' un segreto da
	// repo: per questo sta nel database e non in wp-config.
	Container::make( 'theme_options', __( 'Integrazioni' ) )
		->set_page_file( 'tp-integrazioni' )
		->add_fields( array(
			Field::make( 'text', 'tp_api_base', __( 'Base URL dell\'API del portale' ) )
				->set_default_value( 'https://api.trovapulizie.com' )
				->set_help_text( __( 'Produzione https://api.trovapulizie.com, staging https://staging.api.trovapulizie.com. Il form lead chiama /api/leads da qui. [ATTENZIONE] L\'allowlist CORS del backend accetta solo www.trovapulizie.it e staging.trovapulizie.it: da altri hostname le chiamate falliscono.' ) ),
			Field::make( 'select', 'tp_search_target', __( 'Barra di ricerca della Home: destinazione per i privati' ) )
				->set_options( array(
					'home'    => 'Home dell\'app (/home?address=...) - funziona in produzione al 25/10/2026',
					'results' => 'Risultati (/results?address=...&service=...) - solo dopo il backport di 947de5d8',
				) )
				->set_help_text( __( '[ATTENZIONE] Al 25/10/2026 la pagina risultati dell\'app di produzione non legge address dall\'URL (il ripiego e\' solo su staging, commit 947de5d8): con "Risultati" resta bloccata su "Ricerca in corso". Passare a Risultati solo quando Niccolo\' conferma il rilascio. I servizi solo aziende vanno sempre al preventivo B2B.' ) ),
			Field::make( 'text', 'tp_here_key', __( 'Chiave HERE (autocomplete indirizzo)' ) )
				->set_help_text( __( 'Chiave dedicata al sito, limitata ai referrer staging.trovapulizie.it e www.trovapulizie.it, solo servizio Autosuggest. Vuota = campo indirizzo a testo libero.' ) ),
		) );

	// Tracking — Theme Options > Tracking (inc/tracking.php). ID pubblici (stanno nel sorgente di ogni pagina),
	// non segreti: per questo vivono nel database e non in wp-config. Campo vuoto = snippet non emesso.
	Container::make( 'theme_options', __( 'Tracking' ) )
		->set_page_file( 'tp-tracking' )
		->add_fields( array(
			Field::make( 'html', 'tp_tracking_intro' )
				->set_html( '<p>Un solo contenitore GTM per pagina: non aggiungere snippet altrove. Gli ID del live al 25/10/2026: GTM <code>GTM-WKN46NRS</code>, iubenda sito <code>3635239</code> e cookie policy <code>55307359</code>, Clarity <code>xtz09sh904</code>.</p>' ),
			Field::make( 'text', 'tp_gtm_id', __( 'ID contenitore Google Tag Manager' ) )
				->set_default_value( 'GTM-WKN46NRS' )
				->set_help_text( __( 'Formato GTM-XXXXXXX. Vuoto = nessun GTM.' ) ),
			Field::make( 'text', 'tp_iub_site_id', __( 'iubenda: siteId' ) )
				->set_default_value( '3635239' ),
			Field::make( 'text', 'tp_iub_policy_id', __( 'iubenda: cookiePolicyId' ) )
				->set_default_value( '55307359' )
				->set_help_text( __( 'Servono entrambi gli ID iubenda per il banner e il consent mode.' ) ),
			Field::make( 'text', 'tp_clarity_id', __( 'ID progetto Microsoft Clarity' ) )
				->set_default_value( 'xtz09sh904' )
				->set_help_text( __( 'Vuoto = niente Clarity.' ) ),
		) );

	Container::make( 'theme_options', __( 'Footer' ) )
		->set_page_file( 'tp-footer' )
		->add_fields( array(
			Field::make( 'text', 'footer_brand_text', __( 'Wordmark' ) )
				->set_default_value( 'trovapulizie' ),
			Field::make( 'textarea', 'footer_tagline', __( 'Tagline (2 righe)' ) )
				->set_rows( 2 ),
			Field::make( 'complex', 'footer_social', __( 'Social' ) )
				->set_layout( 'tabbed-horizontal' )
				->add_fields( array(
					Field::make( 'select', 'network', __( 'Rete' ) )
						->set_options( array( 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok', 'facebook' => 'Facebook' ) ),
					Field::make( 'text', 'url', __( 'URL profilo' ) )
						->set_help_text( __( 'Vuoto = icona mostrata senza link.' ) ),
				) ),
			Field::make( 'complex', 'footer_columns', __( 'Colonne di link' ) )
				->set_layout( 'tabbed-horizontal' )
				->set_max( 3 )
				->add_fields( array(
					Field::make( 'text', 'title', __( 'Titolo colonna' ) ),
					Field::make( 'textarea', 'links', __( 'Link (una riga per link: Etichetta | URL)' ) ),
				) ),
			Field::make( 'textarea', 'footer_cta_text', __( 'Box cleaner: testo (2 righe)' ) )
				->set_rows( 2 ),
			Field::make( 'text', 'footer_cta_label', __( 'Box cleaner: etichetta link' ) )
				->set_default_value( 'Unisciti al team' ),
			Field::make( 'text', 'footer_cta_link', __( 'Box cleaner: URL' ) ),
			Field::make( 'text', 'footer_copyright', __( 'Riga copyright' ) )
				->set_help_text( __( 'Vuota = anno corrente + Trovapulizie S.R.L.' ) ),
			Field::make( 'textarea', 'footer_legal', __( 'Link legali (una riga per link: Etichetta | URL)' ) ),
		) );
} );
