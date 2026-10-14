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
		) )
		->add_tab( __( 'Come funziona' ), array(
			Field::make( 'complex', 'how_it_works_steps', __( 'Step' ) )
				->set_layout( 'tabbed-vertical' )
				->add_fields( array(
					Field::make( 'text', 'title', __( 'Titolo' ) ),
					Field::make( 'textarea', 'description', __( 'Descrizione' ) ),
					Field::make( 'image', 'image', __( 'Immagine' ) ),
				) ),
		) )
		->add_tab( __( 'Servizi Popolari' ), array(
			Field::make( 'complex', 'popular_services', __( 'Servizi' ) )
				->set_layout( 'tabbed-vertical' )
				->add_fields( array(
					Field::make( 'text', 'tab_label', __( 'Etichetta tab' ) ),
					Field::make( 'text', 'card_title', __( 'Titolo card' ) ),
					Field::make( 'textarea', 'card_description', __( 'Descrizione card' ) ),
					Field::make( 'image', 'card_image', __( 'Immagine card' ) ),
					Field::make( 'text', 'link', __( 'Link' ) ),
				) ),
		) )
		->add_tab( __( 'Social Proof' ), array(
			Field::make( 'complex', 'reviews', __( 'Recensioni' ) )
				->set_layout( 'tabbed-vertical' )
				->add_fields( array(
					Field::make( 'text', 'author_name', __( 'Nome' ) ),
					Field::make( 'textarea', 'review_text', __( 'Testo recensione' ) ),
				) ),
		) )
		->add_tab( __( 'Dove Siamo Attivi' ), array(
			Field::make( 'text', 'zones_title', __( 'Titolo' ) ),
			Field::make( 'textarea', 'zones_description', __( 'Descrizione' ) ),
			Field::make( 'image', 'zones_map_image', __( 'Mappa' ) ),
		) )
		->add_tab( __( 'FAQ' ), array(
			Field::make( 'complex', 'faq_items', __( 'FAQ' ) )
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
