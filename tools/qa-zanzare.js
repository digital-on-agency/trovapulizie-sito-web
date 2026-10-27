// QA della landing zanzare /roma/zanzare-disinfestazione/ (TICKET-73..76): node tools/qa-zanzare.js <chrome-bin> <url> [larghezza=1440]
// Intercetta via CDP (Fetch) ogni chiamata a */api/leads* e risponde come il backend (201, poi 400 e rete caduta a
// comando): nessun lead reale. Verifica struttura e testi del live (sezioni, trust, autorita', focolai, tab, card,
// passi, recensioni, prezzi, FAQ, due form), il contratto di toZanzareLead (tipo b2c per Privato e b2b per
// Condominio/Attivita', note, email solo se compilata, consentMarketing false), errori di validazione, 400 e rete
// caduta, eventi marketing_form_submit hero e bottom, preselezione di "Chi sei" dalle CTA delle tab, JSON-LD.
// Esce con 1 se un controllo fallisce. CDP_PROFILE=<cartella> usa un profilo Chromium pulito.
const { withChrome } = require('./lib/cdp');
const [BIN, URL, W] = process.argv.slice(2, 5);
const width = parseInt(W || '1440', 10);
const mobile = width < 1024;

const calls = [];
const checks = [];
let mode = 'ok';
function check(name, ok, detail) { checks.push([name, !!ok]); console.log((ok ? 'OK  ' : 'KO  ') + name + (detail === undefined ? '' : '  [' + JSON.stringify(detail).slice(0, 260) + ']')); }
const posts = () => calls.filter(c => c.method === 'POST');

withChrome(BIN, URL, { port: 9391, width: width, height: 900, mobile: mobile, profile: process.env.CDP_PROFILE }, async ({ send, ev, sleep, on }) => {
	on('Fetch.requestPaused', async (p) => {
		const req = p.request;
		let body = {};
		try { body = req.postData ? JSON.parse(req.postData) : {}; } catch (e) {}
		calls.push({ method: req.method, body: body, mode: mode });
		if (mode === 'fail' && req.method !== 'OPTIONS') { await send('Fetch.failRequest', { requestId: p.requestId, errorReason: 'Failed' }); return; }
		let status = 201, json = { ok: true, opportunitaId: 'recZZ1' };
		if (req.method === 'OPTIONS') { status = 204; json = {}; }
		else if (mode === 'bad') { status = 400; json = { message: 'Zona non valida' }; }
		await send('Fetch.fulfillRequest', { requestId: p.requestId, responseCode: status, responseHeaders: [
			{ name: 'Content-Type', value: 'application/json' }, { name: 'Access-Control-Allow-Origin', value: '*' },
			{ name: 'Access-Control-Allow-Methods', value: 'GET,POST,PATCH,OPTIONS' }, { name: 'Access-Control-Allow-Headers', value: 'Content-Type' }
		], body: Buffer.from(JSON.stringify(json)).toString('base64') });
	});
	await send('Fetch.enable', { patterns: [{ urlPattern: '*/api/leads*', requestStage: 'Request' }] });
	// Con un profilo nuovo il primo caricamento puo' superare i 3 s: si aspetta il main della pagina (max 20 s).
	for (let t = 0; t < 40 && !(await ev('!!document.querySelector("main.zz") && document.readyState === "complete"')); t++) await sleep(500);
	await sleep(1500);

	// 1. Struttura
	const s = JSON.parse(await ev(`(function(){
		var q = function (sel) { return document.querySelector(sel); }, qa = function (sel) { return Array.prototype.slice.call(document.querySelectorAll(sel)); };
		var ld = qa('script[type="application/ld+json"]').map(function (x) { try { return JSON.parse(x.textContent); } catch (e) { return {}; } });
		return JSON.stringify({
			landing: q('main.zz') && q('main.zz').getAttribute('data-landing'), title: document.title,
			desc: (q('meta[name="description"]') || {}).content || '',
			h1: qa('h1').map(function (h) { return h.textContent.replace(/\\s+/g, ' ').trim(); }),
			ids: qa('main.zz > section[id]').map(function (x) { return x.id; }),
			trust: qa('.sg-trust__bar li').length, auth: qa('#perche-noi .b2b-why__card').length,
			chips: qa('.zz-tiger__chips li').length,
			tabs: qa('#per-chi [role="tab"]').map(function (t) { return t.textContent.trim(); }),
			services: qa('.zz-services__card').length, banner: !!q('.zz-services__banner a[data-preselect="Condominio"]'),
			steps: qa('#come-funziona .sg-step, .sg-steps__card, .sg-steps li').length,
			reviews: qa('.review-card').length,
			tiers: qa('.zz-price__tier').map(function (t) { return t.querySelector('.zz-price__amount strong').textContent.trim(); }),
			highlighted: qa('.zz-price__tier.is-highlighted').length,
			faq: qa('.faq__item').length,
			forms: qa('form[data-lead-b2b]').map(function (f) { return f.id + ':' + qa('#' + f.id + ' input[name="tipoAttivita"]').map(function (r) { return r.value + '=' + r.getAttribute('data-tipo'); }).join(','); }),
			mq: qa('form[data-lead-b2b] input[name="superficieMq"]').length, marketing: qa('form[data-lead-b2b] input[name="consentMarketing"]').length,
			wa: (q('#lead-hero .lead-form__wa') || {}).href || '',
			service: ld.filter(function (x) { return x['@type'] === 'Service'; }).map(function (x) { return [x.serviceType, !!x.offers]; }),
			faqLd: ld.filter(function (x) { return x['@type'] === 'FAQPage'; }).map(function (x) { return x.mainEntity.length; }),
			sw: document.documentElement.scrollWidth, vw: window.innerWidth
		});
	})()`));
	check('main.zz con data-landing', s.landing === 'zanzare-disinfestazione', s.landing);
	check('title e description del live', s.title === 'Disinfestazione zanzare Roma — Sopralluogo gratuito | Trovapulizie' && /^Disinfestazione zanzare a Roma per privati e aziende/.test(s.desc), s.title);
	check('un H1 del live', s.h1.length === 1 && s.h1[0] === 'Riprenditi il giardino. Zanzare fuori, in sicurezza.', s.h1);
	check('sezioni nell\'ordine del Figma', ['hero', 'perche-noi', 'zanzara-tigre', 'per-chi', 'cosa-facciamo', 'come-funziona'].every((id, i, a) => s.ids.indexOf(id) !== -1 && (i === 0 || s.ids.indexOf(id) > s.ids.indexOf(a[i - 1]))) && s.ids.indexOf('prezzi') > s.ids.indexOf('come-funziona') && s.ids.indexOf('faq') > s.ids.indexOf('prezzi'), s.ids);
	check('trust a 5, autorita\' a 4, 6 focolai', s.trust === 5 && s.auth === 4 && s.chips === 6, [s.trust, s.auth, s.chips]);
	check('quattro tab Per chi', JSON.stringify(s.tabs) === JSON.stringify(['Privato', 'Condominio', 'Bar e ristoranti', 'Hotel e B&B']), s.tabs);
	check('quattro card e banner condomini', s.services === 4 && s.banner, [s.services, s.banner]);
	check('tre recensioni, due prezzi, il secondo evidenziato', s.reviews === 3 && JSON.stringify(s.tiers) === '["da €119","da €449"]' && s.highlighted === 1, [s.reviews, s.tiers, s.highlighted]);
	check('nove FAQ e un FAQPage da 9', s.faq === 9 && JSON.stringify(s.faqLd) === '[9]', [s.faq, s.faqLd]);
	check('due form con Chi sei e i tipi del live', s.forms.length === 2 && s.forms.every(f => /Privato=b2c,Condominio=b2b,Attività=b2b$/.test(f)) && s.mq === 0 && s.marketing === 0, s.forms);
	check('WhatsApp col numero del brand', /^https:\/\/wa\.me\/393200980144\?text=/.test(s.wa), s.wa);
	check('JSON-LD Service zanzare senza offerta', s.service.length === 1 && s.service[0][0] === 'Disinfestazione zanzare' && !s.service[0][1], s.service);
	check('nessuno scroll orizzontale', s.sw <= s.vw, [s.sw, s.vw]);

	const fill = (id, o) => ev(`(function(){
		var f = document.getElementById('${id}');
		var set = function (n, v) { var el = f.querySelector('[name="' + n + '"]'); el.value = v; el.dispatchEvent(new Event('input', { bubbles: true })); };
		${o.chi ? `f.querySelector('input[name="tipoAttivita"][value="${o.chi}"]').click();` : ''}
		set('zona', ${JSON.stringify(o.zona || '')}); set('telefono', ${JSON.stringify(o.tel || '')}); set('email', ${JSON.stringify(o.email || '')});
		var c = f.querySelector('input[name="consentPrivacy"]'); if (c.checked !== ${!!o.privacy}) c.click();
		f.requestSubmit(); return true; })()`);
	const state = id => ev(`(function(){ var f=document.getElementById('${id}'); return JSON.stringify({ done: !f.querySelector('[data-done]').hidden, err: (f.querySelector('.lead-form__api-error')||{}).textContent || '', errHidden: (f.querySelector('.lead-form__api-error')||{}).hidden, invalid: Array.prototype.map.call(f.querySelectorAll('.tp-field__error'), function(e){ return e.textContent; }).filter(Boolean),
		ev: (window.dataLayer||[]).filter(function(x){ return x && x.event === 'marketing_form_submit'; }).map(function(x){ return x.landing + ':' + x.cta_position; }) }); })()`);

	// 2. Validazione: niente invio con campi vuoti
	await fill('lead-hero', {}); await sleep(400);
	const v0 = JSON.parse(await state('lead-hero'));
	check('campi vuoti: errori, nessuna POST', posts().length === 0 && v0.invalid.length >= 3 && !v0.done, v0.invalid);

	// 3. Rete caduta e 400: messaggio, nessuna conferma
	mode = 'fail'; await fill('lead-hero', { chi: 'Privato', zona: 'Parioli, 00197', tel: '3331234567', privacy: true }); await sleep(1500);
	const vf = JSON.parse(await state('lead-hero'));
	check('rete caduta: messaggio e nessuna conferma', !vf.done && !vf.errHidden && vf.err.length > 0, vf.err);
	mode = 'bad'; await fill('lead-hero', { chi: 'Privato', zona: 'Parioli, 00197', tel: '3331234567', privacy: true }); await sleep(1500);
	const vb = JSON.parse(await state('lead-hero'));
	check('400: messaggio dell\'API, nessuna conferma', !vb.done && /Zona non valida/.test(vb.err), vb.err);

	// 4. Condominio dal form in alto: tipo b2b, nessuna email
	mode = 'ok'; calls.length = 0;
	await fill('lead-hero', { chi: 'Condominio', zona: 'EUR, 00144', tel: '3331234567', privacy: true }); await sleep(1500);
	const b1 = (posts()[0] || {}).body || {};
	const s1 = JSON.parse(await state('lead-hero'));
	check('Condominio: una POST tipo b2b con la nota del live', posts().length === 1 && b1.tipo === 'b2b' && b1.note === 'Disinfestazione zanzare · Chi sei: Condominio' && b1.zona === 'EUR, 00144' && b1.telefono === '+393331234567' && !('email' in b1) && b1.consentPrivacy === true && b1.consentMarketing === false, b1);
	check('conferma ed evento hero', s1.done && s1.ev.indexOf('zanzare-disinfestazione:hero') !== -1, s1);

	// 5. Preselezione dalla tab Bar e ristoranti verso il form in fondo? Le CTA delle tab portano a #form (in alto):
	// si prova sul form in fondo con la CTA del banner (Condominio) dopo aver scelto Privato.
	await ev(`(function(){ window.addEventListener('click', function (e) { if (e.target.closest('a[data-preselect]')) e.preventDefault(); }, true);
		var tab = Array.prototype.filter.call(document.querySelectorAll('#per-chi [role="tab"]'), function (t) { return /Bar e ristoranti/.test(t.textContent); })[0]; tab.click(); return true; })()`);
	await sleep(400);
	const pre = JSON.parse(await ev(`(function(){
		var link = document.querySelector('#per-chi .sg-when__panel:not([hidden]) a[data-preselect]');
		link.click();
		var r = document.querySelector('#lead-hero input[name="tipoAttivita"]:checked');
		return JSON.stringify({ pre: link.getAttribute('data-preselect'), label: link.textContent.trim(), checked: r ? r.value : null,
			ev: (window.dataLayer||[]).slice(-3).map(function(x){ return x.event + ':' + (x.cta_position || ''); }) });
	})()`));
	check('tab Bar e ristoranti: CTA del tab preseleziona Attività', pre.pre === 'Attività' && /preventivo con fattura/.test(pre.label), pre);

	// 6. Privato con email dal form in fondo: tipo b2c, email inclusa, evento bottom
	calls.length = 0;
	await fill('lead-bottom', { chi: 'Privato', zona: 'Prati', tel: '3331234567', email: 'test@example.com', privacy: true }); await sleep(1500);
	const b2 = (posts()[0] || {}).body || {};
	const s2 = JSON.parse(await state('lead-bottom'));
	check('Privato con email: tipo b2c, email inclusa', b2.tipo === 'b2c' && b2.email === 'test@example.com' && b2.note === 'Disinfestazione zanzare · Chi sei: Privato', b2);
	check('evento bottom', s2.done && s2.ev.indexOf('zanzare-disinfestazione:bottom') !== -1, s2.ev);

	if (mobile) {
		const bar = await ev(`(function(){ window.scrollTo(0, 2000); return true; })()`);
		await sleep(800);
		const st = JSON.parse(await ev(`(function(){ var b=document.querySelector('[data-sticky-cta]'); return JSON.stringify({ ok: !!b, hidden: b ? b.hidden : null, call: b ? !!b.querySelector('a[href^="tel:"]') : false }); })()`));
		check('barra fissa doppia sotto la hero', st.ok && st.hidden === false && st.call, st);
	}

	const ko = checks.filter(c => !c[1]).length;
	console.log((ko ? 'KO ' + ko : 'TUTTO OK') + ' su ' + checks.length + ' controlli a ' + width + 'px');
	process.exitCode = ko ? 1 : 0;
}).catch(e => { console.error('ERR', e); process.exit(1); });
