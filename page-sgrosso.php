<?php
/**
 * Template Name: Landing Sgrosso
 *
 * Landing paid/SEO dello sgrosso B2C: una sola pagina per cinque URL (/roma/sgrosso-casa e quattro
 * verticali), differenziate dal campo "Variante" (inc/sgrosso-variants.php). Figma 4364:6628 desktop,
 * 4364:7087 mobile; i contenuti vengono dal live React. PRD e ticket nel vault:
 * clients/trovapulizie/projects/sito-web/spec/SPEC-landing-sgrosso.md (TICKET-37..50).
 *
 * Legge i campi della scheda "Landing Sgrosso — Sezioni" (prefisso sg_); ogni testo che cambia per
 * variante ha il suo default in tp_sg_defaults() e il campo, se compilato, vince (tp_sg_text()).
 *
 * Stato: hero + testata + barra fissa (TICKET-37/39/41). Le sezioni successive arrivano coi loro ticket,
 * nell'ordine del live: trust bar, Confrontiamo, Quando serve, Come funziona, Quanto costa, recensioni,
 * coupon, FAQ, CTA finale con la seconda istanza del form.
 *
 * [ATTENZIONE] Il template va assegnato a mano sulla pagina (post meta _wp_page_template): il nome del
 * file non entra nella gerarchia di nessuno slug, quindi senza assegnazione la pagina esce con index.php
 * e la scheda dei campi non compare, in silenzio.
 */
require_once __DIR__ . '/inc/sgrosso-variants.php';

$variant  = carbon_get_the_post_meta( 'sg_variant' ) ?: 'sgrosso-casa';
$defaults = tp_sg_defaults( $variant );
$landing  = $variant; // identificatore negli eventi dataLayer, uguale alla SPA

$phone_label = tp_sg_text( 'sg_nav_phone', $defaults, null, '06 9151 6075' );
$phone_href  = 'tel:' . preg_replace( '/\D+/', '', $phone_label );
if ( strpos( $phone_label, '06' ) === 0 ) {
	$phone_href = 'tel:' . preg_replace( '/\D+/', '', $phone_label );
}

$hero_image_field = carbon_get_the_post_meta( 'sg_hero_image' );
$hero_image_base  = 'assets/img/sgrosso/' . $defaults['hero_image'];
$hero_bullets     = tp_lines( tp_sg_text( 'sg_hero_bullets', $defaults, null, '' ) );
if ( ! $hero_bullets ) {
	$hero_bullets = array(
		'Sopralluogo e preventivo gratuiti, senza impegno',
		'Ditte verificate e assicurate, prodotti professionali inclusi',
		'Prezzo fisso concordato prima di iniziare',
	);
}

get_header( 'landing', array(
	'promo_desktop' => tp_sg_text( 'sg_promo_desktop', $defaults, null, 'Sopralluogo gratuito · -30€ sul primo lavoro · ti rispondiamo entro 1 ora' ),
	'promo_mobile'  => tp_sg_text( 'sg_promo_mobile', $defaults, null, 'Sopralluogo gratuito · -30€ sul primo lavoro' ),
	'links'         => array(
		'#quando-serve'  => 'Cosa include',
		'#come-funziona' => 'Come funziona',
	),
	'phone_label'   => $phone_label,
	'phone_href'    => $phone_href,
	'cta_label'     => tp_sg_text( 'sg_nav_cta', $defaults, null, 'Calcola il preventivo gratis' ),
	'cta_short'     => 'Sopralluogo gratuito',
	'cta_href'      => '#form',
) );
?>
<main class="sgrosso" id="contenuto" data-landing="<?php echo esc_attr( $landing ); ?>" data-variant="<?php echo esc_attr( $variant ); ?>">

	<?php if ( current_user_can( 'edit_posts' ) && ! carbon_get_the_post_meta( 'sg_variant' ) ) : ?>
		<div style="background:#fff3cd;border:1px solid #ffe69c;padding:12px 20px;margin:20px;border-radius:8px;">
			Variante non impostata: la pagina usa i testi della landing generica. Vai su <strong>Pagine &rarr; Modifica</strong> e compila la scheda <strong>Landing Sgrosso &mdash; Sezioni</strong>, tab Variante. Questo avviso lo vedi solo da loggato.
		</div>
	<?php endif; ?>

	<section class="sg-hero" id="hero">
		<div class="sg-hero__panel">
			<div class="sg-hero__media" aria-hidden="true">
				<?php if ( $hero_image_field ) : ?>
					<img class="sg-hero__img" src="<?php echo esc_url( $hero_image_field ); ?>" alt="" width="1920" height="1071" fetchpriority="high" decoding="async">
				<?php else : ?>
					<picture>
						<source srcset="<?php echo esc_url( tp_theme_asset( $hero_image_base . '.webp' ) ); ?>" type="image/webp">
						<img class="sg-hero__img" src="<?php echo esc_url( tp_theme_asset( $hero_image_base . '.jpg' ) ); ?>" alt="" width="1920" height="1071" fetchpriority="high" decoding="async">
					</picture>
				<?php endif; ?>
				<div class="sg-hero__scrim"></div>
			</div>

			<div class="sg-hero__inner">
				<div class="sg-hero__copy">
					<p class="sg-hero__eyebrow"><?php echo esc_html( tp_sg_text( 'sg_hero_eyebrow', $defaults, 'eyebrow' ) ); ?></p>
					<h1><?php echo nl2br( esc_html( tp_sg_text( 'sg_hero_title', $defaults, 'h1' ) ) ); ?></h1>
					<p class="sg-hero__subtitle"><?php echo nl2br( esc_html( tp_sg_text( 'sg_hero_subtitle', $defaults, 'subtitle' ) ) ); ?></p>
					<ul class="sg-hero__bullets">
						<?php foreach ( $hero_bullets as $riga ) : ?>
							<li><?php echo tp_icon_span( 'assets/icons/ic-check.svg', 'sg-hero__check' ); ?><span><?php echo esc_html( $riga ); ?></span></li>
						<?php endforeach; ?>
					</ul>
					<?php $social = tp_sg_text( 'sg_hero_social', $defaults, null, 'Famiglie a Parioli, Prati, EUR e Roma Nord' ); ?>
					<?php if ( $social ) : ?>
						<p class="sg-hero__social">
							<span class="sg-hero__stars" aria-label="5 stelle su 5">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
							<span><?php echo esc_html( $social ); ?></span>
						</p>
					<?php endif; ?>
				</div>

				<div class="sg-hero__form" id="form">
					<?php get_template_part( 'template-parts/lead-form', null, array(
						'id'           => 'lead-hero',
						'cta_position' => 'hero',
						'landing'      => $landing,
						'default_tipo' => $defaults['scenario'],
						'title'        => tp_sg_text( 'sg_form_title', $defaults, null, 'I tuoi dati di contatto' ),
						'subtitle'     => tp_sg_text( 'sg_form_subtitle', $defaults, null, "Ti risponderemo entro un'ora con una prima valutazione." ),
						'cta_label'    => tp_sg_text( 'sg_form_cta', $defaults, null, 'Prenota sopralluogo gratuito' ),
						'note'         => tp_sg_text( 'sg_form_note', $defaults, null, 'Gratis · nessun impegno · risposta in 1h' ),
					) ); ?>
				</div>
			</div>
		</div>
	</section>

	<?php
	// Trust bar (TICKET-43): quattro promesse pubbliche, le stesse del live.
	$trust_items = tp_lines( carbon_get_the_post_meta( 'sg_trust_items' ) );
	if ( ! $trust_items ) {
		$trust_items = array( 'Sopralluogo gratuito', 'Soddisfatti o ripuliamo', 'Verificati e assicurati', 'Roma e provincia' );
	}
	get_template_part( 'template-parts/trust-bar', null, array( 'items' => $trust_items ) );

	// Confrontiamo tutta Roma (TICKET-43): pilastri, CTA e card del comparatore con i contenuti del live
	// quando i campi sono vuoti. Le offerte sono un esempio dichiarato (COMPARISON_OFFERS del live).
	$pillars = carbon_get_the_post_meta( 'sg_cmp_pillars' );
	if ( ! $pillars ) {
		$pillars = array(
			array( 'title' => 'Confronto del mercato', 'description' => 'Tutte le imprese attive su Roma e provincia' ),
			array( 'title' => 'Solo imprese verificate', 'description' => 'Documenti, referenze e assicurazione controllati' ),
			array( 'title' => 'Il prezzo migliore', 'description' => "L'offerta più conveniente a parità di servizio" ),
			array( 'title' => 'Un solo referente', 'description' => 'Dal sopralluogo al lavoro finito, gestisci tutto con noi' ),
		);
	}
	$offers = carbon_get_the_post_meta( 'sg_cmp_offers' );
	if ( ! $offers ) {
		$offers = array(
			array( 'logo' => tp_theme_asset( 'assets/img/sgrosso/sgrosso-confronto-logo1.png' ), 'name' => 'Impresa A', 'badge' => 'Verificata · assicurata', 'price' => '€ 560', 'highlighted' => false ),
			array( 'logo' => tp_theme_asset( 'assets/img/sgrosso/sgrosso-confronto-logo2.png' ), 'name' => 'Impresa B', 'badge' => 'Miglior prezzo a parità di servizio', 'price' => '€ 490', 'highlighted' => true ),
			array( 'logo' => tp_theme_asset( 'assets/img/sgrosso/sgrosso-confronto-team.png' ), 'name' => 'Impresa C', 'badge' => 'Verificata · assicurata', 'price' => '€ 610', 'highlighted' => false ),
		);
	}
	get_template_part( 'template-parts/comparison', null, array(
		'eyebrow'         => tp_sg_text( 'sg_cmp_eyebrow', $defaults, null, 'PERCHÉ TROVAPULIZIE' ),
		'title'           => tp_sg_text( 'sg_cmp_title', $defaults, null, "Confrontiamo tutta Roma\nTu paghi il prezzo migliore" ),
		'subtitle'        => tp_sg_text( 'sg_cmp_subtitle', $defaults, 'confrontiamo_sub' ),
		'pillars'         => $pillars,
		'cta_label'       => tp_sg_text( 'sg_cmp_cta_label', $defaults, null, 'Calcola il preventivo gratis' ),
		'cta_link'        => tp_sg_text( 'sg_cmp_cta_link', $defaults, null, '#form' ),
		'card_title'      => tp_sg_text( 'sg_cmp_card_title', $defaults, null, 'Preventivi a confronto' ),
		'card_disclaimer' => tp_sg_text( 'sg_cmp_card_disclaimer', $defaults, null, 'Esempio illustrativo di come funziona il confronto' ),
		'card_chip'       => tp_sg_text( 'sg_cmp_card_chip', $defaults, null, 'Roma e provincia' ),
		'offers'          => $offers,
		'team'            => array(
			'image'   => carbon_get_the_post_meta( 'sg_cmp_team_image' ) ?: tp_theme_asset( 'assets/img/sgrosso/sgrosso-confronto-team-photo.webp' ),
			'eyebrow' => tp_sg_text( 'sg_cmp_team_eyebrow', $defaults, null, 'CHI VERRÀ A CASA TUA' ),
			'title'   => tp_sg_text( 'sg_cmp_team_title', $defaults, null, "Il team dell'impresa scelta" ),
			'sub'     => tp_sg_text( 'sg_cmp_team_sub', $defaults, null, 'Squadra verificata e assicurata' ),
		),
		'note'            => tp_sg_text( 'sg_cmp_note', $defaults, null, 'Il prezzo definitivo è confermato dopo il sopralluogo gratuito. Nessuna sorpresa.' ),
	) );
	?>

	<?php
	// Quando serve (TICKET-44): quattro scenari dal live (SCENARIOS), tab solo sulla landing generica.
	$scenarios = carbon_get_the_post_meta( 'sg_when_scenarios' );
	if ( $scenarios ) {
		$scenarios = array_map( function ( $s ) {
			$s['areas'] = tp_lines( $s['areas'] ?? '' );
			return $s;
		}, $scenarios );
	} else {
		$img       = function ( $k, $s ) { return tp_theme_asset( 'assets/img/sgrosso/sgrosso-scenario-' . $k . '-' . $s . '.webp' ); };
		$scenarios = array(
			array( 'key' => 'post-ristrutturazione', 'label' => 'Post-ristrutturazione', 'title' => 'Post-ristrutturazione', 'text' => 'La ditta rimuove la polvere fine di cantiere e i residui di stucco, vernice e colla da pavimenti, vetri e superfici. La casa passa da cantiere a pronta da vivere.', 'highlight' => 'Pronta da arredare, senza polvere né aloni', 'before' => $img( 'post-ristrutturazione', 'prima' ), 'after' => $img( 'post-ristrutturazione', 'dopo' ), 'areas' => array( 'Vetri e infissi', 'Pavimenti' ) ),
			array( 'key' => 'trasloco', 'label' => 'Trasloco', 'title' => 'Trasloco', 'text' => 'Sgrosso completo sulla casa vuota: interno di armadi e dispense, calcare, pavimenti. Che sia quella che lasci o quella dove entri, la trovi davvero pulita.', 'highlight' => 'Entri in una casa pulita, non "pulita da chi c\'era prima"', 'before' => $img( 'trasloco', 'prima' ), 'after' => $img( 'trasloco', 'dopo' ), 'areas' => array( 'Dietro i mobili', 'Pavimenti' ) ),
			array( 'key' => 'fine-affitto', 'label' => 'Fine affitto', 'title' => 'Fine affitto', 'text' => "La ditta riporta l'appartamento com'era alla consegna: calcare, forno, fughe e segni d'uso. Per riconsegnare senza contestazioni o rimettere in affitto subito.", 'highlight' => 'Riconsegna serena, cauzione salva', 'before' => $img( 'fine-affitto', 'prima' ), 'after' => $img( 'fine-affitto', 'dopo' ), 'areas' => array( 'Cucina', 'Bagni e calcare' ) ),
			array( 'key' => 'prima-pulizia-a-fondo', 'label' => 'Prima pulizia a fondo', 'title' => 'Prima pulizia a fondo', 'text' => 'Quando la pulizia normale non basta più: sporco stratificato, grasso in cucina, calcare vecchio. La ditta riparte da zero, stanza per stanza.', 'highlight' => 'Come il primo giorno, dalla prima passata', 'before' => $img( 'prima-pulizia', 'prima' ), 'after' => $img( 'prima-pulizia', 'dopo' ), 'areas' => array( 'Cucina', 'Soffitti' ) ),
		);
	}
	$all_areas = tp_lines( carbon_get_the_post_meta( 'sg_when_areas' ) );
	if ( ! $all_areas ) {
		$all_areas = array( 'Cucina', 'Bagni e calcare', 'Dietro i mobili', 'Vetri e infissi', 'Soffitti', 'Pavimenti' );
	}
	get_template_part( 'template-parts/scenario-tabs', null, array(
		'eyebrow'     => tp_sg_text( 'sg_when_eyebrow', $defaults, null, 'QUANDO SERVE' ),
		'title'       => tp_sg_text( 'sg_when_title', $defaults, 'quando_serve_h2' ),
		'scenarios'   => $scenarios,
		'all_areas'   => $all_areas,
		'areas_title' => tp_sg_text( 'sg_when_areas_title', $defaults, null, 'Tutte le aree, sempre' ),
		'areas_text'  => tp_sg_text( 'sg_when_areas_text', $defaults, null, 'Lo sgrosso copre tutte le stanze a fondo. Cambia il focus in base alla tua situazione.' ),
		'cta_label'   => tp_sg_text( 'sg_when_cta_label', $defaults, null, 'Calcola il preventivo gratis' ),
		'cta_link'    => tp_sg_text( 'sg_when_cta_link', $defaults, null, '#form' ),
		'default'     => $defaults['scenario'],
		'show_tabs'   => 'sgrosso-casa' === $variant,
	) );
	?>

	<?php
	// Come funziona (TICKET-45): tre card numerate, testi STEPS del live.
	$hiw_steps = carbon_get_the_post_meta( 'sg_hiw_steps' );
	if ( ! $hiw_steps ) {
		$hiw_steps = array(
			array( 'title' => 'Invii la richiesta', 'description' => 'Compili il form in 2 minuti: tipo di casa e contatti. Ti ricontattiamo entro 1 ora.' ),
			array( 'title' => 'Ricevi il preventivo', 'description' => 'Una ditta verificata valuta metratura e stato della casa e ti dà un prezzo fisso. Sopralluogo gratuito solo se serve.' ),
			array( 'title' => 'Fissi la data', 'description' => 'Scegli quando: la ditta verificata fa lo sgrosso. Tu ritrovi la casa pronta da vivere.' ),
		);
	}
	get_template_part( 'template-parts/steps-cards', null, array(
		'eyebrow'  => tp_sg_text( 'sg_hiw_eyebrow', $defaults, null, 'COME FUNZIONA' ),
		'title'    => tp_sg_text( 'sg_hiw_title', $defaults, null, 'Dal sopralluogo alla casa pulita' ),
		'subtitle' => tp_sg_text( 'sg_hiw_subtitle', $defaults, null, 'Tre passaggi, senza sorprese.' ),
		'steps'    => $hiw_steps,
	) );

	// FAQ (TICKET-45): componente della Home, sei domande del live con la prima per variante, card dei fondatori.
	$faq_items = carbon_get_the_post_meta( 'sg_faq_items' );
	if ( ! $faq_items ) {
		$faq_items = array(
			array( 'question' => $defaults['faq_first_q'], 'answer' => $defaults['faq_first_a'] ),
			array( 'question' => 'Quanto dura?', 'answer' => 'In genere da mezza giornata a una giornata, secondo la casa. Te lo diciamo con precisione al sopralluogo.' ),
			array( 'question' => 'Il sopralluogo è davvero gratuito?', 'answer' => 'Sì, gratuito e senza impegno. Se il preventivo non ti convince, non paghi nulla.' ),
			array( 'question' => 'Cosa devo preparare?', 'answer' => 'Nulla: prodotti e attrezzatura professionale sono inclusi nel servizio.' ),
			array( 'question' => 'Le ditte sono affidabili?', 'answer' => 'Sì. Ogni ditta passa una verifica di identità e documenti prima di poter lavorare.' ),
			array( 'question' => 'In che zone operate?', 'answer' => 'Roma città e provincia. Indica la tua zona nel form e ti confermiamo la disponibilità.' ),
		);
	}
	get_template_part( 'template-parts/faq-accordion', null, array(
		'eyebrow'  => tp_sg_text( 'sg_faq_eyebrow', $defaults, null, 'DOMANDE FREQUENTI' ),
		'title'    => tp_sg_text( 'sg_faq_title', $defaults, null, 'Prima di prenotare il sopralluogo' ),
		'subtitle' => tp_sg_text( 'sg_faq_subtitle', $defaults, null, 'Le risposte rapide. Per tutto il resto, il sopralluogo è gratuito e senza impegno.' ),
		'items'    => $faq_items,
		'contact'  => array(
			'people'    => tp_sg_text( 'sg_faq_contact_people', $defaults, null, 'Christian & Guido' ),
			'role'      => tp_sg_text( 'sg_faq_contact_role', $defaults, null, 'Fondatori' ),
			'avatar_1'  => carbon_get_the_post_meta( 'sg_faq_contact_avatar_1' ) ?: tp_theme_asset( 'assets/img/founder-christian.png' ),
			'avatar_2'  => carbon_get_the_post_meta( 'sg_faq_contact_avatar_2' ) ?: tp_theme_asset( 'assets/img/founder-guido.png' ),
			'title'     => tp_sg_text( 'sg_faq_contact_title', $defaults, null, 'Rispondiamo di persona' ),
			'text'      => tp_sg_text( 'sg_faq_contact_text', $defaults, null, 'Scrivici: ti risponde Guido o Christian, in base alla disponibilità. I fondatori, non un call center.' ),
			'cta_label' => tp_sg_text( 'sg_faq_contact_cta_label', $defaults, null, 'Prenota la stima gratis' ),
			'cta_link'  => tp_sg_text( 'sg_faq_contact_cta_link', $defaults, null, '#form-bottom' ),
		),
	) );

	// CTA finale con la seconda istanza del form (TICKET-45): cta_position 'bottom', id #form-bottom.
	$cta_bullets = tp_lines( carbon_get_the_post_meta( 'sg_cta_bullets' ) );
	if ( ! $cta_bullets ) {
		$cta_bullets = array( 'Sopralluogo gratuito e senza impegno', 'Prezzo fisso confermato dopo la visita', 'Ti ricontattiamo entro 1 ora', 'Ditte verificate e assicurate' );
	}
	get_template_part( 'template-parts/cta-form', null, array(
		'eyebrow'  => tp_sg_text( 'sg_cta_eyebrow', $defaults, null, 'ULTIMO PASSO' ),
		'title'    => tp_sg_text( 'sg_cta_title', $defaults, 'cta_final_h2' ),
		'subtitle' => tp_sg_text( 'sg_cta_subtitle', $defaults, null, 'Un sopralluogo gratuito, un prezzo fisso, una ditta verificata. Al resto pensa lei.' ),
		'bullets'  => $cta_bullets,
		'image'    => carbon_get_the_post_meta( 'sg_cta_image' ) ?: tp_theme_asset( 'assets/img/sgrosso/sgrosso-polpo-mascotte.webp' ),
		'form'     => array(
			'id'           => 'lead-bottom',
			'cta_position' => 'bottom',
			'landing'      => $landing,
			'default_tipo' => $defaults['scenario'],
			'title'        => tp_sg_text( 'sg_form_title', $defaults, null, 'I tuoi dati di contatto' ),
			'subtitle'     => tp_sg_text( 'sg_form_subtitle', $defaults, null, "Ti risponderemo entro un'ora con una prima valutazione." ),
			'cta_label'    => tp_sg_text( 'sg_form_cta', $defaults, null, 'Prenota sopralluogo gratuito' ),
			'note'         => tp_sg_text( 'sg_form_note', $defaults, null, 'Gratis · nessun impegno · risposta in 1h' ),
		),
	) );
	?>

	<?php get_template_part( 'template-parts/sticky-cta', null, array(
		'variant'     => 'dual',
		'label'       => 'Prenota sopralluogo', // Figma 4364:7540: accanto a Chiama la CTA lunga non entra a 390

		'target'      => '#form',
		'watch'       => '#form',
		'phone_href'  => $phone_href,
		'phone_label' => 'Chiama',
	) ); ?>

</main>
<?php
get_footer();
