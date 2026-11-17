/**
 * Helper di tracking del tema (TICKET-38), caricato in testa su ogni pagina.
 * - tpTrack(name, params): push sul dataLayer con {event: name, ...params}. Stessi nomi ed eventi della SPA
 *   (registro tracking/events.ts): i tag GTM, il key event GA4 e la conversione Google Ads non cambiano.
 * - Click id (gclid > gbraid > wbraid) salvato in localStorage `tp_gclid` come {value, ts}, 90 giorni, come fa
 *   lib/analytics/tracking.ts della SPA; tpGetGclid() lo rilegge e lo scade. Solo con il consenso marketing
 *   (cookie tp_consent, banner di tp-consent.js): senza, il click id non si salva e non parte con i form; se il
 *   consenso arriva dopo l'atterraggio si salva in quel momento, se manca o e' revocato la copia salvata si cancella.
 * - tpConsent(): la scelta salvata {v, ts, stats, ads} o null se l'utente non ha ancora scelto.
 * - tpGetUtm(): utm_source/medium/campaign dalla query string (prima visita salvata in sessionStorage).
 *   Forma invariata: la usa lead-form.js verso /api/leads. In `tp_utm` si salvano anche utm_content e utm_term.
 * - tpAppUrl(url) e click verso https://app.trovapulizie.com/ (SPEC-utm-verso-app): con l'interruttore acceso
 *   (window.tpTrackConfig.utmToApp, da Theme Options > Integrazioni, inc/tracking.php) e il consenso statistiche,
 *   aggiunge all'URL dell'app i cinque utm_* della visita. Un URL che ha gia' un utm_* non si tocca. La decisione si
 *   prende al click: un consenso dato a meta' visita vale subito. L'href del link torna com'era subito dopo.
 * - Click su tel:, wa.me e ancore verso il form nelle pagine con [data-landing]: marketing_click_to_call,
 *   marketing_click_whatsapp, marketing_click_to_form con {landing} (useLandingClickTracking della SPA).
 * [ATTENZIONE] Il parametro di posizione della CTA si chiama cta_position: `source` e' riservato a GA4.
 */
(function () {
	'use strict';

	var GCLID_KEY = 'tp_gclid';
	var GCLID_TTL = 90 * 24 * 60 * 60 * 1000;
	var CLICK_ID_PARAMS = ['gclid', 'gbraid', 'wbraid'];
	var UTM_KEY = 'tp_utm';
	var APP_ORIGIN = 'https://app.trovapulizie.com';
	// Parametro della query -> chiave in tp_utm. I primi tre sono anche la forma di tpGetUtm().
	var UTM_PARAMS = { utm_source: 'utmSource', utm_medium: 'utmMedium', utm_campaign: 'utmCampaign', utm_content: 'utmContent', utm_term: 'utmTerm' };

	window.dataLayer = window.dataLayer || [];

	function store(kind, key, value) {
		try { window[kind].setItem(key, value); } catch (e) { /* storage bloccato: si prosegue senza */ }
	}
	function read(kind, key) {
		try { return window[kind].getItem(key); } catch (e) { return null; }
	}

	window.tpTrack = function (name, params) {
		var payload = { event: name };
		if (params) { for (var k in params) { if (Object.prototype.hasOwnProperty.call(params, k) && params[k] !== undefined) payload[k] = params[k]; } }
		window.dataLayer.push(payload);
	};

	window.tpConsent = function () {
		try {
			var c = document.cookie.split(';').filter(function (x) { return x.trim().indexOf('tp_consent=') === 0; })[0];
			if (!c) return null;
			var v = JSON.parse(decodeURIComponent(c.trim().slice(11)));
			return v && typeof v === 'object' ? v : null;
		} catch (e) { return null; }
	};
	function adConsent() { var c = window.tpConsent(); return !!(c && c.ads === true); }

	var pendingClick = null; // click id dell'atterraggio: resta in memoria per tutta la pagina, si salva con il consenso

	function persistClick() {
		if (pendingClick && adConsent() && read('localStorage', GCLID_KEY) === null) store('localStorage', GCLID_KEY, JSON.stringify({ value: pendingClick, ts: Date.now() }));
	}

	window.tpGetGclid = function () {
		persistClick();
		if (!adConsent()) { try { localStorage.removeItem(GCLID_KEY); } catch (e) {} return undefined; }
		var raw = read('localStorage', GCLID_KEY);
		if (!raw) return undefined;
		try {
			var o = JSON.parse(raw);
			if (typeof o.value !== 'string' || typeof o.ts !== 'number') return undefined;
			if (Date.now() - o.ts > GCLID_TTL) { try { localStorage.removeItem(GCLID_KEY); } catch (e) {} return undefined; }
			return o.value;
		} catch (e) { return undefined; }
	};

	// UTM dalla query string, o null se non ce n'e' nessuno.
	function utmFromQuery() {
		var p = new URLSearchParams(window.location.search), utm = {}, found = false;
		for (var k in UTM_PARAMS) { var v = p.get(k); if (v) { utm[UTM_PARAMS[k]] = v; found = true; } }
		return found ? utm : null;
	}

	// UTM della visita: quelli della pagina, altrimenti gli ultimi salvati in tp_utm. Oggetto vuoto se nessuno.
	function visitUtm() {
		var q = utmFromQuery();
		if (q) return q;
		try { var o = JSON.parse(read('sessionStorage', UTM_KEY)); if (o && typeof o === 'object') return o; } catch (e) {}
		return {};
	}

	window.tpGetUtm = function () {
		var u = visitUtm();
		return { utmSource: u.utmSource, utmMedium: u.utmMedium, utmCampaign: u.utmCampaign };
	};

	function utmToAppOn() {
		var cfg = window.tpTrackConfig;
		return !!(cfg && cfg.utmToApp === true);
	}

	// URL verso l'app con gli UTM della visita, se ammesso; altrimenti l'URL invariato.
	window.tpAppUrl = function (url) {
		if (!utmToAppOn()) return url;
		var c = window.tpConsent();
		if (!c || c.stats !== true) return url;
		var u;
		try { u = new URL(url, window.location.href); } catch (e) { return url; }
		if (u.origin !== APP_ORIGIN) return url;
		var has = false;
		u.searchParams.forEach(function (v, k) { if (k.indexOf('utm_') === 0) has = true; });
		if (has) return url;
		var utm = visitUtm(), added = false;
		for (var k in UTM_PARAMS) { var v = utm[UTM_PARAMS[k]]; if (typeof v === 'string' && v) { u.searchParams.set(k, v); added = true; } }
		return added ? u.href : url;
	};

	window.tpLanding = function () {
		var el = document.querySelector('[data-landing]');
		return el ? el.getAttribute('data-landing') : '';
	};

	// Cattura al caricamento: click id e UTM dalla query string.
	(function capture() {
		var p = new URLSearchParams(window.location.search);
		for (var i = 0; i < CLICK_ID_PARAMS.length; i++) {
			var v = p.get(CLICK_ID_PARAMS[i]);
			if (v) { pendingClick = v; break; }
		}
		persistClick();
		// Consenso espresso dopo l'atterraggio (banner di tp-consent.js).
		document.addEventListener('tp:consent', function (e) {
			var ok = !!(e.detail && e.detail.ads);
			if (ok) persistClick();
			else { try { localStorage.removeItem(GCLID_KEY); } catch (err) {} }
		});
		var utm = utmFromQuery();
		if (utm) store('sessionStorage', UTM_KEY, JSON.stringify(utm));
	})();

	// Link verso l'app: UTM aggiunti al click (anche rotella e ctrl/cmd, che aprono una nuova scheda) e href
	// ripristinato subito dopo, cosi' una revoca del consenso vale per il click successivo. Un solo listener.
	function decorateAppLink(e) {
		var a = e.target && e.target.closest ? e.target.closest('a[href]') : null;
		if (!a || !a.href || a.href.indexOf(APP_ORIGIN + '/') !== 0) return;
		var original = a.getAttribute('href');
		var next = window.tpAppUrl(a.href);
		if (next === a.href) return;
		a.setAttribute('href', next);
		setTimeout(function () { a.setAttribute('href', original); }, 0);
	}
	document.addEventListener('click', decorateAppLink, true);
	document.addEventListener('auxclick', decorateAppLink, true);

	// Click sulle landing: un solo listener, event delegation.
	document.addEventListener('click', function (e) {
		var a = e.target && e.target.closest ? e.target.closest('a[href]') : null;
		if (!a) return;
		var landing = window.tpLanding();
		if (!landing) return;
		var href = a.getAttribute('href') || '';
		// data-cta-position (pagina Diventa cleaner, TICKET-51): posizione della CTA negli eventi. Le landing
		// Sgrosso non lo usano, i loro eventi restano identici.
		var pos = a.getAttribute('data-cta-position');
		var extra = pos ? { cta_position: pos } : {};
		function p(base) { for (var k in extra) base[k] = extra[k]; return base; }
		if (href.indexOf('tel:') === 0) window.tpTrack('marketing_click_to_call', p({ landing: landing }));
		else if (href.indexOf('wa.me') !== -1 || href.indexOf('whatsapp') !== -1) window.tpTrack('marketing_click_whatsapp', p({ landing: landing }));
		else if (/#form(-bottom)?$/.test(href)) window.tpTrack('marketing_click_to_form', p({ landing: landing, target: href.slice(href.indexOf('#')) }));
		else if (pos) window.tpTrack('marketing_cta_click', { landing: landing, cta_position: pos, target: href });
	}, true);
})();
