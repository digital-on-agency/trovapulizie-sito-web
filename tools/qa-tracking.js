// QA del tracking di base (TICKET-38): node tools/qa-tracking.js <chrome-bin> <url>
// Apre l'URL con ?gclid=QA123&utm_source=qa&utm_medium=cpc&utm_campaign=sgrosso e verifica:
// una sola richiesta a gtm.js, consent default negato nel dataLayer, tp_gclid in localStorage, tpGetUtm(),
// iubenda e Clarity caricati, e gli eventi marketing_* emessi dai click su tel: e sulle ancore verso il form
// (click intercettati con preventDefault: nessuna navigazione). Esce con 1 se un controllo fallisce.
const { withChrome } = require('./lib/cdp');
const [BIN, URL0] = process.argv.slice(2, 4);
const URL = URL0 + (URL0.indexOf('?') === -1 ? '?' : '&') + 'gclid=QA123&utm_source=qa&utm_medium=cpc&utm_campaign=sgrosso';

withChrome(BIN, URL, { port: 9343 }, async ({ ev, sleep }) => {
	await sleep(4000);
	const r = JSON.parse(await ev(`(function(){
		var res = performance.getEntriesByType('resource').map(function(e){ return e.name; });
		var gtm = res.filter(function(n){ return n.indexOf('googletagmanager.com/gtm.js') !== -1; }).length;
		var iub = res.filter(function(n){ return n.indexOf('iubenda') !== -1; }).length;
		// Clarity e' bloccata dall'autoblocking iubenda finche' non c'e' consenso: si verifica lo snippet, non la richiesta.
		var clarity = Array.prototype.filter.call(document.scripts, function(s){ return !s.src && s.textContent.indexOf('clarity.ms/tag') !== -1; }).length;
		var dl = window.dataLayer || [];
		var consent = null;
		for (var i = 0; i < dl.length; i++) { var x = dl[i]; if (x && x[0] === 'consent' && x[1] === 'default') { consent = x[2]; break; } }
		var gclid = window.tpGetGclid && window.tpGetGclid();
		var utm = window.tpGetUtm && window.tpGetUtm();
		var before = dl.length;
		document.addEventListener('click', function (e) { e.preventDefault(); }, { capture: false });
		var tel = document.querySelector('a[href^="tel:"]'); if (tel) tel.click();
		var form = document.querySelector('a[href="#form"]'); if (form) form.click();
		var after = dl.slice(before).filter(function(x){ return x && x.event; }).map(function(x){ return x.event + ':' + (x.landing || '') + (x.target ? ':' + x.target : ''); });
		return JSON.stringify({ gtm: gtm, iub: iub, clarity: clarity, consent: consent, gclid: gclid, utm: utm, events: after, landing: window.tpLanding && window.tpLanding() });
	})()`));
	const checks = [
		['gtm.js richiesto una volta', r.gtm === 1, r.gtm],
		['iubenda caricata', r.iub >= 1, r.iub],
		['snippet Clarity presente (parte dopo il consenso)', r.clarity >= 1, r.clarity],
		['consent default negato', !!r.consent && r.consent.analytics_storage === 'denied' && r.consent.ad_storage === 'denied', JSON.stringify(r.consent)],
		['tp_gclid salvato', r.gclid === 'QA123', r.gclid],
		['tpGetUtm', !!r.utm && r.utm.utmSource === 'qa' && r.utm.utmMedium === 'cpc' && r.utm.utmCampaign === 'sgrosso', JSON.stringify(r.utm)],
		['click tel: -> marketing_click_to_call', r.events.indexOf('marketing_click_to_call:' + r.landing) !== -1, r.events.join(' | ')],
		['click #form -> marketing_click_to_form', r.events.some(function (e) { return e.indexOf('marketing_click_to_form:' + r.landing) === 0; }), r.events.join(' | ')],
	];
	let ko = 0;
	checks.forEach(function (c) { console.log((c[1] ? 'OK  ' : 'KO  ') + c[0] + '  [' + c[2] + ']'); if (!c[1]) ko++; });
	console.log(ko ? 'ESITO: ' + ko + ' controlli falliti' : 'ESITO: verde');
	if (ko) process.exitCode = 1;
}).catch(e => { console.error('ERR', e); process.exit(1); });
