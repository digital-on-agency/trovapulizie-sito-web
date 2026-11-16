<?php
/**
 * Tabella delle fasce di prezzo della guida prezzi (TICKET-96, SPEC-crescita-organica-fase-b US-01).
 * Una riga per servizio sgrosso (link alla landing), una colonna per metratura. Le cifre arrivano da
 * tp_guida_prezzi_righe() (inc/prezzi.php): la formula unica di assets/js/sgrosso-estimator.js lato server,
 * senza nessun numero scritto qui o nei campi. Ogni cella porta data-mq / data-min / data-max, letti da
 * tools/qa-guida-prezzi.js per confrontarli con window.tpStimaSgrosso nel browser. Senza JavaScript la tabella
 * e' gia' completa: non c'e' nulla che la riempia dopo.
 * Se la formula smette di essere uguale per tutti i servizi la nota "uguali in ogni riga" sparisce da sola.
 *
 * Args: eyebrow, title, subtitle, note_same (testo mostrato se le righe sono uguali), note (sempre),
 * righe (default tp_guida_prezzi_righe()), metrature (default tp_guida_prezzi_metrature()), id.
 */
$args = wp_parse_args( $args ?? array(), array(
	'id'        => 'tabella-prezzi',
	'eyebrow'   => '',
	'title'     => '',
	'subtitle'  => '',
	'note_same' => '',
	'note'      => '',
	'righe'     => null,
	'metrature' => null,
) );
$righe     = null === $args['righe'] ? tp_guida_prezzi_righe() : (array) $args['righe'];
$metrature = null === $args['metrature'] ? tp_guida_prezzi_metrature() : (array) $args['metrature'];
if ( ! $righe || ! $metrature ) {
	return;
}
$uguali = tp_guida_prezzi_uguali( $righe );
?>
<section class="pg-table" id="<?php echo esc_attr( $args['id'] ); ?>" data-reveal>
	<div class="pg-table__inner">
		<div class="sg-section-head">
			<?php if ( $args['eyebrow'] ) : ?><p class="sg-eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p><?php endif; ?>
			<?php if ( $args['title'] ) : ?><h2><?php echo esc_html( $args['title'] ); ?></h2><?php endif; ?>
			<?php if ( $args['subtitle'] ) : ?><p class="sg-section-head__sub"><?php echo esc_html( $args['subtitle'] ); ?></p><?php endif; ?>
		</div>

		<div class="pg-table__scroll" role="region" aria-labelledby="<?php echo esc_attr( $args['id'] ); ?>-caption" tabindex="0">
			<table class="pg-table__table" data-price-table>
				<caption class="screen-reader-text" id="<?php echo esc_attr( $args['id'] ); ?>-caption">Fasce di prezzo indicative per servizio e metri quadri, in euro</caption>
				<thead>
					<tr>
						<th scope="col" class="pg-table__corner">Servizio</th>
						<?php foreach ( $metrature as $mq ) : ?>
							<th scope="col"><?php echo (int) $mq; ?>&nbsp;m²</th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $righe as $riga ) : ?>
						<tr>
							<th scope="row"><a href="<?php echo esc_url( home_url( $riga['path'] ) ); ?>"><?php echo esc_html( $riga['label'] ); ?></a></th>
							<?php foreach ( $metrature as $mq ) : $f = $riga['fasce'][ $mq ] ?? null; ?>
								<?php if ( $f ) : ?>
									<td data-mq="<?php echo (int) $mq; ?>" data-min="<?php echo (int) $f['min']; ?>" data-max="<?php echo (int) $f['max']; ?>"><?php echo esc_html( tp_guida_prezzi_formato( $f ) ); ?></td>
								<?php else : ?>
									<td></td>
								<?php endif; ?>
							<?php endforeach; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php if ( $uguali && $args['note_same'] ) : ?><p class="pg-table__note pg-table__note--same"><?php echo esc_html( $args['note_same'] ); ?></p><?php endif; ?>
		<?php if ( $args['note'] ) : ?><p class="pg-table__note"><?php echo esc_html( $args['note'] ); ?></p><?php endif; ?>
	</div>
</section>
