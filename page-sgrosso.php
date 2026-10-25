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
