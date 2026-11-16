// QA dell'hub /roma/ su "impresa di pulizie a Roma" (TICKET-98, SPEC-crescita-organica-fase-b US-03):
//   PUBBLICATE=/roma/sgrosso-casa/,... ASSENTI=/roma/zanzare-disinfestazione/,... \
//   node tools/qa-hub-roma.js <chrome-bin> <url-hub> [larghezza=390]
// Controlla, nel browser: title (<= 60, "impresa di pulizie" e "Roma", uno solo, og:title uguale), meta description
// (70-160), un solo H1 con le due parole, gerarchia dei titoli senza salti (h1, h2, h3), introduzione sotto la hero con
// h2 e paragrafi che spiegano il marketplace, sezione "Servizi a Roma" con un link per ogni pagina pubblicata
// (PUBBLICATE, lista di percorsi separati da virgola) e nessun link per le pagine in bozza o non create (ASSENTI),
// ogni link risponde 200 (nessuna 404), nessun link ai percorsi non pubblicati in tutta la pagina tranne il footer,
// sezioni esistenti ancora al loro posto (ricerca verso l'app, mappa CAP, barra fissa), LocalBusiness con la
// description della meta, BreadcrumbList, nessun aggregateRating, nessun numero di ditte/clienti/recensioni,
// promessa di tempi o garanzia, emoji o stella nei testi nuovi, bersagli dei link da 44 px, nessuno scroll orizzontale,
// pagina leggibile con JavaScript disattivato.
// Con OVERRIDE=1 la pagina da provare e' una copia dell'hub con roma_hero_title, roma_hero_subtitle, roma_intro_text e
// _seo_title compilati con i valori "PROVA ..." (blueprint di Playground): verifica che un campo compilato vinca sul
// default e che il default non compaia piu'. Esce con 1 se un controllo fallisce. Nessuna scrittura, nessun lead.
const { withChrome } = require('./lib/cdp');
const [BIN, BASE_URL, WIDTH_ARG] = process.argv.slice(2, 5);
const WIDTH = parseInt(WIDTH_ARG, 10) || 390;
const URL = BASE_URL;
const OVERRIDE = process.env.OVERRIDE === '1';
const lista = (v) => (v || '').split(',').map((s) => s.trim()).filter(Boolean);
const PUBBLICATE = lista(process.env.PUBBLICATE);
const ASSENTI = lista(process.env.ASSENTI);

const checks = [];
function check(name, ok, detail) { checks.push([name, ok]); console.log((ok ? 'OK  ' : 'KO  ') + name + (detail !== undefined && detail !== '' ? '  [' + String(detail).slice(0, 300) + ']' : '')); }

withChrome(BIN, URL, { port: 9362, width: WIDTH, mobile: WIDTH < 800, height: WIDTH < 800 ? 844 : 980 }, async ({ send, ev, sleep }) => {
	// Pagina pronta: su Playground la prima risposta puo' essere lenta.
	for (let i = 0; i < 60 && !(await ev(`!!document.querySelector('h1')`)); i++) await sleep(1000);
	await sleep(3200);
	const J = (expr) => ev('JSON.stringify(' + expr + ')').then((v) => (typeof v === 'string' ? JSON.parse(v) : v));
	const pathOf = (u) => u.replace(/^https?:\/\/[^/]+/, '');

	// ---- Title, description, H1, gerarchia ---------------------------------------------------------------------
	const meta = await J(`(function(){
		var h1 = document.querySelectorAll('h1');
		var d = document.querySelector('meta[name=description]');
		var can = document.querySelector('link[rel=canonical]');
		var ogt = document.querySelector('meta[property="og:title"]');
		var heads = Array.prototype.map.call(document.querySelectorAll('main h1, main h2, main h3, main h4'), function (h) { return { l: Number(h.tagName.slice(1)), t: h.textContent.replace(/\\s+/g, ' ').trim() }; });
		return { title: document.title, titles: document.querySelectorAll('title').length, h1n: h1.length, h1: h1[0] ? h1[0].textContent.replace(/\\s+/g, ' ').trim() : '',
			desc: d ? d.content : '', canonical: can ? can.href : '', og: ogt ? ogt.content : '', heads: heads, path: location.pathname };
	})()`);
	if (OVERRIDE) {
		check('campo compilato vince: title = _seo_title della pagina di prova', meta.title === 'PROVA title campo' && meta.og === meta.title, meta.title);
		check('campo compilato vince: H1 = roma_hero_title della pagina di prova', meta.h1n === 1 && meta.h1 === 'PROVA H1 campo', meta.h1);
	} else {
		check('title <= 60 caratteri, uno solo, con "impresa di pulizie", Roma e Trovapulizie', meta.titles === 1 && meta.title.length <= 60 && /impresa di pulizie/i.test(meta.title) && /Roma/.test(meta.title) && /Trovapulizie/.test(meta.title), meta.title.length + ': ' + meta.title);
		check('un solo H1 con "impresa di pulizie" e "Roma"', meta.h1n === 1 && /impresa di pulizie/i.test(meta.h1) && /Roma/.test(meta.h1), meta.h1);
		check('meta description tra 70 e 160 caratteri con "impresa di pulizie" e Roma', meta.desc.length >= 70 && meta.desc.length <= 160 && /impresa di pulizie/i.test(meta.desc) && /Roma/.test(meta.desc), meta.desc.length + ': ' + meta.desc);
		check('og:title uguale al title', meta.og === meta.title, meta.og);
	}
	check('canonical presente e assoluto', /^https?:\/\//.test(meta.canonical), meta.canonical);
	let salti = '';
	meta.heads.forEach((h, i) => { if (i === 0 && h.l !== 1) salti += 'primo titolo h' + h.l + '; '; if (i > 0 && h.l > meta.heads[i - 1].l + 1) salti += 'h' + meta.heads[i - 1].l + ' -> h' + h.l + ' (' + h.t.slice(0, 40) + '); '; });
	check('gerarchia dei titoli senza salti (h1 poi h2, h3 solo sotto un h2)', meta.heads.length > 3 && !salti, salti || meta.heads.map((h) => 'h' + h.l).join(' '));

	// ---- Introduzione -------------------------------------------------------------------------------------------
	const intro = await J(`(function(){
		var s = document.getElementById('cosa-facciamo');
		if (!s) return { present: false };
		var h = s.querySelector('h2');
		var hero = document.getElementById('cerca');
		var ps = Array.prototype.map.call(s.querySelectorAll('p.hub-intro__text'), function (p) { return p.textContent.replace(/\\s+/g, ' ').trim(); });
		return { present: true, h2: h ? h.textContent.trim() : '', ps: ps, afterHero: !!hero && !!(hero.compareDocumentPosition(s) & Node.DOCUMENT_POSITION_FOLLOWING), top: Math.round(s.getBoundingClientRect().top + scrollY), heroBottom: hero ? Math.round(hero.getBoundingClientRect().bottom + scrollY) : 0 };
	})()`);
	check('introduzione presente subito sotto la hero, con h2', intro.present && intro.afterHero && !!intro.h2 && intro.top - intro.heroBottom < 5, JSON.stringify({ h2: intro.h2, gap: intro.top - intro.heroBottom }));
	if (OVERRIDE) {
		check('campo compilato vince: introduzione = roma_intro_text della pagina di prova (due paragrafi)', intro.present && intro.ps.length === 2 && intro.ps[0] === 'PROVA intro uno' && intro.ps[1] === 'PROVA intro due', JSON.stringify(intro.ps));
	} else {
		const testoIntro = (intro.ps || []).join(' ');
		check('introduzione: tre paragrafi che spiegano marketplace, prenotazione dall\'app e sopralluogo con preventivo', intro.ps.length === 3 && /marketplace/i.test(testoIntro) && /imprese di pulizia verificate/i.test(testoIntro) && /app/i.test(intro.ps[1] || '') && /sopralluogo gratuito/i.test(intro.ps[2] || '') && /preventivo/i.test(intro.ps[2] || ''), intro.ps.length + ' paragrafi');
	}

	// ---- Servizi a Roma: un link per ogni pagina pubblicata, nessuno per le altre --------------------------------
	const serv = await J(`(function(){
		var s = document.getElementById('servizi-roma');
		if (!s) return { present: false, links: [] };
		var links = Array.prototype.map.call(s.querySelectorAll('a.hub-services__link'), function (a) { var r = a.getBoundingClientRect(); return { href: a.getAttribute('href').replace(/^https?:\\/\\/[^/]+/, ''), label: a.querySelector('.hub-services__label').textContent.trim(), h: Math.round(r.height), w: Math.round(r.width) }; });
		return { present: true, h2: (s.querySelector('h2') || {}).textContent, groups: Array.prototype.map.call(s.querySelectorAll('h3'), function (h) { return h.textContent.trim(); }), links: links };
	})()`);
	check('sezione "Servizi a Roma" con h2 e gruppi in h3', serv.present && /Servizi a Roma/.test(serv.h2 || '') && serv.groups.length >= 1, JSON.stringify(serv.groups));
	const hrefs = serv.links.map((l) => l.href);
	if (PUBBLICATE.length) {
		check('un link per ogni pagina di servizio pubblicata, nell\'ordine dei gruppi', PUBBLICATE.join() === hrefs.join(), 'trovati ' + hrefs.join(' ') + ' | attesi ' + PUBBLICATE.join(' '));
	} else {
		check('almeno un link ai servizi (PUBBLICATE non indicata)', hrefs.length > 0, hrefs.join(' '));
	}
	check('nessun link per le pagine in bozza o non create nell\'elenco', ASSENTI.every((p) => hrefs.indexOf(p) === -1), ASSENTI.filter((p) => hrefs.indexOf(p) !== -1).join(' '));
	const nelMain = await J(`Array.prototype.map.call(document.querySelectorAll('main a[href]'), function (a) { return a.getAttribute('href').replace(/^https?:\\/\\/[^/]+/, ''); })`);
	check('nessun link alle pagine non pubblicate in tutto il main (hub compreso il blocco Altri servizi)', ASSENTI.every((p) => nelMain.indexOf(p) === -1), ASSENTI.filter((p) => nelMain.indexOf(p) !== -1).join(' '));
	check('ogni link e\' unico e punta sotto /roma/', new Set(hrefs).size === hrefs.length && hrefs.every((h) => /^\/roma\/[a-z-]+\/$/.test(h)), hrefs.join(' '));
	// Ogni link deve rispondere 200: nessun link a una 404.
	const stati = JSON.parse(await ev(`Promise.all(${JSON.stringify(hrefs)}.map(function (h) { return fetch(h, { redirect: 'manual' }).then(function (r) { return h + ' ' + r.status; }).catch(function (e) { return h + ' ERR'; }); })).then(function (a) { return JSON.stringify(a); })`));
	check('ogni link dell\'elenco risponde 200', stati.length === hrefs.length && stati.every((s) => / 200$/.test(s)), stati.filter((s) => !/ 200$/.test(s)).join(' ') || stati.length + ' link');
	check('bersagli dei link da 44 px di altezza e dentro il viewport', serv.links.every((l) => l.h >= 44 && l.w <= WIDTH), serv.links.filter((l) => l.h < 44 || l.w > WIDTH).map((l) => l.label + ' ' + l.h + 'x' + l.w).join(' '));
	check('nessun blocco "Altri servizi a Roma" duplicato sull\'hub (sostituito dall\'elenco)', (await ev(`document.querySelectorAll('.related-services').length`)) === 0);

	// ---- Sezioni esistenti mantenute -----------------------------------------------------------------------------
	const sez = await J(`(function(){
		var f = document.querySelector('#cerca form.tp-search');
		return { search: !!f, action: f ? f.getAttribute('action') : '', name: f && f.querySelector('input') ? f.querySelector('input').name : '', zones: !!document.querySelector('main.hub-roma .zones-map'), sticky: !!document.querySelector('.sticky-cta'),
			order: Array.prototype.map.call(document.querySelectorAll('main.hub-roma > section'), function (s) { return s.id || s.className.split(' ')[0]; }).slice(0, 4) };
	})()`);
	check('ricerca verso l\'app (GET /home, campo address) e barra fissa mobile al loro posto', sez.search && /\/home\/?$/.test(sez.action) && sez.name === 'address' && sez.sticky, JSON.stringify(sez));
	check('ordine: hero, introduzione, servizi, poi le sezioni esistenti', sez.order[0] === 'cerca' && sez.order[1] === 'cosa-facciamo' && sez.order[2] === 'servizi-roma', sez.order.join(' > '));

	// ---- Dati strutturati ------------------------------------------------------------------------------------------
	const ld = await J(`(function(){
		var out = [];
		Array.prototype.forEach.call(document.querySelectorAll('script[type="application/ld+json"]'), function (s) { try { var d = JSON.parse(s.textContent); (Array.isArray(d) ? d : [d]).forEach(function (x) { out.push(x); }); } catch (e) { out.push({ '@type': 'ERRORE' }); } });
		return out;
	})()`);
	const byType = (t) => ld.filter((x) => x['@type'] === t);
	const lb = byType('LocalBusiness');
	check('un solo LocalBusiness con description uguale alla meta description e areaServed Roma', lb.length === 1 && lb[0].description === meta.desc && lb[0].areaServed && lb[0].areaServed.name === 'Roma', lb[0] && lb[0].description);
	const bc = byType('BreadcrumbList');
	// La copia di prova (OVERRIDE) non e' in tp_service_pages(): niente breadcrumb, atteso.
	if (!OVERRIDE) check('BreadcrumbList Home > Roma, URL assoluti', bc.length === 1 && bc[0].itemListElement.length === 2 && bc[0].itemListElement.every((i) => /^https?:\/\//.test(i.item)), JSON.stringify(bc[0] && bc[0].itemListElement.map((i) => i.name)));
	check('nessun JSON-LD non valido e nessun aggregateRating', !ld.some((x) => x['@type'] === 'ERRORE') && !JSON.stringify(ld).includes('aggregateRating'), '');

	// ---- Testi nuovi: contenuti vietati (SPEC §2) ------------------------------------------------------------------
	const nuovo = await ev(`['cerca', 'cosa-facciamo', 'servizi-roma'].map(function (id) { var s = document.getElementById(id); return s ? s.innerText : ''; }).join('\\n')`);
	const vietati = [/\d+\s*(clienti|ditte|imprese|recensioni|zone|quartieri)/i, /recension/i, /\bgarantit/i, /entro\s+\d+\s*(ore|h|giorni|minuti)\b/i, /\d+\s*minuti/i, /certificat/i, /assicurat/i, /★/, /\d+\s*(€|euro)/i];
	const trovati = vietati.filter((re) => re.test(nuovo)).map(String);
	check('hero, introduzione e servizi: nessun numero di ditte/clienti/zone, tempo, garanzia, certificazione, prezzo o stella', trovati.length === 0, trovati.join(' '));
	// Emoji vere (presentazione emoji): il segno \u2713 delle spunte del tema, preesistente, non lo e'.
	check('nessuna emoji nella pagina', !/\p{Emoji_Presentation}/u.test(await ev('document.documentElement.outerHTML')), '');
	const imgsNuove = await J(`Array.prototype.map.call(document.querySelectorAll('#cosa-facciamo img, #servizi-roma img'), function (i) { return i.src; })`);
	check('nessuna immagine nelle sezioni nuove', imgsNuove.length === 0, imgsNuove.join(' '));

	// ---- Layout ------------------------------------------------------------------------------------------------------
	const lay = await J(`({ doc: document.documentElement.scrollWidth, win: innerWidth })`);
	check('nessuno scroll orizzontale a ' + WIDTH + ' px', lay.doc <= lay.win + 1, lay.doc + ' su ' + lay.win);

	// ---- JavaScript disattivato -----------------------------------------------------------------------------------
	await send('Emulation.setScriptExecutionDisabled', { value: true });
	await send('Page.navigate', { url: URL });
	await sleep(3000);
	const nojs = await J(`(function(){
		function vis(el) { var s = getComputedStyle(el); var r = el.getBoundingClientRect(); return s.display !== 'none' && s.visibility !== 'hidden' && parseFloat(s.opacity) > 0.9 && r.width > 0 && r.height > 0; }
		var links = Array.prototype.filter.call(document.querySelectorAll('#servizi-roma a.hub-services__link'), vis);
		var ps = Array.prototype.filter.call(document.querySelectorAll('#cosa-facciamo p.hub-intro__text'), vis);
		return { h1: !!document.querySelector('h1') && vis(document.querySelector('h1')), links: links.length, ps: ps.length };
	})()`);
	check('senza JavaScript: H1, introduzione e link dei servizi visibili', nojs.h1 && nojs.ps === intro.ps.length && nojs.links === hrefs.length, JSON.stringify(nojs));
	await send('Emulation.setScriptExecutionDisabled', { value: false });

	const failed = checks.filter((c) => !c[1]).length;
	console.log('ESITO: ' + (failed ? failed + ' controlli falliti su ' + checks.length : 'verde (' + checks.length + ' controlli)'));
	process.exitCode = failed ? 1 : 0;
});
