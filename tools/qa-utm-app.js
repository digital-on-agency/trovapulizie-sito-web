// QA del passaggio degli UTM all'app (SPEC-utm-verso-app, TICKET-101):
//   node tools/qa-utm-app.js <chrome-bin> <url-pagina> [--js <tp-track.js locale>] [--force-on] [--expect-off]
// Apre la pagina piu' volte (profilo pulito per scenario), clicca link verso https://app.trovapulizie.com/ e legge
// l'URL della navigazione intercettata con Fetch (la richiesta all'app viene bloccata: nessuna visita all'app).
// --js     serve il file indicato al posto di assets/js/tp-track.js della pagina (prova del codice prima del deploy).
// --force-on  aggiunge in testa al file servito window.tpTrackConfig = {utmToApp: true} (interruttore acceso).
// --expect-off  verifica solo che con l'interruttore della pagina (spento) nessun URL cambi.
// La pagina deve avere almeno un link verso l'app (header "Accedi" su ogni pagina del tema). Esce con 1 se un
// controllo fallisce.
const fs = require('fs');
const { withChrome } = require('./lib/cdp');

const args = process.argv.slice(2);
const BIN = args[0], PAGE = args[1];
const opt = n => { const i = args.indexOf(n); return i === -1 ? null : (args[i + 1] || true); };
const LOCAL_JS = opt('--js');
const FORCE_ON = args.indexOf('--force-on') !== -1;
const EXPECT_OFF = args.indexOf('--expect-off') !== -1;
const UTM = 'utm_source=google&utm_medium=cpc&utm_campaign=sgrosso_casa_roma&utm_content=198071219439&utm_term=pulizia%20a%20fondo';
const withQuery = (u, q) => u + (u.indexOf('?') === -1 ? '?' : '&') + q;
const origin = new URL(PAGE).origin;

const checks = [];
const check = (name, ok, info) => checks.push([name, !!ok, info]);
const params = u => { try { return Object.fromEntries(new URL(u).searchParams); } catch (e) { return {}; } };
const hasUtm = u => Object.keys(params(u)).some(k => k.indexOf('utm_') === 0);

// Uno scenario = una pagina aperta in un profilo nuovo. setup(ev) prima dei click, act(ev) restituisce gli
// href cliccati; ritorna { clicked, navigated[] }.
let port = 9361;
async function scenario(url, { consent, before, act }) {
	let navigated = [];
	await withChrome(BIN, 'about:blank', { port: port++ }, async ({ send, ev, sleep, on }) => {
		const patterns = [{ urlPattern: 'https://app.trovapulizie.com/*', requestStage: 'Request' }];
		if (LOCAL_JS) patterns.push({ urlPattern: '*assets/js/tp-track.js*', requestStage: 'Request' });
		on('Fetch.requestPaused', async p => {
			if (p.request.url.indexOf('https://app.trovapulizie.com/') === 0) {
				navigated.push(p.request.url);
				await send('Fetch.failRequest', { requestId: p.requestId, errorReason: 'Aborted' });
			} else {
				let body = fs.readFileSync(LOCAL_JS, 'utf8');
				if (FORCE_ON) body = 'window.tpTrackConfig = { utmToApp: true };\n' + body;
				await send('Fetch.fulfillRequest', { requestId: p.requestId, responseCode: 200, responseHeaders: [{ name: 'Content-Type', value: 'application/javascript' }], body: Buffer.from(body).toString('base64') });
			}
		});
		await send('Fetch.enable', { patterns });
		await send('Network.enable');
		if (consent) await send('Network.setCookie', { name: 'tp_consent', value: encodeURIComponent(JSON.stringify(Object.assign({ v: 1, ts: Date.now() }, consent))), url: origin, path: '/' });
		await send('Page.navigate', { url });
		await sleep(3500);
		if (before) await ev(before);
		const out = await act(ev, sleep);
		await sleep(1200);
		navigated = navigated.slice();
		Object.assign(out, { navigated });
		scenario.last = out;
	});
	return scenario.last;
}

// Clicca il primo link verso l'app con un listener che non blocca la navigazione; restituisce l'href originale.
const clickApp = sel => `(function(){
	var a = document.querySelector(${JSON.stringify(sel || 'a[href^="https://app.trovapulizie.com/"]')});
	if (!a) return JSON.stringify({ clicked: null });
	var h = a.getAttribute('href'); a.click();
	// L'href decorato si ripristina con setTimeout 0: si legge dopo un tick.
	return new Promise(function (r) { setTimeout(function () { r(JSON.stringify({ clicked: h, after: a.getAttribute('href') })); }, 50); });
})()`;
const inject = href => `(function(){ var a = document.createElement('a'); a.id = 'qa-app'; a.href = ${JSON.stringify(href)}; a.textContent = 'qa'; document.body.appendChild(a); })()`;
const parse = s => { try { return JSON.parse(s); } catch (e) { return { raw: s }; } };

(async () => {
	if (EXPECT_OFF) {
		const r = await scenario(withQuery(PAGE, UTM), { consent: { stats: true, ads: true }, act: async ev => parse(await ev(clickApp())) });
		check('interruttore spento: navigazione all\'href invariato', r.clicked && r.navigated.length && r.navigated[0] === new URL(r.clicked, origin).href, JSON.stringify(r));
	} else {
		// 1. Consenso statistiche e UTM nella visita: cinque parametri, query originale conservata, href ripristinato.
		let r = await scenario(withQuery(PAGE, UTM), { consent: { stats: true, ads: false }, act: async ev => parse(await ev(clickApp())) });
		let p = params(r.navigated[0]);
		check('consenso + UTM: cinque utm_* sul link verso l\'app', p.utm_source === 'google' && p.utm_medium === 'cpc' && p.utm_campaign === 'sgrosso_casa_roma' && p.utm_content === '198071219439' && p.utm_term === 'pulizia a fondo', JSON.stringify(r));
		check('href del link ripristinato dopo il click', r.after === r.clicked, r.after);

		// 2. Link con query propria: si conserva e si aggiungono gli UTM.
		r = await scenario(withQuery(PAGE, UTM), { consent: { stats: true }, before: inject('https://app.trovapulizie.com/searcher-signup?email=a%40b.it'), act: async ev => parse(await ev(clickApp('#qa-app'))) });
		p = params(r.navigated[0]);
		check('query del link conservata (email) + UTM', p.email === 'a@b.it' && p.utm_source === 'google', JSON.stringify(r));

		// 3. Link che ha gia' un utm_*: non si tocca.
		r = await scenario(withQuery(PAGE, UTM), { consent: { stats: true }, before: inject('https://app.trovapulizie.com/home?utm_campaign=keep'), act: async ev => parse(await ev(clickApp('#qa-app'))) });
		check('link con utm_* proprio: invariato', r.navigated[0] === 'https://app.trovapulizie.com/home?utm_campaign=keep', JSON.stringify(r));

		// 4. Senza consenso statistiche (solo marketing), e senza nessuna scelta: invariato.
		r = await scenario(withQuery(PAGE, UTM), { consent: { stats: false, ads: true }, act: async ev => parse(await ev(clickApp())) });
		check('stats=false: nessun utm_*', r.navigated.length && !hasUtm(r.navigated[0]), JSON.stringify(r));
		r = await scenario(withQuery(PAGE, UTM), { act: async ev => parse(await ev(clickApp())) });
		check('nessuna scelta: nessun utm_*', r.navigated.length && !hasUtm(r.navigated[0]), JSON.stringify(r));

		// 5. Consenso dato a meta' visita (evento del banner): decide il click.
		r = await scenario(withQuery(PAGE, UTM), { act: async ev => parse(await ev(`(function(){
			document.cookie = 'tp_consent=' + encodeURIComponent(JSON.stringify({ v: 1, ts: Date.now(), stats: true, ads: false })) + '; path=/';
			document.dispatchEvent(new CustomEvent('tp:consent', { detail: { stats: true, ads: false } }));
			return ${clickApp()};
		})()`)) });
		check('consenso dato dopo l\'atterraggio: UTM al click', params(r.navigated[0]).utm_source === 'google', JSON.stringify(r));

		// 6. Visita senza UTM: invariato.
		r = await scenario(PAGE, { consent: { stats: true }, act: async ev => parse(await ev(clickApp())) });
		check('visita senza UTM: nessun utm_*', r.navigated.length && !hasUtm(r.navigated[0]), JSON.stringify(r));

		// 7. tp_utm corrotto e pagina senza query: nessuna eccezione, navigazione invariata.
		r = await scenario(PAGE, { consent: { stats: true }, before: `sessionStorage.setItem('tp_utm', '{rotto')`, act: async ev => parse(await ev(clickApp())) });
		check('tp_utm non JSON: navigazione senza utm_*', r.navigated.length && !hasUtm(r.navigated[0]), JSON.stringify(r));

		// 8. UTM salvati in tp_utm da una pagina precedente della visita: valgono anche senza query.
		r = await scenario(withQuery(PAGE, UTM), { consent: { stats: true }, act: async (ev, sleep) => {
			await ev(`location.href = ${JSON.stringify(PAGE)}`);
			await sleep(3500);
			return parse(await ev(clickApp()));
		} });
		check('UTM della pagina d\'arrivo validi sulle pagine successive', params(r.navigated[0]).utm_term === 'pulizia a fondo', JSON.stringify(r));

		// 9. tpGetUtm invariato (lead-form.js), anche con content e term nella visita.
		r = await scenario(withQuery(PAGE, UTM), { act: async ev => parse(await ev(`JSON.stringify({ u: window.tpGetUtm && window.tpGetUtm() })`)) });
		check('tpGetUtm: solo utmSource, utmMedium, utmCampaign', r.u && Object.keys(r.u).sort().join(',') === 'utmCampaign,utmMedium,utmSource' && r.u.utmSource === 'google', JSON.stringify(r.u));
	}

	let ko = 0;
	checks.forEach(c => { console.log((c[1] ? 'OK  ' : 'KO  ') + c[0] + '  [' + String(c[2]).slice(0, 300) + ']'); if (!c[1]) ko++; });
	console.log(ko ? 'ESITO: ' + ko + ' controlli falliti' : 'ESITO: verde');
	if (ko) process.exitCode = 1;
})().catch(e => { console.error('ERR', e); process.exit(1); });
