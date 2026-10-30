// QA della survey OnField v2 (page-survey.php): tutte le diramazioni A/B/C/D a 390 e 1280 px, misure delle opzioni,
// validazioni del contatto, salvataggio e prove dell'endpoint (honeypot, consenso, nonce, tempo minimo, rate limit).
// Uso: node tools/qa-survey.js <chrome-bin> <base-url> <out-dir> [admin]
//   admin = apre anche l'elenco admin e l'export CSV (serve una sessione di amministratore nel browser: WordPress
//   Playground con --login, oppure il cookie gia' presente nel profilo). Sullo slot di produzione NON lanciare le
//   prove di scrittura senza ripulire le risposte di prova dall'admin subito dopo.
// Per aggirare il rate limit fra una prova e l'altra ogni run usa un X-Forwarded-For diverso (vale solo se REMOTE_ADDR e'
// privato, come in Playground; su Hostinger il limite per IP resta quello vero).
const { withChrome, sleep } = require('./lib/cdp');
const fs = require('fs');

const [chrome, base, outDir, withAdmin] = [process.argv[2], (process.argv[3] || '').replace(/\/$/, ''), process.argv[4] || '/tmp', process.argv[5]];
if (!chrome || !base) { console.log('uso: node tools/qa-survey.js <chrome> <base-url> <out-dir> [admin]'); process.exit(2); }
fs.mkdirSync(outDir, { recursive: true });
const URL_SURVEY = base + '/survey-dubai/';
let fails = 0, port = 9560, runN = 0;
const ok = (c, msg) => { if (!c) fails++; console.log((c ? 'ok   ' : 'FAIL ') + msg); };
const stamp = Date.now() % 100000;

const JS = {
	click: t => `(function(){var b=[].slice.call(document.querySelectorAll('.sv__opt')).filter(function(x){return x.textContent.trim()===${JSON.stringify(t)}})[0]; if(!b) return false; b.click(); return true;})()`,
	title: `(function(){var h=document.querySelector('.sv__title');return h?h.textContent:''})()`,
	count: `document.querySelector('[data-sv-count]').textContent`,
	topVisible: `!document.querySelector('[data-sv-top]').hidden`,
	backVisible: `!document.querySelector('[data-sv-back]').hidden`,
	btn: t => `(function(){var b=[].slice.call(document.querySelectorAll('.sv__cta,.sv__link')).filter(function(x){return x.textContent.trim()===${JSON.stringify(t)}})[0]; if(!b) return 'missing'; if(b.disabled) return 'disabled'; b.click(); return 'clicked';})()`,
	has: t => `[].slice.call(document.querySelectorAll('button')).some(function(x){return x.textContent.trim()===${JSON.stringify(t)}})`,
	type: (sel, v) => `(function(){var i=document.querySelector(${JSON.stringify(sel)}); i.focus(); i.value=${JSON.stringify(v)}; i.dispatchEvent(new Event('input',{bubbles:true})); return true;})()`,
	check: sel => `(function(){var i=document.querySelector(${JSON.stringify(sel)}); if(i.checked!==true) i.click(); return i.checked;})()`,
	overflow: `document.documentElement.scrollWidth <= window.innerWidth + 1`,
	optsGeom: `(function(){var f=document.querySelector('.sv__frame').getBoundingClientRect(); return [].slice.call(document.querySelectorAll('.sv__opt')).map(function(b){var r=b.getBoundingClientRect(),cs=getComputedStyle(b);return {w:Math.round(r.width),h:Math.round(r.height),fw:Math.round(f.width),ta:cs.textAlign}})})()`,
};

async function wait(ev, expr, ms) { const t = Date.now(); while (Date.now() - t < (ms || 4000)) { if (await ev(expr)) return true; await sleep(100); } return false; }

async function scenario(name, width, steps) {
	runN++;
	const xff = `10.${stamp % 250}.${runN}.${width % 250}`;
	await withChrome(chrome, URL_SURVEY, { width, height: width < 500 ? 844 : 900, mobile: width < 500, port: port++, profile: '/tmp/qa-survey-profile-' + process.pid + '-' + runN }, async (c) => {
		await c.send('Network.enable');
		await c.send('Network.setExtraHTTPHeaders', { headers: { 'X-Forwarded-For': xff } });
		await c.send('Page.navigate', { url: URL_SURVEY });
		await sleep(2500);
		const loaded = Date.now();
		await wait(c.ev, `!!document.querySelector('.sv__cta')`, 8000);
		const label = `${name}@${width}`;
		await steps(Object.assign({}, c, { label, loaded, xff }));
		ok(await c.ev(JS.overflow), `${label}: nessuno scroll orizzontale a fine corsa`);
	});
}

async function pick(c, text) { ok(await c.ev(JS.click(text)), `${c.label}: opzione "${text}"`); await sleep(420); }
async function expectTitle(c, re, count) {
	const t = await c.ev(JS.title);
	ok(re.test(t), `${c.label}: schermata "${t.slice(0, 55)}"`);
	if (count !== undefined) ok((await c.ev(JS.count)) === count, `${c.label}: etichetta "${count}" (trovata "${await c.ev(JS.count)}")`);
}
async function startAndFirstQuestions(c, { sizeText, tools, software, challenge }) {
	ok(!(await c.ev(JS.topVisible)), `${c.label}: la welcome non ha barra ne' indietro`);
	await c.shot(`${outDir}/${c.label}-s0-welcome.png`);
	await c.ev(JS.btn('Start →')); await sleep(300);
	await expectTitle(c, /role in the business/, '1 / 10');
	const g = await c.ev(JS.optsGeom);
	ok(g.length === 4 && g.every(o => o.h >= 52), `${c.label}: opzioni da almeno 52px (${g.map(o => o.h).join(',')})`);
	ok(g.every(o => Math.abs(o.w - o.fw) <= 1), `${c.label}: opzioni a tutta larghezza (${g[0].w}/${g[0].fw})`);
	ok(g.every(o => o.ta === 'left' || o.ta === 'start'), `${c.label}: testo allineato a sinistra`);
	ok(!(await c.ev(JS.backVisible)) === false, `${c.label}: tasto indietro visibile dalla seconda schermata`);
	await c.shot(`${outDir}/${c.label}-s1-role.png`);
	await pick(c, 'Owner / co-founder');
	await expectTitle(c, /kind of cleaning/, '2 / 10'); await pick(c, 'Hourly maid service (homes)');
	await expectTitle(c, /How many cleaners/, '3 / 10'); await pick(c, sizeText || '6–15');
	await expectTitle(c, /jobs do you complete/, '4 / 10'); await pick(c, '20–100');
	await expectTitle(c, /schedule jobs/, '5 / 10');
	ok((await c.ev(JS.btn('Next →'))) === 'disabled', `${c.label}: Next disabilitato senza selezione (multipla)`);
	for (const t of tools) await pick(c, t);
	if (tools.indexOf('Other') !== -1) {
		ok(await c.ev(`!document.querySelector('.sv__other').hidden`), `${c.label}: "Other" apre il campo testo`);
		await c.ev(JS.type('.sv__other input', 'Pen and paper notes'));
	}
	await c.shot(`${outDir}/${c.label}-s5-tools.png`);
	ok((await c.ev(JS.btn('Next →'))) === 'clicked', `${c.label}: Next sulla domanda multipla`); await sleep(300);
	if (software) {
		await expectTitle(c, /Which software/, '5 / 10');
		for (const t of software) await pick(c, t);
		if (software.indexOf('Other') !== -1) await c.ev(JS.type('.sv__other input', 'Custom ERP'));
		await c.ev(JS.btn('Next →')); await sleep(300);
	} else {
		ok(!/Which software/.test(await c.ev(JS.title)), `${c.label}: senza "software" la sotto-domanda e' saltata`);
	}
	await expectTitle(c, /biggest headache/, '6 / 10');
	if (challenge === 'other') {
		await pick(c, 'Something else');
		ok(await c.ev(`!document.querySelector('.sv__other').hidden`), `${c.label}: "Something else" apre il campo testo`);
		await c.ev(JS.type('.sv__other input', 'Visa renewals for staff'));
		ok((await c.ev(JS.btn('Next →'))) === 'clicked', `${c.label}: Next dopo il testo libero`); await sleep(300);
	} else { await pick(c, 'Finding new clients'); }
	await expectTitle(c, /spend on business software/, '7 / 10'); await pick(c, 'AED 100–500');
	await expectTitle(c, /fair monthly price/, '8 / 10'); await pick(c, 'AED 250–500');
	await expectTitle(c, /opening OnField/, '9 / 10');
}
async function fillContact(c, { name, wa, email, company }) {
	if (name !== undefined) await c.ev(JS.type('[name="name"]', name));
	if (wa !== undefined) await c.ev(JS.type('[name="whatsapp"]', wa));
	if (email !== undefined) await c.ev(JS.type('[name="email"]', email));
	if (company !== undefined) await c.ev(JS.type('[name="company"]', company));
}
async function submitContact(c, label) {
	const left = 12500 - (Date.now() - c.loaded);
	if (left > 0) await sleep(left); // tempo minimo dal caricamento (anti-bot)
	await c.ev(`document.querySelector('.sv__form').requestSubmit()`);
	await sleep(1500);
	const err = await c.ev(`(function(){var e=document.querySelector('.sv__error');return e&&!e.hidden?e.textContent:''})()`);
	if (err) console.log('     (errore a schermo dopo l\'invio: ' + err + ')');
}

(async () => {
	for (const width of (process.env.ONLY_ADMIN ? [] : [390, 1280])) {
		// ---- Ramo A: tutte le sotto-domande, WhatsApp + email, commento
		await scenario('A', width, async (c) => {
			await startAndFirstQuestions(c, { tools: ['WhatsApp groups and calls', 'Booking or field-service software', 'Other'], software: ['Jobber', 'Other'], challenge: 'other' });
			await pick(c, "Yes, I'd like to try it");
			await expectTitle(c, /Where should we send your access/, '10 / 10');
			ok(await c.ev(JS.has('Get my access →')), `${c.label}: CTA del ramo A`);
			await c.shot(`${c.label}-s10-contact-empty.png`.replace(/^/, outDir + '/'));
			// validazioni
			await c.ev(`document.querySelector('.sv__form').requestSubmit()`); await sleep(300);
			ok(await c.ev(`document.querySelectorAll('[aria-invalid="true"]').length>=2 && !document.querySelector('.sv__error').hidden`), `${c.label}: contatto vuoto segnala gli errori`);
			ok(await c.ev(`document.querySelector('.sv__consent').classList.contains('is-error')`), `${c.label}: consenso obbligatorio segnalato`);
			await fillContact(c, { name: 'QA Tester A', wa: '+971 50 123 4567', email: 'not-an-email', company: 'QA Cleaning LLC' });
			await c.ev(JS.check('[name="consent"]'));
			await c.ev(`document.querySelector('.sv__form').requestSubmit()`); await sleep(300);
			ok(await c.ev(`document.querySelector('[name="email"]').getAttribute('aria-invalid')==='true'`), `${c.label}: email non valida rifiutata`);
			await fillContact(c, { email: 'qa-a@example.com' });
			await c.shot(`${outDir}/${c.label}-s10-contact-filled.png`);
			await submitContact(c);
			await expectTitle(c, /Anything else/);
			ok(!(await c.ev(JS.backVisible)), `${c.label}: dopo il salvataggio il commento non ha "indietro" (evita il doppione)`);
			const ta = await c.ev(`document.querySelector('textarea').rows`);
			ok(ta === 4, `${c.label}: textarea a 4 righe`);
			await c.ev(JS.type('textarea', 'Please add Arabic support.'));
			await c.shot(`${outDir}/${c.label}-s11-feedback.png`);
			await c.ev(JS.btn('Send →')); await sleep(1200);
			await expectTitle(c, /^Thank you!$/);
			ok(/access within 2 working days/.test(await c.ev(`document.querySelector('.sv__body').textContent`)), `${c.label}: testo grazie del ramo A`);
			ok(!(await c.ev(JS.topVisible)), `${c.label}: nessuna barra nella schermata finale`);
			await c.shot(`${outDir}/${c.label}-s12-thanks.png`);
		});
		// ---- Ramo B: solo WhatsApp, senza software, commento saltato
		await scenario('B', width, async (c) => {
			await startAndFirstQuestions(c, { sizeText: 'More than 150', tools: ['Paper or whiteboard'], software: null, challenge: 'x' });
			await pick(c, 'Show me a demo first');
			await expectTitle(c, /set up the demo/, '10 / 10');
			ok(await c.ev(JS.has('Book my demo →')), `${c.label}: CTA del ramo B`);
			await fillContact(c, { name: 'QA Tester B', wa: '0501234567' });
			await c.ev(JS.check('[name="consent"]'));
			await submitContact(c);
			await expectTitle(c, /Anything else/);
			await c.ev(JS.btn('Skip')); await sleep(600);
			ok(/pick a time for the demo/.test(await c.ev(`document.querySelector('.sv__body').textContent`)), `${c.label}: testo grazie del ramo B`);
		});
		// ---- Ramo C: solo email, indietro dal contatto, commento
		await scenario('C', width, async (c) => {
			await startAndFirstQuestions(c, { tools: ['Spreadsheets (Excel, Google Sheets)'], software: null, challenge: 'x' });
			await pick(c, 'Maybe later');
			await expectTitle(c, /keep you posted/, '10 / 10');
			ok(await c.ev(JS.has('Keep me posted →')), `${c.label}: CTA del ramo C`);
			await c.ev(`document.querySelector('[data-sv-back]').click()`); await sleep(300);
			await expectTitle(c, /opening OnField/, '9 / 10');
			ok(await c.ev(`document.querySelector('.sv__opt.is-on')&&document.querySelector('.sv__opt.is-on').textContent==='Maybe later'`), `${c.label}: indietro conserva la risposta`);
			await pick(c, 'Maybe later');
			await expectTitle(c, /keep you posted/);
			await fillContact(c, { name: 'QA Tester C', email: 'qa-c@example.com' });
			await c.ev(JS.check('[name="consent"]'));
			await submitContact(c);
			await c.ev(JS.type('textarea', '=HYPERLINK("http://x.example","hi")'));
			await c.ev(JS.btn('Send →')); await sleep(1200);
			ok(/let you know when OnField is live/.test(await c.ev(`document.querySelector('.sv__body').textContent`)), `${c.label}: testo grazie del ramo C`);
		});
		// ---- Ramo D senza contatto
		await scenario('D', width, async (c) => {
			await startAndFirstQuestions(c, { tools: ['WhatsApp groups and calls'], software: null, challenge: 'x' });
			await pick(c, 'Not for me');
			await expectTitle(c, /Anything else/);
			ok(!/Where|keep you posted/.test(await c.ev(JS.title)), `${c.label}: il ramo D salta il contatto`);
			ok(await c.ev(JS.has('Leave your contact anyway')), `${c.label}: link "Leave your contact anyway"`);
			ok(await c.ev(JS.backVisible), `${c.label}: indietro disponibile (ancora nulla salvato)`);
			await c.ev(JS.type('textarea', 'Not now, thanks.'));
			await c.ev(JS.btn('Send →'));
			await sleep(Math.max(0, 12500 - (Date.now() - c.loaded)) + 1500);
			// il primo invio puo' essere arrivato prima dei 12 secondi: in quel caso l'errore e' dichiarato e si riprova
			if (!/Thanks for your time/.test(await c.ev(JS.title))) { await c.ev(JS.btn('Send →')); await sleep(1500); }
			ok(/Thanks for your time/.test(await c.ev(JS.title)), `${c.label}: schermata finale del ramo D`);
		});
		// ---- Ramo D che lascia il contatto
		await scenario('Dc', width, async (c) => {
			await startAndFirstQuestions(c, { tools: ['WhatsApp groups and calls'], software: null, challenge: 'x' });
			await pick(c, 'Not for me');
			await c.ev(JS.btn('Leave your contact anyway')); await sleep(300);
			await expectTitle(c, /keep you posted/, '10 / 10');
			await fillContact(c, { name: 'QA Tester D', wa: '+971 55 765 4321' });
			await c.ev(`document.querySelector('.sv__form').requestSubmit()`); await sleep(300);
			ok(await c.ev(`document.querySelector('.sv__consent').classList.contains('is-error')`), `${c.label}: se lascia il contatto il consenso e' obbligatorio anche nel ramo D`);
			await c.ev(JS.check('[name="consent"]'));
			await submitContact(c);
			await expectTitle(c, /Anything else/);
			await c.ev(JS.btn('Skip')); await sleep(500);
			ok(/Thanks for your time/.test(await c.ev(JS.title)), `${c.label}: ramo D con contatto arriva al grazie del ramo D`);
		});
	}

	// ---- Endpoint: prove dirette (fetch dal browser: stessa origine, nonce e firma veri)
	if (!process.env.ONLY_ADMIN) await withChrome(chrome, URL_SURVEY, { width: 390, height: 800, mobile: true, port: port++, profile: '/tmp/qa-survey-profile-api-' + process.pid }, async (c) => {
		await c.send('Network.enable');
		await c.send('Network.setExtraHTTPHeaders', { headers: { 'X-Forwarded-For': '10.99.' + (stamp % 250) + '.1' } });
		await c.send('Page.navigate', { url: URL_SURVEY }); await sleep(2500);
		const t0 = Date.now();
		const call = (path, body) => c.ev(`(async()=>{var cfg=JSON.parse(document.querySelector('[data-survey]').dataset.config);var b=Object.assign({nonce:cfg.nonce,ts:cfg.ts,sig:cfg.sig},${JSON.stringify(body)});var r=await fetch(cfg.endpoint+${JSON.stringify(path)},{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(b)});var j=await r.json().catch(function(){return {}});return JSON.stringify({s:r.status,j:j});})()`).then(JSON.parse);
		const good = { role: 'owner', business_type: 'commercial', team_size: '1-5', weekly_jobs: '<20', tools: ['whatsapp'], challenge: 'quality', software_spend: 'none', price_expectation: 'free_only', interest: 'yes', name: 'API Test', email: 'api@example.com', consent: true };
		let r = await call('', good);
		ok(r.s === 429 && r.j.code === 'tp_survey_fast', `API: invio prima del tempo minimo respinto (${r.s} ${r.j.code})`);
		await sleep(Math.max(0, 12500 - (Date.now() - t0)));
		r = await call('', Object.assign({}, good, { consent: false }));
		ok(r.s === 400 && r.j.data.fields.indexOf('consent') !== -1, `API: senza consenso 400 (${r.s})`);
		r = await call('', Object.assign({}, good, { email: '', whatsapp: '' }));
		ok(r.s === 400 && r.j.data.fields.indexOf('email') !== -1 && r.j.data.fields.indexOf('whatsapp') !== -1, `API: ne' WhatsApp ne' email 400 (${r.s})`);
		r = await call('', Object.assign({}, good, { role: 'hacker' }));
		ok(r.s === 400 && r.j.data.fields.indexOf('role') !== -1, `API: opzione fuori elenco 400 (${r.s})`);
		r = await call('', Object.assign({}, good, { nonce: 'deadbeef' }));
		ok(r.s === 403, `API: nonce sbagliato 403 (${r.s})`);
		const before = await c.ev(`1`);
		r = await call('', Object.assign({}, good, { website: 'http://spam.example' }));
		ok(r.s === 200 && r.j.id === 0, `API: honeypot compilato, successo finto e nessun id (${r.s} id=${r.j.id})`);
		r = await call('', good);
		ok(r.s === 201 && r.j.id > 0 && r.j.key, `API: invio valido 201 (id ${r.j.id})`);
		const id = r.j.id, key = r.j.key;
		r = await call('/feedback', { id, key: 'bad', feedback: 'x' });
		ok(r.s === 403, `API: commento con chiave sbagliata 403 (${r.s})`);
		r = await call('/feedback', { id, key, feedback: 'Prima volta' });
		ok(r.s === 200 && r.j.ok === true, `API: commento accettato (${r.s})`);
		r = await call('/feedback', { id, key, feedback: 'Seconda volta' });
		ok(r.s === 200 && r.j.ok === false, `API: il commento non si sovrascrive (${r.s} ok=${r.j.ok})`);
		// rate limit: 6 creazioni per ora per IP; ne sono state fatte 1 valida (le respinte prima del limite non contano tutte): si continua fino al 429
		let limited = 0, created = 1;
		r = await call('', Object.assign({}, good, { consent: false })); // un invio non valido non consuma il limite
		for (let i = 0; i < 14 && !limited; i++) { r = await call('', good); if (r.s === 429 && r.j.code === 'tp_survey_limit') limited = i + 1; else if (r.s === 201) created++; }
		ok(limited > 0 && created === 10, `API: rate limit scatta al decimo salvataggio valido (${created} salvati, 429 al tentativo ${limited}); gli invii non validi non lo consumano`);
		console.log('id risposta di prova API:', id); global.apiId = id;
	});

	// ---- Pagina: noindex, sitemap, intestazioni
	const head = await (await fetch(URL_SURVEY, { headers: { 'X-Forwarded-For': '10.1.1.1' } })).text();
	ok(/<meta name='robots' content='noindex, nofollow' \/>/.test(head), 'HTML: meta robots noindex, nofollow');
	ok(!/googletagmanager|clarity\.ms|iubenda/.test(head), 'HTML: nessun GTM, Clarity o iubenda');
	ok(/<html lang="en">/.test(head), 'HTML: lang="en"');
	const hdr = await fetch(URL_SURVEY, { method: 'HEAD' });
	ok(/noindex/.test(hdr.headers.get('x-robots-tag') || ''), 'HEAD: X-Robots-Tag noindex');
	ok(/no-store|no-cache/.test(hdr.headers.get('cache-control') || ''), 'HEAD: Cache-Control non cacheabile');
	const sm = await (await fetch(base + '/wp-sitemap-posts-page-1.xml')).text();
	ok(sm.indexOf('survey-dubai') === -1 && sm.indexOf('<urlset') !== -1, 'sitemap: survey-dubai assente (sitemap pagine non vuota: ' + (sm.match(/<loc>/g) || []).length + ' url)');

	if (withAdmin) {
		await withChrome(chrome, base + '/wp-admin/admin.php?page=tp-survey', { width: 1280, height: 900, port: port++, profile: '/tmp/qa-survey-profile-adm-' + process.pid }, async (c) => {
			await sleep(2500);
			if (/wp-login/.test(await c.ev('location.href'))) {
				await c.ev(`(function(){document.getElementById('user_login').value='admin';document.getElementById('user_pass').value='password';document.getElementById('wp-submit').click();})()`);
				await sleep(4000);
			}
			await c.ev(`location.href=${JSON.stringify(base + '/wp-admin/admin.php?page=tp-survey')}`); await sleep(3500);
			const html = await c.ev(`document.documentElement.outerHTML`);
			ok(/Survey OnField/.test(html) && /QA Tester A/.test(html), 'admin: elenco con le risposte di prova');
			const rows = await c.ev(`document.querySelectorAll('tbody tr').length`);
			console.log('admin: righe in elenco', rows);
			await c.shot(`${outDir}/admin-list.png`);
			const csvHref = await c.ev(`(document.querySelector('a.page-title-action')||{}).href||''`);
			ok(/tp_survey_csv/.test(csvHref), 'admin: link export CSV con nonce');
			const csv = await c.ev(`fetch(document.querySelector('a.page-title-action').href).then(function(r){return r.text()})`);
			const bom = await c.ev(`fetch(document.querySelector('a.page-title-action').href).then(function(r){return r.arrayBuffer()}).then(function(b){return Array.from(new Uint8Array(b).slice(0,3)).join(',')})`);
			fs.writeFileSync(`${outDir}/export.csv`, csv);
			ok(bom === '239,187,191' && /Survey version/.test(csv) && /QA Tester A/.test(csv), 'admin: CSV con BOM, intestazioni e righe');
			ok(/'=HYPERLINK/.test(csv) && !/(^|,)"=HYPERLINK/m.test(csv), 'admin: CSV neutralizza le formule (apice davanti a "=")');
			ok(/Prima volta/.test(csv) && !/Seconda volta/.test(csv), 'admin: il commento di prova e\' stato scritto una volta sola nella riga giusta');
			ok(/Jobber, Custom ERP|Jobber, Other/.test(csv) && /WhatsApp groups and calls/.test(csv), 'admin: CSV con le etichette leggibili');
			// Cancellazione: una risposta dal dettaglio, poi tutte le altre di prova in blocco (con filtro per nome)
			const nav = async u => { await c.ev(`location.href=${JSON.stringify(u)}`); await sleep(2500); };
			const detailId = process.env.ONLY_ADMIN || global.apiId;
			await nav(base + '/wp-admin/admin.php?page=tp-survey');
			const viewHref = await c.ev(`(function(){var a=[].slice.call(document.querySelectorAll('tbody a')).filter(function(x){return x.textContent==='View'})[0];return a?a.href:''})()`);
			ok(!!viewHref, 'admin: dettaglio apribile dall\'elenco');
			if (viewHref) {
				await nav(base + '/wp-admin/admin.php?page=tp-survey&view=' + detailId);
				const txt = await c.ev('document.body.innerText');
				ok(/API Test|FB Test/.test(txt) && /Prima volta/.test(txt), 'admin: il dettaglio mostra la risposta e il commento');
				await c.ev(`window.confirm=function(){return true}; document.querySelector('.button-link-delete').click()`); await sleep(5000);
				await c.shot(`${outDir}/admin-after-delete.png`);
				ok(/1 response\(s\) deleted/.test(await c.ev('document.body.innerText')), 'admin: cancellazione singola dal dettaglio');
			}
			let removed = 0;
			for (const q of ['QA Tester', 'API Test', 'FB Test']) {
				for (let k = 0; k < 6; k++) {
					await nav(base + '/wp-admin/admin.php?page=tp-survey&q=' + encodeURIComponent(q));
					const n = await c.ev(`document.querySelectorAll('input[name="ids[]"]').length`);
					if (!n) break;
					await c.ev(`window.confirm=function(){return true}; document.querySelectorAll('input[name="ids[]"]').forEach(function(i){i.checked=true}); [].slice.call(document.querySelectorAll('button.button')).filter(function(b){return /Delete selected/.test(b.textContent)})[0].click()`); await sleep(2500);
					removed += n;
				}
			}
			await nav(base + '/wp-admin/admin.php?page=tp-survey&q=' + encodeURIComponent('QA Tester'));
			ok(removed > 0 && (await c.ev(`document.querySelectorAll('input[name="ids[]"]').length`)) === 0, `admin: cancellazione multipla (${removed} risposte di prova rimosse, nessuna rimasta)`);
		});
	}
	console.log(fails ? `\n${fails} controlli FALLITI` : '\ntutti i controlli verdi');
	process.exit(fails ? 1 : 0);
})();
