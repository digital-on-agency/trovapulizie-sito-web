// QA dei collegamenti interni della landing sgrosso (TICKET-86, TICKET-85): node tools/qa-sgrosso-cta.js <chrome-bin> <url> [larghezza=390]
// Clic reali (Input.dispatchMouseEvent) e misure di posizione dopo lo scorrimento:
//   - schede di "Preventivi a confronto" -> #quanto-costa (link vero, area di tocco su tutta la scheda, da tastiera)
//   - stelle della hero -> #recensioni
//   - CTA "Sopralluogo gratuito" della testata (sotto 1024 px): form non visibile -> scorre a #form; form visibile ->
//     focus sul primo campo del passo corrente e classe .is-attention per al massimo 1,2 s
//   - prefers-reduced-motion: scorrimento senza animazione ed evidenziamento statico
//   - TICKET-85: pulsanti "Calcola il preventivo gratis" verso #quanto-costa; calcolatore -> form precompilato
//     (tipo, mq, extra nella stima del passo 2, focus sull'indirizzo, marketing_cta_click); #form assente -> #form-bottom,
//     entrambi assenti -> pulsante nascosto; ancore #prezzi e #cosa-include; #prezzi assente -> pagina in cima
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
	// ---- TICKET-85 -----------------------------------------------------------------------------------------------------------------
	await send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'no-preference' }] });
	await ev(`(function(){ var st=document.createElement('style'); st.id='qa-instant'; st.textContent='html{scroll-behavior:auto !important}'; document.head.appendChild(st); return 'ok'; })()`);
	const calcLinks = JSON.parse(await ev(`JSON.stringify(Array.prototype.filter.call(document.querySelectorAll('a'), function(a){ return /Calcola il preventivo gratis/.test(a.textContent); }).map(function(a){ return { href: a.getAttribute('href'), vis: getComputedStyle(a).display !== 'none' }; }))`));
	check('tutti i pulsanti "Calcola il preventivo gratis" portano a #quanto-costa (' + calcLinks.length + ' trovati)', calcLinks.length >= 6 && calcLinks.every(l => l.href === '#quanto-costa'), JSON.stringify(calcLinks.map(l => l.href)));
	await jump(0); await ev(`history.replaceState(null,'',location.pathname+location.search)`);
	const cbtn = await centerOn('.sg-cta', 0);
	await click(cbtn.x, cbtn.y); await settle();
	check('clic su "Calcola il preventivo gratis" (Confrontiamo): scorre al calcolatore', (await ev('location.hash')) === '#quanto-costa' && (await nearNav('#quanto-costa')) === 'ok', (await ev('location.hash')) + ' ' + (await nearNav('#quanto-costa')));
	if (WIDTH >= 1024) {
		await jump(0); await ev(`history.replaceState(null,'',location.pathname+location.search)`);
		const hb = await rectOf('.landing-nav__cta--full');
		await click(hb.x, hb.y); await settle();
		check('clic su "Calcola il preventivo gratis" della testata: scorre al calcolatore', (await ev('location.hash')) === '#quanto-costa' && (await nearNav('#quanto-costa')) === 'ok', await nearNav('#quanto-costa'));
	}

	// Calcolatore -> form precompilato
	await ev(`(function(){ var root=document.querySelector('[data-estimator]'); var r=root.querySelector('input[name=mq]'); r.value=120; r.dispatchEvent(new Event('input',{bubbles:true})); root.querySelector('input[name=tipo][value="Fine affitto"]').click(); var ex=root.querySelectorAll('input[name=extra]'); ex[0].click(); ex[1].click(); return 'ok'; })()`);
	await sleep(1200);
	const secondary = await ev(`(function(){ var b=document.querySelector('.sg-calc__result [data-submit]'); var r=b.getBoundingClientRect(); return r.width > 0 && r.height > 0; })()`);
	await jump(0); await ev(`history.replaceState(null,'',location.pathname+location.search)`);
	await ev(`window.dataLayer.length = 0`);
	const bk = await centerOn('[data-calc-book]');
	const bkVis = await ev(`!document.querySelector('[data-calc-book]').hidden`);
	await click(bk.x, bk.y); await settle();
	const pf = JSON.parse(await ev(`(function(){ var f=document.getElementById('lead-hero'); var c=f.querySelector('input[name=tipoPulizia]:checked'); return JSON.stringify({ tipo: c && c.getAttribute('data-label'), mq: f.querySelector('[name=mq]').value, focus: document.activeElement.name, hash: location.hash, top: Math.round(document.getElementById('form').getBoundingClientRect().top), nav: document.querySelector('.landing-nav').getBoundingClientRect().height, ev: window.dataLayer.filter(function(x){ return x.event === 'marketing_cta_click'; }) }); })()`));
	check('calcolatore: il pulsante di prenotazione e visibile accanto all invio per sola email', bkVis && secondary);
	check('"Prenota il sopralluogo con questi dati": scorre a #form, passo 1 con tipo e 120 mq, focus sull indirizzo', pf.hash === '#form' && pf.tipo === 'Fine affitto' && pf.mq === '120' && pf.focus === 'indirizzo' && Math.abs(pf.top - pf.nav) <= 24, JSON.stringify(pf));
	check('evento marketing_cta_click {landing, cta_position:calcolatore}', pf.ev.length === 1 && pf.ev[0].landing === 'sgrosso-casa' && pf.ev[0].cta_position === 'calcolatore', JSON.stringify(pf.ev));
	const est = JSON.parse(await ev(`(function(){ var f=document.getElementById('lead-hero'); function set(n,v){ var e=f.querySelector('[name="'+n+'"]'); e.value=v; e.dispatchEvent(new Event('input',{bubbles:true})); } set('indirizzo','Via Tuscolana 118, Roma'); f.requestSubmit(); var root=document.querySelector('[data-estimator]'); var c={ base: parseFloat(root.getAttribute('data-base')), perMq: parseFloat(root.getAttribute('data-per-mq')), extraMin: parseFloat(root.getAttribute('data-extra-min')), extraMax: parseFloat(root.getAttribute('data-extra-max')), maxMult: parseFloat(root.getAttribute('data-max-mult')) }; var e2=window.tpStimaSgrosso(120, 2, c); var e0=window.tpStimaSgrosso(120, 0, c); return JSON.stringify({ line: f.querySelector('[data-estimate-line]').textContent, exp2: 'Stima indicativa: ' + e2.min + '-' + e2.max + ' euro', exp0: 'Stima indicativa: ' + e0.min + '-' + e0.max + ' euro' }); })()`));
	check('la stima del passo 2 usa gli extra del calcolatore (120 mq, 2 extra)', est.line === est.exp2 && est.line !== est.exp0, JSON.stringify(est));
	// dal passo 2, un nuovo uso del calcolatore riporta al passo 1 con i dati nuovi
	await ev(`(function(){ var root=document.querySelector('[data-estimator]'); var r=root.querySelector('input[name=mq]'); r.value=60; r.dispatchEvent(new Event('input',{bubbles:true})); return 'ok'; })()`);
	await sleep(1000);
	const bk2 = await centerOn('[data-calc-book]'); await click(bk2.x, bk2.y); await settle();
	const back1 = JSON.parse(await ev(`(function(){ var f=document.getElementById('lead-hero'); return JSON.stringify({ step: f.querySelector('[data-step]:not([hidden])').getAttribute('data-step'), mq: f.querySelector('[name=mq]').value, ind: f.querySelector('[name=indirizzo]').value }); })()`));
	check('prefill con il form al passo 2: torna al passo 1 con i metri quadri aggiornati e l indirizzo intatto', back1.step === '1' && back1.mq === '60' && back1.ind === 'Via Tuscolana 118, Roma', JSON.stringify(back1));
	check('nessuna chiamata a /api/leads dal calcolatore verso il form', apiCalls.length === 0, apiCalls.join(' | '));

	// Ancore #prezzi e #cosa-include, #form assente, nessun form
	const idsGone = (ids) => "(function(){ var ids=" + JSON.stringify(ids) + "; window.__errs=[]; window.addEventListener('error', function(e){ window.__errs.push(String(e.message)); }); new MutationObserver(function(){ ids.forEach(function(i){ var el=document.getElementById(i); if (el) el.removeAttribute('id'); }); }).observe(document,{childList:true,subtree:true}); })();";
	let hook = await send('Page.addScriptToEvaluateOnNewDocument', { source: idsGone(['form']) });
	await send('Page.navigate', { url: URL.split('#')[0] }); await sleep(4000);
	await ev(`(function(){ var st=document.createElement('style'); st.textContent='html{scroll-behavior:auto !important}'; document.head.appendChild(st); return 'ok'; })()`);
	const bkB = await centerOn('[data-calc-book]');
	await click(bkB.x, bkB.y); await settle();
	const fb = JSON.parse(await ev(`(function(){ var f=document.getElementById('lead-bottom'); return JSON.stringify({ hash: location.hash, mq: f.querySelector('[name=mq]').value, focus: document.activeElement.id, hidden: document.querySelector('[data-calc-book]').hidden }); })()`));
	check('#form assente: il pulsante usa #form-bottom (scorre, precompila, focus sull indirizzo)', fb.hash === '#form-bottom' && fb.mq === '100' && fb.focus === 'lead-bottom-indirizzo' && !fb.hidden, JSON.stringify(fb));
	await send('Page.removeScriptToEvaluateOnNewDocument', { identifier: hook.result.identifier });
	hook = await send('Page.addScriptToEvaluateOnNewDocument', { source: idsGone(['form', 'form-bottom']) });
	await send('Page.navigate', { url: URL.split('#')[0] }); await sleep(4000);
	check('#form e #form-bottom assenti: il pulsante non e mostrato', (await ev(`document.querySelector('[data-calc-book]').hidden`)) === true);
	await send('Page.removeScriptToEvaluateOnNewDocument', { identifier: hook.result.identifier });

	for (const [anchor, sel] of [['prezzi', '#quanto-costa'], ['cosa-include', '#quando-serve']]) {
		await send('Page.navigate', { url: 'about:blank' }); await sleep(300);
		await send('Page.navigate', { url: URL.split('#')[0] + '#' + anchor }); await sleep(5000);
		const pos = JSON.parse(await ev(`(function(){ var a=document.getElementById(${JSON.stringify(anchor)}); var sec=document.querySelector(${JSON.stringify(sel)}); var nav=document.querySelector('.landing-nav').getBoundingClientRect().height; return JSON.stringify({ exists: !!a, inSection: !!(a && sec && sec.contains(a)), top: a ? Math.round(a.getBoundingClientRect().top) : null, nav: nav, secTop: sec ? Math.round(sec.getBoundingClientRect().top) : null, scrollY: scrollY }); })()`));
		check('#' + anchor + ': ancora dentro ' + sel + ', la pagina si posiziona sotto la testata', pos.exists && pos.inSection && Math.abs(pos.top - pos.nav) <= 40 && pos.scrollY > 0, JSON.stringify(pos));
	}
	await send('Page.navigate', { url: 'about:blank' }); await sleep(300);
	await send('Page.addScriptToEvaluateOnNewDocument', { source: "window.__errs=[]; window.addEventListener('error', function(e){ window.__errs.push(String(e.message)); });" });
	await send('Page.navigate', { url: URL.split('#')[0] + '#prezzi-non-esiste' }); await sleep(4000);
	const noAnchor = JSON.parse(await ev(`JSON.stringify({ y: scrollY, errs: window.__errs })`));
	check('ancora inesistente (sezione disattivata): la pagina si apre in cima senza errori', noAnchor.y === 0 && noAnchor.errs.length === 0, JSON.stringify(noAnchor));

	check('nessuna chiamata a /api/leads durante i controlli', apiCalls.length === 0, apiCalls.join(' | '));

	const ko = checks.filter(c => !c[1]).length;
	console.log(ko ? 'ESITO: ' + ko + ' controlli falliti su ' + checks.length : 'ESITO: verde (' + checks.length + ' controlli)');
	process.exitCode = ko ? 1 : 0;
}).catch(e => { console.error('ERR', e); process.exit(1); });
