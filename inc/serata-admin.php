<?php
/**
 * Admin della survey della serata 25/11 (inc/serata.php): elenco con le risposte leggibili, cancellazione (singola e
 * multipla) ed export CSV. Solo per chi ha manage_options. Voce di menu: Strumenti > Survey serata 25/11.
 * Le celle del CSV passano da tp_survey_csv_cell() (inc/survey-admin.php): niente iniezione di formule.
 */

add_action( 'admin_menu', function () {
	add_management_page( 'Survey serata 25/11', 'Survey serata 25/11', 'manage_options', 'tp-serata', 'tp_serata_admin_page' );
} );

/** Etichetta leggibile di un valore (o dei valori separati da virgola) di una domanda a scelta. */
function tp_serata_label( $field, $value ) {
	static $map = null;
	if ( null === $map ) {
		$map = array();
		foreach ( tp_serata_definition()['steps'] as $s ) {
			foreach ( $s['options'] ?? array() as $o ) {
				$map[ $s['id'] ][ $o['v'] ] = $o['l'];
			}
		}
	}
	if ( '' === (string) $value || ! isset( $map[ $field ] ) ) {
		return (string) $value;
	}
	$out = array();
	foreach ( explode( ',', (string) $value ) as $v ) {
		$out[] = $map[ $field ][ $v ] ?? $v;
	}
	return implode( ', ', $out );
}

/** Colonne dell'elenco e dell'export: campo => intestazione. */
function tp_serata_columns() {
	return array(
		'id' => 'ID', 'created_at' => 'Data', 'name' => 'Nome', 'rating' => 'Voto (1-5)', 'adesso' => 'E adesso?', 'adesso_altro' => 'E adesso? (altro)',
		'chiarezza' => 'Modello chiaro', 'dubbio' => 'Dubbio', 'ricordo' => 'Cosa è rimasto', 'servizi' => 'Usa servizi', 'approfondire' => 'Da approfondire',
		'contact_ref' => 'Contatto gestionale', 'at_status' => 'Copia gestionale',
	);
}

function tp_serata_cell( $k, array $r ) {
	if ( 'created_at' === $k ) {
		return get_date_from_gmt( $r['created_at'], 'Y-m-d H:i' );
	}
	if ( 'at_status' === $k ) {
		return 'ok' === $r['at_status'] ? 'ok' : $r['at_status'] . ( '' !== $r['at_error'] ? ' - ' . $r['at_error'] : '' );
	}
	return in_array( $k, array( 'chiarezza', 'servizi', 'approfondire', 'adesso' ), true ) ? tp_serata_label( $k, $r[ $k ] ) : (string) $r[ $k ];
}

add_action( 'admin_post_tp_serata_csv', function () {
	global $wpdb;
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Forbidden', 403 );
	}
	check_admin_referer( 'tp_serata_csv' );
	$cols = tp_serata_columns();
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="survey-serata-25-11-' . gmdate( 'Ymd-His' ) . '.csv"' );
	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" ); // BOM: Excel legge l'UTF-8.
	fputcsv( $out, array_values( $cols ) );
	foreach ( $wpdb->get_results( 'SELECT * FROM ' . tp_serata_table() . ' ORDER BY id ASC', ARRAY_A ) as $r ) {
		$line = array();
		foreach ( array_keys( $cols ) as $k ) {
			$line[] = tp_survey_csv_cell( tp_serata_cell( $k, $r ) );
		}
		fputcsv( $out, $line );
	}
	fclose( $out );
	exit;
} );

add_action( 'admin_post_tp_serata_delete', function () {
	global $wpdb;
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Forbidden', 403 );
	}
	check_admin_referer( 'tp_serata_delete' );
	$ids = isset( $_POST['ids'] ) ? array_filter( array_map( 'intval', (array) $_POST['ids'] ) ) : array();
	$n   = 0;
	if ( $ids ) {
		$n = (int) $wpdb->query( 'DELETE FROM ' . tp_serata_table() . ' WHERE id IN (' . implode( ',', array_map( 'intval', $ids ) ) . ')' );
	}
	wp_safe_redirect( add_query_arg( array( 'page' => 'tp-serata', 'deleted' => $n ), admin_url( 'tools.php' ) ) );
	exit;
} );

function tp_serata_admin_page() {
	global $wpdb;
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Forbidden', 403 );
	}
	$rows = $wpdb->get_results( 'SELECT * FROM ' . tp_serata_table() . ' ORDER BY id DESC', ARRAY_A );
	$csv  = wp_nonce_url( add_query_arg( 'action', 'tp_serata_csv', admin_url( 'admin-post.php' ) ), 'tp_serata_csv' );
	echo '<div class="wrap"><h1 class="wp-heading-inline">Survey serata 25/11</h1>';
	echo ' <a class="page-title-action" href="' . esc_url( $csv ) . '">Esporta CSV</a><hr class="wp-header-end">';
	if ( isset( $_GET['deleted'] ) ) {
		echo '<div class="notice notice-success"><p>' . (int) $_GET['deleted'] . ' risposte cancellate.</p></div>';
	}
	echo '<p>' . count( $rows ) . ' risposte. Pagina pubblica: <code>/serata-25-11/?n=Nome%20Cognome</code>.</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'tp_serata_delete' );
	echo '<input type="hidden" name="action" value="tp_serata_delete">';
	echo '<table class="widefat striped"><thead><tr><td class="check-column"></td>';
	foreach ( tp_serata_columns() as $label ) {
		echo '<th>' . esc_html( $label ) . '</th>';
	}
	echo '</tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="12">Nessuna risposta.</td></tr>';
	}
	foreach ( $rows as $r ) {
		echo '<tr><th class="check-column"><input type="checkbox" name="ids[]" value="' . (int) $r['id'] . '"></th>';
		foreach ( array_keys( tp_serata_columns() ) as $k ) {
			echo '<td>' . nl2br( esc_html( tp_serata_cell( $k, $r ) ) ) . '</td>';
		}
		echo '</tr>';
	}
	echo '</tbody></table>';
	if ( $rows ) {
		echo '<p><button class="button" onclick="return confirm(\'Cancellare le risposte selezionate? Non si torna indietro.\')">Cancella selezionate</button></p>';
	}
	echo '</form></div>';
}
