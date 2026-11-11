/**
 * Helper di tracking del tema (TICKET-38), caricato in testa su ogni pagina.
 * - tpTrack(name, params): push sul dataLayer con {event: name, ...params}. Stessi nomi ed eventi della SPA
 *   (registro tracking/events.ts): i tag GTM, il key event GA4 e la conversione Google Ads non cambiano.
 * - Click id (gclid > gbraid > wbraid) salvato in localStorage `tp_gclid` come {value, ts}, 90 giorni, come fa
 *   lib/analytics/tracking.ts della SPA; tpGetGclid() lo rilegge e lo scade. Solo con il consenso alla finalita'
 *   marketing di iubenda (5): senza, il click id non si salva e non parte con i form; se il consenso arriva dopo
 *   l'atterraggio si salva in quel momento, se manca o e' revocato la copia salvata si cancella.
 * - tpGetUtm(): utm_source/medium/campaign dalla query string (prima visita salvata in sessionStorage).
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

	// Consenso alla finalita' marketing (5) letto dal cookie iubenda, l'unica fonte disponibile anche prima che
	// iubenda_cs.js (async) sia caricato.
	function adConsent() {
		var id = window._iub && window._iub.csConfiguration && window._iub.csConfiguration.cookiePolicyId;
		if (!id) return false;
		try {
			var c = document.cookie.split(';').filter(function (s) { return s.trim().indexOf('_iub_cs-' + id + '=') === 0; })[0];
			if (!c) return false;
			var val = JSON.parse(decodeURIComponent(c.trim().split('=').slice(1).join('=')));
			return !!(val && val.purposes && val.purposes['5'] === true);
		} catch (e) { return false; }
	}

	var pendingClick = null; // click id dell'atterraggio, in attesa del consenso

	function persistClick() {
		if (pendingClick && adConsent()) { store('localStorage', GCLID_KEY, JSON.stringify({ value: pendingClick, ts: Date.now() })); pendingClick = null; }
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

	window.tpGetUtm = function () {
		var p = new URLSearchParams(window.location.search);
		var utm = { utmSource: p.get('utm_source') || undefined, utmMedium: p.get('utm_medium') || undefined, utmCampaign: p.get('utm_campaign') || undefined };
		if (utm.utmSource || utm.utmMedium || utm.utmCampaign) return utm;
		try { var saved = read('sessionStorage', UTM_KEY); if (saved) return JSON.parse(saved); } catch (e) {}
		return utm;
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
		// Consenso espresso dopo l'atterraggio: iubenda chiama onPreferenceExpressed quando l'utente sceglie.
		var cfg = window._iub && window._iub.csConfiguration;
		if (cfg) {
			cfg.callback = cfg.callback || {};
			var prev = cfg.callback.onPreferenceExpressed;
			cfg.callback.onPreferenceExpressed = function (pref) {
				if (typeof prev === 'function') prev(pref);
				var ok = !!(pref && pref.purposes && pref.purposes['5'] === true);
				if (ok && pendingClick) { store('localStorage', GCLID_KEY, JSON.stringify({ value: pendingClick, ts: Date.now() })); pendingClick = null; }
				if (!ok) { try { localStorage.removeItem(GCLID_KEY); } catch (e) {} }
			};
		}
		if (p.get('utm_source') || p.get('utm_medium') || p.get('utm_campaign')) {
			store('sessionStorage', UTM_KEY, JSON.stringify({ utmSource: p.get('utm_source') || undefined, utmMedium: p.get('utm_medium') || undefined, utmCampaign: p.get('utm_campaign') || undefined }));
		}
	})();

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
