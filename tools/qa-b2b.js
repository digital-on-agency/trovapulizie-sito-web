// QA della landing B2B /roma/sgrosso-commerciale/ (TICKET-58..62): node tools/qa-b2b.js <chrome-bin> <url> [larghezza=1440]
// Intercetta via CDP (Fetch) ogni chiamata a */api/leads* e risponde come il backend (201, poi 400 e rete caduta a
// comando): nessun lead reale, nessun CORS. Verifica struttura e testi (sezioni, tab, chip, iniziali, JSON-LD), le
// validazioni del form a un passo (telefono finto, "Altro" vuoto, consenso), il payload della POST (SPEC §2), la
// conferma inline, gli errori 400 e di rete, l'evento marketing_form_submit con cta_position hero e bottom, i tab da
// tastiera, il confronto prima/dopo, gli eventi delle CTA. Sotto 1024px anche barra fissa e scroll orizzontale.
// Esce con 1 se un controllo fallisce.
const { withChrome } = require('./lib/cdp');
const [BIN, URL, W] = process.argv.slice(2, 5);
const width = parseInt(W || '1440', 10);
const mobile = width < 1024;

const calls = [];
const checks = [];
let mode = 'ok'; // ok | bad | fail
function check(name, ok, detail) { checks.push([name, !!ok]); console.log((ok ? 'OK  ' : 'KO  ') + name + (detail === undefined ? '' : '  [' + JSON.stringify(detail).slice(0, 240) + ']')); }
const posts = () => calls.filter(c => c.method === 'POST');

withChrome(BIN, URL, { port: 9363, width: width, height: 900, mobile: mobile }, async ({ send, ev, sleep, on }) => {
	on('Fetch.requestPaused', async (p) => {
		const req = p.request;
		let body = {};
		try { body = req.postData ? JSON.parse(req.postData) : {}; } catch (e) {}
		calls.push({ method: req.method, url: req.url, body: body, mode: mode });
		if (mode === 'fail' && req.method !== 'OPTIONS') { await send('Fetch.failRequest', { requestId: p.requestId, errorReason: 'Failed' }); return; }
		let status = 201, json = { ok: true, opportunitaId: 'recB2B1' };
		if (req.method === 'OPTIONS') { status = 204; json = {}; }
		else if (mode === 'bad') { status = 400; json = { message: 'Campo descrizione mancante' }; }
		const headers = [
			{ name: 'Content-Type', value: 'application/json' },
			{ name: 'Access-Control-Allow-Origin', value: '*' },
			{ name: 'Access-Control-Allow-Methods', value: 'GET,POST,PATCH,OPTIONS' },
			{ name: 'Access-Control-Allow-Headers', value: 'Content-Type' }
		];
		await send('Fetch.fulfillRequest', { requestId: p.requestId, responseCode: status, responseHeaders: headers, body: Buffer.from(JSON.stringify(json)).toString('base64') });
	});
	await send('Fetch.enable', { patterns: [{ urlPattern: '*/api/leads*', requestStage: 'Request' }] });
	await sleep(3000);

	// 1. Struttura e testi
	const s = JSON.parse(await ev(`(function(){
		var q = function (sel) { return document.querySelector(sel); }, qa = function (sel) { return Array.prototype.slice.call(document.querySelectorAll(sel)); };
		var ld = qa('script[type="application/ld+json"]').map(function (x) { return x.textContent; });
		var panelOn = qa('.sg-when__panel').filter(function (p) { return !p.hidden; })[0];
		var light = q('.dc-who__card:not(.dc-who__card--dark) .dc-who__cta'), dark = q('.dc-who__card--dark .dc-who__cta');
		return JSON.stringify({
			main: q('main.b2b') ? q('main.b2b').getAttribute('data-landing') : null,
			h1: qa('h1').length, h1Text: q('h1') ? q('h1').textContent.replace(/\\s+/g, ' ').trim() : '',
			title: document.title, desc: (q('meta[name="description"]') || {}).content,
			sezioni: ['.sg-hero', '.sg-trust', '.sg-when', '.b2b-works', '.dc-who', '.b2b-why', '.sg-steps', '.reviews', '.faq', '.sg-final', '[data-sticky-cta]'].filter(function (x) { return !q(x); }),
			trust: qa('.sg-trust__bar li').length,
			tabs: qa('.sg-when [role="tab"]').map(function (t) { return t.textContent.trim(); }),
			panelOn: panelOn ? panelOn.getAttribute('data-key') : null,
			panelImg: panelOn && panelOn.querySelector('.sg-when__photo img') ? panelOn.querySelector('.sg-when__photo img').getAttribute('src').split('/').pop().split('?')[0] : null,
			chips: panelOn ? qa('.sg-when__panel:not([hidden]) .sg-area.is-on').length : 0,
			compare: !!q('.b2b-works [data-compare] input[type="range"]'), gallery: qa('.b2b-works__item').length,
			forms: ['#lead-hero[data-lead-b2b]', '#lead-bottom[data-lead-b2b]'].filter(function (x) { return !q(x); }),
			formChips: qa('#lead-hero .lead-form__chips .tp-pill').length, altroHidden: q('#lead-hero [data-other-field]').hidden,
			plans: qa('.dc-who__card').length, planLight: light ? [light.className.indexOf('tp-btn--secondary') !== -1, light.getAttribute('href'), light.getAttribute('data-cta-position')] : null,
			planDark: dark ? [dark.className.indexOf('tp-btn--primary') !== -1, dark.getAttribute('href')] : null,
			why: qa('.b2b-why__card').length, whyIcons: qa('.b2b-why__glyph').length,
			initials: qa('.review-card__initials').map(function (x) { return x.textContent.trim(); }),
			faqPage: ld.filter(function (x) { return x.indexOf('FAQPage') !== -1; }).length,
			service: ld.filter(function (x) { return x.indexOf('"Service"') !== -1; }), rating: ld.some(function (x) { return x.indexOf('AggregateRating') !== -1; }),
			mascotte: !!q('.sg-final__mascot'), stickyHidden: q('[data-sticky-cta]') ? q('[data-sticky-cta]').hidden : null,
			heroSource: !!q('.sg-hero picture source[media]'),
			scrollWidth: document.documentElement.scrollWidth, vw: window.innerWidth
		});
	})()`));
	check('main.b2b con data-landing', s.main === 'sgrosso-commerciale', s.main);
	check('un solo H1 del ridisegno', s.h1 === 1 && /^Pulizie per uffici, negozi e locali\. Anche quando sei chiuso\.$/.test(s.h1Text), s.h1Text);
	check('title e description del live', s.title === 'Pulizie commerciali Roma — Uffici, negozi e locali | Trovapulizie' && /^Pulizie commerciali a Roma per uffici/.test(s.desc || ''), [s.title, s.desc]);
	check('tutte le sezioni presenti', s.sezioni.length === 0, s.sezioni);
	check('trust strip a quattro voci', s.trust === 4, s.trust);
	check('quattro tab dei settori', JSON.stringify(s.tabs) === JSON.stringify(['Uffici', 'Negozi & retail', 'Supermercati', 'Palestre & centri']), s.tabs);
	check('pannello Uffici attivo con foto e sei chip', s.panelOn === 'uffici' && s.panelImg === 'settore-uffici.webp' && s.chips === 6, [s.panelOn, s.panelImg, s.chips]);
	check('confronto trascinabile e tre foto', s.compare && s.gallery === 3, [s.compare, s.gallery]);
	check('due form B2B (hero e fondo)', s.forms.length === 0 && s.formChips === 5 && s.altroHidden === true, [s.forms, s.formChips, s.altroHidden]);
	check('due card delle formule con i bottoni giusti', s.plans === 2 && s.planLight && s.planLight[0] && s.planLight[1] === '#form-bottom' && s.planLight[2] === 'formule' && s.planDark && s.planDark[0] && s.planDark[1] === '#form-bottom', [s.planLight, s.planDark]);
	check('quattro vantaggi con icona', s.why === 4 && s.whyIcons === 4, [s.why, s.whyIcons]);
	check('recensioni con le iniziali', JSON.stringify(s.initials) === JSON.stringify(['MC', 'LR', 'AP']), s.initials);
	check('un solo FAQPage, un Service senza offerta, nessun AggregateRating', s.faqPage === 1 && s.service.length === 1 && s.service[0].indexOf('offers') === -1 && !s.rating, [s.faqPage, s.service.length, s.rating]);
	check('nessuna mascotte nella CTA finale', s.mascotte === false, s.mascotte);
	check('barra fissa presente e nascosta al caricamento', s.stickyHidden === true, s.stickyHidden);
	check('hero con rendition mobile', s.heroSource === true, s.heroSource);
	check('nessuno scroll orizzontale', s.scrollWidth === s.vw, [s.scrollWidth, s.vw]);

	// 2. Validazioni del form della hero: nessuna chiamata.
	const fill = (id, v) => `(function(){ var f=document.getElementById('${id}'); function set(n,val){ var e=f.querySelector('[name="'+n+'"]'); if(!e) return; e.value=val; e.dispatchEvent(new Event('input',{bubbles:true})); }
		var v=${JSON.stringify(v)}; if (v.chip) { var r=f.querySelector('input[name=tipoAttivita][value="'+v.chip+'"]'); r.checked=true; r.dispatchEvent(new Event('change',{bubbles:true})); }
		if ('altro' in v) set('tipoAttivitaAltro', v.altro); if ('mq' in v) set('superficieMq', v.mq); if ('zona' in v) set('zona', v.zona); if ('tel' in v) set('telefono', v.tel);
		if ('consent' in v) f.querySelector('input[name=consentPrivacy]').checked = v.consent; f.requestSubmit(); return 'ok'; })()`;
	const err = (id, name) => ev(`document.querySelector('#${id} [data-error-for="${name}"]').textContent`);

	await ev(fill('lead-hero', { chip: 'Palestra', zona: 'Prati', tel: '3333333333', consent: true }));
	await sleep(500);
	check('telefono ripetuto bloccato lato client', (await err('lead-hero', 'telefono')) === 'Numero non valido' && posts().length === 0, await err('lead-hero', 'telefono'));
	await ev(fill('lead-hero', { chip: 'Altro', tel: '06 9151 6075' }));
	await sleep(500);
	const altroVisibile = await ev(`!document.querySelector('#lead-hero [data-other-field]').hidden`);
	check('"Altro" apre il campo e senza testo blocca', altroVisibile === true && (await err('lead-hero', 'tipoAttivitaAltro')) === 'Scrivi che attività è' && posts().length === 0, await err('lead-hero', 'tipoAttivitaAltro'));
	await ev(fill('lead-hero', { altro: 'Studio dentistico', consent: false }));
	await sleep(500);
	check('consenso privacy obbligatorio', /Privacy Policy/.test(await err('lead-hero', 'consentPrivacy')) && posts().length === 0, await err('lead-hero', 'consentPrivacy'));

	// 3. Invio valido dalla hero: payload, conferma inline, evento.
	await ev(fill('lead-hero', { mq: '200', consent: true }));
	await sleep(1800);
	const c1 = posts()[0] && posts()[0].body;
	check('POST /api/leads col contratto b2b', !!c1 && c1.tipo === 'b2b' && c1.telefono === '+390691516075' && c1.zona === 'Prati' && c1.note === 'Studio dentistico' && c1.superficieMq === '200' && c1.consentPrivacy === true && c1.consentMarketing === false && c1.honeypot === '' && typeof c1.pageUrl === 'string' && c1.pageUrl.indexOf('?') === -1 && !('contactOnly' in c1) && !('email' in c1), c1);
	const d1 = JSON.parse(await ev(`(function(){ var f=document.getElementById('lead-hero'); return JSON.stringify({ done: !f.querySelector('[data-done]').hidden, step: f.querySelector('.lead-form__step').hidden, actions: f.querySelector('.lead-form__actions').hidden, cls: f.classList.contains('is-done'),
		testo: f.querySelector('[data-done]').textContent.replace(/\\s+/g,' ').trim().slice(0, 60),
		ev: (window.dataLayer||[]).filter(function(x){ return x && x.event === 'marketing_form_submit'; }).map(function(x){ return [x.landing, x.form, x.cta_position]; }) }); })()`));
	check('conferma inline al posto dei campi', d1.done && d1.step && d1.actions && d1.cls && /^Richiesta inviata\./.test(d1.testo), d1);
	check('evento marketing_form_submit hero', d1.ev.length === 1 && JSON.stringify(d1.ev[0]) === JSON.stringify(['sgrosso-commerciale', 'b2b', 'hero']), d1.ev);

	// 4. Form in fondo: 400 del backend, rete caduta, poi 201.
	mode = 'bad';
	await ev(fill('lead-bottom', { zona: 'EUR', tel: '347 123 4568', consent: true }));
	await sleep(1500);
	const e400 = JSON.parse(await ev(`(function(){ var f=document.getElementById('lead-bottom'); var b=f.querySelector('.lead-form__api-error'); return JSON.stringify({ hidden: b.hidden, text: b.textContent.trim(), zona: f.querySelector('[name=zona]').value, step: f.querySelector('.lead-form__step').hidden, busy: f.querySelector('[data-submit]').disabled }); })()`));
	check('400: messaggio del backend, campi intatti, bottone riattivato', e400.hidden === false && e400.text === 'Campo descrizione mancante' && e400.zona === 'EUR' && e400.step === false && e400.busy === false, e400);
	mode = 'fail';
	await ev(`document.getElementById('lead-bottom').requestSubmit()`);
	await sleep(1500);
	const eNet = JSON.parse(await ev(`(function(){ var b=document.querySelector('#lead-bottom .lead-form__api-error'); return JSON.stringify({ text: b.textContent.trim(), tel: !!b.querySelector('a[href^="tel:"]') }); })()`));
	check('rete caduta: messaggio generico con il telefono', /^Invio non riuscito/.test(eNet.text) && eNet.tel, eNet);
	mode = 'ok';
	await ev(`document.getElementById('lead-bottom').requestSubmit()`);
	await sleep(1800);
	const okB = posts().filter(c => c.mode === 'ok')[1] && posts().filter(c => c.mode === 'ok')[1].body;
	const d2 = JSON.parse(await ev(`(function(){ var f=document.getElementById('lead-bottom'); return JSON.stringify({ done: !f.querySelector('[data-done]').hidden, ev: (window.dataLayer||[]).filter(function(x){ return x && x.event === 'marketing_form_submit'; }).map(function(x){ return x.cta_position; }) }); })()`));
	check('form in fondo: POST con chip Ufficio di default e cellulare', !!okB && okB.note === 'Ufficio' && okB.telefono === '+393471234568' && okB.zona === 'EUR' && !('superficieMq' in okB), okB);
	check('form in fondo: conferma ed evento bottom', d2.done && JSON.stringify(d2.ev) === JSON.stringify(['hero', 'bottom']), d2);

	// 5. Tab da tastiera e confronto prima/dopo.
	const tabs = JSON.parse(await ev(`(function(){ var t=document.querySelector('.sg-when [role="tab"]'); t.focus();
		document.activeElement.dispatchEvent(new KeyboardEvent('keydown',{key:'ArrowRight',bubbles:true}));
		document.activeElement.dispatchEvent(new KeyboardEvent('keydown',{key:'ArrowRight',bubbles:true}));
		var ts=Array.prototype.slice.call(document.querySelectorAll('.sg-when [role="tab"]')); var on=document.querySelector('.sg-when__panel:not([hidden])');
		return JSON.stringify({ sel: ts.map(function(x){ return x.getAttribute('aria-selected')+'/'+x.tabIndex; }), key: on.getAttribute('data-key'), img: on.querySelector('.sg-when__photo img').getAttribute('src').split('/').pop().split('?')[0], chips: Array.prototype.map.call(on.querySelectorAll('.sg-area'), function(c){ return c.textContent.trim(); }), hidden: document.querySelectorAll('.sg-when__panel[hidden]').length, focus: document.activeElement.getAttribute('data-key') }); })()`));
	check('tab Supermercati da tastiera', JSON.stringify(tabs.sel) === JSON.stringify(['false/-1', 'false/-1', 'true/0', 'false/-1']) && tabs.key === 'supermercati' && tabs.img === 'settore-supermercati.webp' && tabs.chips[0] === 'Reparti' && tabs.hidden === 3 && tabs.focus === 'supermercati', tabs);
	const pos = await ev(`(function(){ var m=document.querySelector('.b2b-works [data-compare]'); var r=m.querySelector('input[type=range]'); r.value=20; r.dispatchEvent(new Event('input',{bubbles:true})); return getComputedStyle(m).getPropertyValue('--pos').trim(); })()`);
	check('confronto: la maniglia segue il range', pos === '20%', pos);

	// 6. Eventi delle CTA (navigazione bloccata).
	const cta = JSON.parse(await ev(`(function(){ window.addEventListener('click', function (e) { e.preventDefault(); }, true); var before=(window.dataLayer||[]).length;
		['settori','formule'].forEach(function(p){ var a=document.querySelector('a[data-cta-position="'+p+'"]'); if(a) a.click(); });
		var bar=document.querySelector('[data-sticky-cta]'); if(bar){ bar.hidden=false; bar.querySelector('.sticky-cta__btn').click(); bar.querySelector('.sticky-cta__call').click(); }
		return JSON.stringify((window.dataLayer||[]).slice(before).filter(function(x){ return x && /^marketing_/.test(x.event); }).map(function(x){ return x.event+':'+(x.landing||'')+':'+(x.cta_position||''); })); })()`));
	check('eventi delle CTA con landing e posizione', cta.some(x => /^marketing_(cta_click|click_to_form):sgrosso-commerciale:settori$/.test(x)) && cta.some(x => /^marketing_(cta_click|click_to_form):sgrosso-commerciale:formule$/.test(x)) && cta.some(x => /^marketing_click_to_call:sgrosso-commerciale/.test(x)), cta);

	// 7. Mobile: barra fissa che compare quando il form esce dallo schermo.
	if (mobile) {
		await ev(`document.querySelector('[data-sticky-cta]').hidden = true; window.scrollTo(0, document.documentElement.scrollHeight / 2)`);
		await sleep(800);
		const bar = await ev(`document.querySelector('[data-sticky-cta]').hidden`);
		check('barra fissa visibile a meta pagina', bar === false, bar);
	}

	const ko = checks.filter(c => !c[1]).length;
	console.log(ko ? 'ESITO: ' + ko + ' controlli falliti' : 'ESITO: verde');
	process.exitCode = ko ? 1 : 0;
}).catch(e => { console.error('ERR', e); process.exit(1); });
