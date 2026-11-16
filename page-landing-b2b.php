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
 * Legge la scheda "Landing B2B — Sezioni" (prefisso b2b_): ogni testo ha il default della variante (inc/b2b-variants.php,
 * campo b2b_variant) e il campo, se compilato, vince (tp_b2b()). Form a un passo (template-parts/lead-form-b2b.php):
 * una sola POST /api/leads tipo b2b, conferma inline, nessuna thank-you.
 *
 * Tre varianti (TICKET-97): sgrosso-commerciale (di partenza, /roma/sgrosso-commerciale/), pulizie-uffici
 * (/roma/pulizie-uffici/) e pulizie-condomini (/roma/pulizie-condomini/). Le due pagine di settore non hanno
 * recensioni, lavori prima/dopo ne' riga con le stelle e non promettono tempi o frequenze (SPEC-crescita-organica-fase-b §2).
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

$V       = tp_b2b_defaults();
$landing = $V['landing']; // identificatore negli eventi dataLayer (per sgrosso-commerciale uguale alla SPA)

$phone_label = tp_b2b( 'b2b_nav_phone', '06 9151 6075' );
$phone_href  = 'tel:' . preg_replace( '/\D+/', '', $phone_label );

$hero_image_field = carbon_get_the_post_meta( 'b2b_hero_image' );
$hero_img         = $V['hero']['image'];
$hero_bullets     = tp_lines( tp_b2b( 'b2b_hero_bullets', '' ) );
if ( ! $hero_bullets ) {
	$hero_bullets = $V['hero']['bullets'];
}
$activities = tp_lines( tp_b2b( 'b2b_form_activities', '' ) );
if ( ! $activities ) {
	$activities = $V['form']['activities'];
}
$form_args = array_merge( array(
	'landing'     => $landing,
	'title'       => tp_b2b( 'b2b_form_title', $V['form']['title'] ),
	'subtitle'    => tp_b2b( 'b2b_form_subtitle', $V['form']['subtitle'] ),
	'cta_label'   => tp_b2b( 'b2b_form_cta', $V['form']['cta'] ),
	'note'        => tp_b2b( 'b2b_form_note', $V['form']['note'] ),
	'activities'  => $activities,
	'phone_label' => $phone_label,
	'phone_href'  => $phone_href,
), $V['form']['extra'] );

get_header( 'landing', array(
	'promo_desktop' => tp_b2b( 'b2b_promo_desktop', $V['promo_desktop'] ),
	'promo_mobile'  => tp_b2b( 'b2b_promo_mobile', $V['promo_mobile'] ),
	'links'         => $V['nav_links'],
	'phone_label'   => $phone_label,
	'phone_href'    => $phone_href,
	'cta_label'     => tp_b2b( 'b2b_nav_cta', $V['nav_cta'] ),
	'cta_short'     => 'Preventivo gratuito',
	'cta_href'      => '#form',
) );
?>
<main class="sgrosso b2b" id="contenuto" data-landing="<?php echo esc_attr( $landing ); ?>">

	<section class="sg-hero" id="hero">
		<div class="sg-hero__panel">
			<div class="sg-hero__media" aria-hidden="true">
				<?php if ( $hero_image_field ) : ?>
					<img class="sg-hero__img" src="<?php echo esc_url( $hero_image_field ); ?>" alt="<?php echo esc_attr( tp_img_alt( $hero_image_field, $hero_img['alt'] ) ); ?>" width="1920" height="1071" fetchpriority="high" decoding="async">
				<?php elseif ( $hero_img['mobile'] ) : ?>
					<picture>
						<source media="(max-width: 767px)" srcset="<?php echo esc_url( $hero_img['mobile'] ); ?>" type="image/webp">
						<img class="sg-hero__img" src="<?php echo esc_url( $hero_img['src'] ); ?>" alt="<?php echo esc_attr( $hero_img['alt'] ); ?>" width="<?php echo (int) $hero_img['width']; ?>" height="<?php echo (int) $hero_img['height']; ?>" fetchpriority="high" decoding="async">
					</picture>
				<?php else : ?>
					<img class="sg-hero__img" src="<?php echo esc_url( $hero_img['src'] ); ?>" alt="<?php echo esc_attr( $hero_img['alt'] ); ?>" width="<?php echo (int) $hero_img['width']; ?>" height="<?php echo (int) $hero_img['height']; ?>" fetchpriority="high" decoding="async">
				<?php endif; ?>
				<div class="sg-hero__scrim"></div>
			</div>

			<div class="sg-hero__inner">
				<div class="sg-hero__copy">
					<p class="sg-hero__eyebrow"><?php echo esc_html( tp_b2b( 'b2b_hero_eyebrow', $V['hero']['eyebrow'] ) ); ?></p>
					<h1><?php echo nl2br( esc_html( tp_b2b( 'b2b_hero_title', $V['hero']['title'] ) ) ); ?></h1>
					<p class="sg-hero__subtitle"><?php echo nl2br( esc_html( tp_b2b( 'b2b_hero_subtitle', $V['hero']['subtitle'] ) ) ); ?></p>
					<ul class="sg-hero__bullets">
						<?php foreach ( $hero_bullets as $riga ) : ?>
							<li><?php echo tp_icon_span( 'assets/icons/ic-check.svg', 'sg-hero__check' ); ?><span><?php echo esc_html( $riga ); ?></span></li>
						<?php endforeach; ?>
					</ul>
					<?php $social = tp_b2b( 'b2b_hero_social', $V['hero']['social'] ); ?>
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
	// Trust strip: le promesse delle pagine, senza claim aperti (SPEC §6 del ridisegno, SPEC-crescita-organica-fase-b §2).
	$trust_items = tp_lines( tp_b2b( 'b2b_trust_items', '' ) );
	if ( ! $trust_items ) {
		$trust_items = $V['trust'];
	}
	get_template_part( 'template-parts/trust-bar', null, array( 'items' => $trust_items ) );

	// Settori (TICKET-59): componente "Quando serve" dello Sgrosso con foto singola e chip per settore.
	if ( $V['show']['sectors'] ) {
		$sectors = tp_b2b( 'b2b_sectors', array() );
		if ( $sectors ) {
			$sectors = array_map( function ( $s ) {
				$s['chips'] = tp_lines( $s['chips'] ?? '' );
				return $s;
			}, $sectors );
		} else {
			$sectors = $V['sectors']['items'];
		}
		get_template_part( 'template-parts/scenario-tabs', null, array(
			'eyebrow'      => tp_b2b( 'b2b_sectors_eyebrow', $V['sectors']['eyebrow'] ),
			'title'        => tp_b2b( 'b2b_sectors_title', $V['sectors']['title'] ),
			'scenarios'    => $sectors,
			'areas_title'  => tp_b2b( 'b2b_sectors_areas_title', $V['sectors']['areas_title'] ),
			'areas_text'   => tp_b2b( 'b2b_sectors_areas_text', $V['sectors']['areas_text'] ),
			'cta_label'    => tp_b2b( 'b2b_sectors_cta', $V['sectors']['cta'] ),
			'cta_link'     => '#form',
			'cta_position' => 'settori',
			'default'      => $sectors[0]['key'],
			'show_tabs'    => true,
			'id'           => 'settori',
		) );
	}

	// Servizi del settore (TICKET-97): elenco dalla SPEC US-02, riuso del componente "Perche' noi" in griglia 2 colonne.
	if ( $V['show']['services'] ) {
		$services = tp_b2b( 'b2b_services', array() );
		if ( $services ) {
			$services = array_map( function ( $v ) {
				$v['icon'] = 'assets/icons/ic-check.svg';
				return $v;
			}, $services );
		} else {
			$services = $V['services']['items'];
		}
		get_template_part( 'template-parts/value-props', null, array(
			'eyebrow'  => tp_b2b( 'b2b_services_eyebrow', $V['services']['eyebrow'] ),
			'title'    => tp_b2b( 'b2b_services_title', $V['services']['title'] ),
			'subtitle' => tp_b2b( 'b2b_services_subtitle', $V['services']['subtitle'] ),
			'id'       => 'servizi',
			'class'    => 'b2b-why--services',
			'items'    => $services,
		) );
	}

	// Lavori a Roma (TICKET-59): prima/dopo trascinabile delle vetrate e galleria, foto reali del live.
	if ( $V['show']['works'] ) {
		$W       = $V['works'];
		$gallery = tp_b2b( 'b2b_works_gallery', array() );
		if ( ! $gallery ) {
			$gallery = $W['gallery'];
		}
		get_template_part( 'template-parts/works-gallery', null, array(
			'eyebrow'  => tp_b2b( 'b2b_works_eyebrow', $W['eyebrow'] ),
			'title'    => tp_b2b( 'b2b_works_title', $W['title'] ),
			'subtitle' => tp_b2b( 'b2b_works_subtitle', $W['subtitle'] ),
			'compare'  => array(
				'before'         => carbon_get_the_post_meta( 'b2b_works_before' ) ?: $W['compare']['before'],
				'after'          => carbon_get_the_post_meta( 'b2b_works_after' ) ?: $W['compare']['after'],
				'before_alt'     => $W['compare']['before_alt'],
				'after_alt'      => $W['compare']['after_alt'],
				'title'          => tp_b2b( 'b2b_works_compare_title', $W['compare']['title'] ),
				'text'           => tp_b2b( 'b2b_works_compare_text', $W['compare']['text'] ),
				'before_caption' => tp_b2b( 'b2b_works_before_caption', $W['compare']['before_caption'] ),
				'after_caption'  => tp_b2b( 'b2b_works_after_caption', $W['compare']['after_caption'] ),
			),
			'gallery'  => $gallery,
		) );
	}

	// Una volta sola o tutto l'anno (TICKET-60): componente "Per chi e'" della pagina Diventa cleaner.
	if ( $V['show']['plans'] ) {
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
			$plans = $V['plans']['items'];
		}
		get_template_part( 'template-parts/audience-cards', null, array(
			'eyebrow'  => tp_b2b( 'b2b_plans_eyebrow', $V['plans']['eyebrow'] ),
			'title'    => tp_b2b( 'b2b_plans_title', $V['plans']['title'] ),
			'subtitle' => tp_b2b( 'b2b_plans_subtitle', $V['plans']['subtitle'] ),
			'id'       => 'formule',
			'cards'    => $plans,
		) );
	}

	// Perche' noi (TICKET-60).
	if ( $V['show']['why'] ) {
		$why_icons = array( 'user-check' => 'assets/icons/ic-user-check.svg', 'clock' => 'assets/icons/ic-clock.svg', 'shield-check' => 'assets/icons/ic-shield-check.svg', 'spray' => 'assets/icons/ic-spray.svg', 'check' => 'assets/icons/ic-check.svg' );
		$why = tp_b2b( 'b2b_why_items', array() );
		if ( $why ) {
			$why = array_map( function ( $v ) use ( $why_icons ) {
				$v['icon'] = $why_icons[ $v['icon'] ?? '' ] ?? $why_icons['check'];
				return $v;
			}, $why );
		} else {
			$why = $V['why']['items'];
		}
		get_template_part( 'template-parts/value-props', null, array(
			'eyebrow' => tp_b2b( 'b2b_why_eyebrow', $V['why']['eyebrow'] ),
			'title'   => tp_b2b( 'b2b_why_title', $V['why']['title'] ),
			'items'   => $why,
		) );
	}

	// Come funziona (TICKET-61): tre card numerate.
	$hiw_steps = tp_b2b( 'b2b_hiw_steps', array() );
	if ( ! $hiw_steps ) {
		$hiw_steps = $V['hiw']['steps'];
	}
	get_template_part( 'template-parts/steps-cards', null, array(
		'eyebrow'  => tp_b2b( 'b2b_hiw_eyebrow', $V['hiw']['eyebrow'] ),
		'title'    => tp_b2b( 'b2b_hiw_title', $V['hiw']['title'] ),
		'subtitle' => tp_b2b( 'b2b_hiw_subtitle', $V['hiw']['subtitle'] ),
		'steps'    => $hiw_steps,
	) );

	// Link alle altre pagine B2B (TICKET-97): card del componente "Per chi e'", destinazioni e testi in inc/b2b-variants.php.
	if ( $V['show']['cross'] ) {
		$cross = array_map( function ( $c ) {
			$c['cta_link'] = home_url( $c['cta_link'] );
			return $c;
		}, $V['cross']['items'] );
		get_template_part( 'template-parts/audience-cards', null, array(
			'eyebrow'  => $V['cross']['eyebrow'],
			'title'    => $V['cross']['title'],
			'subtitle' => $V['cross']['subtitle'],
			'id'       => 'altri-spazi',
			'cards'    => $cross,
		) );
	}

	// Recensioni (TICKET-61): solo sulla pagina di partenza, dichiarate fittizie nel sorgente del live (SPEC §6).
	if ( $V['show']['reviews'] ) {
		$reviews = tp_b2b( 'b2b_reviews', array() );
		if ( ! $reviews ) {
			$reviews = $V['reviews']['items'];
		}
		get_template_part( 'template-parts/reviews-marquee', null, array(
			'eyebrow'     => tp_b2b( 'b2b_reviews_eyebrow', $V['reviews']['eyebrow'] ),
			'title'       => tp_b2b( 'b2b_reviews_title', $V['reviews']['title'] ),
			'reviews'     => $reviews,
			'layout'      => 'grid',
			'card'        => 'stars',
			'avatar_mode' => 'initials',
		) );
	}

	// FAQ (TICKET-61): card dei fondatori con le foto della Home.
	$F         = $V['faq'];
	$faq_items = tp_b2b( 'b2b_faq_items', array() );
	if ( ! $faq_items ) {
		$faq_items = $F['items'];
	}
	$founders = tp_founders_contact();
	get_template_part( 'template-parts/faq-accordion', null, array(
		'eyebrow'  => tp_b2b( 'b2b_faq_eyebrow', $F['eyebrow'] ),
		'title'    => tp_b2b( 'b2b_faq_title', $F['title'] ),
		'subtitle' => tp_b2b( 'b2b_faq_subtitle', $F['subtitle'] ),
		'items'    => $faq_items,
		'contact'  => array(
			'people'    => $founders['people'],
			'role'      => $founders['role'],
			'avatar_1'  => $founders['avatar_1'],
			'avatar_2'  => $founders['avatar_2'],
			'title'     => tp_b2b( 'b2b_faq_contact_title', $F['contact_title'] ),
			'text'      => tp_b2b( 'b2b_faq_contact_text', $F['contact_text'] ),
			'cta_label' => tp_b2b( 'b2b_faq_contact_cta', $F['contact_cta'] ),
			'cta_link'  => '#form-bottom',
		),
	) );

	// CTA finale con la seconda istanza del form (TICKET-61): cta_position 'bottom', id #form-bottom, senza mascotte.
	$cta_bullets = tp_lines( tp_b2b( 'b2b_cta_bullets', '' ) );
	if ( ! $cta_bullets ) {
		$cta_bullets = $V['cta']['bullets'];
	}
	get_template_part( 'template-parts/cta-form', null, array(
		'eyebrow'   => tp_b2b( 'b2b_cta_eyebrow', $V['cta']['eyebrow'] ),
		'title'     => tp_b2b( 'b2b_cta_title', $V['cta']['title'] ),
		'subtitle'  => tp_b2b( 'b2b_cta_subtitle', $V['cta']['subtitle'] ),
		'bullets'   => $cta_bullets,
		'image'     => '',
		'form_part' => 'template-parts/lead-form-b2b',
		'form'      => array_merge( $form_args, array( 'id' => 'lead-bottom', 'cta_position' => 'bottom' ) ),
	) );
	?>

	<?php get_template_part( 'template-parts/related-services' ); ?>

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
