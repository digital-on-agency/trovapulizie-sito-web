<?php
/**
 * Template Name: Guida prezzi
 *
 * Guida ai prezzi delle pulizie straordinarie a Roma: /roma/prezzi-pulizie/ (figlia di /roma/).
 * PRD e ticket nel vault: clients/trovapulizie/projects/sito-web/spec/SPEC-crescita-organica-fase-b.md (US-01) e
 * spec/tickets/TICKET-96-guida-prezzi.md.
 *
 * Una pagina sola per tutti i servizi sgrosso (decisione Guido 16/11). Le cifre della tabella non sono scritte
 * in nessun punto: vengono da tp_guida_prezzi_righe() (inc/prezzi.php), la formula unica di
 * assets/js/sgrosso-estimator.js lato server. Calcolatore e form lead sono i componenti delle landing Sgrosso
 * (template-parts/sgrosso-estimator.php, lead-form.php), non duplicati; il form porta landing 'prezzi-pulizie' negli
 * eventi dataLayer e il tipo di intervento preselezionato di sgrosso-casa.
 *
 * I testi stanno qui, senza campi Carbon: sono approvati da Guido e cambiano con un deploy. Solo fatti gia'
 * pubblicati sulle landing (sopralluogo gratuito, prezzo fisso concordato prima di iniziare, prodotti inclusi):
 * nessun numero di clienti, ditte o recensioni, nessuna promessa di tempi.
 *
 * [ATTENZIONE] Il file NON si chiama page-prezzi-pulizie.php di proposito: con il nome dello slug WordPress lo
 * applicherebbe da solo a /roma/prezzi-pulizie/ lasciando _wp_page_template vuoto, e is_page_template() (script,
 * title, JSON-LD) non partirebbe in silenzio. Il template va assegnato a mano (post meta _wp_page_template =
 * page-guida-prezzi.php): vedi il comando WP-CLI nel pacchetto di revisione S3.
 */
require_once __DIR__ . '/inc/sgrosso-variants.php';

$landing  = 'prezzi-pulizie'; // identificatore negli eventi dataLayer
$defaults = tp_sg_defaults( 'sgrosso-casa' );
$tipo     = $defaults['scenario']; // tipo di intervento preselezionato nel form: quello di sgrosso casa

$phone_label = '06 9151 6075';
$phone_href  = 'tel:' . preg_replace( '/\D+/', '', $phone_label );

$hero_base = 'assets/img/sgrosso/' . $defaults['hero_image'];

get_header( 'landing', array(
	'promo_desktop' => 'Sopralluogo gratuito · senza impegno · prezzo concordato prima di iniziare',
	'promo_mobile'  => 'Sopralluogo gratuito · senza impegno',
	'links'         => array(
		'#tabella-prezzi' => 'Prezzi',
		'#fattori-prezzo' => 'Cosa cambia il prezzo',
		'#faq'            => 'Domande',
	),
	'phone_label'   => $phone_label,
	'phone_href'    => $phone_href,
	'cta_label'     => 'Calcola la stima',
	'cta_short'     => 'Sopralluogo gratuito',
	'cta_href'      => '#form',
	'cta_full_href' => '#quanto-costa',
	'cta_focus'     => true,
) );

$form_args = array(
	'landing'      => $landing,
	'default_tipo' => $tipo,
	'title'        => 'La tua casa',
	'subtitle'     => 'Tre informazioni e vedi subito una stima indicativa.',
	'step1_label'  => 'Vedi la stima',
	'cta_label'    => 'Prenota il sopralluogo gratuito',
	'note'         => 'Gratis · nessun impegno',
	'phone_label'  => $phone_label,
);
?>
<main class="sgrosso pg" id="contenuto" data-landing="<?php echo esc_attr( $landing ); ?>">

	<?php // Senza JavaScript i componenti con ingresso a cascata (fattori, FAQ) restano a opacita' 0 e le risposte chiuse: qui si mostrano interi (SPEC US-01). ?>
	<noscript><style>.pg .b2b-why__card,.pg .faq__aside,.pg .faq__item{opacity:1;transform:none}.pg .faq__a{grid-template-rows:1fr}.pg .faq__a-inner{opacity:1}.pg .faq__toggle{display:none}</style></noscript>

	<section class="sg-hero" id="hero">
		<div class="sg-hero__panel">
			<div class="sg-hero__media" aria-hidden="true">
				<picture>
					<source srcset="<?php echo esc_url( tp_theme_asset( $hero_base . '.webp' ) ); ?>" type="image/webp">
					<img class="sg-hero__img" src="<?php echo esc_url( tp_theme_asset( $hero_base . '.jpg' ) ); ?>" alt="" width="1920" height="1071" fetchpriority="high" decoding="async">
				</picture>
				<div class="sg-hero__scrim"></div>
			</div>

			<div class="sg-hero__inner">
				<div class="sg-hero__copy">
					<p class="sg-hero__eyebrow">PREZZI · PULIZIE STRAORDINARIE · ROMA</p>
					<h1>Prezzo delle pulizie straordinarie a Roma</h1>
					<p class="sg-hero__subtitle">Quanto costano sgrosso casa, pulizia post ristrutturazione, trasloco e fine affitto: fasce di prezzo per metratura e cosa le fa cambiare.</p>
					<ul class="sg-hero__bullets">
						<li><?php echo tp_icon_span( 'assets/icons/ic-check.svg', 'sg-hero__check' ); ?><span>Sopralluogo e preventivo gratuiti, senza impegno</span></li>
						<li><?php echo tp_icon_span( 'assets/icons/ic-check.svg', 'sg-hero__check' ); ?><span>Prezzo fisso concordato prima di iniziare</span></li>
						<li><?php echo tp_icon_span( 'assets/icons/ic-check.svg', 'sg-hero__check' ); ?><span>Prodotti professionali inclusi</span></li>
					</ul>
				</div>

				<div class="sg-hero__form" id="form">
					<?php get_template_part( 'template-parts/lead-form', null, array_merge( $form_args, array( 'id' => 'lead-hero', 'cta_position' => 'hero' ) ) ); ?>
				</div>
			</div>
		</div>
	</section>

	<section class="pg-intro" id="introduzione" data-reveal>
		<div class="pg-intro__inner">
			<h2 class="pg-intro__title">Da cosa dipende il prezzo di una pulizia straordinaria</h2>
			<p>Il prezzo di una pulizia straordinaria, quello che chiamiamo sgrosso, dipende dai metri quadri della casa e dalle condizioni in cui si trova. Più la casa è grande e più lo sporco è accumulato, più lavoro serve.</p>
			<p>Qui sotto trovi le fasce indicative per <?php echo esc_html( tp_guida_prezzi_metrature_testo() ); ?> m², calcolate con lo stesso calcolo del nostro calcolatore, e gli elementi che spostano la cifra. Sono stime: la cifra definitiva la concordiamo con te dopo il sopralluogo gratuito, prima di iniziare il lavoro.</p>
		</div>
	</section>

	<?php
	get_template_part( 'template-parts/price-guide-table', null, array(
		'eyebrow'   => 'FASCE DI PREZZO',
		'title'     => 'Quanto costa, per metratura',
		'subtitle'  => 'Fasce indicative in euro per una casa senza extra. Ogni servizio rimanda alla sua pagina.',
		'note_same' => 'La fascia dipende da metri quadri e condizioni della casa, non dal tipo di servizio: per questo le cifre sono uguali in ogni riga. Cambia il lavoro da fare, che valutiamo al sopralluogo.',
		'note'      => 'Stima indicativa. Il prezzo definitivo è confermato dopo il sopralluogo gratuito, senza sorprese.',
	) );

	// Cosa fa salire o scendere il prezzo: nessuna cifra, solo gli elementi che la formula e il sopralluogo considerano.
	get_template_part( 'template-parts/value-props', null, array(
		'id'       => 'fattori-prezzo',
		'eyebrow'  => 'COSA CAMBIA IL PREZZO',
		'title'    => 'Cosa fa salire o scendere il prezzo',
		'subtitle' => 'Gli stessi elementi che alzano la cifra, se non ci sono, la tengono più bassa.',
		'items'    => array(
			array( 'icon' => 'assets/icons/ic-pin.svg', 'title' => 'Metri quadri', 'text' => 'Più la casa è grande, più sale la fascia di prezzo. È il dato che ci serve per primo.' ),
			array( 'icon' => 'assets/icons/ic-spray.svg', 'title' => 'Condizioni e sporco', 'text' => 'Sporco stratificato, calcare vecchio, polvere e residui di cantiere richiedono più lavoro di una casa tenuta in buone condizioni.' ),
			array( 'icon' => 'assets/icons/ic-servizio.svg', 'title' => 'Bagni e cucina', 'text' => 'Il numero di bagni e lo stato della cucina, con grasso e calcare, cambiano il tempo necessario: li verifichiamo al sopralluogo.' ),
			array( 'icon' => 'assets/icons/ic-check.svg', 'title' => 'Extra', 'text' => 'Forno e cappa, vetri e infissi, balconi e terrazzi, sanificazione a vapore: ogni extra aggiunto sposta la fascia verso l’alto.' ),
		),
	) );

	// Calcolatore: stesso componente e stessa formula delle landing. Senza coupon: la guida non promette nulla di nuovo.
	$coef = tp_sgrosso_coef();
	get_template_part( 'template-parts/sgrosso-estimator', null, array(
		'eyebrow'       => 'CALCOLA LA STIMA',
		'title'         => 'Calcola la stima sul tuo caso',
		'subtitle'      => 'Scegli i metri quadri e gli extra: la fascia si aggiorna subito. Senza registrarti.',
		'types'         => array( 'Post-ristrutturazione', 'Trasloco', 'Fine affitto', 'Prima pulizia a fondo' ),
		'default_type'  => 'Post-ristrutturazione',
		'extras'        => array( 'Forno e cappa', 'Vetri e infissi', 'Balconi e terrazzi', 'Sanificazione a vapore' ),
		'mq_default'    => 100,
		'coef'          => $coef,
		'result_label'  => 'STIMA INDICATIVA',
		'cta_title'     => 'Ricevi il preventivo esatto',
		'coupon_amount' => '',
		'cta_label'     => 'Ricevi il preventivo',
		'note'          => 'Prezzo indicativo. Quello fisso, senza sorprese, lo confermiamo dopo il sopralluogo gratuito.',
		'landing'       => $landing,
	) );

	// FAQ: solo fatti gia' pubblicati sulle landing. Il FAQPage lo emette il componente.
	get_template_part( 'template-parts/faq-accordion', null, array(
		'eyebrow'  => 'DOMANDE FREQUENTI',
		'title'    => 'Le domande sul prezzo',
		'subtitle' => 'Le risposte rapide. Per la cifra sul tuo caso il sopralluogo è gratuito e senza impegno.',
		'items'    => array(
			array( 'question' => 'Quanto costa una pulizia straordinaria a Roma?', 'answer' => 'Dipende dai metri quadri e dalle condizioni della casa. Nella tabella trovi le fasce indicative per ' . tp_guida_prezzi_metrature_testo() . ' m²; la cifra definitiva la concordiamo con te dopo il sopralluogo gratuito.' ),
			array( 'question' => 'Il prezzo in tabella è quello che pagherò?', 'answer' => 'No, è una stima indicativa. Il prezzo fisso lo concordiamo prima di iniziare, dopo il sopralluogo gratuito e senza impegno.' ),
			array( 'question' => 'Il sopralluogo è davvero gratuito?', 'answer' => 'Sì, gratuito e senza impegno. Se il preventivo non ti convince, non paghi nulla.' ),
			array( 'question' => 'Cosa fa cambiare il prezzo di uno sgrosso?', 'answer' => 'I metri quadri, lo stato della casa (sporco, calcare, residui di cantiere), il numero di bagni e gli extra che scegli, come forno e cappa, vetri e infissi, balconi e terrazzi, sanificazione a vapore.' ),
			array( 'question' => 'Il prezzo cambia tra post ristrutturazione, trasloco e fine affitto?', 'answer' => 'Nella nostra stima la fascia dipende da metri quadri e condizioni della casa, non dal tipo di servizio. Cambia invece il lavoro da fare, per esempio la polvere di cantiere dopo i lavori o il calcare e il forno a fine affitto, e lo valutiamo al sopralluogo.' ),
			array( 'question' => 'Cosa è incluso nel servizio?', 'answer' => 'Prodotti e attrezzatura professionale sono inclusi nel servizio: non devi preparare nulla.' ),
		),
	) );

	// CTA finale con la seconda istanza del form (#form-bottom, cta_position 'bottom').
	get_template_part( 'template-parts/cta-form', null, array(
		'eyebrow'  => 'ULTIMO PASSO',
		'title'    => 'Vuoi il prezzo sul tuo caso?',
		'subtitle' => 'Un sopralluogo gratuito, un prezzo fisso, una ditta verificata. Al resto pensa lei.',
		'bullets'  => array( 'Sopralluogo gratuito e senza impegno', 'Prezzo fisso confermato dopo la visita', 'Ditte verificate e assicurate' ),
		'image'    => tp_theme_asset( 'assets/img/sgrosso/sgrosso-polpo-mascotte.webp' ),
		'form'     => array_merge( $form_args, array( 'id' => 'lead-bottom', 'cta_position' => 'bottom' ) ),
	) );
	?>

	<?php get_template_part( 'template-parts/related-services' ); ?>

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
