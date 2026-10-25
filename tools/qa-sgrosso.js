// QA del form lead in headless con API simulata (TICKET-40/50): node tools/qa-sgrosso.js <chrome-bin> <url>
// Intercetta via CDP (Fetch) ogni chiamata a */api/leads* e risponde come il backend (201 con id e token, 200 al
// PATCH): nessun lead reale, nessun CORS. Compila i tre passi del form hero, poi il calcolatore, e verifica i
// payload intercettati (campi, contactOnly, token, datiPulizia, note), la sequenza degli eventi dataLayer e la
// navigazione a /thank-you/ con sessionStorage tp_thankyou. Esce con 1 se un controllo fallisce.
const { withChrome } = require('./lib/cdp');
const [BIN, URL] = process.argv.slice(2, 4);

const calls = [];
const checks = [];
function check(name, ok, detail) { checks.push([name, ok]); console.log((ok ? 'OK  ' : 'KO  ') + name + (detail ? '  [' + String(detail).slice(0, 220) + ']' : '')); }

withChrome(BIN, URL, { port: 9347 }, async ({ send, ev, sleep, on }) => {
	on('Fetch.requestPaused', async (p) => {
		const req = p.request;
		let body = {};
		try { body = req.postData ? JSON.parse(req.postData) : {}; } catch (e) {}
		calls.push({ method: req.method, url: req.url, body });
		let status = 200, json = {};
		if (req.method === 'OPTIONS') { status = 204; }
		else if (/\/api\/leads\/[^/]+\/opportunita$/.test(req.url)) { status = 201; json = { opportunitaId: 'recOPP1', token: 'tok-opp' }; }
		else if (/\/api\/leads\/[^/]+$/.test(req.url) && req.method === 'PATCH') { status = 200; json = { ok: true }; }
		else if (/\/api\/leads$/.test(req.url)) { status = 201; json = body.contactOnly ? { contattoId: 'recCONT1', token: 'tok-cont' } : { ok: true, opportunitaId: 'recATOM1' }; }
		const headers = [
			{ name: 'Content-Type', value: 'application/json' },
			{ name: 'Access-Control-Allow-Origin', value: '*' },
			{ name: 'Access-Control-Allow-Methods', value: 'GET,POST,PATCH,OPTIONS' },
			{ name: 'Access-Control-Allow-Headers', value: 'Content-Type' }
		];
		await send('Fetch.fulfillRequest', { requestId: p.requestId, responseCode: status, responseHeaders: headers, body: Buffer.from(JSON.stringify(json)).toString('base64') });
	});
	await send('Fetch.enable', { patterns: [{ urlPattern: '*/api/leads*', requestStage: 'Request' }] });
	await sleep(2500);

	// Passo 1 con numero finto: deve fermarsi senza chiamate.
	await ev(`(function(){ var f=document.getElementById('lead-hero'); function set(n,v){ var e=f.querySelector('[name="'+n+'"]'); e.value=v; e.dispatchEvent(new Event('input',{bubbles:true})); }
		set('nome','TEST QA'); set('cellulare','3333333333'); set('email','qa@example.com'); set('indirizzo','Via Tuscolana 118, Roma');
		f.querySelector('input[name=consentPrivacy]').checked = true; f.requestSubmit(); return 'ok'; })()`);
	await sleep(600);
	const err1 = await ev(`document.querySelector('#lead-hero [data-error-for="cellulare"]').textContent`);
	check('numero ripetuto bloccato lato client', err1 === 'Numero non valido' && calls.filter(c => c.method !== 'OPTIONS').length === 0, err1);

	// Passo 1 valido.
	await ev(`(function(){ var f=document.getElementById('lead-hero'); var e=f.querySelector('[name=cellulare]'); e.value='347 123 4568'; e.dispatchEvent(new Event('input',{bubbles:true})); f.requestSubmit(); return 'ok'; })()`);
	await sleep(1500);
	const c1 = calls.find(c => c.method === 'POST' && /\/api\/leads$/.test(c.url));
	check('passo 1: POST /api/leads contactOnly', !!c1 && c1.body.contactOnly === true && c1.body.tipo === 'b2c' && c1.body.nome === 'TEST QA' && c1.body.telefono === '+393471234568' && c1.body.email === 'qa@example.com' && c1.body.zona === 'Via Tuscolana 118, Roma' && c1.body.consentPrivacy === true && c1.body.consentMarketing === false && c1.body.honeypot === '' && typeof c1.body.pageUrl === 'string' && c1.body.pageUrl.indexOf('?') === -1, JSON.stringify(c1 && c1.body));
	const s2visible = await ev(`!document.querySelector('#lead-hero [data-step="2"]').hidden && document.querySelector('#lead-hero [data-step-n]').textContent`);
	check('passo 2 visibile', s2visible === '2', s2visible);

	// Passo 2.
	await ev(`(function(){ var f=document.getElementById('lead-hero'); function set(n,v){ var e=f.querySelector('[name="'+n+'"]'); e.value=v; e.dispatchEvent(new Event('input',{bubbles:true})); }
		set('mq','85'); set('bagni','2'); f.querySelector('input[name=tipoAppartamento][value=Trilocale]').checked=true; f.querySelector('input[name=pavimento][value=Parquet]').checked=true; f.querySelector('input[name=arredata][value="In parte"]').checked=true;
		var r=f.querySelector('input[name=livelloSporcoIdx]'); r.value=3; r.dispatchEvent(new Event('input',{bubbles:true})); f.requestSubmit(); return 'ok'; })()`);
	await sleep(1500);
	const c2 = calls.find(c => c.method === 'POST' && /\/opportunita$/.test(c.url)); // il primo match sarebbe il preflight OPTIONS
	const d2 = c2 && c2.body.datiPulizia;
	check('passo 2: POST opportunita con token e datiPulizia', !!c2 && c2.body.token === 'tok-cont' && c2.body.tipo === 'b2c' && c2.body.superficieMq === '85' && c2.body.note === 'Post-ristrutturazione · Trilocale · 85mq (form non completato)' && d2 && d2.tipoPulizia === 'post-ristrutturazione' && d2.tipoAppartamento === 'Trilocale' && d2.mq === '85' && d2.pavimento === 'Parquet' && d2.bagni === '2' && d2.arredata === 'In parte' && d2.livello_sporco === 'pesante' && d2._step === 2 && d2._completo === false && !('urgenza' in d2), JSON.stringify(c2 && c2.body));

	// Passo 3.
	await ev(`(function(){ var f=document.getElementById('lead-hero'); f.querySelector('input[name=tipoPulizia][value=trasloco]').checked=true; f.querySelector('input[name=urgenza][value="Entro 48h"]').checked=true; f.requestSubmit(); return 'ok'; })()`);
	await sleep(2000);
	const c3 = calls.find(c => c.method === 'PATCH');
	const d3 = c3 && c3.body.datiPulizia;
	check('passo 3: PATCH finale', !!c3 && /\/api\/leads\/recOPP1$/.test(c3.url) && c3.body.token === 'tok-opp' && c3.body.step === 3 && c3.body.final === true && c3.body.note === 'Trasloco · Trilocale · 85mq' && d3 && d3.tipoPulizia === 'trasloco' && d3.urgenza === 'Entro 48h' && d3._step === 3 && d3._completo === true && !('foto' in c3.body), JSON.stringify(c3 && c3.body));
	const nav = await ev(`JSON.stringify({ path: location.pathname, ty: sessionStorage.getItem('tp_thankyou') })`);
	const navObj = JSON.parse(nav);
	check('navigazione a /thank-you/ con riepilogo', navObj.path === '/thank-you/' && !!navObj.ty && JSON.parse(navObj.ty).tipoCasa === 'Trasloco' && JSON.parse(navObj.ty).email === 'qa@example.com', nav);

	// Torno sulla landing per il calcolatore e gli eventi: il dataLayer e' per pagina, qui controllo solo il calcolatore.
	await send('Page.navigate', { url: URL });
	await sleep(3000);
	calls.length = 0;
	await ev(`(function(){ var f=document.querySelector('[data-lead-atomic]'); var e=f.querySelector('[name=email]'); e.value='qa-calc@example.com'; e.dispatchEvent(new Event('input',{bubbles:true})); f.querySelector('input[name=consentPrivacy]').checked=true; f.requestSubmit(); return 'ok'; })()`);
	await sleep(1500);
	const ca = calls.find(c => c.method === 'POST');
	check('calcolatore: POST atomico', !!ca && ca.body.tipo === 'b2c' && ca.body.email === 'qa-calc@example.com' && ca.body.zona === 'Non specificata (calcolatore)' && /^Calcolatore: 100mq · Post-ristrutturazione · extra: nessuno · stima 420-570€$/.test(ca.body.note) && ca.body.consentPrivacy === true && ca.body.consentMarketing === false && !ca.body.contactOnly, JSON.stringify(ca && ca.body));
	const nav2 = await ev(`location.pathname`);
	check('calcolatore: navigazione a /thank-you/', nav2 === '/thank-you/', nav2);

	// Eventi dataLayer: rifaccio solo il passo 1 e leggo il dataLayer prima della navigazione.
	await send('Page.navigate', { url: URL });
	await sleep(3000);
	const events = await ev(`(function(){ var f=document.getElementById('lead-hero'); function set(n,v){ var e=f.querySelector('[name="'+n+'"]'); e.value=v; e.dispatchEvent(new Event('input',{bubbles:true})); }
		set('nome','TEST QA'); set('cellulare','3471234568'); set('email','qa@example.com'); set('indirizzo','Via Tuscolana 118, Roma'); f.querySelector('input[name=consentPrivacy]').checked=true; f.requestSubmit();
		return new Promise(function(res){ setTimeout(function(){ res(JSON.stringify((window.dataLayer||[]).filter(function(x){ return x && /^preventivo_/.test(x.event||''); }))); }, 1500); }); })()`);
	const evs = JSON.parse(events);
	check('evento preventivo_sgrosso_step passo 1', evs.length === 1 && evs[0].event === 'preventivo_sgrosso_step' && evs[0].step === 1 && evs[0].step_name === 'contatto' && evs[0].landing === 'sgrosso-casa' && evs[0].cta_position === 'hero' && evs[0].tipoPulizia === 'post-ristrutturazione' && !('source' in evs[0]), events);

	const ko = checks.filter(c => !c[1]).length;
	console.log(ko ? 'ESITO: ' + ko + ' controlli falliti' : 'ESITO: verde');
	process.exitCode = ko ? 1 : 0;
}).catch(e => { console.error('ERR', e); process.exit(1); });
