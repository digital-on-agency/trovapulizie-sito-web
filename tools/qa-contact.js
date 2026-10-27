// QA della pagina Contatti /contatti/ (TICKET-63..65): node tools/qa-contact.js <chrome-bin> <url> [larghezza=1440] [out.png]
// Verifica struttura e testi del Figma 6803:895 (H1, quattro card, canali, dati aziendali, FAQ), destinazioni dei
// link, eventi marketing_* con cta_position (navigazione bloccata), title e description, JSON-LD Organization con
// ContactPoint e un solo FAQPage, nessun form; colonne delle card per larghezza e scroll orizzontale.
// Esce con 1 se un controllo fallisce. CDP_PROFILE=<cartella> usa un profilo Chromium pulito (HTML cachato 7 giorni).
const { withChrome } = require('./lib/cdp');
const [BIN, URL, W, OUT] = process.argv.slice(2, 6);
const width = parseInt(W || '1440', 10);
const mobile = width < 1024;

const checks = [];
function check(name, ok, detail) { checks.push([name, !!ok]); console.log((ok ? 'OK  ' : 'KO  ') + name + (detail === undefined ? '' : '  [' + JSON.stringify(detail).slice(0, 260) + ']')); }

withChrome(BIN, URL, { port: 9371, width: width, height: 900, mobile: mobile, profile: process.env.CDP_PROFILE }, async ({ ev, sleep, shot, send }) => {
	await sleep(2500);
	// Porta in vista ogni sezione: reveal.js mette .is-in all'ingresso nel viewport.
	await ev(`(async function(){ for (var y = 0; y < document.body.scrollHeight; y += 400) { window.scrollTo(0, y); await new Promise(function(r){ setTimeout(r, 60); }); } window.scrollTo(0, 0); })()`);
	await sleep(1200);

	const s = JSON.parse(await ev(`(function(){
		var q = function (sel) { return document.querySelector(sel); }, qa = function (sel) { return Array.prototype.slice.call(document.querySelectorAll(sel)); };
		var ld = qa('script[type="application/ld+json"]').map(function (x) { try { return JSON.parse(x.textContent); } catch (e) { return null; } });
		var cards = qa('.ct-route__card');
		// cols = righe distinte (top diversi), rows = card per riga
		var cols = cards.length ? new Set(cards.map(function (c) { return Math.round(c.getBoundingClientRect().top); })).size : 0;
		var links = cards.map(function (c) { var a = c.querySelector('.ct-route__link'); return [a.getAttribute('href').replace(location.origin, ''), a.getAttribute('data-cta-position'), a.getAttribute('target') || '', a.getBoundingClientRect().top]; });
		var hidden = qa('.ct-route__card, .ct-info__card').filter(function (el) { return getComputedStyle(el).opacity < 0.99; }).length;
		var channels = q('.ct-info__card--channels'), company = q('.ct-info__card--company');
		return JSON.stringify({
			landing: q('main.ct') ? q('main.ct').getAttribute('data-landing') : null,
			h1: qa('h1').map(function (h) { return h.textContent.trim(); }),
			title: document.title, desc: (q('meta[name="description"]') || {}).content || '',
			cards: cards.map(function (c) { return c.querySelector('.ct-route__card-title').textContent.trim(); }),
			dark: cards.map(function (c) { return c.classList.contains('ct-route__card--dark'); }),
			links: links, rows: cards.length / (cols || 1),
			linkTops: links.map(function (l) { return Math.round(l[3]); }),
			wa: channels ? (channels.querySelector('a[href*="wa.me"]') || {}).getAttribute && channels.querySelector('a[href*="wa.me"]').getAttribute('href') : null,
			waText: channels && channels.querySelector('.ct-info__channel-text a') ? channels.querySelector('.ct-info__channel-text a').textContent.trim() : null,
			mail: channels && channels.querySelector('a[href^="mailto:"]') ? channels.querySelector('a[href^="mailto:"]').getAttribute('href') : null,
			cta: channels && channels.querySelector('.ct-info__cta') ? channels.querySelector('.ct-info__cta').textContent.trim() : null,
			channelsFirst: channels && company ? (channels.getBoundingClientRect().top < company.getBoundingClientRect().top - 1 || channels.getBoundingClientRect().left < company.getBoundingClientRect().left) : null,
			companyRows: company ? qa('.ct-info__row dt').map(function (d) { return d.textContent.trim(); }) : [],
			companyText: company ? company.textContent.replace(/\\s+/g, ' ') : '',
			faq: qa('.faq__item').length, faqOpen: qa('.faq__item.is-open').length,
			faqContact: !!q('.faq__contact'),
			ld: ld.filter(Boolean).map(function (x) { return x['@type']; }),
			org: ld.filter(function (x) { return x && x['@type'] === 'Organization'; })[0] || null,
			forms: qa('main form').length,
			hidden: hidden,
			scrollWidth: document.documentElement.scrollWidth, vw: window.innerWidth
		});
	})()`));

	check('main.ct con data-landing contatti', s.landing === 'contatti', s.landing);
	check('un solo H1 "Come possiamo aiutarti?"', s.h1.length === 1 && s.h1[0] === 'Come possiamo aiutarti?', s.h1);
	check('title e description della SPEC', s.title === 'Contatti — Trovapulizie' && /^Come contattare Trovapulizie: scrivici su WhatsApp/.test(s.desc), [s.title, s.desc.slice(0, 60)]);
	check('quattro card nell\'ordine del Figma', JSON.stringify(s.cards) === JSON.stringify(['Cerchi una pulizia per casa', "Hai un'azienda o un negozio", 'Vuoi lavorare con noi', 'Hai già prenotato']), s.cards);
	check('solo la quarta card scura', JSON.stringify(s.dark) === '[false,false,false,true]', s.dark);
	const L = s.links;
	check('destinazioni e posizioni delle card', L.length === 4
		&& /\/#hero$/.test(L[0][0]) && L[0][1] === 'contatti_card_casa'
		&& /\/roma\/sgrosso-commerciale\/$/.test(L[1][0]) && L[1][1] === 'contatti_card_azienda'
		&& /\/diventa-cleaner\/$/.test(L[2][0]) && L[2][1] === 'contatti_card_lavora'
		&& /^https:\/\/wa\.me\/393200980144\?text=/.test(L[3][0]) && L[3][1] === 'contatti_card_prenotato' && L[3][2] === '_blank', L.map(l => l.slice(0, 3)));
	const cols = width >= 1024 ? 4 : width >= 640 ? 2 : 1;
	check('card su ' + cols + ' colonne', s.rows === cols, s.rows);
	if (width >= 1024) check('link in fondo allineati fra le card', new Set(s.linkTops).size === 1, s.linkTops);
	check('canali: WhatsApp, email, bottone', /^https:\/\/wa\.me\/393200980144$/.test(s.wa || '') && s.waText === '320 098 0144' && s.mail === 'mailto:help@trovapulizie.it' && s.cta === 'Scrivici su WhatsApp', [s.wa, s.waText, s.mail, s.cta]);
	check('canali prima dei dati aziendali', s.channelsFirst === true, s.channelsFirst);
	check('dati aziendali completi', JSON.stringify(s.companyRows) === JSON.stringify(['Sede legale', 'Partita IVA e C.F.', 'REA', 'Iscrizione']) && /Trovapulizie S\.R\.L\./.test(s.companyText) && /18445521000/.test(s.companyText) && /RM-1785357/.test(s.companyText), s.companyRows);
	check('quattro FAQ, la prima aperta, senza card contatto', s.faq === 4 && s.faqOpen === 1 && !s.faqContact, [s.faq, s.faqOpen, s.faqContact]);
	const o = s.org || {};
	const cp = o.contactPoint || {};
	check('JSON-LD Organization con ContactPoint', o.legalName === 'Trovapulizie SRL' && o.vatID === 'IT18445521000' && cp.telephone === '+393200980144' && cp.email === 'help@trovapulizie.it' && cp.contactType === 'customer support', o);
	check('un solo Organization e un solo FAQPage', s.ld.filter(t => t === 'Organization').length === 1 && s.ld.filter(t => t === 'FAQPage').length === 1, s.ld);
	check('nessun form in pagina', s.forms === 0, s.forms);
	check('nessuna card rimasta nascosta dopo lo scroll', s.hidden === 0, s.hidden);
	check('nessuno scroll orizzontale', s.scrollWidth <= s.vw, [s.scrollWidth, s.vw]);

	// Eventi: navigazione bloccata, click su ogni link tracciato.
	const evs = JSON.parse(await ev(`(function(){ window.addEventListener('click', function (e) { e.preventDefault(); }, true); var before=(window.dataLayer||[]).length;
		Array.prototype.forEach.call(document.querySelectorAll('.ct-route__link, .ct-info__cta'), function(a){ a.click(); });
		return JSON.stringify((window.dataLayer||[]).slice(before).filter(function(x){ return x && /^marketing_/.test(x.event); }).map(function(x){ return x.event+':'+(x.landing||'')+':'+(x.cta_position||''); })); })()`));
	const want = ['marketing_cta_click:contatti:contatti_card_casa', 'marketing_cta_click:contatti:contatti_card_azienda', 'marketing_cta_click:contatti:contatti_card_lavora', 'marketing_click_whatsapp:contatti:contatti_card_prenotato', 'marketing_click_whatsapp:contatti:contatti_canali'];
	check('eventi marketing_* con landing e posizione', want.every(w => evs.indexOf(w) !== -1), evs);

	if (OUT) {
		const h = await ev('document.documentElement.scrollHeight');
		await send('Emulation.setDeviceMetricsOverride', { width: width, height: h, deviceScaleFactor: 1, mobile: mobile });
		await sleep(600);
		await shot(OUT);
	}
	const ko = checks.filter(c => !c[1]).length;
	console.log((ko ? 'KO ' + ko : 'TUTTO OK') + ' su ' + checks.length + ' controlli a ' + width + 'px');
	process.exitCode = ko ? 1 : 0;
}).catch(e => { console.error('ERR', e); process.exit(1); });
