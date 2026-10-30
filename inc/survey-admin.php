<?php
/**
 * Admin della survey OnField (inc/survey.php): elenco filtrabile, dettaglio, cancellazione (singola e multipla) ed
 * export CSV delle risposte. Solo per chi ha manage_options. Voce di menu "Survey OnField".
 *
 * L'export riporta le etichette leggibili delle domande (non i codici) e protegge dall'iniezione di formule: le celle
 * di testo che iniziano con = + - @ o tab ricevono un apice davanti.
 */

const TP_SURVEY_PER_PAGE = 30;

add_action( 'admin_menu', function () {
	add_menu_page( 'Survey OnField', 'Survey OnField', 'manage_options', 'tp-survey', 'tp_survey_admin_page', 'dashicons-feedback', 58 );
} );

/** Etichetta leggibile di un valore di una domanda (o dei valori separati da virgola). */
function tp_survey_label( $field, $value ) {
	static $map = null;
	if ( null === $map ) {
		$map = array();
		foreach ( tp_survey_definition()['steps'] as $s ) {
			foreach ( $s['options'] as $o ) {
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

/** Colonne dell'export e dell'elenco: campo => intestazione. */
function tp_survey_columns() {
	return array(
		'id' => 'ID', 'created_at' => 'Date (UTC)', 'survey_version' => 'Survey version', 'branch' => 'Branch', 'interest' => 'Interest',
		'role' => 'Role', 'business_type' => 'Business type', 'team_size' => 'Team size', 'weekly_jobs' => 'Weekly jobs',
		'tools' => 'Current tools', 'tools_other' => 'Current tools (other)', 'software_names' => 'Software used', 'software_other' => 'Software (other)',
		'challenge' => 'Biggest headache', 'challenge_other' => 'Headache (other)', 'software_spend' => 'Software spend / month (AED)',
		'price_expectation' => 'Fair price / month (AED)', 'name' => 'Name', 'whatsapp' => 'WhatsApp', 'email' => 'Email', 'company' => 'Company',
		'feedback' => 'Feedback', 'consent' => 'Consent', 'consent_at' => 'Consent date (UTC)', 'consent_ref' => 'Consent text ref', 'lang' => 'Language',
	);
}

function tp_survey_csv_cell( $v ) {
	$v = (string) $v;
	if ( '' !== $v && preg_match( '/^[=+\-@\t\r]/', $v ) ) {
		$v = "'" . $v;
	}
	return $v;
}

/** Condizioni WHERE dai filtri GET (versione, ramo, testo). */
function tp_survey_admin_where() {
	global $wpdb;
	$w = array( '1=1' );
	$a = array();
	if ( isset( $_GET['sv'] ) && '' !== $_GET['sv'] ) {
		$w[] = 'survey_version = %d';
		$a[] = (int) $_GET['sv'];
	}
	if ( isset( $_GET['br'] ) && in_array( $_GET['br'], array( 'A', 'B', 'C', 'D' ), true ) ) {
		$w[] = 'branch = %s';
		$a[] = $_GET['br'];
	}
	if ( ! empty( $_GET['q'] ) ) {
		$like = '%' . $wpdb->esc_like( sanitize_text_field( wp_unslash( $_GET['q'] ) ) ) . '%';
		$w[]  = '(name LIKE %s OR email LIKE %s OR whatsapp LIKE %s OR company LIKE %s)';
		array_push( $a, $like, $like, $like, $like );
	}
	$sql = implode( ' AND ', $w );
	return $a ? $wpdb->prepare( $sql, $a ) : $sql;
}

add_action( 'admin_post_tp_survey_csv', function () {
	global $wpdb;
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Forbidden', 403 );
	}
	check_admin_referer( 'tp_survey_csv' );
	$cols = tp_survey_columns();
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="onfield-survey-' . gmdate( 'Ymd-His' ) . '.csv"' );
	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" ); // BOM: Excel legge l'UTF-8.
	fputcsv( $out, array_values( $cols ) );
	$where = tp_survey_admin_where();
	$last  = 0;
	do {
		$rows = $wpdb->get_results( 'SELECT * FROM ' . tp_survey_table() . " WHERE {$where} AND id > " . (int) $last . ' ORDER BY id ASC LIMIT 500', ARRAY_A );
		foreach ( $rows as $r ) {
			$line = array();
			foreach ( array_keys( $cols ) as $k ) {
				$line[] = tp_survey_csv_cell( in_array( $k, array( 'tools', 'software_names', 'role', 'business_type', 'team_size', 'weekly_jobs', 'challenge', 'software_spend', 'price_expectation', 'interest' ), true ) ? tp_survey_label( $k, $r[ $k ] ) : $r[ $k ] );
			}
			fputcsv( $out, $line );
			$last = (int) $r['id'];
		}
	} while ( count( $rows ) === 500 );
	fclose( $out );
	exit;
} );

add_action( 'admin_post_tp_survey_delete', function () {
	global $wpdb;
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Forbidden', 403 );
	}
	check_admin_referer( 'tp_survey_delete' );
	$ids = isset( $_POST['ids'] ) ? array_filter( array_map( 'intval', (array) $_POST['ids'] ) ) : array();
	if ( ! $ids && isset( $_REQUEST['id'] ) ) {
		$ids = array( (int) $_REQUEST['id'] );
	}
	$n = 0;
	if ( $ids ) {
		$n = (int) $wpdb->query( 'DELETE FROM ' . tp_survey_table() . ' WHERE id IN (' . implode( ',', array_map( 'intval', $ids ) ) . ')' );
	}
	wp_safe_redirect( add_query_arg( array( 'page' => 'tp-survey', 'deleted' => $n ), admin_url( 'admin.php' ) ) );
	exit;
} );

function tp_survey_admin_page() {
	global $wpdb;
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Forbidden', 403 );
	}
	$table = tp_survey_table();
	echo '<div class="wrap"><h1 class="wp-heading-inline">Survey OnField</h1>';

	if ( ! empty( $_GET['view'] ) ) {
		tp_survey_admin_detail( (int) $_GET['view'] );
		echo '</div>';
		return;
	}
	if ( isset( $_GET['deleted'] ) ) {
		echo '<div class="notice notice-success"><p>' . (int) $_GET['deleted'] . ' response(s) deleted.</p></div>';
	}

	$where = tp_survey_admin_where();
	$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE {$where}" );
	$paged = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
	$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT %d OFFSET %d", TP_SURVEY_PER_PAGE, ( $paged - 1 ) * TP_SURVEY_PER_PAGE ), ARRAY_A );
	$versions = $wpdb->get_col( "SELECT DISTINCT survey_version FROM {$table} ORDER BY survey_version" );
	$sv = isset( $_GET['sv'] ) ? (string) $_GET['sv'] : '';
	$br = isset( $_GET['br'] ) ? (string) $_GET['br'] : '';
	$q  = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	$filters = array( 'sv' => $sv, 'br' => $br, 'q' => $q );

	$csv = wp_nonce_url( add_query_arg( array_merge( array( 'action' => 'tp_survey_csv' ), array_filter( $filters, 'strlen' ) ), admin_url( 'admin-post.php' ) ), 'tp_survey_csv' );
	echo ' <a class="page-title-action" href="' . esc_url( $csv ) . '">Export CSV' . ( array_filter( $filters, 'strlen' ) ? ' (filtered)' : '' ) . '</a><hr class="wp-header-end">';

	echo '<form method="get" style="margin:12px 0"><input type="hidden" name="page" value="tp-survey">';
	echo '<select name="sv"><option value="">All versions</option>';
	foreach ( $versions as $v ) {
		echo '<option value="' . esc_attr( $v ) . '"' . selected( $sv, $v, false ) . '>Version ' . esc_html( $v ) . '</option>';
	}
	echo '</select> <select name="br"><option value="">All branches</option>';
	foreach ( array( 'A' => 'A - wants access', 'B' => 'B - wants a demo', 'C' => 'C - maybe later', 'D' => 'D - not for me' ) as $k => $l ) {
		echo '<option value="' . esc_attr( $k ) . '"' . selected( $br, $k, false ) . '>' . esc_html( $l ) . '</option>';
	}
	echo '</select> <input type="search" name="q" value="' . esc_attr( $q ) . '" placeholder="Name, email, WhatsApp, company"> ';
	echo '<button class="button">Filter</button></form>';

	echo '<p>' . (int) $total . ' response(s).</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'tp_survey_delete' );
	echo '<input type="hidden" name="action" value="tp_survey_delete">';
	echo '<table class="widefat striped"><thead><tr><td class="check-column"></td><th>ID</th><th>Date</th><th>v</th><th>Branch</th><th>Name</th><th>WhatsApp</th><th>Email</th><th>Company</th><th>Business</th><th>Team</th><th></th></tr></thead><tbody>';
	if ( ! $rows ) {
		echo '<tr><td colspan="12">No responses.</td></tr>';
	}
	foreach ( $rows as $r ) {
		$view = add_query_arg( array( 'page' => 'tp-survey', 'view' => $r['id'] ), admin_url( 'admin.php' ) );
		echo '<tr><th class="check-column"><input type="checkbox" name="ids[]" value="' . (int) $r['id'] . '"></th>';
		echo '<td>' . (int) $r['id'] . '</td><td>' . esc_html( get_date_from_gmt( $r['created_at'], 'Y-m-d H:i' ) ) . '</td><td>' . (int) $r['survey_version'] . '</td><td>' . esc_html( $r['branch'] ) . '</td>';
		echo '<td>' . esc_html( $r['name'] ) . '</td><td>' . esc_html( $r['whatsapp'] ) . '</td><td>' . esc_html( $r['email'] ) . '</td><td>' . esc_html( $r['company'] ) . '</td>';
		echo '<td>' . esc_html( tp_survey_label( 'business_type', $r['business_type'] ) ) . '</td><td>' . esc_html( tp_survey_label( 'team_size', $r['team_size'] ) ) . '</td>';
		echo '<td><a href="' . esc_url( $view ) . '">View</a></td></tr>';
	}
	echo '</tbody></table>';
	if ( $rows ) {
		echo '<p><button class="button" onclick="return confirm(\'Delete the selected responses? This cannot be undone.\')">Delete selected</button></p>';
	}
	echo '</form>';
	$pages = (int) ceil( $total / TP_SURVEY_PER_PAGE );
	if ( $pages > 1 ) {
		echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $paged, 'total' => $pages ) ) ) . '</div></div>';
	}
	echo '</div>';
}

function tp_survey_admin_detail( $id ) {
	global $wpdb;
	$r = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . tp_survey_table() . ' WHERE id = %d', $id ), ARRAY_A );
	echo ' <a class="page-title-action" href="' . esc_url( admin_url( 'admin.php?page=tp-survey' ) ) . '">All responses</a><hr class="wp-header-end">';
	if ( ! $r ) {
		echo '<p>Response not found.</p>';
		return;
	}
	echo '<table class="widefat striped" style="max-width:820px"><tbody>';
	foreach ( tp_survey_columns() as $k => $label ) {
		$val = in_array( $k, array( 'tools', 'software_names', 'role', 'business_type', 'team_size', 'weekly_jobs', 'challenge', 'software_spend', 'price_expectation', 'interest' ), true ) ? tp_survey_label( $k, $r[ $k ] ) : $r[ $k ];
		echo '<tr><th style="width:240px">' . esc_html( $label ) . '</th><td>' . nl2br( esc_html( (string) $val ) ) . '</td></tr>';
	}
	echo '</tbody></table>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:14px">';
	wp_nonce_field( 'tp_survey_delete' );
	echo '<input type="hidden" name="action" value="tp_survey_delete"><input type="hidden" name="id" value="' . (int) $r['id'] . '">';
	echo '<button class="button button-link-delete" onclick="return confirm(\'Delete this response? This cannot be undone.\')">Delete this response</button></form>';
}
