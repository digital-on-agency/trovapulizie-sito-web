// QA della guida prezzi /roma/prezzi-pulizie/ (TICKET-96, SPEC-crescita-organica-fase-b US-01):
//   node tools/qa-guida-prezzi.js <chrome-bin> <url> [larghezza=390]
// Controlla, nel browser: H1 e title, tabella (cinque servizi x 50/80/100/120 mq) con ogni cifra uguale a
// window.tpStimaSgrosso (la formula di sgrosso-estimator.js) e a window.tpSgrossoStima, link a ogni landing, note
// oneste sulla formula unica, calcolatore e form lead presenti con landing 'prezzi-pulizie' e tipo di sgrosso casa,
// FAQ (4-6) con un solo FAQPage, Service con offerta 200-700, BreadcrumbList, meta description, nessun numero di
// clienti/ditte/recensioni, pagina leggibile con JavaScript disattivato, nessuno scroll orizzontale della pagina e
// tabella scorrevole sotto 1024px. Con COMPARE_URL=<url di una landing sgrosso> confronta anche la formula con quella
// della landing viva (copre i campi sg_calc_* compilati a mano). Il flusso del form con API simulata e' di
// qa-sgrosso.js: EXPECT_LANDING=prezzi-pulizie EXPECT_PATH=/roma/prezzi-pulizie/ node tools/qa-sgrosso.js <chrome> <url>.
// Esce con 1 se un controllo fallisce. Nessun lead reale: la pagina non invia nulla.
const { withChrome } = require('./lib/cdp');
const [BIN, BASE_URL, WIDTH_ARG] = process.argv.slice(2, 5);
const WIDTH = parseInt(WIDTH_ARG, 10) || 390;
const URL = BASE_URL;

const LANDING_PATHS = ['/roma/sgrosso-casa/', '/roma/sgrosso-post-ristrutturazione/', '/roma/sgrosso-trasloco/', '/roma/sgrosso-fine-affitto/', '/roma/sgrosso-prima-pulizia/'];
const METRATURE = [50, 80, 100, 120];

const checks = [];
function check(name, ok, detail) { checks.push([name, ok]); console.log((ok ? 'OK  ' : 'KO  ') + name + (detail !== undefined && detail !== '' ? '  [' + String(detail).slice(0, 300) + ']' : '')); }

withChrome(BIN, URL, { port: 9361, width: WIDTH, mobile: WIDTH < 800, height: WIDTH < 800 ? 844 : 980 }, async ({ send, ev, sleep }) => {
	await sleep(3200);
	const J = (expr) => ev('JSON.stringify(' + expr + ')').then((v) => (typeof v === 'string' ? JSON.parse(v) : v));

	// ---- Struttura e testi ------------------------------------------------------------------------------------
	const meta = await J(`(function(){
		var h1 = document.querySelectorAll('h1');
		var d = document.querySelector('meta[name=description]');
		var can = document.querySelector('link[rel=canonical]');
		var ogt = document.querySelector('meta[property="og:title"]');
		return { title: document.title, titles: document.querySelectorAll('title').length, h1n: h1.length, h1: h1[0] ? h1[0].textContent.trim() : '',
			desc: d ? d.content : '', canonical: can ? can.href : '', og: ogt ? ogt.content : '', path: location.pathname,
			landing: document.querySelector('main') ? document.querySelector('main').getAttribute('data-landing') : '' };
	})()`);
	check('title <= 60 caratteri, uno solo, con Roma e Trovapulizie', meta.titles === 1 && meta.title.length > 0 && meta.title.length <= 60 && /Roma/.test(meta.title) && /Trovapulizie/.test(meta.title), meta.title.length + ': ' + meta.title);
	check('un solo H1 sul prezzo delle pulizie straordinarie a Roma', meta.h1n === 1 && /prezzo/i.test(meta.h1) && /pulizie straordinarie/i.test(meta.h1) && /Roma/.test(meta.h1), meta.h1);
	check('meta description tra 70 e 160 caratteri', meta.desc.length >= 70 && meta.desc.length <= 160, meta.desc.length + ': ' + meta.desc);
	check('og:title uguale al title', meta.og === meta.title, meta.og);
	check('main con data-landing prezzi-pulizie', meta.landing === 'prezzi-pulizie', meta.landing);

	// ---- Tabella: cifre contro la formula del browser ---------------------------------------------------------
	const tab = await J(`(function(){
		var t = document.querySelector('table[data-price-table]');
		if (!t) return { present: false };
		var root = document.querySelector('[data-estimator]');
		var c = root ? { base: parseFloat(root.getAttribute('data-base')), perMq: parseFloat(root.getAttribute('data-per-mq')), extraMin: parseFloat(root.getAttribute('data-extra-min')), extraMax: parseFloat(root.getAttribute('data-extra-max')), maxMult: parseFloat(root.getAttribute('data-max-mult')) } : null;
		var rows = Array.prototype.map.call(t.querySelectorAll('tbody tr'), function (tr) {
			var a = tr.querySelector('th a');
			return { href: a ? a.getAttribute('href') : '', label: a ? a.textContent.trim() : '', cells: Array.prototype.map.call(tr.querySelectorAll('td'), function (td) {
				var s = window.tpStimaSgrosso ? window.tpStimaSgrosso(Number(td.getAttribute('data-mq')), 0, c) : null;
				var s2 = window.tpSgrossoStima ? window.tpSgrossoStima(Number(td.getAttribute('data-mq')), 0) : null;
				var nums = (td.textContent.match(/\\d+/g) || []).map(Number);
				return { mq: Number(td.getAttribute('data-mq')), min: Number(td.getAttribute('data-min')), max: Number(td.getAttribute('data-max')), text: td.textContent.replace(/\\u00a0/g, ' ').trim(), nums: nums, js: s, js2: s2 };
			}) };
		});
		var heads = Array.prototype.map.call(t.querySelectorAll('thead th'), function (th) { return th.textContent.replace(/\\u00a0/g, ' ').trim(); });
		return { present: true, rows: rows, heads: heads, coef: c };
	})()`);
	check('tabella dei prezzi presente', tab.present === true);
	if (tab.present) {
		check('intestazioni: Servizio e le quattro metrature', JSON.stringify(tab.heads) === JSON.stringify(['Servizio'].concat(METRATURE.map((m) => m + ' m²'))), JSON.stringify(tab.heads));
		check('cinque righe, una per servizio, con link alla landing in ordine', tab.rows.length === 5 && tab.rows.every((r, i) => r.href.replace(/^https?:\/\/[^/]+/, '') === LANDING_PATHS[i]), JSON.stringify(tab.rows.map((r) => r.href)));
		check('ogni riga ha quattro celle 50/80/100/120', tab.rows.every((r) => r.cells.map((c) => c.mq).join() === METRATURE.join()), '');
		let tutteUguali = true; let dettaglio = '';
		for (const r of tab.rows) {
			for (const c of r.cells) {
				const ok = c.js && c.min === c.js.min && c.max === c.js.max && c.js2 && c.min === c.js2.min && c.max === c.js2.max && c.nums.join() === [c.min, c.max].join();
				if (!ok) { tutteUguali = false; dettaglio += r.label + ' ' + c.mq + ': ' + c.text + ' / tpStimaSgrosso ' + JSON.stringify(c.js) + ' / tpSgrossoStima ' + JSON.stringify(c.js2) + '; '; }
			}
		}
		check('ogni cifra della tabella uguale a tpStimaSgrosso e tpSgrossoStima (formula del browser, extra 0)', tutteUguali, dettaglio || (tab.rows.length * 4) + ' celle');
		check('celle con testo "min–max €" e min < max', tab.rows.every((r) => r.cells.every((c) => c.min > 0 && c.max > c.min && /^\d[\d.]*–\d[\d.]*\s€$/.test(c.text))), tab.rows[0] ? tab.rows[0].cells.map((c) => c.text).join(' | ') : '');
		const uguali = tab.rows.every((r) => JSON.stringify(r.cells.map((c) => [c.min, c.max])) === JSON.stringify(tab.rows[0].cells.map((c) => [c.min, c.max])));
		const nota = await ev(`(function(){ var n = document.querySelector('.pg-table__note--same'); return n ? n.textContent.trim() : ''; })()`);
		check('formula uguale per tutti i servizi: la pagina lo dichiara (e solo in quel caso)', uguali === (nota !== '') && (!uguali || /non dal tipo di servizio/.test(nota)), nota);
		if (process.env.COMPARE_URL) {
			const mine = tab.rows[0].cells.map((c) => c.min + '-' + c.max).join();
			await send('Page.navigate', { url: process.env.COMPARE_URL });
			await sleep(3000);
			const theirs = await ev(`window.tpSgrossoStima ? ${JSON.stringify(METRATURE)}.map(function(m){ var s = window.tpSgrossoStima(m, 0); return s.min + '-' + s.max; }).join() : 'assente'`);
			check('cifre uguali a quelle del calcolatore della landing viva (' + process.env.COMPARE_URL + ')', mine === theirs, mine + ' contro ' + theirs);
			await send('Page.navigate', { url: URL });
			await sleep(3000);
		}
	}

	// ---- Calcolatore: stessa formula, stessa cifra della tabella a 100 mq -------------------------------------
	const calc = await J(`(function(){
		var root = document.querySelector('[data-estimator]');
		if (!root) return { present: false };
		return { present: true, id: root.id, mq: root.querySelector('input[name=mq]').value, min: root.querySelector('[data-price-min]').textContent, max: root.querySelector('[data-price-max]').textContent,
			tipo: (root.querySelector('input[name=tipo]:checked') || {}).value || '', extras: root.querySelectorAll('input[name=extra]').length };
	})()`);
	check('calcolatore riusato (#quanto-costa) con quattro tipi e quattro extra', calc.present && calc.id === 'quanto-costa' && calc.extras === 4, JSON.stringify(calc));
	if (calc.present && tab.present) {
		const riga100 = tab.rows[0].cells.find((c) => c.mq === 100);
		check('calcolatore a 100 mq mostra la stessa fascia della tabella', riga100 && Number(calc.min) === riga100.min && Number(calc.max) === riga100.max, calc.min + '-' + calc.max);
		// Il calcolatore anima i valori solo quando il risultato entra nel viewport: lo si porta in vista, poi si sposta lo slider a 80 mq.
		await ev(`document.querySelector('[data-estimator] .sg-calc__result').scrollIntoView({ block: 'center' })`);
		await sleep(2500);
		await ev(`(function(){ var r = document.querySelector('[data-estimator] input[name=mq]'); r.value = 80; r.dispatchEvent(new Event('input', { bubbles: true })); })()`);
		await sleep(2000);
		const dopo = await J(`(function(){ var root = document.querySelector('[data-estimator]'); return { y: scrollY, top: Math.round(root.querySelector('.sg-calc__result').getBoundingClientRect().top), min: Number(root.querySelector('[data-price-min]').textContent), max: Number(root.querySelector('[data-price-max]').textContent), att: window.tpSgrossoStima(80, 0) }; })()`);
		const r80 = tab.rows[0].cells.find((c) => c.mq === 80);
		check('slider a 80 mq: il calcolatore segue la formula e coincide con la tabella', dopo.min === dopo.att.min && dopo.max === dopo.att.max && r80 && dopo.min === r80.min && dopo.max === r80.max, JSON.stringify(dopo));
	}

	// ---- Form lead (hero e fondo) ---------------------------------------------------------------------------------
	const forms = await J(`Array.prototype.map.call(document.querySelectorAll('form[data-lead-form]'), function (f) { var c = f.querySelector('input[name=tipoPulizia]:checked'); return { id: f.id, landing: f.getAttribute('data-landing'), pos: f.getAttribute('data-cta-position'), tipo: c ? c.value : '' }; })`);
	check('due form lead (hero e fondo) con landing prezzi-pulizie e tipo di sgrosso casa preselezionato', forms.length === 2 && forms.every((f) => f.landing === 'prezzi-pulizie' && f.tipo === 'post-ristrutturazione') && forms.map((f) => f.pos).join() === 'hero,bottom', JSON.stringify(forms));
	check('un solo form del calcolatore con landing prezzi-pulizie', (await ev(`(document.querySelector('form[data-lead-atomic]') || document.body).getAttribute('data-landing')`)) === 'prezzi-pulizie');

	// ---- FAQ, dati strutturati -------------------------------------------------------------------------------------
	const ld = await J(`(function(){
		var out = [];
		Array.prototype.forEach.call(document.querySelectorAll('script[type="application/ld+json"]'), function (s) { try { var d = JSON.parse(s.textContent); (Array.isArray(d) ? d : [d]).forEach(function (x) { out.push(x); }); } catch (e) { out.push({ '@type': 'ERRORE' }); } });
		return out;
	})()`);
	const byType = (t) => ld.filter((x) => x['@type'] === t);
	const faq = byType('FAQPage');
	check('un solo FAQPage con 4-6 domande e risposte', faq.length === 1 && faq[0].mainEntity.length >= 4 && faq[0].mainEntity.length <= 6 && faq[0].mainEntity.every((q) => q.name && q.acceptedAnswer && q.acceptedAnswer.text), faq[0] ? faq[0].mainEntity.length : 0);
	const svc = byType('Service');
	check('un solo Service con offerta 200-700 EUR e provider senza indirizzo stradale', svc.length === 1 && svc[0].offers && svc[0].offers.priceSpecification.minPrice === 200 && svc[0].offers.priceSpecification.maxPrice === 700 && svc[0].offers.priceSpecification.priceCurrency === 'EUR' && !svc[0].provider.address.streetAddress && /\/roma\/prezzi-pulizie\/$/.test(svc[0].url), JSON.stringify(svc[0] && svc[0].offers));
	const bc = byType('BreadcrumbList');
	check('BreadcrumbList Home > Roma > pagina, URL assoluti', bc.length === 1 && bc[0].itemListElement.length === 3 && bc[0].itemListElement.map((i) => i.position).join() === '1,2,3' && /\/roma\/$/.test(bc[0].itemListElement[1].item) && /\/roma\/prezzi-pulizie\/$/.test(bc[0].itemListElement[2].item) && bc[0].itemListElement.every((i) => /^https?:\/\//.test(i.item)), JSON.stringify(bc[0] && bc[0].itemListElement.map((i) => i.name)));
	check('nessun JSON-LD non valido e nessun aggregateRating', !ld.some((x) => x['@type'] === 'ERRORE') && !JSON.stringify(ld).includes('aggregateRating'), '');

	// ---- Altri servizi, link interni, contenuti vietati ---------------------------------------------------------
	const rel = await J(`Array.prototype.map.call(document.querySelectorAll('.related-services__link'), function (a) { return a.getAttribute('href').replace(/^https?:\\/\\/[^/]+/, ''); })`);
	check('blocco "Altri servizi a Roma": hub e pagine di servizio, non la pagina stessa', rel.length >= 5 && rel.indexOf('/roma/') !== -1 && rel.indexOf('/roma/prezzi-pulizie/') === -1 && rel.indexOf('/roma/sgrosso-casa/') !== -1, rel.join(' '));
	const foot = await J(`Array.prototype.map.call(document.querySelectorAll('footer a'), function (a) { return a.getAttribute('href').replace(/^https?:\\/\\/[^/]+/, ''); })`);
	check('footer: la guida prezzi e fra i servizi a Roma', foot.indexOf('/roma/prezzi-pulizie/') !== -1, '');
	const testo = await ev(`document.querySelector('main').innerText`);
	const vietati = [/\d+\s*(clienti|ditte|imprese|recensioni)/i, /recension/i, /\bgarantit/i, /entro\s+\d+\s*(ore|h)\b/i, /\u2605/];
	const trovati = vietati.filter((re) => re.test(testo)).map(String);
	check('nessun numero di clienti/ditte/recensioni, nessuna promessa di tempi o garanzie, nessuna stella', trovati.length === 0, trovati.join(' '));
	check('nessuna emoji', !/[\u{1F300}-\u{1FAFF}\u{2600}-\u{27BF}]/u.test((await ev('document.documentElement.outerHTML')).replace(/\u2605/g, '')), '');
	const imgs = await J(`Array.prototype.map.call(document.querySelectorAll('main img'), function (i) { return i.getAttribute('src'); }).filter(function (s) { return s && s.indexOf('/themes/trovapulizie/') === -1 && s.indexOf('http') === 0; })`);
	check('solo immagini del tema (nessuna immagine nuova o esterna)', imgs.length === 0, imgs.join(' '));
	const cta = await J(`Array.prototype.map.call(document.querySelectorAll('.landing-nav a, .sticky-cta a'), function (a) { return a.getAttribute('href'); })`);
	check('CTA della testata e barra fissa verso #form, #quanto-costa, tel:', cta.some((h) => h === '#form') && cta.some((h) => h === '#quanto-costa') && cta.some((h) => /^tel:/.test(h)), cta.join(' '));
	const ancore = await J(`['tabella-prezzi','fattori-prezzo','quanto-costa','faq','form','form-bottom','prezzi'].filter(function (id) { return !document.getElementById(id); })`);
	check('ancore della testata e dei link presenti nella pagina', ancore.length === 0, ancore.join(' '));

	// ---- Layout: nessuno scroll orizzontale, tabella scorrevole sotto 1024 ---------------------------------------
	const lay = await J(`(function(){ var sc = document.querySelector('.pg-table__scroll'); var t = document.querySelector('.pg-table__table'); var th = t ? t.querySelector('tbody th') : null;
		return { doc: document.documentElement.scrollWidth, win: innerWidth, scrollW: sc ? sc.scrollWidth : 0, clientW: sc ? sc.clientWidth : 9e9, sticky: th ? getComputedStyle(th).position : '', tableW: t ? t.getBoundingClientRect().width : 0 }; })()`);
	check('nessuno scroll orizzontale della pagina a ' + WIDTH + ' px', lay.doc <= lay.win + 1, lay.doc + ' su ' + lay.win);
	if (WIDTH < 600) check('su telefono la tabella scorre nel suo contenitore e la prima colonna resta ferma', lay.scrollW > lay.clientW && lay.sticky === 'sticky', JSON.stringify(lay));
	else check('da 600 px la tabella entra nel contenitore senza scorrimento e senza sforare la pagina', lay.scrollW <= lay.clientW + 1 && lay.doc <= lay.win + 1, JSON.stringify(lay));

	// ---- JavaScript disattivato -----------------------------------------------------------------------------------
	await send('Emulation.setScriptExecutionDisabled', { value: true });
	await send('Page.navigate', { url: URL });
	await sleep(3000);
	const nojs = await J(`(function(){
		function vis(el) { var s = getComputedStyle(el); var r = el.getBoundingClientRect(); return s.display !== 'none' && s.visibility !== 'hidden' && parseFloat(s.opacity) > 0.9 && r.width > 0 && r.height > 0; }
		var tds = document.querySelectorAll('table[data-price-table] td');
		var cards = document.querySelectorAll('.b2b-why__card');
		var items = document.querySelectorAll('.faq__item');
		var ans = document.querySelectorAll('.faq__a-inner');
		var links = Array.prototype.filter.call(document.querySelectorAll('table[data-price-table] tbody th a'), vis);
		return { tds: tds.length, tdsText: Array.prototype.filter.call(tds, function (t) { return /\\d/.test(t.textContent) && vis(t); }).length, h1: !!document.querySelector('h1') && vis(document.querySelector('h1')), links: links.length,
			cards: Array.prototype.filter.call(cards, vis).length, cardsN: cards.length, items: Array.prototype.filter.call(items, vis).length, itemsN: items.length,
			ansOpen: Array.prototype.filter.call(ans, function (a) { return vis(a) && a.getBoundingClientRect().height > 10; }).length, ansN: ans.length,
			form: !!document.querySelector('form[data-lead-form]') };
	})()`);
	check('senza JavaScript: tabella completa (20 cifre visibili) e cinque link alle landing', nojs.tds === 20 && nojs.tdsText === 20 && nojs.links === 5 && nojs.h1, JSON.stringify(nojs));
	check('senza JavaScript: fattori e risposte delle FAQ visibili (nessun testo nascosto)', nojs.cardsN > 0 && nojs.cards === nojs.cardsN && nojs.itemsN > 0 && nojs.items === nojs.itemsN && nojs.ansOpen === nojs.ansN, JSON.stringify(nojs));
	await send('Emulation.setScriptExecutionDisabled', { value: false });

	const failed = checks.filter((c) => !c[1]).length;
	console.log('ESITO: ' + (failed ? failed + ' controlli falliti su ' + checks.length : 'verde (' + checks.length + ' controlli)'));
	process.exitCode = failed ? 1 : 0;
});
