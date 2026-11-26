// QA della survey della serata 25/11 (page-serata.php): percorso completo a 390 e 1280 px, nome dal link, domanda 4b
// condizionata, opzioni esclusive, "Altro" obbligatorio, invio e schermata finale; prove dell'endpoint (honeypot, nonce,
// tempo minimo, campi non validi); con "admin" anche l'elenco in Strumenti > Survey serata 25/11 e l'export CSV.
// v0.3: logo, nome e recapiti in fondo (0.87.4), id del Contatto dal link (?c=), telefono o email (almeno uno) validati, tre voti, domanda di approfondimento solo con
// investimento "No", video nella schermata finale solo con "Vorrei ricevere materiale" (serve l'opzione
// tp_serata_video_url impostata sul sito provato).
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
	// A: link personale, telefono, recapito non valido poi corretto, "Altro" obbligatorio, investimento "No" (compare la
	// domanda di approfondimento), materiale -> video nella schermata finale.
	await run('A', 390, PAGE + '?n=Mario%20Rossi&c=recQAQAQAQAQAQAQA', async (c) => {
		await expect(c, /II tappa del Wine Venture Club\?/);
		ok(await c.ev(`(function(){var i=document.querySelector('.sv__logo img');return !!i&&i.complete&&i.naturalWidth>0&&i.alt==='Trovapulizie'})()`), `${c.label}: logo del marchio caricato`);
		ok(/Ci vogliono solo 2 minuti/.test(await c.ev(`document.querySelector('.sv__body').textContent`)), `${c.label}: testo di benvenuto`);
		await c.shot(`${outDir}/A-0-benvenuto.png`);
		await press(c);
		await expect(c, /piaciuta la serata/, '1 / 9');
		ok(await c.ev(JS.scaleRow), `${c.label}: scala 1-5 su una riga`);
		ok(/Per niente/.test(await c.ev(`document.querySelector('.sv__scale-legend').textContent`)) && /Moltissimo/.test(await c.ev(`document.querySelector('.sv__scale-legend').textContent`)), `${c.label}: legenda Per niente / Moltissimo`);
		await c.shot(`${outDir}/A-1-voto.png`);
		await pick(c, '5');
		await expect(c, /piace il progetto/, '2 / 9');
		await pick(c, '4');
		await expect(c, /chiaro il modello/, '3 / 9');
		await pick(c, '3');
		await expect(c, /Aiutaci a migliorare/, '4 / 9');
		await c.ev(JS.type('.sv__input', 'Come guadagnate?'));
		await press(c);
		await expect(c, /più interessante/, '5 / 9');
		await pick(c, 'Altro');
		await press(c);
		ok(/Scrivi qualcosa/.test(await c.ev(JS.error)), `${c.label}: "Altro" vuoto bloccato`);
		await c.ev(JS.type('.sv__other .sv__input', 'Il team'));
		await press(c);
		await expect(c, /investimento in Trovapulizie/, '6 / 9');
		await c.shot(`${outDir}/A-6-investimento.png`);
		await pick(c, 'No, non fa per me in questa fase');
		await expect(c, /servirebbe vedere o chiarire/, '6 / 9');
		await c.ev(JS.type('.sv__input', 'I numeri del primo anno'));
		await press(c);
		await expect(c, /E adesso\?/, '7 / 9');
		await pick(c, 'Vorrei ricevere materiale (es. pitch deck, one-pager, link al video)');
		await c.shot(`${outDir}/A-7-adesso.png`);
		await expect(c, /Nome e cognome/, '8 / 9');
		ok((await c.ev(JS.value('.sv__input'))) === 'Mario Rossi', `${c.label}: nome gia' compilato dal link`);
		await press(c);
		await expect(c, /Come ti ricontattiamo/, '9 / 9');
		ok(await c.ev(`document.querySelector('input[name=telefono]').type==='tel'&&document.querySelector('input[name=email]').type==='email'`), `${c.label}: campi telefono ed email sulla stessa schermata`);
		ok((await c.ev(JS.cta)) === 'Invia', `${c.label}: ultima schermata (recapiti) con "Invia"`);
		ok(await c.ev(`!!document.querySelector('.sv__note a[href$="/privacy/"]')`), `${c.label}: link all'informativa sotto Invia`);
		await press(c);
		ok(/almeno il numero di telefono o/.test(await c.ev(JS.error)), `${c.label}: senza telefono ne' email bloccato`);
		await c.ev(JS.type('input[name=telefono]', '12ab'));
		await press(c);
		ok(/numero di telefono non sembra valido/.test(await c.ev(JS.error)), `${c.label}: telefono non valido bloccato`);
		await c.ev(JS.type('input[name=telefono]', '+39 333 123 4567'));
		await c.ev(JS.type('input[name=email]', 'mario@'));
		await press(c);
		ok(/email non sembra valida/.test(await c.ev(JS.error)), `${c.label}: email non valida bloccata`);
		await c.ev(JS.type('input[name=email]', 'mario.rossi@example.com'));
		await c.shot(`${outDir}/A-9-recapiti.png`);
		await press(c);
		ok(await wait(c.ev, `/Grazie, Mario\\./.test(document.querySelector('.sv__title')&&document.querySelector('.sv__title').textContent)`, 20000), `${c.label}: schermata finale "Grazie, Mario."`);
		ok(await c.ev(`document.querySelector('[data-sv-top]').hidden`), `${c.label}: nessuna barra nella schermata finale`);
		ok(await c.ev(`(function(){var v=document.querySelector('.sv__video video');return !!v&&v.preload==='none'&&v.controls&&!!v.getAttribute('src')})()`), `${c.label}: con "materiale" video nella schermata finale, scaricato solo al play`);
		await c.shot(`${outDir}/A-10-grazie.png`);
	});

	// B: senza link personale, desktop, solo email, investimento "Forse" (niente approfondimento), niente video.
	await run('B', 1280, PAGE, async (c) => {
		await press(c);
		await pick(c, '4'); await pick(c, '5'); await pick(c, '5');
		await press(c); // Salta "migliorare"
		await pick(c, "L'intero progetto");
		await expect(c, /investimento in Trovapulizie/, '6 / 9');
		await pick(c, 'Forse, vorrei prima approfondire');
		await expect(c, /E adesso\?/, '7 / 9');
		await pick(c, 'Restiamo in contatto');
		await expect(c, /Nome e cognome/, '8 / 9');
		ok((await c.ev(JS.value('.sv__input'))) === '', `${c.label}: senza ?n= il nome e' vuoto`);
		await press(c);
		ok(/Serve una risposta/.test(await c.ev(JS.error)), `${c.label}: nome obbligatorio`);
		await c.ev(JS.type('.sv__input', 'Laura Bianchi'));
		await press(c);
		await expect(c, /Come ti ricontattiamo/, '9 / 9');
		await c.ev(JS.type('input[name=email]', 'laura.bianchi@example.com'));
		await press(c); // solo email: basta uno dei due
		ok(await wait(c.ev, `/Grazie, Laura\\./.test(document.querySelector('.sv__title')&&document.querySelector('.sv__title').textContent)`, 20000), `${c.label}: schermata finale "Grazie, Laura."`);
		ok(await c.ev(`!document.querySelector('.sv__video')`), `${c.label}: senza "materiale" nessun video`);
	});

	// C: indietro e prove dell'endpoint dalla pagina (stessa origine).
	await run('C', 390, PAGE + '?n=Prova%20Endpoint', async (c) => {
		await press(c); await pick(c, '3');
		ok((await c.ev(`document.querySelector('[data-sv-back]').click(),document.querySelector('.sv__title').textContent`)).includes('piaciuta la serata'), `${c.label}: indietro torna al voto`);
		ok(await c.ev(`document.querySelector('.sv__scale-opt.is-on').textContent==='3'`), `${c.label}: il voto dato resta selezionato`);
		const cfg = `JSON.parse(document.querySelector('[data-serata]').getAttribute('data-config'))`;
		const post = body => `(function(){var c=${cfg};var b=Object.assign({nonce:c.nonce,ts:c.ts,sig:c.sig,name:'QA',telefono:'3331234567',serata:4,progetto:4,chiaro:4,interesse:'app',investimento:'forse',adesso:'prossima'},${body});return fetch(c.endpoint,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(b)}).then(function(r){return r.json().then(function(j){return r.status+' '+(j.code||'')+' '+((j.data&&j.data.fields)||[]).join(',')})})})()`;
		ok((await c.ev(post(`{website:'x'}`))).startsWith('200'), `${c.label}: honeypot = 200 finto`);
		ok((await c.ev(post(`{nonce:'x'}`))).startsWith('403 tp_survey_expired'), `${c.label}: nonce errato = 403`);
		ok((await c.ev(post(`{}`))).startsWith('429 tp_survey_fast'), `${c.label}: invio troppo veloce = 429`);
		await sleep(12500);
		const bad = await c.ev(post(`{telefono:'abc',email:'x@',serata:9,interesse:'altro',investimento:'boh',adesso:'x'}`));
		ok(/^400 tp_survey_invalid .*telefono.*email.*serata.*investimento.*adesso.*interesse_altro/.test(bad), `${c.label}: campi non validi = 400 (${bad})`);
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
			ok(/I numeri del primo anno/.test(txt) && /Come guadagnate\?/.test(txt) && /\+39 333 123 4567/.test(txt) && /mario\.rossi@example\.com/.test(txt), `${c.label}: testi liberi e recapito visibili`);
			ok(/No, non fa per me in questa fase/.test(txt) && /Restiamo in contatto/.test(txt), `${c.label}: etichette leggibili delle scelte`);
			ok(/recQAQAQAQAQAQAQA/.test(txt), `${c.label}: id del Contatto dal link salvato`);
			await c.shot(`${outDir}/admin-elenco.png`);
			const href = await c.ev(`document.querySelector('.page-title-action').href`);
			const csv = await c.ev(`fetch(${JSON.stringify('')}+document.querySelector('.page-title-action').href,{credentials:'same-origin'}).then(function(r){return r.text()})`);
			ok(href.includes('tp_serata_csv') && /Nome/.test(csv) && /Mario Rossi/.test(csv) && /Vorrei ricevere materiale/.test(csv), `${c.label}: export CSV con intestazioni ed etichette`);
		});
	}

	console.log(fails ? `\n${fails} controlli falliti` : '\ntutti i controlli ok');
	process.exit(fails ? 1 : 0);
})();
