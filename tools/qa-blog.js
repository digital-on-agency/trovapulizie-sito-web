// QA del blog (SPEC-blog, TICKET-70..72): node tools/qa-blog.js <chrome-bin> <base-url> <slug-articolo> [larghezza=1440] [cartella-png]
// Servono almeno 7 articoli pubblicati (uno in evidenza + sei in griglia) e l'articolo indicato con almeno 3 H2,
// una checklist (tp-checklist), un consiglio (tp-consiglio) e il servizio collegato "sgrosso-fine-affitto".
// Verifica: indice (title, H1, chip con "Tutti" attivo, evidenza, sei card, paginazione, JSON-LD Blog), pagina di
// categoria (chip attivo, solo articoli di quella categoria), articolo (breadcrumb, H1, sommario, indice con le
// ancore, checklist con le spunte, consiglio, box servizio prima delle FAQ con l'evento, autore, Leggi anche,
// JSON-LD BlogPosting e BreadcrumbList), scroll orizzontale. Esce con 1 se un controllo fallisce.
// CDP_PROFILE=<cartella> usa un profilo Chromium pulito (HTML cachato 7 giorni).
const { withChrome } = require('./lib/cdp');
const [BIN, BASE, SLUG, W, OUT] = process.argv.slice(2, 7);
const width = parseInt(W || '1440', 10);
const mobile = width < 1024;
const q = '?qa=' + Date.now();

const checks = [];
function check(name, ok, detail) { checks.push([name, !!ok]); console.log((ok ? 'OK  ' : 'KO  ') + name + (detail === undefined ? '' : '  [' + JSON.stringify(detail).slice(0, 240) + ']')); }

async function page(url, port, fn) {
	await withChrome(BIN, url, { port: port, width: width, height: 900, mobile: mobile, profile: process.env.CDP_PROFILE ? process.env.CDP_PROFILE + '-' + port : undefined }, async (cdp) => {
		await cdp.sleep(2500);
		await fn(cdp);
	});
}
const snap = async ({ ev, send, shot, sleep }, name) => {
	if (!OUT) return;
	const h = await ev('document.documentElement.scrollHeight');
	await send('Emulation.setDeviceMetricsOverride', { width: width, height: h, deviceScaleFactor: 1, mobile: mobile });
	await sleep(600);
	await shot(OUT + '/blog-' + name + '-' + width + '.png');
};

(async () => {
	// 1. Indice
	await page(BASE + '/blog/' + q, 9381, async (cdp) => {
		const s = JSON.parse(await cdp.ev(`(function(){
			var qa = function (sel) { return Array.prototype.slice.call(document.querySelectorAll(sel)); }, q = function (sel) { return document.querySelector(sel); };
			var ld = qa('script[type="application/ld+json"]').map(function (x) { try { return JSON.parse(x.textContent); } catch (e) { return {}; } });
			var cards = qa('.bl-grid .bl-card');
			var tops = new Set(cards.map(function (c) { return Math.round(c.getBoundingClientRect().top); }));
			return JSON.stringify({
				landing: q('main.bl') && q('main.bl').getAttribute('data-landing'), title: document.title,
				h1: qa('h1').map(function (h) { return h.textContent.replace(/\\s+/g, ' ').trim(); }),
				chips: qa('.bl-chip').map(function (c) { return c.textContent.trim() + (c.classList.contains('is-active') ? '*' : ''); }),
				featured: qa('.bl-card--featured').length, cards: cards.length, perRow: cards.length / (tops.size || 1),
				pages: qa('.bl-pagination .page-numbers').length,
				blog: ld.filter(function (x) { return x['@type'] === 'Blog'; }).length,
				catHref: (qa('.bl-chip')[2] || {}).getAttribute ? qa('.bl-chip')[2].getAttribute('href') : '',
				sw: document.documentElement.scrollWidth, vw: window.innerWidth
			});
		})()`));
		check('indice: main.bl con data-landing blog', s.landing === 'blog', s.landing);
		check('indice: title e H1', /^Blog — Trovapulizie/.test(s.title) && s.h1.length === 1 && s.h1[0] === 'Guide pratiche sulle pulizie a Roma', [s.title, s.h1]);
		check('indice: chip con Tutti attivo, ordine del Figma', s.chips[0] === 'Tutti*' && s.chips.join('|').indexOf('Casa|Sgrosso|Aziende e condomini|Prezzi|Per i professionisti') !== -1, s.chips);
		check('indice: chip di categoria sotto /blog/categoria/', /\/blog\/categoria\/[a-z-]+\/$/.test(s.catHref), s.catHref);
		const cols = width >= 1024 ? 3 : width >= 640 ? 2 : 1;
		check('indice: un articolo in evidenza e sei card su ' + cols + ' colonne', s.featured === 1 && s.cards === 6 && s.perRow === cols, [s.featured, s.cards, s.perRow]);
		check('indice: paginazione numerata', s.pages >= 2, s.pages);
		check('indice: JSON-LD Blog', s.blog === 1, s.blog);
		check('indice: nessuno scroll orizzontale', s.sw <= s.vw, [s.sw, s.vw]);
		await snap(cdp, 'indice');
	});

	// 2. Categoria
	await page(BASE + '/blog/categoria/sgrosso/' + q, 9382, async (cdp) => {
		const s = JSON.parse(await cdp.ev(`(function(){
			var qa = function (sel) { return Array.prototype.slice.call(document.querySelectorAll(sel)); };
			return JSON.stringify({ active: qa('.bl-chip.is-active').map(function (c) { return c.textContent.trim(); }), tags: qa('.bl-card .bl-tag:not(.bl-tag--dark)').map(function (t) { return t.textContent.trim(); }), h1: document.querySelector('h1').textContent.trim() });
		})()`));
		check('categoria: chip Sgrosso attivo e H1', JSON.stringify(s.active) === '["Sgrosso"]' && s.h1 === 'Sgrosso', [s.active, s.h1]);
		check('categoria: solo articoli Sgrosso', s.tags.length > 0 && s.tags.every(t => t === 'Sgrosso'), s.tags);
	});

	// 3. Articolo
	await page(BASE + '/blog/' + SLUG + '/' + q, 9383, async (cdp) => {
		const s = JSON.parse(await cdp.ev(`(function(){
			var qa = function (sel) { return Array.prototype.slice.call(document.querySelectorAll(sel)); }, q = function (sel) { return document.querySelector(sel); };
			var ld = qa('script[type="application/ld+json"]').map(function (x) { try { return JSON.parse(x.textContent); } catch (e) { return {}; } });
			var h2 = qa('.bl-prose h2');
			var toc = qa('.bl-toc a').map(function (a) { return a.getAttribute('href'); });
			var svc = q('.bl-service'), faq = h2.filter(function (h) { return /Domande frequenti/.test(h.textContent); })[0];
			var li = q('.tp-checklist li');
			return JSON.stringify({
				landing: q('main.bl') && q('main.bl').getAttribute('data-landing'), title: document.title,
				crumbs: qa('.bl-crumbs a').map(function (a) { return a.textContent.trim(); }),
				h1: qa('h1').length, lead: !!q('.bl-post__lead'), cover: !!q('.bl-post__cover img'),
				toc: toc, tocOk: toc.length === h2.length && toc.every(function (h) { return !!document.getElementById(h.slice(1)); }),
				tocSticky: q('.bl-toc') ? getComputedStyle(q('.bl-toc')).position : null,
				checkBg: li ? getComputedStyle(li, '::before').backgroundImage.indexOf('svg') !== -1 : false,
				tip: q('.tp-consiglio') ? getComputedStyle(q('.tp-consiglio')).borderLeftWidth : null,
				svc: svc ? [svc.querySelector('a').getAttribute('href').replace(location.origin, ''), svc.querySelector('a').getAttribute('data-cta-position'), !!(faq && (svc.compareDocumentPosition(faq) & Node.DOCUMENT_POSITION_FOLLOWING))] : null,
				services: qa('.bl-service').length,
				author: !!q('.bl-author:not(.bl-author--small)'), related: qa('.bl-related .bl-card').length,
				posting: ld.filter(function (x) { return x['@type'] === 'BlogPosting'; }).map(function (x) { return [x.headline, !!x.image, x.author && x.author.name]; }),
				crumbLd: (ld.filter(function (x) { return x['@type'] === 'BreadcrumbList'; })[0] || {}).itemListElement,
				sw: document.documentElement.scrollWidth, vw: window.innerWidth
			});
		})()`));
		check('articolo: data-landing articolo e title', s.landing === 'articolo' && / — Trovapulizie$/.test(s.title), [s.landing, s.title]);
		check('articolo: breadcrumb Blog / categoria', s.crumbs[0] === 'Blog' && s.crumbs.length === 2, s.crumbs);
		check('articolo: un H1, sommario, copertina', s.h1 === 1 && s.lead && s.cover, [s.h1, s.lead, s.cover]);
		check('articolo: indice con un\'ancora per ogni H2', s.toc.length >= 3 && s.tocOk, s.toc);
		check('articolo: indice fisso solo da 1024px', s.tocSticky === (width >= 1024 ? 'sticky' : 'static'), s.tocSticky);
		check('articolo: checklist con le spunte, consiglio con la barra', s.checkBg && s.tip === '4px', [s.checkBg, s.tip]);
		check('articolo: un box servizio, prima delle FAQ, verso la landing', s.services === 1 && s.svc && s.svc[0] === '/roma/sgrosso-fine-affitto/' && s.svc[1] === 'servizio' && s.svc[2], s.svc);
		check('articolo: autore e tre articoli in Leggi anche', s.author && s.related === 3, [s.author, s.related]);
		check('articolo: JSON-LD BlogPosting e BreadcrumbList', s.posting.length === 1 && s.posting[0][1] && s.posting[0][2] === 'Trovapulizie' && (s.crumbLd || []).length === 3, [s.posting, (s.crumbLd || []).length]);
		check('articolo: nessuno scroll orizzontale', s.sw <= s.vw, [s.sw, s.vw]);
		const evs = JSON.parse(await cdp.ev(`(function(){ window.addEventListener('click', function (e) { e.preventDefault(); }, true); var b=(window.dataLayer||[]).length; document.querySelector('.bl-service a').click(); return JSON.stringify((window.dataLayer||[]).slice(b).filter(function(x){ return x && /^marketing_/.test(x.event); }).map(function(x){ return x.event+':'+x.landing+':'+x.cta_position; })); })()`));
		check('articolo: evento del box servizio', evs.indexOf('marketing_cta_click:articolo:servizio') !== -1, evs);
		await snap(cdp, 'articolo');
	});

	const ko = checks.filter(c => !c[1]).length;
	console.log((ko ? 'KO ' + ko : 'TUTTO OK') + ' su ' + checks.length + ' controlli a ' + width + 'px');
	process.exitCode = ko ? 1 : 0;
})().catch(e => { console.error('ERR', e); process.exit(1); });
