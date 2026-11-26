// QA della survey della serata 25/11 (page-serata.php): percorso completo a 390 e 1280 px, nome dal link, domanda 4b
// condizionata, opzioni esclusive, "Altro" obbligatorio, invio e schermata finale; prove dell'endpoint (honeypot, nonce,
// tempo minimo, campi non validi); con "admin" anche l'elenco in Strumenti > Survey serata 25/11 e l'export CSV.
// v0.2: logo, id del Contatto dal link (?c=), video nella schermata finale solo a chi lo sceglie nella domanda 6 (serve
// l'opzione tp_serata_video_url impostata sul sito provato).
// Uso: node tools/qa-serata.js <chrome-bin> <base-url> <out-dir> [admin]
//   admin = login con admin/password (WordPress Playground). Sullo slot di produzione NON lanciare le prove di scrittura
//   senza cancellare subito dopo le risposte di prova dall'admin.
// Ogni run usa un X-Forwarded-For diverso per il limite orario (vale solo se REMOTE_ADDR e' privato, come in Playground).
const { withChrome, sleep } = require('./lib/cdp');
const fs = require('fs');

const [chrome, base, outDir, withAdmin] = [process.argv[2], (process.argv[3] || '').replace(/\/$/, ''), process.argv[4] || '/tmp', process.argv[5]];
if (!chrome || !base) { console.log('uso: node tools/qa-serata.js <chrome> <base-url> <out-dir> [admin]'); process.exit(2); }
fs.mkdirSync(outDir, { recursive: true });
const PAGE = base + '/serata-25-11/';
let fails = 0, port = 9660, runN = 0;
const ok = (c, msg) => { if (!c) fails++; console.log((c ? 'ok   ' : 'FAIL ') + msg); };
const stamp = Date.now() % 100000;

const JS = {
	opt: t => `(function(){var b=[].slice.call(document.querySelectorAll('.sv__opt,.sv__scale-opt')).filter(function(x){return x.textContent.trim()===${JSON.stringify(t)}})[0]; if(!b) return false; b.click(); return true;})()`,
	on: t => `[].slice.call(document.querySelectorAll('.sv__opt.is-on')).map(function(x){return x.textContent.trim()}).join('|')`,
	title: `(function(){var h=document.querySelector('.sv__title');return h?h.textContent:''})()`,
	count: `document.querySelector('[data-sv-count]').textContent`,
	cta: `(function(){var b=document.querySelector('.sv__cta');return b?b.textContent.trim():''})()`,
	press: `(function(){var b=document.querySelector('.sv__cta'); if(!b) return 'missing'; if(b.disabled) return 'disabled'; b.click(); return 'clicked';})()`,
	value: sel => `(function(){var i=document.querySelector(${JSON.stringify(sel)});return i?i.value:null})()`,
	type: (sel, v) => `(function(){var i=document.querySelector(${JSON.stringify(sel)}); i.focus(); i.value=${JSON.stringify(v)}; i.dispatchEvent(new Event('input',{bubbles:true})); return true;})()`,
	error: `(function(){var e=document.querySelector('.sv__error');return e&&!e.hidden?e.textContent:''})()`,
	overflow: `document.documentElement.scrollWidth <= window.innerWidth + 1`,
	scaleRow: `(function(){var r=[].slice.call(document.querySelectorAll('.sv__scale-opt')).map(function(b){return Math.round(b.getBoundingClientRect().top)});return r.length===5&&r.every(function(t){return t===r[0]})})()`,
};

async function wait(ev, expr, ms) { const t = Date.now(); while (Date.now() - t < (ms || 4000)) { if (await ev(expr)) return true; await sleep(100); } return false; }

async function run(name, width, url, steps) {
	runN++;
	const xff = `10.${stamp % 250}.${runN}.${width % 250}`;
	await withChrome(chrome, url, { width, height: width < 500 ? 844 : 900, mobile: width < 500, port: port++, profile: '/tmp/qa-serata-profile-' + process.pid + '-' + runN }, async (c) => {
		await c.send('Network.enable');
		await c.send('Network.setExtraHTTPHeaders', { headers: { 'X-Forwarded-For': xff } });
		await c.send('Page.navigate', { url });
		await sleep(2500);
		await wait(c.ev, `!!document.querySelector('.sv__cta')`, 8000);
		const label = `${name}@${width}`;
		await steps(Object.assign({}, c, { label }));
		ok(await c.ev(JS.overflow), `${label}: nessuno scroll orizzontale`);
	});
}

async function expect(c, re, count) {
	const t = await c.ev(JS.title);
	ok(re.test(t), `${c.label}: schermata "${t.slice(0, 60)}"`);
	if (count !== undefined) { const n = await c.ev(JS.count); ok(n === count, `${c.label}: etichetta "${count}" (trovata "${n}")`); }
}
async function pick(c, t, wait) { ok(await c.ev(JS.opt(t)), `${c.label}: tocco "${t}"`); await sleep(wait === undefined ? 420 : wait); }
async function press(c) { const l = await c.ev(JS.cta); ok((await c.ev(JS.press)) === 'clicked', `${c.label}: bottone "${l}"`); await sleep(350); }

(async () => {
	// A: link personale, telefono, modello poco chiaro (compare 4b), opzioni esclusive, "Altro" obbligatorio.
	await run('A', 390, PAGE + '?n=Mario%20Rossi&c=recQAQAQAQAQAQAQA', async (c) => {
		await expect(c, /Com'è andata la serata del 25\?/);
		ok(await c.ev(`(function(){var i=document.querySelector('.sv__logo img');return !!i&&i.complete&&i.naturalWidth>0&&i.alt==='Trovapulizie'})()`), `${c.label}: logo del marchio caricato`);
		ok(/mercoledì sera/.test(await c.ev(`document.querySelector('.sv__body').textContent`)), `${c.label}: benvenuto con "mercoledì sera"`);
		await c.shot(`${outDir}/A-0-benvenuto.png`);
		await press(c);
		await expect(c, /Nome e cognome/, '1 / 7');
		ok((await c.ev(JS.value('.sv__input'))) === 'Mario Rossi', `${c.label}: nome gia' compilato dal link`);
		await press(c);
		await expect(c, /nel complesso/, '2 / 7');
		ok(await c.ev(JS.scaleRow), `${c.label}: scala 1-5 su una riga`);
		await c.shot(`${outDir}/A-2-voto.png`);
		await pick(c, '4');
		await expect(c, /rimasto in mente/, '3 / 7');
		ok((await c.ev(JS.cta)) === 'Salta', `${c.label}: domanda facoltativa vuota = "Salta"`);
		await c.ev(JS.type('.sv__input', 'Il posto vuoto delle pulizie'));
		ok((await c.ev(JS.cta)) === 'Avanti', `${c.label}: con testo il bottone diventa "Avanti"`);
		await press(c);
		await expect(c, /modello di Trovapulizie/, '4 / 7');
		await pick(c, 'Poco chiaro');
		await expect(c, /Se hai un dubbio/, '4 / 7');
		await c.ev(JS.type('.sv__input', 'Come guadagnate?'));
		await press(c);
		await expect(c, /servizi di pulizia/, '5 / 7');
		await pick(c, 'Sì, per casa', 150);
		await pick(c, 'No', 150);
		ok((await c.ev(JS.on())) === 'No', `${c.label}: "No" toglie le altre risposte`);
		await pick(c, "Sì, per un'azienda o un ufficio", 150);
		ok((await c.ev(JS.on())) === "Sì, per un'azienda o un ufficio", `${c.label}: un'altra risposta toglie "No"`);
		await press(c);
		await expect(c, /approfondire/, '6 / 7');
		await pick(c, 'Il team', 150);
		await pick(c, 'Come partecipare al progetto', 150);
		await pick(c, 'Rivedere il video della serata', 150);
		await press(c);
		await expect(c, /E adesso\?/, '7 / 7');
		ok((await c.ev(JS.cta)) === 'Invia', `${c.label}: ultima domanda con "Invia"`);
		ok(await c.ev(`!!document.querySelector('.sv__note a[href$="/privacy/"]')`), `${c.label}: link all'informativa sotto Invia`);
		await pick(c, 'Altro');
		ok(await c.ev(`!document.querySelector('.sv__other').hidden`), `${c.label}: "Altro" apre il campo`);
		await press(c);
		ok(/Scrivi qualcosa/.test(await c.ev(JS.error)), `${c.label}: "Altro" vuoto bloccato`);
		await c.shot(`${outDir}/A-7-altro-vuoto.png`);
		await c.ev(JS.type('.sv__other .sv__input', 'Ci vediamo alla prossima serata'));
		await press(c);
		ok(await wait(c.ev, `/Grazie, Mario\\./.test(document.querySelector('.sv__title')&&document.querySelector('.sv__title').textContent)`, 20000), `${c.label}: schermata finale "Grazie, Mario."`);
		ok(await c.ev(`document.querySelector('[data-sv-top]').hidden`), `${c.label}: nessuna barra nella schermata finale`);
		ok(await c.ev(`(function(){var v=document.querySelector('.sv__video video');return !!v&&v.preload==='none'&&v.controls&&!!v.getAttribute('src')})()`), `${c.label}: video nella schermata finale, scaricato solo al play`);
		await c.shot(`${outDir}/A-8-grazie.png`);
	});

	// B: senza link personale, desktop, modello chiaro (4b saltata), facoltative saltate, scelta singola finale.
	await run('B', 1280, PAGE, async (c) => {
		await press(c);
		ok((await c.ev(JS.value('.sv__input'))) === '', `${c.label}: senza ?n= il nome e' vuoto`);
		await press(c);
		ok(/Serve una risposta/.test(await c.ev(JS.error)), `${c.label}: nome obbligatorio`);
		await c.ev(JS.type('.sv__input', 'Laura Bianchi'));
		await press(c);
		await pick(c, '5');
		await press(c); // Salta
		await pick(c, 'Sì, chiaro');
		await expect(c, /servizi di pulizia/, '5 / 7');
		await press(c); // Salta
		await press(c); // Salta
		await expect(c, /E adesso\?/, '7 / 7');
		await press(c);
		ok(/Serve una risposta/.test(await c.ev(JS.error)), `${c.label}: ultima domanda obbligatoria`);
		await pick(c, 'Sentiamoci');
		ok(/E adesso\?/.test(await c.ev(JS.title)), `${c.label}: la scelta finale non invia da sola`);
		await c.shot(`${outDir}/B-7-adesso.png`);
		await press(c);
		ok(await wait(c.ev, `/Grazie, Laura\\./.test(document.querySelector('.sv__title')&&document.querySelector('.sv__title').textContent)`, 20000), `${c.label}: schermata finale "Grazie, Laura."`);
		ok(await c.ev(`!document.querySelector('.sv__video')`), `${c.label}: senza la scelta del video nessun video`);
	});

	// C: indietro e prove dell'endpoint dalla pagina (stessa origine).
	await run('C', 390, PAGE + '?n=Prova%20Endpoint', async (c) => {
		await press(c); await press(c); await pick(c, '3');
		ok((await c.ev(`document.querySelector('[data-sv-back]').click(),document.querySelector('.sv__title').textContent`)).includes('nel complesso'), `${c.label}: indietro torna al voto`);
		ok(await c.ev(`document.querySelector('.sv__scale-opt.is-on').textContent==='3'`), `${c.label}: il voto dato resta selezionato`);
		const cfg = `JSON.parse(document.querySelector('[data-serata]').getAttribute('data-config'))`;
		const post = body => `(function(){var c=${cfg};var b=Object.assign({nonce:c.nonce,ts:c.ts,sig:c.sig,name:'QA',rating:4,chiarezza:'chiaro',adesso:'piacere',servizi:[],approfondire:[]},${body});return fetch(c.endpoint,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(b)}).then(function(r){return r.json().then(function(j){return r.status+' '+(j.code||'')+' '+((j.data&&j.data.fields)||[]).join(',')})})})()`;
		ok((await c.ev(post(`{website:'x'}`))).startsWith('200'), `${c.label}: honeypot = 200 finto`);
		ok((await c.ev(post(`{nonce:'x'}`))).startsWith('403 tp_survey_expired'), `${c.label}: nonce errato = 403`);
		ok((await c.ev(post(`{}`))).startsWith('429 tp_survey_fast'), `${c.label}: invio troppo veloce = 429`);
		await sleep(12500);
		const bad = await c.ev(post(`{rating:9,chiarezza:'boh',adesso:'altro',servizi:['no','casa']}`));
		ok(/^400 tp_survey_invalid .*rating.*chiarezza.*adesso_altro.*servizi/.test(bad), `${c.label}: campi non validi = 400 (${bad})`);
	});

	if (withAdmin) {
		await run('admin', 1280, base + '/wp-login.php', async (c) => {
			await c.ev(`document.getElementById('user_login').value='admin';document.getElementById('user_pass').value='password';document.getElementById('wp-submit').click();true`);
			await sleep(4000);
			await c.send('Page.navigate', { url: base + '/wp-admin/tools.php?page=tp-serata' });
			await sleep(4000);
			const txt = await c.ev(`document.querySelector('.wrap')?document.querySelector('.wrap').innerText:''`);
			ok(/Survey serata 25\/11/.test(txt), `${c.label}: pagina in Strumenti`);
			ok(/Mario Rossi/.test(txt) && /Laura Bianchi/.test(txt), `${c.label}: le due risposte nell'elenco`);
			ok(/Ci vediamo alla prossima serata/.test(txt) && /Come guadagnate\?/.test(txt), `${c.label}: testo di "Altro" e dubbio visibili`);
			ok(/Il team, Come partecipare al progetto, Rivedere il video della serata/.test(txt), `${c.label}: etichette leggibili delle scelte multiple`);
			ok(/recQAQAQAQAQAQAQA/.test(txt), `${c.label}: id del Contatto dal link salvato`);
			await c.shot(`${outDir}/admin-elenco.png`);
			const href = await c.ev(`document.querySelector('.page-title-action').href`);
			const csv = await c.ev(`fetch(${JSON.stringify('')}+document.querySelector('.page-title-action').href,{credentials:'same-origin'}).then(function(r){return r.text()})`);
			ok(href.includes('tp_serata_csv') && /Nome/.test(csv) && /Mario Rossi/.test(csv) && /Sentiamoci/.test(csv), `${c.label}: export CSV con intestazioni ed etichette`);
		});
	}

	console.log(fails ? `\n${fails} controlli falliti` : '\ntutti i controlli ok');
	process.exit(fails ? 1 : 0);
})();
