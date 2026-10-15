<?php
use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action( 'carbon_fields_register_fields', function () {

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
			Field::make( 'text', 'faq_title', __( 'Titolo' ) )
				->set_default_value( 'Domande frequenti' ),
			Field::make( 'complex', 'faq_items', __( 'FAQ (la prima parte aperta; una aperta per volta)' ) )
				->set_layout( 'tabbed-vertical' )
				->add_fields( array(
					Field::make( 'text', 'question', __( 'Domanda' ) ),
					Field::make( 'textarea', 'answer', __( 'Risposta' ) ),
				) ),
		) )
		->add_tab( __( 'CTA Finale' ), array(
			Field::make( 'text', 'cta_title', __( 'Titolo' ) ),
			Field::make( 'textarea', 'cta_subtitle', __( 'Sottotitolo' ) ),
			Field::make( 'text', 'cta_button_text', __( 'Testo bottone' ) ),
			Field::make( 'text', 'cta_button_link', __( 'Link bottone' ) ),
		) );

} );
