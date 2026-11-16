// QA del campo cellulare del form lead sgrosso (normalizzazione, errore specifico, validazione all'uscita dal campo):
// node tools/qa-lead-phone.js <chrome-bin> <url> [larghezza=390] [cartella-screenshot]
// Ogni chiamata a */api/leads* e' intercettata via CDP e risolta con una risposta simulata: nessun lead reale.
// Per i numeri accettati controlla anche il valore che parte verso il backend (prefisso + sole cifre, es. +393331234568).
// Esce con 1 se un controllo fallisce.
const { withChrome } = require('./lib/cdp');
const fs = require('fs');
const [BIN, URL, WIDTH_ARG, SHOTS] = process.argv.slice(2, 6);
const WIDTH = parseInt(WIDTH_ARG, 10) || 390;
const calls = [];
const checks = [];
function check(name, ok, detail) { checks.push([name, ok]); console.log((ok ? 'OK  ' : 'KO  ') + name + (detail ? '  [' + String(detail).slice(0, 260) + ']' : '')); }

const ACCEPT = [['333 123 4568', '+393331234568'], ['+39 333 123 4568', '+393331234568'], ['0039 3331234568', '+393331234568'], ['333-1234568', '+393331234568'], ['(333) 123.4568', '+393331234568'], ['347 123 4568', '+393471234568']];
const REJECT = ['06 9151 6075', '0669151234', '12345', '+39 06 9151 6075', '333 333 3333'];
const MSG = /^Inserisci un cellulare italiano \(inizia con 3\): ti scriviamo su WhatsApp\. Se hai solo un fisso, chiamaci al 06 9151 6075$/;

withChrome(BIN, URL + (URL.indexOf('?') === -1 ? '?' : '&') + 'qa=' + Date.now(), { port: 9351, width: WIDTH, mobile: WIDTH < 800, height: WIDTH < 800 ? 844 : 980 }, async ({ send, ev, shot, sleep, on }) => {
	on('Fetch.requestPaused', (p) => {
		const req = p.request;
		let body = {}; try { body = req.postData ? JSON.parse(req.postData) : {}; } catch (e) {}
		calls.push({ method: req.method, url: req.url, body });
		const headers = [{ name: 'Content-Type', value: 'application/json' }, { name: 'Access-Control-Allow-Origin', value: '*' }, { name: 'Access-Control-Allow-Methods', value: 'GET,POST,PATCH,OPTIONS' }, { name: 'Access-Control-Allow-Headers', value: 'Content-Type' }];
		const reply = (status, json) => send('Fetch.fulfillRequest', { requestId: p.requestId, responseCode: status, responseHeaders: headers, body: Buffer.from(JSON.stringify(json)).toString('base64') });
		if (req.method === 'OPTIONS') return reply(204, {});
		return reply(201, { contattoId: 'recCONT1', token: 'tok-cont', opportunitaId: 'recOPP1' });
	});
	await send('Fetch.enable', { patterns: [{ urlPattern: '*/api/leads*', requestStage: 'Request' }] });
	const F = "document.getElementById('lead-hero')";
	const setf = (n, v) => ev(`(function(){ var e=${F}.querySelector('[name="${n}"]'); e.value=${JSON.stringify(v)}; e.dispatchEvent(new Event('input',{bubbles:true})); return e.value; })()`);
	const err = () => ev(`${F}.querySelector('[data-error-for="cellulare"]').textContent`);
	const invalid = () => ev(`${F}.querySelector('[data-field="cellulare"]').classList.contains('is-invalid')`);
	const blur = () => ev(`(function(){ var e=${F}.querySelector('[name="cellulare"]'); e.focus(); e.blur(); return 1; })()`);
	const toStep2 = async () => { await setf('mq', '80'); await setf('indirizzo', 'Via Tuscolana 118, Roma'); await ev(`${F}.requestSubmit()`); await sleep(500); };
	const load = async () => { calls.length = 0; await send('Page.navigate', { url: URL + (URL.indexOf('?') === -1 ? '?' : '&') + 'qa=' + Date.now() }); await sleep(3200); await toStep2(); };

	await load();
	check('passo 2 aperto', (await ev(`${F}.querySelector('[data-step]:not([hidden])').getAttribute('data-step')`)) === '2');
	const hint = await ev(`(function(){ var h=${F}.querySelector('.tp-field__hint'); var i=${F}.querySelector('[name=cellulare]'); return JSON.stringify({ t: h ? h.textContent : null, vis: !!(h && h.offsetParent), desc: i.getAttribute('aria-describedby'), id: h ? h.id : null }); })()`).then(JSON.parse);
	check('suggerimento sotto il campo, visibile e collegato con aria-describedby', hint.t && hint.vis && hint.desc === hint.id, JSON.stringify(hint));
	if (SHOTS) { fs.mkdirSync(SHOTS, { recursive: true }); await ev(`document.querySelector('#form').scrollIntoView()`); await sleep(300); await shot(SHOTS + '/passo2-suggerimento-' + WIDTH + '.png'); }

	// Rifiutati: l'errore compare all'uscita dal campo, con il messaggio specifico e il link tel:.
	for (const v of REJECT) {
		await setf('cellulare', ''); await blur();
		await setf('cellulare', v); await blur(); await sleep(100);
		const e = await err();
		const link = await ev(`(function(){ var a=${F}.querySelector('[data-error-for="cellulare"] a'); return a ? a.getAttribute('href') + '|' + a.textContent : ''; })()`);
		check('rifiutato all uscita dal campo: "' + v + '"', MSG.test(e) && (await invalid()) && link === 'tel:+390691516075|06 9151 6075', e + ' / ' + link);
		const hv = await ev(`!!${F}.querySelector('.tp-field__hint').offsetParent`);
		if (v === REJECT[0]) check('con l errore il suggerimento si nasconde', hv === false, hv);
		if (v === REJECT[0] && SHOTS) { await ev(`document.querySelector('#form').scrollIntoView()`); await sleep(300); await shot(SHOTS + '/passo2-errore-' + WIDTH + '.png'); }
	}
	// L'errore sparisce appena il valore diventa valido, senza uscire dal campo.
	await setf('cellulare', '12345'); await blur();
	check('errore presente su "12345"', await invalid());
	await setf('cellulare', '333 123 4568');
	check('errore tolto appena il valore e valido (senza blur)', (await err()) === '' && !(await invalid()), await err());
	// Campo vuoto all'uscita: nessun errore (Tab attraverso il form).
	await setf('cellulare', ''); await blur();
	check('campo vuoto all uscita: nessun errore', (await err()) === '' && !(await invalid()), await err());
	// Cambio prefisso: errore generico per gli altri paesi.
	await setf('cellulare', '12345'); await blur();
	await ev(`(function(){ var s=${F}.querySelector('[name=prefisso]'); s.value='+44'; s.dispatchEvent(new Event('change',{bubbles:true})); })()`);
	check('prefisso diverso da +39: errore generico "Numero non valido"', (await err()) === 'Numero non valido', await err());

	// Accettati: nessun errore e valore inviato nel formato di sempre (prefisso + sole cifre).
	for (const [v, sent] of ACCEPT) {
		await load();
		await setf('nome', 'TEST QA'); await setf('cellulare', v); await blur(); await sleep(100);
		const okField = (await err()) === '' && !(await invalid());
		await ev(`${F}.querySelector('input[name=consentPrivacy]').checked = true`);
		await ev(`${F}.requestSubmit()`); await sleep(1200);
		const post = calls.filter(c => c.method === 'POST' && /\/api\/leads$/.test(c.url))[0];
		check('accettato: "' + v + '" -> telefono inviato ' + sent, okField && post && post.body.telefono === sent, post ? post.body.telefono : 'nessuna POST');
	}
	const failed = checks.filter(c => !c[1]).length;
	console.log(failed ? failed + ' controlli falliti su ' + checks.length : 'tutti i ' + checks.length + ' controlli ok');
	process.exitCode = failed ? 1 : 0;
});
