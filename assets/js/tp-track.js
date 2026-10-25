/**
 * Helper di tracking del tema (TICKET-38), caricato in testa su ogni pagina.
 * - tpTrack(name, params): push sul dataLayer con {event: name, ...params}. Stessi nomi ed eventi della SPA
 *   (registro tracking/events.ts): i tag GTM, il key event GA4 e la conversione Google Ads non cambiano.
 * - Click id (gclid > gbraid > wbraid) salvato in localStorage `tp_gclid` come {value, ts}, 90 giorni, come fa
 *   lib/analytics/tracking.ts della SPA; tpGetGclid() lo rilegge e lo scade.
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

	window.tpGetGclid = function () {
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
			if (v) { store('localStorage', GCLID_KEY, JSON.stringify({ value: v, ts: Date.now() })); break; }
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
