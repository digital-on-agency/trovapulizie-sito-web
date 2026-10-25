<?php
/**
 * Template Name: Landing B2B
 *
 * Landing "Pulizie commerciali" per uffici, negozi, supermercati e palestre: /roma/sgrosso-commerciale/ (post 12,
 * figlia di /roma/). Design: ridisegno Figma 6780:895 (pagina "Sito Web", 25/10) con la nota 6783:895 e gli stati
 * dei tab dei settori 6784:901/951/1001; testi e claim dal live React (SgrossoCommercialeB2BPage.tsx su master).
 * PRD e ticket nel vault: clients/trovapulizie/projects/sito-web/spec/SPEC-landing-sgrosso-commerciale.md
 * (TICKET-58..62).
 *
 * Legge la scheda "Landing B2B — Sezioni" (prefisso b2b_): ogni testo ha il default qui sotto e il campo, se
 * compilato, vince (tp_b2b()). Form a un passo (template-parts/lead-form-b2b.php): una sola POST /api/leads tipo
 * b2b, conferma inline, nessuna thank-you.
 *
 * [ATTENZIONE] Il template va assegnato a mano (post meta _wp_page_template): il file non si chiama
 * page-sgrosso-commerciale.php di proposito, perche' col nome dello slug WordPress lo applicherebbe da solo
 * lasciando _wp_page_template vuoto, e la scheda dei campi e gli script (is_page_template) non partirebbero.
 */

if ( ! function_exists( 'tp_b2b' ) ) {
	/** Campo della pagina se compilato, altrimenti il default; per i complex, l'array se non vuoto. */
	function tp_b2b( $field, $default = '' ) {
		$v = carbon_get_the_post_meta( $field );
		if ( is_array( $v ) ) {
			return $v ?: $default;
		}
		$v = trim( (string) $v );
		return '' === $v ? $default : $v;
	}
}

$landing = 'sgrosso-commerciale'; // identificatore negli eventi dataLayer, uguale alla SPA
$img     = function ( $name ) { return tp_theme_asset( 'assets/img/b2b/' . $name . '.webp' ); };

$phone_label = tp_b2b( 'b2b_nav_phone', '06 9151 6075' );
$phone_href  = 'tel:' . preg_replace( '/\D+/', '', $phone_label );

$hero_image_field = carbon_get_the_post_meta( 'b2b_hero_image' );
$hero_bullets     = tp_lines( tp_b2b( 'b2b_hero_bullets', '' ) );
if ( ! $hero_bullets ) {
	$hero_bullets = array(
		'Orari flessibili, anche fuori apertura o notturni',
		'Fattura elettronica su ogni intervento',
		'Una-tantum o contratto ricorrente, come preferisci',
	);
}
$activities = tp_lines( tp_b2b( 'b2b_form_activities', '' ) );
if ( ! $activities ) {
	$activities = array( 'Ufficio', 'Negozio', 'Supermercato', 'Palestra', 'Altro' );
}
$form_args = array(
	'landing'     => $landing,
	'title'       => tp_b2b( 'b2b_form_title', 'Richiedi il preventivo gratuito' ),
	'subtitle'    => tp_b2b( 'b2b_form_subtitle', 'Sopralluogo gratuito in sede. Ti richiamiamo entro 1 ora.' ),
	'cta_label'   => tp_b2b( 'b2b_form_cta', 'Richiedi il preventivo gratuito' ),
	'note'        => tp_b2b( 'b2b_form_note', '★ Gratis · con fattura · risposta in 1h' ),
	'activities'  => $activities,
	'phone_label' => $phone_label,
	'phone_href'  => $phone_href,
);

get_header( 'landing', array(
	'promo_desktop' => tp_b2b( 'b2b_promo_desktop', 'Sopralluogo gratuito in sede · preventivo con fattura · ti richiamiamo entro 1 ora' ),
	'promo_mobile'  => tp_b2b( 'b2b_promo_mobile', 'Sopralluogo gratuito in sede · preventivo con fattura' ),
	'links'         => array(
		'#settori'       => 'Settori',
		'#come-funziona' => 'Come funziona',
	),
	'phone_label'   => $phone_label,
	'phone_href'    => $phone_href,
	'cta_label'     => tp_b2b( 'b2b_nav_cta', 'Preventivo gratuito' ),
	'cta_short'     => 'Preventivo gratuito',
	'cta_href'      => '#form',
) );
?>
<main class="sgrosso b2b" id="contenuto" data-landing="<?php echo esc_attr( $landing ); ?>">

	<section class="sg-hero" id="hero">
		<div class="sg-hero__panel">
			<div class="sg-hero__media" aria-hidden="true">
				<?php if ( $hero_image_field ) : ?>
					<img class="sg-hero__img" src="<?php echo esc_url( $hero_image_field ); ?>" alt="" width="1920" height="1071" fetchpriority="high" decoding="async">
				<?php else : ?>
					<picture>
						<source media="(max-width: 767px)" srcset="<?php echo esc_url( $img( 'hero-pavimento-720' ) ); ?>" type="image/webp">
						<img class="sg-hero__img" src="<?php echo esc_url( $img( 'hero-pavimento' ) ); ?>" alt="" width="1000" height="1133" fetchpriority="high" decoding="async">
					</picture>
				<?php endif; ?>
				<div class="sg-hero__scrim"></div>
			</div>

			<div class="sg-hero__inner">
				<div class="sg-hero__copy">
					<p class="sg-hero__eyebrow"><?php echo esc_html( tp_b2b( 'b2b_hero_eyebrow', 'PULIZIE COMMERCIALI · ROMA' ) ); ?></p>
					<h1><?php echo nl2br( esc_html( tp_b2b( 'b2b_hero_title', "Pulizie per uffici, negozi e locali.\nAnche quando sei chiuso." ) ) ); ?></h1>
					<p class="sg-hero__subtitle"><?php echo nl2br( esc_html( tp_b2b( 'b2b_hero_subtitle', 'Pulizia a fondo e servizi ricorrenti per la tua attività. Sopralluogo gratuito in sede, preventivo su misura e fattura.' ) ) ); ?></p>
					<ul class="sg-hero__bullets">
						<?php foreach ( $hero_bullets as $riga ) : ?>
							<li><?php echo tp_icon_span( 'assets/icons/ic-check.svg', 'sg-hero__check' ); ?><span><?php echo esc_html( $riga ); ?></span></li>
						<?php endforeach; ?>
					</ul>
					<?php $social = tp_b2b( 'b2b_hero_social', 'Uffici, retail e locali serviti a Roma' ); ?>
					<?php if ( $social ) : ?>
						<p class="sg-hero__social">
							<span class="sg-hero__stars" aria-label="5 stelle su 5">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
							<span><?php echo esc_html( $social ); ?></span>
						</p>
					<?php endif; ?>
				</div>

				<div class="sg-hero__form" id="form">
					<?php get_template_part( 'template-parts/lead-form-b2b', null, array_merge( $form_args, array( 'id' => 'lead-hero', 'cta_position' => 'hero' ) ) ); ?>
				</div>
			</div>
		</div>
	</section>

	<?php
	// Trust strip: le quattro promesse del ridisegno (il live diceva "Soddisfatti o ripuliamo" e "assicurazione RC",
	// claim ancora aperti: qui restano fuori, SPEC §6).
	$trust_items = tp_lines( tp_b2b( 'b2b_trust_items', '' ) );
	if ( ! $trust_items ) {
		$trust_items = array( 'Sopralluogo gratuito in sede', 'Fattura su ogni intervento', 'Anche di notte o nel weekend', 'Roma e provincia' );
	}
	get_template_part( 'template-parts/trust-bar', null, array( 'items' => $trust_items ) );

	// Settori (TICKET-59): componente "Quando serve" dello Sgrosso con foto singola e chip per settore.
	// Testi di Uffici dal live; gli altri tre scritti sul live per il ridisegno (frame 6784:*), da rileggere (SPEC §2.5).
	$sectors = tp_b2b( 'b2b_sectors', array() );
	if ( $sectors ) {
		$sectors = array_map( function ( $s ) {
			$s['chips'] = tp_lines( $s['chips'] ?? '' );
			return $s;
		}, $sectors );
	} else {
		$sectors = array(
			array( 'key' => 'uffici', 'label' => 'Uffici', 'title' => 'Uffici', 'text' => "Postazioni, sale riunioni, aree comuni e servizi. Interveniamo prima dell'apertura o dopo la chiusura, senza fermare il lavoro.", 'highlight' => 'Sempre lo stesso referente, fattura su ogni intervento', 'image' => $img( 'settore-uffici' ), 'image_alt' => 'Ufficio open space pulito', 'chips' => array( 'Postazioni', 'Sale riunioni', 'Aree comuni', 'Bagni', 'Cucinette', 'Vetrate' ) ),
			array( 'key' => 'negozi', 'label' => 'Negozi & retail', 'title' => 'Negozi & retail', 'text' => "Vetrine, camerini, magazzino e superfici vendita. Interveniamo prima dell'apertura: il negozio è pronto quando entra il primo cliente.", 'highlight' => "Vetrine pulite prima dell'apertura, fattura su ogni intervento", 'image' => $img( 'settore-retail' ), 'image_alt' => 'Negozio con vetrine pulite', 'chips' => array( 'Vetrine', 'Camerini', 'Magazzino', 'Superfici vendita', 'Bagni', 'Ingresso' ) ),
			array( 'key' => 'supermercati', 'label' => 'Supermercati', 'title' => 'Supermercati', 'text' => "Reparti, banchi, retro e pavimenti industriali. Lavasciuga professionale per le superfici grandi, fuori dall'orario di apertura.", 'highlight' => 'Interventi di notte, senza fermare la vendita', 'image' => $img( 'settore-supermercati' ), 'image_alt' => 'Corsia di supermercato pulita', 'chips' => array( 'Reparti', 'Banchi', 'Retro', 'Pavimenti', 'Bagni', 'Ingresso' ) ),
			array( 'key' => 'palestre', 'label' => 'Palestre & centri', 'title' => 'Palestre & centri', 'text' => 'Spogliatoi, attrezzi e aree umide sanificate. Igiene costante dove i clienti la notano di più.', 'highlight' => 'Aree umide sanificate, anche ogni giorno', 'image' => $img( 'settore-palestre' ), 'image_alt' => 'Sala attrezzi di una palestra pulita', 'chips' => array( 'Spogliatoi', 'Docce', 'Attrezzi', 'Sala corsi', 'Reception', 'Bagni' ) ),
		);
	}
	get_template_part( 'template-parts/scenario-tabs', null, array(
		'eyebrow'      => tp_b2b( 'b2b_sectors_eyebrow', 'SETTORI SERVITI' ),
		'title'        => tp_b2b( 'b2b_sectors_title', 'Puliamo ogni tipo di attività' ),
		'scenarios'    => $sectors,
		'areas_title'  => tp_b2b( 'b2b_sectors_areas_title', 'Cosa puliamo' ),
		'areas_text'   => tp_b2b( 'b2b_sectors_areas_text', 'Cambia settore per vedere cosa include. Il resto lo concordiamo al sopralluogo.' ),
		'cta_label'    => tp_b2b( 'b2b_sectors_cta', 'Richiedi il preventivo gratuito' ),
		'cta_link'     => '#form',
		'cta_position' => 'settori',
		'default'      => $sectors[0]['key'],
		'show_tabs'    => true,
		'id'           => 'settori',
	) );

	// Lavori a Roma (TICKET-59): prima/dopo trascinabile delle vetrate e galleria, foto reali del live.
	$gallery = tp_b2b( 'b2b_works_gallery', array() );
	if ( ! $gallery ) {
		$gallery = array(
			array( 'image' => $img( 'lavoro-pavimento' ), 'caption' => 'Lavasciuga professionale su pavimento post-cantiere' ),
			array( 'image' => $img( 'lavoro-spogliatoio' ), 'caption' => 'Spogliatoio consegnato pulito' ),
			array( 'image' => $img( 'lavoro-ufficio' ), 'caption' => 'Pavimento lucidato a specchio' ),
		);
	}
	get_template_part( 'template-parts/works-gallery', null, array(
		'eyebrow'  => tp_b2b( 'b2b_works_eyebrow', 'LAVORI A ROMA' ),
		'title'    => tp_b2b( 'b2b_works_title', 'Fine cantiere: il prima e dopo reale' ),
		'subtitle' => tp_b2b( 'b2b_works_subtitle', 'Foto reali dei nostri interventi a Roma. Nessun ritocco: solo taglio ed esposizione.' ),
		'compare'  => array(
			'before'         => carbon_get_the_post_meta( 'b2b_works_before' ) ?: $img( 'vetrate-prima' ),
			'after'          => carbon_get_the_post_meta( 'b2b_works_after' ) ?: $img( 'vetrate-dopo' ),
			'before_alt'     => 'Vetrate prima della pulizia, velate dai residui di cantiere',
			'after_alt'      => 'Vetrate dopo la pulizia, riflesso limpido',
			'title'          => tp_b2b( 'b2b_works_compare_title', 'Vetrate di un locale commerciale' ),
			'text'           => tp_b2b( 'b2b_works_compare_text', 'Vetri velati dai residui di cantiere, riportati a un riflesso limpido prima della riapertura.' ),
			'before_caption' => tp_b2b( 'b2b_works_before_caption', 'Vetri velati da residui di cantiere' ),
			'after_caption'  => tp_b2b( 'b2b_works_after_caption', 'Vetri puliti, riflesso limpido' ),
		),
		'gallery'  => $gallery,
	) );

	// Una volta sola o tutto l'anno (TICKET-60): componente "Per chi e'" della pagina Diventa cleaner.
	$plans = tp_b2b( 'b2b_plans', array() );
	if ( $plans ) {
		$plans = array_map( function ( $c ) {
			$c['bullets']      = tp_lines( $c['bullets'] ?? '' );
			$c['cta_link']     = '#form-bottom';
			$c['cta_position'] = 'formule';
			$c['cta_variant']  = ! empty( $c['dark'] ) ? 'primary' : 'ghost';
			return $c;
		}, $plans );
	} else {
		$plans = array(
			array( 'badge' => 'UNA-TANTUM', 'title' => 'Uno sgrosso quando serve', 'text' => 'Per riaprire dopo un cantiere, cambiare sede o rimettere a nuovo gli spazi.', 'bullets' => array( 'Fine cantiere, riapertura, trasloco di sede', 'Preventivo dopo il sopralluogo gratuito', 'Fattura elettronica' ), 'cta_label' => 'Richiedi il preventivo', 'cta_link' => '#form-bottom', 'cta_position' => 'formule', 'cta_variant' => 'ghost', 'dark' => false ),
			array( 'badge' => 'CONTRATTO RICORRENTE', 'title' => 'Pulizie ricorrenti, stesso team', 'text' => 'Per chi vuole spazi sempre in ordine senza pensarci: frequenza e orari li decidi tu.', 'bullets' => array( 'Frequenza su misura: giornaliera, settimanale o mensile', 'Sempre lo stesso referente', 'Senza vincoli di durata' ), 'cta_label' => 'Richiedi il preventivo', 'cta_link' => '#form-bottom', 'cta_position' => 'formule', 'cta_variant' => 'primary', 'dark' => true ),
		);
	}
	get_template_part( 'template-parts/audience-cards', null, array(
		'eyebrow'  => tp_b2b( 'b2b_plans_eyebrow', 'COME LAVORIAMO' ),
		'title'    => tp_b2b( 'b2b_plans_title', "Una volta sola o tutto l'anno" ),
		'subtitle' => tp_b2b( 'b2b_plans_subtitle', 'Scegli tu la formula. Al sopralluogo gratuito definiamo servizi, orari e prezzo.' ),
		'id'       => 'formule',
		'cards'    => $plans,
	) );

	// Perche' noi (TICKET-60): tre card del live e la quarta del ridisegno al posto di "Una-tantum o ricorrente",
	// gia' coperta dalla sezione sopra.
	$why_icons = array( 'user-check' => 'assets/icons/ic-user-check.svg', 'clock' => 'assets/icons/ic-clock.svg', 'shield-check' => 'assets/icons/ic-shield-check.svg', 'spray' => 'assets/icons/ic-spray.svg', 'check' => 'assets/icons/ic-check.svg' );
	$why = tp_b2b( 'b2b_why_items', array() );
	if ( $why ) {
		$why = array_map( function ( $v ) use ( $why_icons ) {
			$v['icon'] = $why_icons[ $v['icon'] ?? '' ] ?? $why_icons['check'];
			return $v;
		}, $why );
	} else {
		$why = array(
			array( 'icon' => $why_icons['user-check'], 'title' => 'Un referente dedicato', 'text' => 'Una sola persona segue la tua attività, sempre la stessa. Zero passaggi di mano.' ),
			array( 'icon' => $why_icons['clock'], 'title' => 'Orari su misura', 'text' => "Interveniamo quando sei chiuso: notte, prima o dopo l'orario. Nessuno stop all'attività." ),
			array( 'icon' => $why_icons['shield-check'], 'title' => 'Tutto in regola', 'text' => 'Fattura, personale in regola e assicurazione RC. Prodotti professionali inclusi.' ),
			array( 'icon' => $why_icons['spray'], 'title' => 'Prodotti e macchinari professionali', 'text' => 'Prodotti professionali inclusi e lavasciuga per le superfici grandi. Tu non devi procurare niente.' ),
		);
	}
	get_template_part( 'template-parts/value-props', null, array(
		'eyebrow' => tp_b2b( 'b2b_why_eyebrow', 'PERCHÉ NOI' ),
		'title'   => tp_b2b( 'b2b_why_title', 'Un fornitore serio per la tua attività' ),
		'items'   => $why,
	) );

	// Come funziona (TICKET-61): tre card numerate, testi STEPS del live.
	$hiw_steps = tp_b2b( 'b2b_hiw_steps', array() );
	if ( ! $hiw_steps ) {
		$hiw_steps = array(
			array( 'title' => 'Richiedi il preventivo', 'description' => 'Compili il form o ci chiami. Fissiamo il sopralluogo gratuito in sede.' ),
			array( 'title' => 'Sopralluogo e preventivo', 'description' => 'Vediamo gli spazi, concordiamo orari, servizi e prezzo. Con fattura.' ),
			array( 'title' => 'Interveniamo', 'description' => 'Team verificato, fuori dai tuoi orari. Una-tantum o contratto ricorrente.' ),
		);
	}
	get_template_part( 'template-parts/steps-cards', null, array(
		'eyebrow'  => tp_b2b( 'b2b_hiw_eyebrow', 'COME FUNZIONA' ),
		'title'    => tp_b2b( 'b2b_hiw_title', "Dal preventivo all'intervento" ),
		'subtitle' => tp_b2b( 'b2b_hiw_subtitle', 'Tre passi, senza fermare la tua attività.' ),
		'steps'    => $hiw_steps,
	) );

	// Recensioni (TICKET-61): le tre del live, dichiarate fittizie nel sorgente (REVIEWS); iniziali al posto delle
	// foto di persone a caso (decisione Guido 25/10). Da sostituire con recensioni vere prima del cutover (SPEC §6).
	$reviews = tp_b2b( 'b2b_reviews', array() );
	if ( ! $reviews ) {
		$reviews = array(
			array( 'author_name' => 'Marco C.', 'author_city' => 'Studio, Prati', 'review_text' => 'Puliscono il nostro studio ogni settimana, sempre fuori orario. Fattura puntuale e un referente unico. Zero pensieri.' ),
			array( 'author_name' => 'Laura R.', 'author_city' => 'Negozio, Centro', 'review_text' => 'Sgrosso completo prima della riapertura. Preventivo chiaro, lavoro serio. Ora abbiamo un contratto fisso.' ),
			array( 'author_name' => 'Andrea P.', 'author_city' => 'Palestra, EUR', 'review_text' => 'Spogliatoi e aree umide sempre impeccabili. Personale affidabile e in regola. Consigliatissimi.' ),
		);
	}
	get_template_part( 'template-parts/reviews-marquee', null, array(
		'eyebrow'     => tp_b2b( 'b2b_reviews_eyebrow', 'DICONO DI NOI' ),
		'title'       => tp_b2b( 'b2b_reviews_title', 'Attività che si affidano a noi' ),
		'reviews'     => $reviews,
		'layout'      => 'grid',
		'card'        => 'stars',
		'avatar_mode' => 'initials',
	) );

	// FAQ (TICKET-61): sei domande del live (tolta "Solo una-tantum o anche ricorrente?", coperta dalle formule),
	// card dei fondatori con le foto della Home.
	$faq_items = tp_b2b( 'b2b_faq_items', array() );
	if ( ! $faq_items ) {
		$faq_items = array(
			array( 'question' => 'Intervenite fuori dagli orari di apertura?', 'answer' => "Sì: notte, prima o dopo l'orario, weekend. Concordiamo la fascia al sopralluogo, senza fermare l'attività." ),
			array( 'question' => 'Emettete fattura?', 'answer' => 'Sempre: fattura elettronica su ogni intervento, sia una-tantum sia ricorrente.' ),
			array( 'question' => 'Quanto costa?', 'answer' => 'Dipende da superficie, tipo di attività e frequenza. Il sopralluogo è gratuito: preventivo su misura, senza sorprese.' ),
			array( 'question' => 'In quanto tempo partite?', 'answer' => 'Dopo il sopralluogo definiamo tempi e prima data. In genere entro pochi giorni.' ),
			array( 'question' => 'In che zone operate?', 'answer' => 'Roma città e provincia. Indica la zona nel form e confermiamo la disponibilità.' ),
			array( 'question' => 'Siete assicurati?', 'answer' => 'Sì: copertura assicurativa RC su ogni intervento e personale in regola.' ),
		);
	}
	$founders = tp_founders_contact();
	get_template_part( 'template-parts/faq-accordion', null, array(
		'eyebrow'  => tp_b2b( 'b2b_faq_eyebrow', 'DOMANDE FREQUENTI' ),
		'title'    => tp_b2b( 'b2b_faq_title', 'Prima di richiedere il preventivo' ),
		'subtitle' => tp_b2b( 'b2b_faq_subtitle', 'Le risposte rapide. Per tutto il resto, il sopralluogo in sede è gratuito.' ),
		'items'    => $faq_items,
		'contact'  => array(
			'people'    => $founders['people'],
			'role'      => $founders['role'],
			'avatar_1'  => $founders['avatar_1'],
			'avatar_2'  => $founders['avatar_2'],
			'title'     => tp_b2b( 'b2b_faq_contact_title', 'Rispondiamo di persona' ),
			'text'      => tp_b2b( 'b2b_faq_contact_text', 'Scrivici: ti risponde Guido o Christian, in base alla disponibilità. I fondatori, non un call center.' ),
			'cta_label' => tp_b2b( 'b2b_faq_contact_cta', 'Richiedi il preventivo gratuito' ),
			'cta_link'  => '#form-bottom',
		),
	) );

	// CTA finale con la seconda istanza del form (TICKET-61): cta_position 'bottom', id #form-bottom, senza mascotte.
	$cta_bullets = tp_lines( tp_b2b( 'b2b_cta_bullets', '' ) );
	if ( ! $cta_bullets ) {
		$cta_bullets = array( 'Sopralluogo gratuito in sede', 'Fattura su ogni intervento', 'Ti richiamiamo entro 1 ora', 'Anche di notte o nel weekend' );
	}
	get_template_part( 'template-parts/cta-form', null, array(
		'eyebrow'   => tp_b2b( 'b2b_cta_eyebrow', 'ULTIMO PASSO' ),
		'title'     => tp_b2b( 'b2b_cta_title', 'Un preventivo su misura per la tua attività' ),
		'subtitle'  => tp_b2b( 'b2b_cta_subtitle', 'Sopralluogo gratuito in sede, preventivo con fattura. Ti richiamiamo entro 1 ora.' ),
		'bullets'   => $cta_bullets,
		'image'     => '',
		'form_part' => 'template-parts/lead-form-b2b',
		'form'      => array_merge( $form_args, array( 'id' => 'lead-bottom', 'cta_position' => 'bottom' ) ),
	) );
	?>

	<?php get_template_part( 'template-parts/sticky-cta', null, array(
		'variant'     => 'dual',
		'label'       => 'Richiedi preventivo',
		'target'      => '#form',
		'watch'       => '#form',
		'phone_href'  => $phone_href,
		'phone_label' => 'Chiama',
	) ); ?>

</main>
<?php
get_footer();
