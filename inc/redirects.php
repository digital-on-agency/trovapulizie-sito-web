<?php
/**
 * Redirect del dominio pubblico per il cutover di www.trovapulizie.it su WordPress
 * (vault: clients/trovapulizie/projects/sito-web/sviluppo/cutover-checklist.md, sezione A).
 *
 * Oggi la SPA React serve su www anche le rotte dell'app (il DomainGuard non riconosce www.trovapulizie.it come
 * host pubblico): segnalibri, link vecchi e /login indicizzato devono continuare a funzionare dopo il cutover.
 * Gira su `init`, prima della query di WordPress: /home e' anche lo slug della pagina Home e /login verrebbe
 * mandato da WordPress a wp-login.php.
 *
 * - Rotte dell'app (stessi prefissi di APP_PREFIXES in front-end/src/lib/shared/domain.ts, confine su '/' e '-')
 *   -> 301 su app.trovapulizie.com con percorso e query. /terms resta qui (pagina legale).
 * - /partner -> /diventa-cleaner/; /help -> /contatti/; /sitemap.xml -> /wp-sitemap.xml.
 * - /pulizie-<quartiere> -> /roma/pulizie-<quartiere>/ (come vercel.json). Se la pagina del quartiere non e'
 *   pubblicata, e per gli URL di produzione senza pagina (zanzare), 302 temporaneo sull'hub /roma/: quando la
 *   pagina esiste la regola smette di scattare da sola.
 * - /sw.js: service worker che si deregistra (la SPA lo registrava su www, pass-through e senza cache).
 *
 * [ATTENZIONE] Mai rimandare all'app un percorso che l'app considera pubblico (isPublicPath): il DomainGuard
 * dell'app lo rimanda al dominio pubblico e il giro diventa un ciclo. /survey-dubai resta 404 finche' l'app non
 * la serve solo da app.trovapulizie.com.
 */

const TP_APP_ORIGIN = 'https://app.trovapulizie.com';

/** Prefissi delle rotte dell'app, come APP_PREFIXES del portale (senza /terms). */
function tp_app_prefixes() {
	return array(
		'/login', '/searcher-signup', '/cleaner', '/splash', '/forgot-password',
		'/reset-password', '/auth/', '/verify-email',
		'/c-', '/c/', '/s-', '/home', '/results', '/notifications',
		'/book/', '/richiedi-preventivo', '/recap', '/payment', '/checkout/',
		'/booking-', '/receipt',
	);
}

/** True se il percorso e' una rotta dell'app: stesso confine di isAppPath() ('/cleanerz' non conta). */
function tp_is_app_path( $path ) {
	foreach ( tp_app_prefixes() as $p ) {
		if ( $path === $p || $path === $p . '/' ) {
			return true;
		}
		if ( 0 !== strpos( $path, $p ) ) {
			continue;
		}
		$last = substr( $p, -1 );
		if ( '/' === $last || '-' === $last ) {
			return true;
		}
		$next = substr( $path, strlen( $p ), 1 );
		if ( '/' === $next || '-' === $next ) {
			return true;
		}
	}
	return false;
}

/** URL di produzione che non hanno ancora una pagina WordPress: 302 sull'hub finche' la pagina non esiste. */
function tp_pending_paths() {
	return array( '/roma/zanzare-disinfestazione' );
}

/** True se a quel percorso risponde una pagina pubblicata. */
function tp_published_page_exists( $path ) {
	$page = get_page_by_path( trim( $path, '/' ) );
	return $page && 'publish' === $page->post_status;
}

add_action( 'init', function () {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	$uri   = (string) ( $_SERVER['REQUEST_URI'] ?? '/' );
	$path  = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$query = (string) wp_parse_url( $uri, PHP_URL_QUERY );
	if ( '' === $path || 0 === strpos( $path, '/wp-' ) ) {
		return;
	}
	$bare = '/' === $path ? '/' : rtrim( $path, '/' );

	if ( '/sw.js' === $bare ) {
		header( 'Content-Type: application/javascript; charset=utf-8' );
		header( 'Cache-Control: no-cache' );
		echo "self.addEventListener('install',function(){self.skipWaiting();});self.addEventListener('activate',function(e){e.waitUntil(self.registration.unregister());});";
		exit;
	}

	$target = '';
	$code   = 301;
	if ( tp_is_app_path( $bare ) ) {
		$target = TP_APP_ORIGIN . $path . ( '' !== $query ? '?' . $query : '' );
	} elseif ( '/partner' === $bare ) {
		$target = home_url( '/diventa-cleaner/' );
	} elseif ( '/help' === $bare ) {
		$target = home_url( '/contatti/' );
	} elseif ( '/sitemap.xml' === $bare ) {
		$target = home_url( '/wp-sitemap.xml' );
	} elseif ( preg_match( '#^/pulizie-([a-z-]+)$#', $bare, $m ) && 'pulizie-casa-roma' !== 'pulizie-' . $m[1] && 'pulizie-aziende-roma' !== 'pulizie-' . $m[1] ) {
		// Un solo salto: se la pagina del quartiere non c'e' ancora si va diretti all'hub, in 302.
		$nuovo = '/roma/pulizie-' . $m[1];
		if ( tp_published_page_exists( $nuovo ) ) {
			$target = home_url( $nuovo . '/' );
		} else {
			$target = home_url( '/roma/' );
			$code   = 302;
		}
	} elseif ( ( preg_match( '#^/roma/pulizie-[a-z-]+$#', $bare ) || in_array( $bare, tp_pending_paths(), true ) ) && ! tp_published_page_exists( $bare ) ) {
		$target = home_url( '/roma/' );
		$code   = 302;
	}
	if ( $target ) {
		wp_redirect( $target, $code, 'Trovapulizie' );
		exit;
	}
}, 1 );
