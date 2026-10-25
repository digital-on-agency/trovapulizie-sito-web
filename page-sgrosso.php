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

	<?php get_template_part( 'template-parts/sticky-cta', null, array(
		'variant'     => 'dual',
		'label'       => 'Calcola il preventivo gratis',
		'target'      => '#form',
		'watch'       => '#form',
		'phone_href'  => $phone_href,
		'phone_label' => 'Chiama',
	) ); ?>

</main>
<?php
get_footer();
