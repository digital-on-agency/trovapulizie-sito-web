<?php
/**
 * "Cosa serve / Cosa non serve" — pagina Diventa cleaner (Figma 6752:895; TICKET-56). Due card affiancate da 588px:
 * bianca con spunte, menta con crocette. Sotto 1024px impilate. Ancora #requisiti (link del footer globale).
 * Args: eyebrow, title, id, yes_title, yes (array di [title, text]), no_title, no (array di [title, text]).
 * Le liste arrivano da tp_pipe_lines() ("Titolo | testo" per riga). Senza voci in entrambe la sezione non esce.
 */
$args = wp_parse_args( $args ?? array(), array(
	'eyebrow'   => '',
	'title'     => '',
	'id'        => 'requisiti',
	'yes_title' => '',
	'yes'       => array(),
	'no_title'  => '',
	'no'        => array(),
) );
if ( ! $args['yes'] && ! $args['no'] ) {
	return;
}
$lists = array(
	array( 'title' => $args['yes_title'], 'items' => $args['yes'], 'ok' => true ),
	array( 'title' => $args['no_title'], 'items' => $args['no'], 'ok' => false ),
);
?>
<section class="dc-req" id="<?php echo esc_attr( $args['id'] ); ?>" data-reveal>
	<div class="dc-req__inner">
		<div class="sg-section-head">
			<?php if ( $args['eyebrow'] ) : ?><p class="sg-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p><?php endif; ?>
			<?php if ( $args['title'] ) : ?><h2><?php echo esc_html( $args['title'] ); ?></h2><?php endif; ?>
		</div>
		<div class="dc-req__cards">
			<?php foreach ( $lists as $l ) : if ( ! $l['items'] ) { continue; } ?>
				<div class="dc-req__card<?php echo $l['ok'] ? '' : ' dc-req__card--no'; ?>">
					<?php if ( $l['title'] ) : ?><h3 class="dc-req__title"><?php echo esc_html( $l['title'] ); ?></h3><?php endif; ?>
					<ul class="dc-req__list">
						<?php foreach ( $l['items'] as $it ) : ?>
							<li>
								<span class="<?php echo $l['ok'] ? 'dc-check' : 'dc-cross'; ?>" aria-hidden="true"><?php echo $l['ok'] ? tp_icon_span( 'assets/icons/ic-check.svg', 'dc-check__icon' ) : '&times;'; ?></span>
								<span><strong><?php echo esc_html( $it['title'] ); ?></strong><?php if ( ! empty( $it['text'] ) ) : ?><span class="dc-req__text"><?php echo esc_html( $it['text'] ); ?></span><?php endif; ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>
