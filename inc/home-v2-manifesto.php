<?php
/**
 * Manifesto "Quattro niente" della Home v2 (SPEC-homepage-v2, TICKET-123): contenuti di default e icone delle prove.
 *
 * Una sola fonte per i default: la usano il campo Carbon `h2_manifesto_items` (inc/carbon-fields.php, valore
 * precompilato nel pannello) e il template-part template-parts/home-manifesto.php. Cosi' i testi si cambiano da
 * WordPress senza deploy e il codice non ne tiene una seconda copia.
 *
 * [ATTENZIONE] Le prove di default non portano nomi propri (revisione UI/UX giro 1, 21/11): l'esempio del prezzo e' dichiarato
 * come tale. Regola di contenuto: nelle prove vanno dati reali dell'app, mai numeri o nomi inventati.
 */

/**
 * Icone selezionabili per la prova di ogni promessa. Chiave => [ file nel tema, etichetta, dimensione ].
 * "pieno" = disegno su griglia 28 (riempie il tondo da 28 px), "piccolo" = disegno su griglia 24 (16 px dentro il tondo).
 */
function tp_home_v2_manifesto_icons() {
	return array(
		'spunta'    => array( 'assets/icons/ic-prova-spunta.svg', 'Spunta', 'pieno' ),
		'etichetta' => array( 'assets/icons/ic-prova-etichetta.svg', 'Etichetta prezzo', 'pieno' ),
		'carta'     => array( 'assets/icons/ic-prova-carta.svg', 'Carta di pagamento', 'pieno' ),
		'scambio'   => array( 'assets/icons/ic-prova-scambio.svg', 'Scambio', 'pieno' ),
		'scudo'     => array( 'assets/icons/ic-shield-check.svg', 'Scudo', 'piccolo' ),
		'utente'    => array( 'assets/icons/ic-user-check.svg', 'Persona verificata', 'piccolo' ),
		'orologio'  => array( 'assets/icons/ic-clock.svg', 'Orologio', 'piccolo' ),
		'pin'       => array( 'assets/icons/ic-pin.svg', 'Luogo', 'piccolo' ),
	);
}

/**
 * Le quattro promesse del Figma (nodo 7903:7379). Ogni riga: lead (parte verde #007A5C), keyword (parola chiave
 * #0F1F1B), text, proof (testo della prova), icon (chiave di tp_home_v2_manifesto_icons()). Il numero d'ordine
 * (01, 02...) lo calcola il template dalla posizione, non e' un campo.
 */
function tp_home_v2_manifesto_defaults() {
	return array(
		array( 'lead' => 'Niente', 'keyword' => 'sconosciuti.', 'text' => 'Controlliamo documenti e identità di ognuno.', 'proof' => 'Documento verificato', 'icon' => 'spunta' ),
		array( 'lead' => 'Niente', 'keyword' => 'sorprese.', 'text' => 'Il prezzo lo vedi prima di inviare la richiesta.', 'proof' => 'Esempio: totale 76 € · 4 ore', 'icon' => 'etichetta' ),
		array( 'lead' => 'Niente', 'keyword' => 'anticipi a vuoto.', 'text' => 'L’addebito parte solo quando il professionista accetta.', 'proof' => 'Addebito dopo l’accettazione', 'icon' => 'carta' ),
		array( 'lead' => 'Niente', 'keyword' => 'buchi.', 'text' => 'Se chi hai scelto non può, te ne proponiamo un altro.', 'proof' => 'Un altro professionista, stesse condizioni', 'icon' => 'scambio' ),
	);
}
