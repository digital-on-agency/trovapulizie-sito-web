// QA della landing B2B /roma/sgrosso-commerciale/ (TICKET-58..62) e delle due pagine di settore /roma/pulizie-uffici/ e
// /roma/pulizie-condomini/ (TICKET-97): node tools/qa-b2b.js <chrome-bin> <url> [larghezza=1440]
// Variabile d'ambiente VARIANT = sgrosso-commerciale (default) | pulizie-uffici | pulizie-condomini: sceglie le attese
// (testi, sezioni presenti e assenti, chip del form, link incrociati). Sulle due pagine di settore controlla anche i
// vincoli della SPEC-crescita-organica-fase-b §2: nessun prezzo, nessun tempo o frequenza garantiti, nessuna
// certificazione, nessun numero di ditte o clienti, nessuna recensione, nessuna emoji.
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

// Attese per variante (TICKET-97). Per sgrosso-commerciale restano quelle di sempre.
const VARIANT = process.env.VARIANT || 'sgrosso-commerciale';
const CFG = {
	'sgrosso-commerciale': {
		landing: 'sgrosso-commerciale', settore: false,
		h1: /^Pulizie per uffici, negozi e locali\. Anche quando sei chiuso\.$/,
		title: 'Pulizie commerciali Roma: uffici e negozi | Trovapulizie', descRe: /^Pulizie commerciali a Roma per uffici/,
		chips: ['Ufficio', 'Negozio', 'Supermercato', 'Palestra', 'Altro'], firstChip: 'Ufficio', chipTest: 'Palestra', mq: true, altro: true, noteDone: /^Richiesta inviata\./,
		serviceType: 'Pulizie commerciali per uffici, negozi e locali', crumb: 'Pulizie commerciali Roma'
	},
	'pulizie-uffici': {
		landing: 'pulizie-uffici', settore: true, nServizi: 7,
		h1: /^Pulizie uffici a Roma\. Un preventivo su misura per i tuoi spazi\.$/,
		title: 'Pulizie uffici Roma: preventivo gratuito | Trovapulizie', descRe: /^Pulizie uffici a Roma:/,
		chips: ['Ufficio', 'Studio professionale', 'Sede aziendale', 'Altro'], firstChip: 'Ufficio', chipTest: 'Studio professionale', mq: true, altro: true, noteDone: /^Richiesta inviata\./,
		serviceType: 'Pulizie uffici', crumb: 'Pulizie uffici Roma',
		cross: ['/roma/pulizie-condomini/', '/roma/sgrosso-commerciale/'], altroLink: '/roma/pulizie-condomini/'
	},
	'pulizie-condomini': {
		landing: 'pulizie-condomini', settore: true, nServizi: 8,
		h1: /^Pulizie condomini a Roma\. Un preventivo su misura per il tuo stabile\.$/,
		title: 'Pulizie condomini Roma: preventivo gratuito | Trovapulizie', descRe: /^Pulizie condomini a Roma:/,
		chips: ['Condominio', 'Amministratore di condominio', 'Complesso residenziale'], firstChip: 'Condominio', chipTest: 'Amministratore di condominio', mq: false, altro: false, noteDone: /^Richiesta inviata\./,
		serviceType: 'Pulizie condomini e parti comuni', crumb: 'Pulizie condomini Roma',
		cross: ['/roma/pulizie-uffici/', '/roma/sgrosso-commerciale/']
	}
}[VARIANT];
if (!CFG) { console.error('VARIANT sconosciuta: ' + VARIANT); process.exit(2); }

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
			sezioni: ((${JSON.stringify(CFG.settore)}) ? ['.sg-hero', '.sg-trust', '.b2b-why--services', '.sg-steps', '.dc-who', '.faq', '.sg-final', '[data-sticky-cta]'] : ['.sg-hero', '.sg-trust', '.sg-when', '.b2b-works', '.dc-who', '.b2b-why', '.sg-steps', '.reviews', '.faq', '.sg-final', '[data-sticky-cta]']).filter(function (x) { return !q(x); }),
			assenti: ((${JSON.stringify(CFG.settore)}) ? ['.sg-when', '.b2b-works', '.reviews', '.review-card', '.sg-hero__social', '.sg-hero__stars', '.sg-hero picture source'] : []).filter(function (x) { return !!q(x); }),
			servizi: qa('.b2b-why--services .b2b-why__card').map(function (c) { return c.querySelector('.b2b-why__title').textContent.trim(); }),
			servSection: q('.b2b-why--services') ? q('.b2b-why--services').id : null,
			cross: qa('.dc-who__cta').map(function (a) { return a.getAttribute('href').replace(location.origin, ''); }),
			related: qa('.related-services__link').map(function (a) { return a.getAttribute('href').replace(location.origin, ''); }),
			footerLinks: qa('footer a').map(function (a) { return a.getAttribute('href').replace(location.origin, ''); }),
			heroImg: q('.sg-hero__img') ? [q('.sg-hero__img').getAttribute('src').split('/').pop().split('?')[0], !!q('.sg-hero__img').getAttribute('alt')] : null,
			crumb: ld.filter(function (x) { return x.indexOf('BreadcrumbList') !== -1; }).map(function (x) { return JSON.parse(x).itemListElement.map(function (i) { return i.name; }); }),
			serviceType: ld.filter(function (x) { return x.indexOf('"Service"') !== -1; }).map(function (x) { return JSON.parse(x).serviceType; }),
			canonical: (q('link[rel=canonical]') || {}).href || null,
			bodyText: q('main').innerText.replace(/\\s+/g, ' '),
			doneText: q('#lead-hero [data-done]').textContent.replace(/\\s+/g, ' ').trim(),
			subtitle: q('#lead-hero .lead-form__sub') ? q('#lead-hero .lead-form__sub').textContent.trim() : '',
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
			faqN: (function () { var f = ld.filter(function (x) { return x.indexOf('FAQPage') !== -1; })[0]; return f ? JSON.parse(f).mainEntity.length : 0; })(),
			service: ld.filter(function (x) { return x.indexOf('"Service"') !== -1; }), rating: ld.some(function (x) { return x.indexOf('AggregateRating') !== -1; }),
			mascotte: !!q('.sg-final__mascot'), stickyHidden: q('[data-sticky-cta]') ? q('[data-sticky-cta]').hidden : null,
			heroSource: !!q('.sg-hero picture source[media]'),
			scrollWidth: document.documentElement.scrollWidth, vw: window.innerWidth
		});
	})()`));
	check('main.b2b con data-landing', s.main === CFG.landing, s.main);
	check('un solo H1', s.h1 === 1 && CFG.h1.test(s.h1Text), s.h1Text);
	check('title e description', s.title === CFG.title && CFG.descRe.test(s.desc || ''), [s.title, s.desc]);
	check('title entro 60 caratteri e description entro 160', s.title.length <= 60 && (s.desc || '').length > 0 && (s.desc || '').length <= 160, [s.title.length, (s.desc || '').length]);
	check('tutte le sezioni presenti', s.sezioni.length === 0, s.sezioni);
	check('trust strip a quattro voci', s.trust === 4, s.trust);
	if (!CFG.settore) {
		check('quattro tab dei settori', JSON.stringify(s.tabs) === JSON.stringify(['Uffici', 'Negozi & retail', 'Supermercati', 'Palestre & centri']), s.tabs);
		check('pannello Uffici attivo con foto e sei chip', s.panelOn === 'uffici' && s.panelImg === 'settore-uffici.webp' && s.chips === 6, [s.panelOn, s.panelImg, s.chips]);
		check('confronto trascinabile e tre foto', s.compare && s.gallery === 3, [s.compare, s.gallery]);
		check('due card delle formule con i bottoni giusti', s.plans === 2 && s.planLight && s.planLight[0] && s.planLight[1] === '#form-bottom' && s.planLight[2] === 'formule' && s.planDark && s.planDark[0] && s.planDark[1] === '#form-bottom', [s.planLight, s.planDark]);
		check('quattro vantaggi con icona', s.why === 4 && s.whyIcons === 4, [s.why, s.whyIcons]);
		check('recensioni con le iniziali', JSON.stringify(s.initials) === JSON.stringify(['MC', 'LR', 'AP']), s.initials);
		check('nessuna mascotte nella CTA finale', s.mascotte === false, s.mascotte);
		check('hero con rendition mobile', s.heroSource === true, s.heroSource);
	} else {
		check('elenco servizi del settore (SPEC US-02) con ancora #servizi', s.servizi.length === CFG.nServizi && s.servSection === 'servizi', [s.servSection, s.servizi]);
		check('niente Settori, Lavori, recensioni, stelle e rendition mobile', s.assenti.length === 0, s.assenti);
		check('due card di link incrociati, verso le pagine giuste', JSON.stringify(s.cross.map(function (x) { return x.replace(/^https?:\/\/[^/]+/, ''); })) === JSON.stringify(CFG.cross), s.cross);
		check('blocco Altri servizi e footer con le due pagine nuove', ['/roma/pulizie-uffici/', '/roma/pulizie-condomini/', '/roma/sgrosso-commerciale/'].every(function (p) { return s.footerLinks.some(function (l) { return l.replace(/^https?:\/\/[^/]+/, '') === p; }); }) && s.related.some(function (l) { return /\/roma\/sgrosso-commerciale\/$/.test(l); }), [s.related.length]);
		check('la pagina non linka se stessa nel blocco Altri servizi', !s.related.some(function (l) { return l.replace(/^https?:\/\/[^/]+/, '') === '/roma/' + CFG.landing + '/'; }), s.related);
		check('hero: immagine del tema con alt', s.heroImg && /^services-visual-(uffici|condomini)\.jpg$/.test(s.heroImg[0]) && s.heroImg[1] === true, s.heroImg);
		check('BreadcrumbList Home > Roma > pagina e Service con il tipo giusto', s.crumb.length === 1 && JSON.stringify(s.crumb[0]) === JSON.stringify(['Home', 'Roma', CFG.crumb]) && JSON.stringify(s.serviceType) === JSON.stringify([CFG.serviceType]), [s.crumb, s.serviceType]);
		check('canonical della pagina', /\/roma\/(pulizie-uffici|pulizie-condomini)\/$/.test(s.canonical || '') && (s.canonical || '').indexOf(CFG.landing.replace('pulizie-', '')) !== -1, s.canonical);
		// Vincoli della SPEC §2: tutto il testo visibile della pagina.
		const bt = s.bodyText + ' ' + s.doneText;
		const vieta = [
			[/€|\beuro\b|\d+\s?(€|euro)/i, 'prezzi'],
			[/entro\s+(1|un'|un|\d+)\s*(ora|ore|giorn|minut)|\bin giornata\b|\b24\s?h|\b48\s?h|risposta in/i, 'tempi'],
			[/\bRC\b|assicura|certificat|\bISO\b|garanzi|garantit|soddisfatti o/i, 'certificazioni o garanzie'],
			[/\b\d+\s+(ditte|imprese|aziende|clienti|recensioni|condomini serviti|uffici serviti)\b|\b\d+[.,]?\d*\s*(stelle|\/5)/i, 'numeri di ditte, clienti o recensioni'],
			[/recension|\bstelle\b|\u2605/i, 'recensioni'],
			[/\b(giornalier|settimanal|mensil|quotidian)/i, 'frequenze'],
			[/[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]/u, 'emoji']
		];
		vieta.forEach(function (v) { const m = bt.match(v[0]); check('nessun contenuto vietato: ' + v[1], !m, m && m[0]); });
		check('il sottotitolo del form non promette tempi', !/ora\b|ore\b/i.test(s.subtitle) && /Ti ricontattiamo/.test(s.subtitle), s.subtitle);
	}
	check('due form B2B (hero e fondo)', s.forms.length === 0 && s.formChips === CFG.chips.length && s.altroHidden === true, [s.forms, s.formChips, s.altroHidden]);
	check('un solo FAQPage con almeno 5 domande, un Service senza offerta, nessun AggregateRating', s.faqPage === 1 && s.faqN >= 5 && s.service.length === 1 && s.service[0].indexOf('offers') === -1 && !s.rating, [s.faqPage, s.faqN, s.service.length, s.rating]);
	check('barra fissa presente e nascosta al caricamento', s.stickyHidden === true, s.stickyHidden);
	check('nessuno scroll orizzontale', s.scrollWidth === s.vw, [s.scrollWidth, s.vw]);

	// 2. Validazioni del form della hero: nessuna chiamata.
	const fill = (id, v) => `(function(){ var f=document.getElementById('${id}'); function set(n,val){ var e=f.querySelector('[name="'+n+'"]'); if(!e) return; e.value=val; e.dispatchEvent(new Event('input',{bubbles:true})); }
		var v=${JSON.stringify(v)}; if (v.chip) { var r=f.querySelector('input[name=tipoAttivita][value="'+v.chip+'"]'); r.checked=true; r.dispatchEvent(new Event('change',{bubbles:true})); }
		if ('altro' in v) set('tipoAttivitaAltro', v.altro); if ('mq' in v) set('superficieMq', v.mq); if ('zona' in v) set('zona', v.zona); if ('tel' in v) set('telefono', v.tel);
		if ('consent' in v) f.querySelector('input[name=consentPrivacy]').checked = v.consent; f.requestSubmit(); return 'ok'; })()`;
	const err = (id, name) => ev(`document.querySelector('#${id} [data-error-for="${name}"]').textContent`);

	const chipOk = CFG.chipTest;
	const nota1 = CFG.altro ? 'Studio dentistico' : CFG.chipTest;
	await ev(fill('lead-hero', { chip: chipOk, zona: 'Prati', tel: '3333333333', consent: true }));
	await sleep(500);
	check('telefono ripetuto bloccato lato client', (await err('lead-hero', 'telefono')) === 'Numero non valido' && posts().length === 0, await err('lead-hero', 'telefono'));
	if (CFG.altro) {
		await ev(fill('lead-hero', { chip: 'Altro', tel: '06 9151 6075' }));
		await sleep(500);
		const altroVisibile = await ev(`!document.querySelector('#lead-hero [data-other-field]').hidden`);
		check('"Altro" apre il campo e senza testo blocca', altroVisibile === true && (await err('lead-hero', 'tipoAttivitaAltro')) === 'Scrivi che attività è' && posts().length === 0, await err('lead-hero', 'tipoAttivitaAltro'));
		await ev(fill('lead-hero', { altro: 'Studio dentistico', consent: false }));
	} else {
		await ev(fill('lead-hero', { tel: '06 9151 6075', consent: false }));
	}
	await sleep(500);
	check('consenso privacy obbligatorio', /Privacy Policy/.test(await err('lead-hero', 'consentPrivacy')) && posts().length === 0, await err('lead-hero', 'consentPrivacy'));

	// 3. Invio valido dalla hero: payload, conferma inline, evento.
	await ev(fill('lead-hero', { mq: '200', consent: true }));
	await sleep(1800);
	const c1 = posts()[0] && posts()[0].body;
	check('POST /api/leads col contratto b2b', !!c1 && c1.tipo === 'b2b' && c1.telefono === '+390691516075' && c1.zona === 'Prati' && c1.note === nota1 && (CFG.mq ? c1.superficieMq === '200' : !('superficieMq' in c1)) && c1.consentPrivacy === true && c1.consentMarketing === false && c1.honeypot === '' && typeof c1.pageUrl === 'string' && c1.pageUrl.indexOf('?') === -1 && c1.pageUrl.indexOf(CFG.settore ? CFG.landing : 'sgrosso-commerciale') !== -1 && !('contactOnly' in c1) && !('email' in c1), c1);
	const d1 = JSON.parse(await ev(`(function(){ var f=document.getElementById('lead-hero'); return JSON.stringify({ done: !f.querySelector('[data-done]').hidden, step: f.querySelector('.lead-form__step').hidden, actions: f.querySelector('.lead-form__actions').hidden, cls: f.classList.contains('is-done'),
		testo: f.querySelector('[data-done]').textContent.replace(/\\s+/g,' ').trim().slice(0, 60),
		ev: (window.dataLayer||[]).filter(function(x){ return x && x.event === 'marketing_form_submit'; }).map(function(x){ return [x.landing, x.form, x.cta_position]; }) }); })()`));
	check('conferma inline al posto dei campi', d1.done && d1.step && d1.actions && d1.cls && CFG.noteDone.test(d1.testo), d1);
	check('evento marketing_form_submit hero con la landing della pagina', d1.ev.length === 1 && JSON.stringify(d1.ev[0]) === JSON.stringify([CFG.landing, 'b2b', 'hero']), d1.ev);

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
	check('form in fondo: POST con il primo chip di default e cellulare', !!okB && okB.note === CFG.firstChip && okB.telefono === '+393471234568' && okB.zona === 'EUR' && !('superficieMq' in okB), okB);
	check('form in fondo: conferma ed evento bottom', d2.done && JSON.stringify(d2.ev) === JSON.stringify(['hero', 'bottom']), d2);

	// 5. Tab da tastiera e confronto prima/dopo (solo pagina di partenza).
	if (!CFG.settore) {
	const tabs = JSON.parse(await ev(`(function(){ var t=document.querySelector('.sg-when [role="tab"]'); t.focus();
		document.activeElement.dispatchEvent(new KeyboardEvent('keydown',{key:'ArrowRight',bubbles:true}));
		document.activeElement.dispatchEvent(new KeyboardEvent('keydown',{key:'ArrowRight',bubbles:true}));
		var ts=Array.prototype.slice.call(document.querySelectorAll('.sg-when [role="tab"]')); var on=document.querySelector('.sg-when__panel:not([hidden])');
		return JSON.stringify({ sel: ts.map(function(x){ return x.getAttribute('aria-selected')+'/'+x.tabIndex; }), key: on.getAttribute('data-key'), img: on.querySelector('.sg-when__photo img').getAttribute('src').split('/').pop().split('?')[0], chips: Array.prototype.map.call(on.querySelectorAll('.sg-area'), function(c){ return c.textContent.trim(); }), hidden: document.querySelectorAll('.sg-when__panel[hidden]').length, focus: document.activeElement.getAttribute('data-key') }); })()`));
	check('tab Supermercati da tastiera', JSON.stringify(tabs.sel) === JSON.stringify(['false/-1', 'false/-1', 'true/0', 'false/-1']) && tabs.key === 'supermercati' && tabs.img === 'settore-supermercati.webp' && tabs.chips[0] === 'Reparti' && tabs.hidden === 3 && tabs.focus === 'supermercati', tabs);
	const pos = await ev(`(function(){ var m=document.querySelector('.b2b-works [data-compare]'); var r=m.querySelector('input[type=range]'); r.value=20; r.dispatchEvent(new Event('input',{bubbles:true})); return getComputedStyle(m).getPropertyValue('--pos').trim(); })()`);
	check('confronto: la maniglia segue il range', pos === '20%', pos);
	}

	// 6. Eventi delle CTA (navigazione bloccata).
	const cta = JSON.parse(await ev(`(function(){ window.addEventListener('click', function (e) { e.preventDefault(); }, true); var before=(window.dataLayer||[]).length;
		['settori','formule'].forEach(function(p){ var a=document.querySelector('a[data-cta-position="'+p+'"]'); if(a) a.click(); });
		Array.prototype.forEach.call(document.querySelectorAll('a[data-cta-position^="altri_spazi"]'), function(a){ a.click(); });
		var bar=document.querySelector('[data-sticky-cta]'); if(bar){ bar.hidden=false; bar.querySelector('.sticky-cta__btn').click(); bar.querySelector('.sticky-cta__call').click(); }
		return JSON.stringify((window.dataLayer||[]).slice(before).filter(function(x){ return x && /^marketing_/.test(x.event); }).map(function(x){ return x.event+':'+(x.landing||'')+':'+(x.cta_position||''); })); })()`));
	if (!CFG.settore) {
		check('eventi delle CTA con landing e posizione', cta.some(x => /^marketing_(cta_click|click_to_form):sgrosso-commerciale:settori$/.test(x)) && cta.some(x => /^marketing_(cta_click|click_to_form):sgrosso-commerciale:formule$/.test(x)) && cta.some(x => /^marketing_click_to_call:sgrosso-commerciale/.test(x)), cta);
	} else {
		check('eventi delle CTA: link incrociati con posizione e telefono con la landing della pagina', cta.some(x => /^marketing_cta_click::altri_spazi_/.test(x) || /^marketing_cta_click:[^:]*:altri_spazi_/.test(x)) && cta.some(x => new RegExp('^marketing_click_to_call:' + CFG.landing).test(x)), cta);
	}

	// 7. Mobile: barra fissa che compare quando il form esce dallo schermo.
	if (mobile) {
		// Si riparte dalla cima, con il form hero in vista: l'osservatore della barra reagisce solo ai cambi di stato, e dopo
		// i test sul form in fondo la pagina e' gia' scesa con la hero fuori schermo (la barra non vedrebbe nessun cambio).
		await ev(`window.scrollTo(0, 0)`);
		await sleep(700);
		await ev(`document.querySelector('[data-sticky-cta]').hidden = true; window.scrollTo(0, document.documentElement.scrollHeight / 2)`);
		await sleep(800);
		const bar = await ev(`document.querySelector('[data-sticky-cta]').hidden`);
		check('barra fissa visibile a meta pagina', bar === false, bar);
	}

	const ko = checks.filter(c => !c[1]).length;
	console.log(ko ? 'ESITO: ' + ko + ' controlli falliti' : 'ESITO: verde');
	process.exitCode = ko ? 1 : 0;
}).catch(e => { console.error('ERR', e); process.exit(1); });
