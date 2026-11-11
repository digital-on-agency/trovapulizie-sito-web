/**
 * Banner del consenso ai cookie (11/11/2026, sostituisce iubenda). Markup in inc/tracking.php (wp_footer).
 * - Cookie `tp_consent` = {v, ts, stats, ads}, 6 mesi: scaduto o assente, il banner si ripresenta.
 * - Consent mode v2: il default lo scrive inc/tracking.php dal cookie; qui solo gli update dopo una scelta.
 * - Clarity (tpConsentConfig.clarity) si carica solo con le statistiche; con una revoca smette alla pagina dopo.
 * - Evento `tp:consent` su document con {stats, ads}: lo ascolta tp-track.js per il click id.
 * - Ogni link con href="#preferenze-cookie" riapre il pannello delle preferenze.
 * Rifiuta e Accetta tutto hanno lo stesso peso visivo; chiudere senza scegliere non e' possibile, ma il sito
 * resta navigabile con il banner aperto (nessun muro).
 */
(function () {
	'use strict';

	var KEY = 'tp_consent';
	var MAX_AGE = 182 * 24 * 60 * 60; // 6 mesi
	var root = document.getElementById('tp-consent');
	if (!root) return;
	var prefs = document.getElementById('tp-consent-prefs');
	var boxes = root.querySelectorAll('input[data-consent]');
	var btn = function (a) { return root.querySelector('[data-consent-action="' + a + '"]'); };

	function current() { return window.tpConsent ? window.tpConsent() : null; }

	function gtagUpdate(s) {
		if (typeof window.gtag !== 'function') return;
		var a = s.stats ? 'granted' : 'denied', m = s.ads ? 'granted' : 'denied';
		window.gtag('consent', 'update', { analytics_storage: a, ad_storage: m, ad_user_data: m, ad_personalization: m });
		window.gtag('set', 'ads_data_redaction', !s.ads);
		window.dataLayer.push({ event: 'tp_consent_update', consent_stats: s.stats, consent_ads: s.ads });
	}

	var clarityOn = false;
	function loadClarity() {
		var id = window.tpConsentConfig && window.tpConsentConfig.clarity;
		if (!id || clarityOn) return;
		clarityOn = true;
		(function (c, l, a, r, i, t, y) { c[a] = c[a] || function () { (c[a].q = c[a].q || []).push(arguments); }; t = l.createElement(r); t.async = 1; t.src = 'https://www.clarity.ms/tag/' + i; y = l.getElementsByTagName(r)[0]; y.parentNode.insertBefore(t, y); })(window, document, 'clarity', 'script', id);
		window.clarity('consent');
	}

	function save(stats, ads) {
		var s = { v: 1, ts: Date.now(), stats: !!stats, ads: !!ads };
		var secure = location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = KEY + '=' + encodeURIComponent(JSON.stringify(s)) + '; Max-Age=' + MAX_AGE + '; Path=/; SameSite=Lax' + secure;
		gtagUpdate(s);
		if (s.stats) loadClarity();
		else if (clarityOn && typeof window.clarity === 'function') window.clarity('consent', false);
		document.dispatchEvent(new CustomEvent('tp:consent', { detail: { stats: s.stats, ads: s.ads } }));
		hide();
	}

	function show(withPrefs) {
		var s = current() || { stats: false, ads: false };
		for (var i = 0; i < boxes.length; i++) boxes[i].checked = !!s[boxes[i].getAttribute('data-consent')];
		prefs.hidden = !withPrefs;
		btn('customize').hidden = !!withPrefs;
		btn('save').hidden = !withPrefs;
		root.hidden = false;
		if (withPrefs && boxes[0]) boxes[0].focus({ preventScroll: true }); // alla prima apertura nessun focus: niente spinta verso un bottone
	}
	function hide() { root.hidden = true; }

	root.addEventListener('click', function (e) {
		var t = e.target.closest('[data-consent-action]');
		if (!t) return;
		var a = t.getAttribute('data-consent-action');
		if (a === 'accept') save(true, true);
		else if (a === 'reject') save(false, false);
		else if (a === 'customize') show(true);
		else if (a === 'save') {
			var v = {};
			for (var i = 0; i < boxes.length; i++) v[boxes[i].getAttribute('data-consent')] = boxes[i].checked;
			save(v.stats, v.ads);
		}
	});

	document.addEventListener('click', function (e) {
		var a = e.target.closest ? e.target.closest('a[href$="#preferenze-cookie"]') : null;
		if (!a) return;
		e.preventDefault();
		show(true);
	});

	var s = current();
	if (s) { if (s.stats) loadClarity(); }
	else show(false);
})();
