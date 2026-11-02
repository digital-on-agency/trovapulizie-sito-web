// QA dei collegamenti interni della landing sgrosso (TICKET-86, TICKET-85): node tools/qa-sgrosso-cta.js <chrome-bin> <url> [larghezza=390]
// Clic reali (Input.dispatchMouseEvent) e misure di posizione dopo lo scorrimento:
//   - schede di "Preventivi a confronto" -> #quanto-costa (link vero, area di tocco su tutta la scheda, da tastiera)
//   - stelle della hero -> #recensioni
//   - CTA "Sopralluogo gratuito" della testata (sotto 1024 px): form non visibile -> scorre a #form; form visibile ->
//     focus sul primo campo del passo corrente e classe .is-attention per al massimo 1,2 s
//   - prefers-reduced-motion: scorrimento senza animazione ed evidenziamento statico
// Nessuna chiamata a /api/leads attesa: le richieste sono intercettate e fanno fallire il controllo se ne parte una.
const { withChrome } = require('./lib/cdp');
const [BIN, URL, WIDTH_ARG] = process.argv.slice(2, 5);
const WIDTH = parseInt(WIDTH_ARG, 10) || 390;
const checks = [];
const apiCalls = [];
function check(name, ok, detail) { checks.push([name, ok]); console.log((ok ? 'OK  ' : 'KO  ') + name + (detail ? '  [' + String(detail).slice(0, 220) + ']' : '')); }

withChrome(BIN, URL, { port: 9348, width: WIDTH, mobile: WIDTH < 800, height: WIDTH < 800 ? 844 : 980 }, async ({ send, ev, sleep, on }) => {
	on('Fetch.requestPaused', async (p) => {
		apiCalls.push(p.request.method + ' ' + p.request.url);
		await send('Fetch.failRequest', { requestId: p.requestId, errorReason: 'Aborted' });
	});
	await send('Fetch.enable', { patterns: [{ urlPattern: '*/api/leads*', requestStage: 'Request' }] });
	await sleep(1500);
	for (let i = 0; i < 30 && (await ev('document.readyState')) !== 'complete'; i++) await sleep(200);
	await sleep(2500); // immagini e font a regime: niente spostamenti di layout durante le misure

	const jump0 = (y) => ev(`window.scrollTo({ top: ${y}, behavior: 'instant' }); scrollY`);
	// Passata di riscaldamento: scorre tutta la pagina perche le immagini pigre carichino e il layout non si sposti dopo.
	for (let y = 0; y < 12000; y += 700) { await jump0(y); await sleep(120); }
	await jump0(0); await sleep(800);
	const jump = (y) => ev(`window.scrollTo({ top: ${y}, behavior: 'instant' }); scrollY`);
	const rectOf = (sel, i) => ev(`(function(){ var e=document.querySelectorAll(${JSON.stringify(sel)})[${i || 0}]; if(!e) return 'null'; var r=e.getBoundingClientRect(); return JSON.stringify({ x:r.left+r.width/2, y:r.top+r.height/2, top:r.top, w:r.width, h:r.height }); })()`).then(JSON.parse);
	async function click(x, y) {
		await send('Input.dispatchMouseEvent', { type: 'mouseMoved', x, y });
		await send('Input.dispatchMouseEvent', { type: 'mousePressed', x, y, button: 'left', clickCount: 1 });
		await send('Input.dispatchMouseEvent', { type: 'mouseReleased', x, y, button: 'left', clickCount: 1 });
	}
	// Attende la fine dello scorrimento morbido: parte dopo qualche frame, quindi prima serve un attesa minima.
	async function settle() { await sleep(700); let last = -1, n = 0; for (let i = 0; i < 40 && n < 4; i++) { await sleep(150); const y = await ev('scrollY'); n = y === last ? n + 1 : 0; last = y; } }
	const navH = () => ev(`document.querySelector('.landing-nav').getBoundingClientRect().height`);
	const topOf = (sel) => ev(`document.querySelector(${JSON.stringify(sel)}).getBoundingClientRect().top`);
	// Porta l'elemento a meta' schermo senza animazione e restituisce il centro.
	async function centerOn(sel, i) { const r = await rectOf(sel, i); const y = await ev('scrollY'); await jump(Math.max(0, y + r.top - 300)); await sleep(900); /* ingresso a cascata (reveal) */ return rectOf(sel, i); }
	const nearNav = async (sel) => { const t = await topOf(sel), h = await navH(); return Math.abs(t - h) <= 4 ? 'ok' : 'top=' + t + ' nav=' + h; };

	// ---- Schede dei preventivi a confronto ------------------------------------------------------------------
	const rows = JSON.parse(await ev(`JSON.stringify({ rows: document.querySelectorAll('.sg-offers__row').length, links: document.querySelectorAll('.sg-offers__row a[href="#quanto-costa"]').length, perRow: Array.prototype.every.call(document.querySelectorAll('.sg-offers__row'), function(r){ return r.querySelectorAll('a').length === 1; }) })`));
	check('ogni scheda di confronto contiene un solo link verso #quanto-costa', rows.rows >= 3 && rows.links === rows.rows && rows.perRow, JSON.stringify(rows));
	await ev(`(function(){ var a=document.querySelector('.sg-offers__row a'); a.focus(); return 'ok'; })()`);
	check('il link della scheda si raggiunge da tastiera', (await ev(`document.activeElement.tagName + ' ' + document.activeElement.getAttribute('href')`)) === 'A #quanto-costa');
	const smooth = await ev(`getComputedStyle(document.documentElement).scrollBehavior`);
	check('scorrimento morbido sulla landing', smooth === 'smooth', smooth);
	// Le posizioni si misurano con lo scorrimento istantaneo: in headless quello morbido viene interrotto dagli spostamenti
	// di layout delle immagini e rende le misure instabili. Che sia morbido lo dice il controllo qui sopra.
	await ev(`(function(){ var st=document.createElement('style'); st.id='qa-instant'; st.textContent='html{scroll-behavior:auto !important}'; document.head.appendChild(st); return 'ok'; })()`);
	for (let i = 0; i < 3; i++) {
		await jump(0); await ev(`history.replaceState(null,'',location.pathname+location.search)`);
		const r = await centerOn('.sg-offers__row', i);
		// clic sul bordo sinistro della scheda, lontano dal testo del nome: l'area del link deve coprire tutta la scheda
		await click(r.x - r.w / 2 + 6, r.y); await settle();
		check('clic sulla scheda ' + (i + 1) + ': scorre a #quanto-costa sotto la testata', (await ev('location.hash')) === '#quanto-costa' && (await nearNav('#quanto-costa')) === 'ok', (await ev('location.hash')) + ' ' + (await nearNav('#quanto-costa')));
	}

	// ---- Stelle della hero -------------------------------------------------------------------------------------------
	await jump(0); await ev(`history.replaceState(null,'',location.pathname+location.search)`);
	const st = await rectOf('.sg-hero__social');
	await click(st.x, st.y); await settle();
	check('clic sulle stelle: scorre a #recensioni sotto la testata', (await ev('location.hash')) === '#recensioni' && (await nearNav('#recensioni')) === 'ok', (await ev('location.hash')) + ' ' + (await nearNav('#recensioni')));
	check('le stelle sono un link verso #recensioni', (await ev(`(document.querySelector('.sg-hero__social a')||{}).getAttribute ? document.querySelector('.sg-hero__social a').getAttribute('href') : 'assente'`)) === '#recensioni');

	// ---- CTA della testata -------------------------------------------------------------------------------------------------
	const vis = JSON.parse(await ev(`JSON.stringify({ short: getComputedStyle(document.querySelector('.landing-nav__cta--short')).display, full: getComputedStyle(document.querySelector('.landing-nav__cta--full')).display, shortHref: document.querySelector('.landing-nav__cta--short').getAttribute('href'), focus: document.querySelector('.landing-nav__cta--short').hasAttribute('data-form-focus') })`));
	if (WIDTH < 1024) {
		check('testata mobile: visibile la CTA "Sopralluogo gratuito" verso #form con data-form-focus', vis.short !== 'none' && vis.full === 'none' && vis.shortHref === '#form' && vis.focus, JSON.stringify(vis));
		await jump(0); await ev(`history.replaceState(null,'',location.pathname+location.search)`);
		const topBefore = await ev(`document.getElementById('lead-hero').getBoundingClientRect().top`);
		let b = await rectOf('.landing-nav__cta--short');
		const inView = await ev(`(function(){ var r=document.getElementById('lead-hero').getBoundingClientRect(); var s=Math.min(r.bottom,innerHeight)-Math.max(r.top,0); return s >= Math.min(r.height,innerHeight)*0.5; })()`);
		if (inView) console.log('--  a ' + WIDTH + ' px il form e gia in vista all apertura: il caso "non in vista" non si presenta, resta il controllo successivo');
		else {
		await click(b.x, b.y); await settle();
		check('form non ancora in vista: il tocco scorre a #form e non evidenzia', topBefore > 300 && (await ev('scrollY')) > 0 && (await ev('location.hash')) === '#form' && !(await ev(`document.getElementById('lead-hero').classList.contains('is-attention')`)), 'top prima=' + Math.round(topBefore) + ' scrollY=' + (await ev('scrollY')) + ' ' + (await ev('location.hash')));
		}
		await ev(`document.getElementById('lead-hero').scrollIntoView({ behavior: 'instant', block: 'center' })`); await sleep(300);
		b = await rectOf('.landing-nav__cta--short');
		const yBefore = await ev('scrollY');
		await click(b.x, b.y); await sleep(250);
		const a1 = JSON.parse(await ev(`JSON.stringify({ focus: document.activeElement.name, att: document.getElementById('lead-hero').classList.contains('is-attention'), y: scrollY })`));
		check('form visibile: focus sul primo campo del passo 1 (mq) e form evidenziato', a1.focus === 'mq' && a1.att, JSON.stringify(a1));
		const anim = await ev(`getComputedStyle(document.getElementById('lead-hero')).animationName`);
		check('evidenziamento animato (nessun riduci movimento)', anim === 'lead-attention', anim);
		await sleep(1300);
		check('evidenziamento rimosso entro 1,2 s', !(await ev(`document.getElementById('lead-hero').classList.contains('is-attention')`)));
		// passo 2
		await ev(`(function(){ var f=document.getElementById('lead-hero'); function set(n,v){ var e=f.querySelector('[name="'+n+'"]'); e.value=v; e.dispatchEvent(new Event('input',{bubbles:true})); } set('mq','80'); set('indirizzo','Via Tuscolana 118, Roma'); f.requestSubmit(); return 'ok'; })()`);
		await sleep(500);
		await ev(`document.activeElement.blur()`);
		b = await rectOf('.landing-nav__cta--short');
		await click(b.x, b.y); await sleep(250);
		check('form visibile al passo 2: focus sul primo campo del passo corrente (nome)', (await ev('document.activeElement.name')) === 'nome', await ev('document.activeElement.name'));
	} else {
		check('testata desktop: visibile solo la CTA lunga, nascosta quella corta', vis.short === 'none' && vis.full !== 'none', JSON.stringify(vis));
	}

	// ---- prefers-reduced-motion -------------------------------------------------------------------------------------------------
	await ev(`document.getElementById('qa-instant') && document.getElementById('qa-instant').remove()`);
	await send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });
	await sleep(200);
	check('riduci movimento: scorrimento senza animazione', (await ev(`getComputedStyle(document.documentElement).scrollBehavior`)) === 'auto', await ev(`getComputedStyle(document.documentElement).scrollBehavior`));
	await jump(0); await ev(`history.replaceState(null,'',location.pathname+location.search)`);
	await jump(0);
	const r2 = await centerOn('.sg-offers__row', 0);
	await click(r2.x, r2.y); await sleep(150);
	check('riduci movimento: la scheda porta a #quanto-costa gia dopo 150 ms', (await nearNav('#quanto-costa')) === 'ok', await nearNav('#quanto-costa'));
	if (WIDTH < 1024) {
		await ev(`document.getElementById('lead-hero').scrollIntoView({ behavior: 'instant', block: 'center' })`);
		await sleep(200);
		const b = await rectOf('.landing-nav__cta--short');
		await click(b.x, b.y); await sleep(150);
		const rm = JSON.parse(await ev(`(function(){ var f=document.getElementById('lead-hero'); var cs=getComputedStyle(f); return JSON.stringify({ att: f.classList.contains('is-attention'), anim: cs.animationName, shadow: cs.boxShadow }); })()`));
		check('riduci movimento: evidenziamento statico (nessuna animazione, contorno verde)', rm.att && rm.anim === 'none' && /0, 200, 150/.test(rm.shadow), JSON.stringify(rm));
	}
	check('nessuna chiamata a /api/leads durante i controlli', apiCalls.length === 0, apiCalls.join(' | '));

	const ko = checks.filter(c => !c[1]).length;
	console.log(ko ? 'ESITO: ' + ko + ' controlli falliti su ' + checks.length : 'ESITO: verde (' + checks.length + ' controlli)');
	process.exitCode = ko ? 1 : 0;
}).catch(e => { console.error('ERR', e); process.exit(1); });
