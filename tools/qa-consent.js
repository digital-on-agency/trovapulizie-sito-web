// QA del banner del consenso (inc/tracking.php + assets/js/tp-consent.js): node tools/qa-consent.js <chrome-bin> <url>
// Apre l'URL con ?gclid=QACONS e prova: Rifiuta (tutto negato, niente Clarity, niente tp_gclid), riapertura da
// un link #preferenze-cookie con le caselle allineate alla scelta, Personalizza con le sole statistiche (analytics
// concesso, ads negato, Clarity caricata), Accetta tutto (ads concesso, tp_gclid salvato), banner chiuso alla
// pagina dopo. Esce con 1 se un controllo fallisce. Svuota cookie e archiviazione locale all'avvio.
const { withChrome } = require('./lib/cdp');
const [BIN, URL0] = process.argv.slice(2, 4);
const URL = URL0 + (URL0.indexOf('?') === -1 ? '?' : '&') + 'gclid=QACONS';

withChrome(BIN, URL, { port: 9344 }, async ({ send, ev, sleep }) => {
	await sleep(1500);
	await send('Network.clearBrowserCookies', {});
	await ev(`localStorage.clear(); sessionStorage.clear(); setTimeout(function(){ location.reload(); }, 50), true`);
	await sleep(3500);
	const state = () => ev(`JSON.stringify((function(){
		var dl = window.dataLayer || [], ups = [];
		for (var i = 0; i < dl.length; i++) { var x = dl[i]; if (x && x[0] === 'consent' && x[1] === 'update') ups.push(x[2]); }
		var b = document.getElementById('tp-consent');
		return { banner: !!(b && !b.hidden), prefs: !document.getElementById('tp-consent-prefs').hidden, last: ups[ups.length - 1] || null,
			cookie: window.tpConsent && window.tpConsent(), gclid: localStorage.getItem('tp_gclid'),
			clarity: performance.getEntriesByType('resource').some(function(e){ return e.name.indexOf('clarity.ms/tag') !== -1; }),
			boxes: Array.prototype.map.call(document.querySelectorAll('#tp-consent input[data-consent]'), function(i){ return i.getAttribute('data-consent') + ':' + i.checked; }).join(',') };
	})())`).then(JSON.parse);
	const click = sel => ev(`(function(){ var e = document.querySelector('${sel}'); if (e) e.click(); return !!e; })()`);
	const checks = [];
	const ck = (n, ok, v) => checks.push([n, ok, v]);

	let s = await state();
	ck('banner visibile alla prima visita', s.banner && !s.prefs, JSON.stringify(s));
	await click('[data-consent-action="reject"]'); await sleep(800); s = await state();
	ck('Rifiuta: banner chiuso, tutto negato', !s.banner && s.cookie && s.cookie.stats === false && s.cookie.ads === false && s.last && s.last.analytics_storage === 'denied' && s.last.ad_storage === 'denied', JSON.stringify(s));
	ck('Rifiuta: niente Clarity ne tp_gclid', !s.clarity && s.gclid === null, JSON.stringify(s));

	await ev(`(function(){ var a = document.createElement('a'); a.href = '#preferenze-cookie'; a.id = 'qa-pref'; a.textContent = 'Preferenze cookie'; document.body.appendChild(a); a.click(); })()`); await sleep(500); s = await state();
	ck('#preferenze-cookie riapre il pannello con le caselle della scelta', s.banner && s.prefs && s.boxes === 'stats:false,ads:false', JSON.stringify(s));
	await ev(`document.querySelector('#tp-consent input[data-consent="stats"]').checked = true`);
	await click('[data-consent-action="save"]'); await sleep(2500); s = await state();
	ck('Solo statistiche: analytics concesso, ads negato', s.cookie && s.cookie.stats === true && s.cookie.ads === false && s.last.analytics_storage === 'granted' && s.last.ad_storage === 'denied', JSON.stringify(s));
	ck('Solo statistiche: Clarity caricata, tp_gclid assente', s.clarity && s.gclid === null, JSON.stringify(s));

	await click('#qa-pref'); await sleep(300);
	await click('[data-consent-action="accept"]'); await sleep(800); s = await state();
	ck('Accetta tutto: ads concesso e tp_gclid salvato', s.cookie && s.cookie.ads === true && s.last.ad_storage === 'granted' && s.gclid && s.gclid.indexOf('QACONS') !== -1, JSON.stringify(s));

	await ev(`setTimeout(function(){ location.reload(); }, 50), true`); await sleep(4000); s = await state();
	ck('pagina dopo: banner chiuso, default dal cookie', !s.banner, JSON.stringify(s));
	const def = await ev(`JSON.stringify((window.dataLayer||[]).filter(function(x){ return x && x[0]==='consent' && x[1]==='default'; }).pop())`);
	ck('pagina dopo: consent default concesso', def.indexOf('"ad_storage":"granted"') !== -1 && def.indexOf('"analytics_storage":"granted"') !== -1, def);

	let ko = 0;
	checks.forEach(function (c) { console.log((c[1] ? 'OK  ' : 'KO  ') + c[0] + (c[1] ? '' : '  [' + c[2] + ']')); if (!c[1]) ko++; });
	console.log(ko ? 'ESITO: ' + ko + ' controlli falliti' : 'ESITO: verde');
	if (ko) process.exitCode = 1;
}).catch(e => { console.error('ERR', e); process.exit(1); });
