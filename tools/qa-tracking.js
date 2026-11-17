// QA del tracking di base (TICKET-38): node tools/qa-tracking.js <chrome-bin> <url> [--js <tp-track.js locale>]
// --js serve il file indicato al posto di assets/js/tp-track.js della pagina (prova prima del deploy).
// Apre l'URL con ?gclid=QA123&utm_source=qa&utm_medium=cpc&utm_campaign=sgrosso e verifica:
// una sola richiesta a gtm.js, banner del consenso visibile, niente iubenda ne' Clarity prima della scelta, consent default negato nel dataLayer, tp_gclid assente senza consenso marketing e
// salvato dopo il consenso (cookie tp_consent con ads simulato), tpGetUtm(),
// e gli eventi marketing_* emessi dai click su tel: e sulle ancore verso il form
// (click intercettati con preventDefault: nessuna navigazione). Esce con 1 se un controllo fallisce.
const fs = require('fs');
const { withChrome } = require('./lib/cdp');
const [BIN, URL0] = process.argv.slice(2, 4);
const JS_AT = process.argv.indexOf('--js');
const LOCAL_JS = JS_AT === -1 ? null : process.argv[JS_AT + 1];
const URL = URL0 + (URL0.indexOf('?') === -1 ? '?' : '&') + 'gclid=QA123&utm_source=qa&utm_medium=cpc&utm_campaign=sgrosso';

withChrome(BIN, LOCAL_JS ? 'about:blank' : URL, { port: 9343 }, async ({ ev, sleep, send, on }) => {
	if (LOCAL_JS) {
		on('Fetch.requestPaused', p => send('Fetch.fulfillRequest', { requestId: p.requestId, responseCode: 200, responseHeaders: [{ name: 'Content-Type', value: 'application/javascript' }], body: fs.readFileSync(LOCAL_JS).toString('base64') }));
		await send('Fetch.enable', { patterns: [{ urlPattern: '*assets/js/tp-track.js*', requestStage: 'Request' }] });
		await send('Page.navigate', { url: URL });
	}
	await sleep(4000);
	const r = JSON.parse(await ev(`(function(){
		var res = performance.getEntriesByType('resource').map(function(e){ return e.name; });
		var gtm = res.filter(function(n){ return n.indexOf('googletagmanager.com/gtm.js') !== -1; }).length;
		var iub = res.filter(function(n){ return n.indexOf('iubenda') !== -1; }).length;
		// Clarity parte solo dopo il consenso alle statistiche (tp-consent.js): senza scelta nessuna richiesta.
		var clarity = res.filter(function(n){ return n.indexOf('clarity.ms') !== -1; }).length;
		var banner = document.getElementById('tp-consent');
		var dl = window.dataLayer || [];
		var consent = null;
		for (var i = 0; i < dl.length; i++) { var x = dl[i]; if (x && x[0] === 'consent' && x[1] === 'default') { consent = x[2]; break; } }
		var gclidNo = window.tpGetGclid && window.tpGetGclid();
		var storedNo = localStorage.getItem('tp_gclid');
		document.cookie = 'tp_consent=' + encodeURIComponent(JSON.stringify({ v: 1, ts: Date.now(), stats: true, ads: true })) + '; path=/';
		var gclid = window.tpGetGclid && window.tpGetGclid();
		document.cookie = 'tp_consent=; path=/; max-age=0';
		localStorage.removeItem('tp_gclid');
		var utm = window.tpGetUtm && window.tpGetUtm();
		var before = dl.length;
		document.addEventListener('click', function (e) { e.preventDefault(); }, { capture: false });
		var tel = document.querySelector('a[href^="tel:"]'); if (tel) tel.click();
		var form = document.querySelector('a[href="#form"]'); if (form) form.click();
		var after = dl.slice(before).filter(function(x){ return x && x.event; }).map(function(x){ return x.event + ':' + (x.landing || '') + (x.target ? ':' + x.target : ''); });
		return JSON.stringify({ gtm: gtm, iub: iub, clarity: clarity, banner: !!(banner && !banner.hidden), consent: consent, gclid: gclid, gclidNo: gclidNo, storedNo: storedNo, utm: utm, events: after, landing: window.tpLanding && window.tpLanding() });
	})()`));
	const checks = [
		['gtm.js richiesto una volta', r.gtm === 1, r.gtm],
		['iubenda assente', r.iub === 0, r.iub],
		['banner del consenso visibile', r.banner === true, r.banner],
		['Clarity non caricata senza consenso', r.clarity === 0, r.clarity],
		['consent default negato', !!r.consent && r.consent.analytics_storage === 'denied' && r.consent.ad_storage === 'denied', JSON.stringify(r.consent)],
		['senza consenso marketing: tp_gclid non salvato', r.gclidNo === undefined && r.storedNo === null, r.gclidNo + ' / ' + r.storedNo],
		['con consenso marketing: tp_gclid salvato', r.gclid === 'QA123', r.gclid],
		['tpGetUtm', !!r.utm && r.utm.utmSource === 'qa' && r.utm.utmMedium === 'cpc' && r.utm.utmCampaign === 'sgrosso', JSON.stringify(r.utm)],
		['click tel: -> marketing_click_to_call', r.events.indexOf('marketing_click_to_call:' + r.landing) !== -1, r.events.join(' | ')],
		['click #form -> marketing_click_to_form', r.events.some(function (e) { return e.indexOf('marketing_click_to_form:' + r.landing) === 0; }), r.events.join(' | ')],
	];
	let ko = 0;
	checks.forEach(function (c) { console.log((c[1] ? 'OK  ' : 'KO  ') + c[0] + '  [' + c[2] + ']'); if (!c[1]) ko++; });
	console.log(ko ? 'ESITO: ' + ko + ' controlli falliti' : 'ESITO: verde');
	if (ko) process.exitCode = 1;
}).catch(e => { console.error('ERR', e); process.exit(1); });
