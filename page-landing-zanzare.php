<?php
/**
 * Template Name: Landing zanzare
 *
 * Landing "Disinfestazione zanzare" per privati, condomini e attivita': /roma/zanzare-disinfestazione/ (post 13,
 * figlia di /roma/). Design: Figma 4849:884 (pagina "Sito Web"); testi, claim, prezzi, recensioni e foto dal live
 * React (ZanzareDisinfestazionePage.tsx su master), portati as-is per decisione di Guido del 27/10.
 * PRD e ticket nel vault: clients/trovapulizie/projects/sito-web/spec/SPEC-landing-zanzare.md (TICKET-73..76).
 *
 * Legge la scheda "Landing zanzare — Sezioni" (prefisso zz_): ogni testo ha il default qui sotto e il campo, se
 * compilato, vince (tp_zz()). Form a un passo: template-parts/lead-form-b2b.php con "Chi sei" (Privato = lead b2c,
 * Condominio e Attivita' = b2b, come toZanzareLead), una sola POST /api/leads, conferma in pagina.
 *
 * [ATTENZIONE] Il template va assegnato a mano: il file non si chiama page-zanzare-disinfestazione.php di
 * proposito (trappola dello slug, vedi page-landing-b2b.php).
 */

if ( ! function_exists( 'tp_zz' ) ) {
	/** Campo della pagina se compilato, altrimenti il default; per i complex, l'array se non vuoto. */
	function tp_zz( $field, $default = '' ) {
		$v = carbon_get_the_post_meta( $field );
		if ( is_array( $v ) ) {
			return $v ?: $default;
		}
		$v = trim( (string) $v );
		return '' === $v ? $default : $v;
	}
}

$landing = 'zanzare-disinfestazione'; // identificatore negli eventi dataLayer, uguale alla SPA
$img     = function ( $name ) { return tp_theme_asset( 'assets/img/zanzare/zanzare-' . $name . '.webp' ); };

$phone_label = tp_zz( 'zz_phone', '06 9151 6075' );
$phone_href  = 'tel:' . preg_replace( '/\D+/', '', $phone_label );
$wa          = tp_whatsapp_parts( TP_CT_WHATSAPP, 'Ciao, vorrei un sopralluogo per disinfestazione zanzare.' );

$form_args = array(
	'landing'           => $landing,
	'title'             => tp_zz( 'zz_form_title', 'Prenota il sopralluogo gratuito' ),
	'subtitle'          => tp_zz( 'zz_form_subtitle', 'Gratis e senza impegno. Ti richiamiamo subito e fissiamo l’intervento.' ),
	'coupon'            => tp_zz( 'zz_form_coupon', '−20% sul primo trattamento, con l’email' ),
	'cta_label'         => 'Prenota il sopralluogo gratuito',
	'note'              => '',
	'activities'        => array( 'Privato', 'Condominio', 'Attività' ),
	'chooser_label'     => 'Chi sei? *',
	'chooser_types'     => array( 'Privato' => 'b2c', 'Condominio' => 'b2b', 'Attività' => 'b2b' ),
	'chooser_error'     => 'Dicci chi sei',
	'note_prefix'       => 'Disinfestazione zanzare · Chi sei: ',
	'show_mq'           => false,
	'show_email'        => true,
	'email_label'       => tp_zz( 'zz_form_email_label', 'Email (opzionale) · vale il −20%' ),
	'show_marketing'    => false,
	'phone_field_label' => 'Telefono, ti richiamiamo noi *',
	'phone_label'       => $phone_label,
	'phone_href'        => $phone_href,
	'call_label'        => $phone_label,
	'whatsapp_href'     => $wa ? $wa['link'] : '',
	'done_title'        => 'Richiesta inviata!',
	'done_text'         => 'Ti contattiamo a breve per fissare il sopralluogo.',
);

get_header( 'landing', array(
	'promo_desktop' => tp_zz( 'zz_promo_desktop', '★ Stagione zanzare · intervento in 24–48h · sopralluogo gratuito' ),
	'promo_mobile'  => tp_zz( 'zz_promo_mobile', '★ Stagione zanzare · sopralluogo gratuito' ),
	'links'         => array(
		'#per-chi'       => 'Per chi',
		'#come-funziona' => 'Come funziona',
		'#prezzi'        => 'Prezzi',
	),
	'phone_label'   => $phone_label,
	'phone_href'    => $phone_href,
	'cta_label'     => 'Sopralluogo gratuito',
	'cta_short'     => 'Sopralluogo gratuito',
	'cta_href'      => '#form',
) );

$hero_bullets = tp_lines( tp_zz( 'zz_hero_bullets', '' ) ) ?: array( 'Intervento in 24–48h', 'Sicuro per bambini e animali domestici', 'Prodotti a norma di legge', 'Garanzia: se tornano, ri-trattiamo', 'Fattura e assicurazione RC per aziende' );
?>
<main class="sgrosso b2b zz" id="contenuto" data-landing="<?php echo esc_attr( $landing ); ?>">

	<section class="sg-hero" id="hero">
		<div class="sg-hero__panel">
			<div class="sg-hero__media" aria-hidden="true">
				<picture>
					<source media="(max-width: 767px)" srcset="<?php echo esc_url( $img( 'hero-720' ) ); ?>" type="image/webp">
					<img class="sg-hero__img" src="<?php echo esc_url( $img( 'hero' ) ); ?>" alt="" width="1376" height="768" fetchpriority="high" decoding="async">
				</picture>
				<div class="sg-hero__scrim"></div>
			</div>
			<div class="sg-hero__inner">
				<div class="sg-hero__copy">
					<p class="sg-hero__eyebrow"><?php echo esc_html( tp_zz( 'zz_hero_eyebrow', 'DISINFESTAZIONE ZANZARE · ROMA' ) ); ?></p>
					<h1><?php echo nl2br( esc_html( tp_zz( 'zz_hero_title', "Riprenditi il giardino.\nZanzare fuori, in sicurezza." ) ) ); ?></h1>
					<p class="sg-hero__subtitle"><?php echo esc_html( tp_zz( 'zz_hero_subtitle', 'Trattamento professionale contro zanzare comuni e tigre — case, giardini, condomini e attività a Roma. Sopralluogo gratuito, intervento in 24–48h, sicuro per bambini e animali.' ) ); ?></p>
					<ul class="sg-hero__bullets">
						<?php foreach ( $hero_bullets as $riga ) : ?>
							<li><?php echo tp_icon_span( 'assets/icons/ic-check.svg', 'sg-hero__check' ); ?><span><?php echo esc_html( $riga ); ?></span></li>
						<?php endforeach; ?>
					</ul>
					<?php $social = tp_zz( 'zz_hero_social', '4,9 su Google · 32 recensioni · Roma Nord, Parioli, EUR' ); ?>
					<?php if ( $social ) : ?>
						<p class="sg-hero__social"><span class="sg-hero__stars" aria-label="5 stelle su 5">&#9733;&#9733;&#9733;&#9733;&#9733;</span><span><?php echo esc_html( $social ); ?></span></p>
					<?php endif; ?>
				</div>
				<div class="sg-hero__form" id="form">
					<?php get_template_part( 'template-parts/lead-form-b2b', null, array_merge( $form_args, array( 'id' => 'lead-hero', 'cta_position' => 'hero', 'urgency' => tp_zz( 'zz_form_urgency', 'In alta stagione l’agenda si riempie in fretta' ) ) ) ); ?>
				</div>
			</div>
		</div>
	</section>

	<?php
	get_template_part( 'template-parts/trust-bar', null, array(
		'items' => tp_lines( tp_zz( 'zz_trust_items', '' ) ) ?: array( 'Operatori formati', 'Prodotti registrati', 'Sicuro bimbi e animali', 'Roma e provincia', 'Fattura e assicurazione RC' ),
	) );

	// Autorita' e sicurezza (TICKET-74): componente "Perche' noi" della B2B, quattro colonne su questa pagina.
	$check = 'assets/icons/ic-check.svg';
	$autorita = tp_pipe_lines( tp_zz( 'zz_auth_items', "Adulti + larve | Nebulizzazione sulle zanzare adulte + larvicida su tombini, ristagni e sottovasi. Colpiamo dove nascono, non solo dove volano.\nOperatori con patentino | Chi tratta ha formazione e abilitazione. Niente fai-da-te con la chimica in giardino.\nProdotti registrati | Solo presidi registrati al Ministero della Salute, ai dosaggi corretti e sicuri.\nGaranzia ri-trattamento | Se le zanzare tornano entro 15 giorni, torniamo a trattare gratis. Nero su bianco." ) );
	get_template_part( 'template-parts/value-props', null, array(
		'eyebrow'  => tp_zz( 'zz_auth_eyebrow', 'PERCHÉ FIDARTI DI NOI' ),
		'title'    => tp_zz( 'zz_auth_title', 'La differenza non è lo spray: è colpire dove nascono' ),
		'subtitle' => tp_zz( 'zz_auth_subtitle', 'Zanzare adulte + focolai larvali, prodotti a norma, operatori formati. Lo spray dà tre giorni di tregua; noi eliminiamo il problema alla fonte.' ),
		'id'       => 'perche-noi',
		'items'    => array_map( function ( $r ) use ( $check ) { return array( 'icon' => $check, 'title' => $r['title'], 'text' => $r['text'] ); }, $autorita ),
	) );

	get_template_part( 'template-parts/zanzare-tigre', null, array(
		'eyebrow'   => tp_zz( 'zz_tiger_eyebrow', 'ZANZARA TIGRE' ),
		'title'     => tp_zz( 'zz_tiger_title', 'Nasce in un tappo d’acqua' ),
		'text'      => tp_zz( 'zz_tiger_text', 'La tigre depone in pochi millilitri d’acqua stagnante: sottovasi, tombini, ciotole. Ecco perché lo spray da solo non basta — la rivedi in tre giorni. Bisogna eliminare i focolai. Noi trattiamo adulti e larve.' ),
		'check'     => tp_zz( 'zz_tiger_check', 'Adulticida sulle adulte + larvicida sui focolai' ),
		'box_title' => tp_zz( 'zz_tiger_box_title', 'Dove nasce (e dove trattiamo)' ),
		'chips'     => tp_lines( tp_zz( 'zz_tiger_chips', '' ) ) ?: array( 'Sottovasi', 'Tombini', 'Grondaie', 'Ciotole animali', 'Teli e copertoni', 'Ristagni d’acqua' ),
		'box_note'  => tp_zz( 'zz_tiger_note', 'Al sopralluogo li mappiamo tutti. Bastano pochi focolai ignorati per ripartire da capo.' ),
	) );

	// Per chi (TICKET-74): componente "Quando serve" con foto singola; ogni tab ha la sua CTA che preseleziona Chi sei.
	$per_chi = array(
		array( 'key' => 'privato', 'label' => 'Privato', 'title' => 'Privati — casa e giardino', 'text' => 'Trattamento completo del giardino: nebulizzazione sulle aree verdi + larvicida sui focolai. Sicuro per bambini e animali. Intervento singolo o programma stagionale maggio–settembre.', 'highlight' => 'Da €119 · prezzo esatto al sopralluogo', 'image' => $img( 'perchi-privato' ), 'image_alt' => 'Casa e giardino', 'chips' => array( 'Giardino e terrazzo', 'Siepi e aree verdi', 'Zona pranzo esterna', 'Giochi e area animali' ), 'areas_text' => 'Sopralluogo gratuito: mappiamo gli spazi e trattiamo solo dove serve.', 'cta_label' => 'Prenota il sopralluogo gratuito', 'preselect' => 'Privato' ),
		array( 'key' => 'condominio', 'label' => 'Condominio', 'title' => 'Condomini', 'text' => 'Aree verdi, tombini e parti comuni con calendario concordato e fattura. Ordinanza 57/2026: il larvicida nei condomini di Roma è obbligatorio — ti mettiamo in regola.', 'highlight' => 'Ordinanza 57/2026 · larvicida obbligatorio', 'image' => $img( 'perchi-condominio' ), 'image_alt' => 'Condominio', 'chips' => array( 'Giardino condominiale', 'Tombini e caditoie', 'Cortili e parti comuni', 'Parcheggi e ristagni' ), 'areas_text' => 'Preventivo scritto per l’amministratore, delibera semplice.', 'cta_label' => 'Richiedi preventivo con fattura', 'preselect' => 'Condominio' ),
		array( 'key' => 'ristorante', 'label' => 'Bar e ristoranti', 'title' => 'Bar e ristoranti', 'text' => 'Clienti punzecchiati = tavoli vuoti. Trattiamo dehors, fioriere e retro fuori dall’orario di apertura, senza odori e senza giorni di chiusura. Fattura e contratto stagionale.', 'highlight' => 'Fuori orario di apertura · zero chiusure', 'image' => $img( 'perchi-ristorante' ), 'image_alt' => 'Dehors di un ristorante', 'chips' => array( 'Dehors e tavoli esterni', 'Fioriere e verde', 'Retro e magazzini', 'Tombini adiacenti' ), 'areas_text' => 'Interventi programmati fuori orario di apertura.', 'cta_label' => 'Richiedi preventivo con fattura', 'preselect' => 'Attività' ),
		array( 'key' => 'hotel', 'label' => 'Hotel e B&B', 'title' => 'Hotel, B&B e strutture', 'text' => 'Una notte di punture vale una recensione negativa. Proteggiamo giardini, piscina e aree comuni con contratto stagionale, referente dedicato e assicurazione RC.', 'highlight' => 'Contratto stagionale · referente dedicato', 'image' => $img( 'perchi-hotel' ), 'image_alt' => 'Hotel con giardino', 'chips' => array( 'Giardini e piscina', 'Aree colazione esterne', 'Terrazze e solarium', 'Percorsi e parcheggi' ), 'areas_text' => 'Referente dedicato e assicurazione RC inclusi.', 'cta_label' => 'Richiedi preventivo con fattura', 'preselect' => 'Attività' ),
	);
	get_template_part( 'template-parts/scenario-tabs', null, array(
		'eyebrow'      => tp_zz( 'zz_who_eyebrow', 'PER CHI' ),
		'title'        => tp_zz( 'zz_who_title', 'Dal giardino di casa al dehors del ristorante' ),
		'scenarios'    => $per_chi,
		'areas_title'  => 'Cosa proteggiamo',
		'cta_link'     => '#form',
		'cta_position' => 'per_chi',
		'default'      => 'privato',
		'show_tabs'    => true,
		'id'           => 'per-chi',
	) );

	get_template_part( 'template-parts/service-cards', null, array(
		'eyebrow' => tp_zz( 'zz_what_eyebrow', 'COSA FACCIAMO' ),
		'title'   => tp_zz( 'zz_what_title', 'Cosa include il trattamento' ),
		'items'   => array(
			array( 'image' => $img( 'cosafacciamo-nebulizzazione' ), 'title' => 'Nebulizzazione', 'text' => 'Abbatte subito le zanzare adulte su siepi, cespugli e aree verdi. Effetto immediato.' ),
			array( 'image' => $img( 'cosafacciamo-larvicida' ), 'title' => 'Larvicida nei focolai', 'text' => 'Blocca il ciclo in tombini, caditoie e ristagni: la parte che lo spray fai-da-te non fa.' ),
			array( 'image' => $img( 'cosafacciamo-barriera' ), 'title' => 'Barriera perimetrale', 'text' => 'Fascia protettiva sui confini, contro le zanzare in arrivo da giardini vicini e aree non curate.' ),
			array( 'image' => $img( 'cosafacciamo-controllo' ), 'title' => 'Controllo tra i passaggi', 'text' => 'Verifichiamo i focolai tra un trattamento e l’altro. Se serve, ri-trattiamo in garanzia.' ),
		),
		'banner'  => array(
			'title'        => tp_zz( 'zz_b2b_title', 'Condomini e attività: preventivo su misura' ),
			'text'         => tp_zz( 'zz_b2b_text', 'Fattura, referente dedicato e nessun vincolo contrattuale. Ordinanza 57/2026: il trattamento larvicida nei condomini di Roma è obbligatorio — mettiti in regola. Preventivo entro 24h dal sopralluogo.' ),
			'cta_label'    => 'Richiedi preventivo con fattura',
			'cta_link'     => '#form',
			'cta_position' => 'cosa_facciamo',
			'preselect'    => 'Condominio',
		),
	) );

	get_template_part( 'template-parts/steps-cards', null, array(
		'eyebrow'  => tp_zz( 'zz_hiw_eyebrow', 'COME FUNZIONA' ),
		'title'    => tp_zz( 'zz_hiw_title', 'Dal sopralluogo al giardino libero' ),
		'subtitle' => tp_zz( 'zz_hiw_subtitle', 'Tre passi: veniamo, trattiamo, ti teniamo le zanzare lontane.' ),
		'steps'    => array(
			array( 'title' => 'Sopralluogo gratuito', 'description' => 'Veniamo a vedere: mappiamo focolai, ristagni e aree critiche del tuo verde.' ),
			array( 'title' => 'Trattamento mirato', 'description' => 'Adulticida sulle adulte + larvicida sui focolai, in sicurezza per bambini e animali.' ),
			array( 'title' => 'Programma stagionale', 'description' => 'Trattamenti ricorrenti tutta l’estate: te ne dimentichi, ci pensiamo noi.' ),
		),
	) );

	// Recensioni (TICKET-75): le tre del live, con le loro foto (decisione Guido 27/10: lasciarle).
	get_template_part( 'template-parts/reviews-marquee', null, array(
		'eyebrow' => tp_zz( 'zz_reviews_eyebrow', 'DICONO DI NOI' ),
		'title'   => tp_zz( 'zz_reviews_title', 'Giardini di nuovo vivibili, a Roma' ),
		'reviews' => tp_zz( 'zz_reviews', array() ) ?: array(
			array( 'author_name' => 'Giulia R.', 'author_city' => 'Parioli, Roma', 'avatar' => tp_theme_asset( 'assets/img/zanzare/zanzare-review-1.png' ), 'review_text' => 'Terrazzo invaso di zanzare tigre, la sera non si usciva. Dopo il trattamento l’abbiamo ripreso. Puntuali e chiari sul prezzo.' ),
			array( 'author_name' => 'Marco T.', 'author_city' => 'Roma Nord', 'avatar' => tp_theme_asset( 'assets/img/zanzare/zanzare-review-2.png' ), 'review_text' => 'Giardino grande, ogni estate un incubo. Col programma stagionale è tutt’altra storia — e i nostri cani stanno benissimo.' ),
			array( 'author_name' => 'Elena F.', 'author_city' => 'EUR, Roma', 'avatar' => tp_theme_asset( 'assets/img/zanzare/zanzare-review-3.png' ), 'review_text' => 'Amministro un condominio: tombini e aree verdi trattati, i residenti hanno smesso di lamentarsi delle zanzare.' ),
		),
		'layout'  => 'grid',
		'card'    => 'stars',
	) );

	get_template_part( 'template-parts/pricing-tiers', null, array(
		'eyebrow'  => tp_zz( 'zz_price_eyebrow', 'PREZZI' ),
		'title'    => tp_zz( 'zz_price_title', 'Un intervento o tutta la stagione' ),
		'subtitle' => tp_zz( 'zz_price_subtitle', 'Prezzo indicativo. Quello esatto lo confermiamo dopo il sopralluogo gratuito.' ),
		'note'     => tp_zz( 'zz_price_note', 'Prezzo esatto dopo il sopralluogo gratuito · nessun impegno' ),
		'tiers'    => array(
			array( 'label' => 'INTERVENTO SINGOLO', 'price' => tp_zz( 'zz_price1', 'da €119' ), 'unit' => '/ trattamento', 'text' => 'Un trattamento completo, quando ti serve.', 'features' => array( 'Adulticida + larvicida sui focolai', 'Sopralluogo e preventivo gratuiti', 'Garanzia ri-trattamento 15 giorni' ), 'cta_label' => 'Prenota il sopralluogo', 'cta_link' => '#form', 'cta_position' => 'prezzi' ),
			array( 'label' => 'PROGRAMMA STAGIONALE', 'badge' => 'PIÙ SCELTO', 'price' => tp_zz( 'zz_price2', 'da €449' ), 'unit' => '/ stagione', 'text' => 'Copre tutta l’estate. Te ne dimentichi.', 'features' => array( '5 trattamenti da maggio a settembre', 'Monitoraggio e larvicida inclusi', '~€90 a trattamento: risparmi €150 sui singoli', 'Priorità in agenda' ), 'cta_label' => 'Prenota il sopralluogo', 'cta_link' => '#form', 'cta_position' => 'prezzi', 'highlighted' => true ),
		),
	) );

	$founders = tp_founders_contact();
	get_template_part( 'template-parts/faq-accordion', null, array(
		'eyebrow'  => tp_zz( 'zz_faq_eyebrow', 'DOMANDE FREQUENTI' ),
		'title'    => tp_zz( 'zz_faq_title', 'La sicurezza, prima di tutto' ),
		'subtitle' => tp_zz( 'zz_faq_subtitle', 'Chimica sì, ma a norma e nelle mani giuste. Le risposte alle domande che ci fanno più spesso.' ),
		'items'    => tp_zz( 'zz_faq_items', array() ) ?: array(
			array( 'question' => 'È pericoloso per bambini e animali?', 'answer' => 'No, se fatto a norma. Usiamo prodotti registrati e, dopo l’asciugatura (in genere un paio d’ore), l’area è di nuovo fruibile in sicurezza da bambini e animali.' ),
			array( 'question' => 'Quanto dura l’effetto?', 'answer' => 'L’adulticida dura circa 3-4 settimane, il larvicida più a lungo sui focolai trattati. Col programma stagionale manteniamo la copertura continua.' ),
			array( 'question' => 'Funziona sulla zanzara tigre?', 'answer' => 'Sì. Trattiamo sia le zanzare adulte che i focolai larvali, dove la tigre depone le uova — è il punto debole su cui agiamo di più.' ),
			array( 'question' => 'Posso stare in giardino subito dopo?', 'answer' => 'Meglio aspettare il tempo di asciugatura indicato dall’operatore al sopralluogo, in genere un paio d’ore.' ),
			array( 'question' => 'Ogni quanto va ripetuto?', 'answer' => 'Un intervento singolo copre alcune settimane. In alta stagione consigliamo il programma stagionale, 5 trattamenti da maggio a settembre.' ),
			array( 'question' => 'Trattate anche tombini e parti comuni?', 'answer' => 'Sì, anche in contesti condominiali: tombini, aree verdi comuni e perimetro sono parte del sopralluogo.' ),
			array( 'question' => 'Emettete fattura e lavorate con condomini e attività?', 'answer' => 'Sì. Emettiamo regolare fattura e lavoriamo con condomini, bar, ristoranti, hotel e B&B: preventivo su misura, referente dedicato e, dove serve, contratto stagionale.' ),
			array( 'question' => 'E se piove?', 'answer' => 'Riprogrammiamo senza costi. La pioggia battente subito dopo il trattamento può ridurne l’efficacia: se serve, torniamo a trattare in garanzia.' ),
			array( 'question' => 'In che zone di Roma intervenite?', 'answer' => 'Roma e provincia — con priorità su Roma Nord, Parioli, Prati ed EUR. Dicci la zona o il CAP e ti confermiamo tempi e disponibilità.' ),
		),
		'contact'  => array(
			'people'    => $founders['people'],
			'role'      => $founders['role'],
			'avatar_1'  => $founders['avatar_1'],
			'avatar_2'  => $founders['avatar_2'],
			'title'     => 'Rispondiamo di persona',
			'text'      => 'Dubbi sulla sicurezza? Scrivici o chiamaci: ti risponde Guido o Christian.',
			'cta_label' => 'Prenota il sopralluogo',
			'cta_link'  => '#form-bottom',
		),
	) );

	get_template_part( 'template-parts/cta-form', null, array(
		'eyebrow'   => tp_zz( 'zz_cta_eyebrow', 'ULTIMO PASSO' ),
		'title'     => tp_zz( 'zz_cta_title', 'Estate senza punture. Riprenditi il giardino.' ),
		'subtitle'  => tp_zz( 'zz_cta_subtitle', 'Sopralluogo gratuito, intervento in 24–48h, trattamento sicuro per bambini e animali. Per condomini e attività: fattura e preventivo su misura.' ),
		'bullets'   => tp_lines( tp_zz( 'zz_cta_bullets', '' ) ) ?: array( 'Sopralluogo gratuito e senza impegno', 'Intervento in 24–48h', 'Sicuro per bambini e animali', 'Garanzia ri-trattamento' ),
		'image'     => '',
		'form_part' => 'template-parts/lead-form-b2b',
		'form'      => array_merge( $form_args, array( 'id' => 'lead-bottom', 'cta_position' => 'bottom' ) ),
	) );
	?>

	<?php get_template_part( 'template-parts/sticky-cta', null, array(
		'variant'     => 'dual',
		'label'       => 'Prenota sopralluogo',
		'target'      => '#form',
		'watch'       => '#form',
		'phone_href'  => $phone_href,
		'phone_label' => 'Chiama',
	) ); ?>

</main>
<?php
get_footer();
