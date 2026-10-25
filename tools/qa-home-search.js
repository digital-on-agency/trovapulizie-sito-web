// QA della barra di ricerca della Home: node tools/qa-home-search.js <chrome-bin> <url> [larghezza=1440] [out.png]
// Compila la barra e invia il form, intercettando con Fetch la navigazione verso app.trovapulizie.com (nessuna
// pagina dell'app aperta). Casi: servizio per privati -> /home o /results?address&service (Theme Options); servizio solo aziende ->
// /richiedi-preventivo?step=0&service&address; indirizzo vuoto -> nessun invio. Esce con 1 se un controllo fallisce.
const { withChrome } = require('./lib/cdp');
const [BIN, URL, W, OUT] = process.argv.slice(2, 6);
const width = parseInt(W || '1440', 10);
const checks = [];
const check = (n, ok, v) => checks.push([n, !!ok, v]);

withChrome(BIN, URL, { port: 9381, width, height: 900, mobile: width < 1024 }, async ({ send, ev, shot, sleep, on }) => {
	let richieste = [];
	on('Fetch.requestPaused', p => { richieste.push(p.request.url); send('Fetch.failRequest', { requestId: p.requestId, errorReason: 'Aborted' }); });
	await send('Fetch.enable', { patterns: [{ urlPattern: 'https://app.trovapulizie.com/*', requestStage: 'Request' }] });
	await sleep(3500);
	if (OUT) await shot(OUT);
	const invia = async (servizio, indirizzo) => {
		richieste = [];
		await ev(`(function(){ var f = document.querySelector('form[data-search-form]'); f.querySelector('select[name=service]').value = '${servizio}';
			f.querySelector('input[name=address]').value = ${JSON.stringify(indirizzo)}; f.querySelector('button[type=submit]').click(); })()`);
		await sleep(1500);
		const r = richieste[0] || null;
		await send('Page.navigate', { url: URL }); await sleep(3000);
		return r;
	};
	const r1 = await invia('pulizia_domestica', 'Via Tuscolana 118, Roma');
	// Destinazione dei privati da Theme Options (tp_search_target): /home (default) o /results.
	check('privati -> /home o /results con address e service', r1 && /\/(home|results)\?/.test(r1) && /address=Via\+Tuscolana\+118%2C\+Roma/.test(r1) && /service=pulizia_domestica/.test(r1) && !/step=/.test(r1), r1);
	const r2 = await invia('pulizia_uffici', 'Via Tuscolana 118, Roma');
	check('aziende -> /richiedi-preventivo con step=0', r2 && /\/richiedi-preventivo\?/.test(r2) && /step=0/.test(r2) && /service=pulizia_uffici/.test(r2) && /address=/.test(r2), r2);
	const r3 = await invia('pulizia_domestica', '');
	check('indirizzo vuoto -> nessun invio', r3 === null, r3);
	const opts = JSON.parse(await ev(`JSON.stringify([].map.call(document.querySelectorAll('form[data-search-form] option'), o => o.value + (o.hasAttribute('data-b2b') ? '*' : '')))`));
	check('menu con i servizi dell\'app, primo per privati', opts.length >= 6 && opts[0] === 'pulizia_domestica', opts);
	let ko = 0;
	checks.forEach(([n, ok, v]) => { if (!ok) ko++; console.log((ok ? 'OK  ' : 'KO  ') + n + '  ' + JSON.stringify(v)); });
	console.log(ko ? 'ESITO: ' + ko + ' controlli falliti' : 'ESITO: verde');
	process.exitCode = ko ? 1 : 0;
}).catch(e => { console.error(e); process.exit(1); });
