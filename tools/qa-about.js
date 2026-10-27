// QA della pagina Chi siamo /chi-siamo/ (TICKET-66..69): node tools/qa-about.js <chrome-bin> <url> [larghezza=1440] [out.png]
// Verifica struttura e testi del Figma 6795:895 con la hero 6814:895 (H1 con la parola in verde, mappa dei CAP con
// le zone coperte, card flottanti e numeri, sezioni nell'ordine, team con foto e iniziali, cinque tappe con l'ultima
// corrente, dati a sinistra dei contatti), destinazioni ed eventi delle CTA (navigazione bloccata), title,
// description, JSON-LD Organization con founder, colonne per larghezza e scroll orizzontale.
// Esce con 1 se un controllo fallisce. CDP_PROFILE=<cartella> usa un profilo Chromium pulito (HTML cachato 7 giorni).
const { withChrome } = require('./lib/cdp');
const [BIN, URL, W, OUT] = process.argv.slice(2, 6);
const width = parseInt(W || '1440', 10);
const mobile = width < 1024;

const checks = [];
function check(name, ok, detail) { checks.push([name, !!ok]); console.log((ok ? 'OK  ' : 'KO  ') + name + (detail === undefined ? '' : '  [' + JSON.stringify(detail).slice(0, 260) + ']')); }

withChrome(BIN, URL, { port: 9372, width: width, height: 900, mobile: mobile, profile: process.env.CDP_PROFILE }, async ({ ev, sleep, shot, send }) => {
	await sleep(2500);
	// Porta in vista ogni sezione: reveal.js mette .is-in all'ingresso nel viewport. In headless
	// IntersectionObserver vede solo i frame disegnati: passi da 300px e un frame prima di ogni attesa.
	await ev(`(async function(){ for (var y = 0; y < document.body.scrollHeight; y += 300) { window.scrollTo(0, y); await new Promise(function(r){ requestAnimationFrame(function(){ setTimeout(r, 180); }); }); } window.scrollTo(0, 0); })()`);
	await sleep(1200);

	const s = JSON.parse(await ev(`(function(){
		var q = function (sel) { return document.querySelector(sel); }, qa = function (sel) { return Array.prototype.slice.call(document.querySelectorAll(sel)); };
		var ld = qa('script[type="application/ld+json"]').map(function (x) { try { return JSON.parse(x.textContent); } catch (e) { return null; } }).filter(Boolean);
		var rowCols = function (sel) { var els = qa(sel); if (!els.length) return 0; var tops = new Set(els.map(function (e) { return Math.round(e.getBoundingClientRect().top); })); return els.length / tops.size; };
		var co = q('.ct-info__card--company'), ch = q('.ct-info__card--channels');
		var photos = qa('.ab-team__photo');
		return JSON.stringify({
			landing: q('main.ab') ? q('main.ab').getAttribute('data-landing') : null,
			h1: qa('h1').map(function (h) { return h.textContent.replace(/\\s+/g, ' ').trim(); }),
			accent: q('.ab-hero__accent') ? q('.ab-hero__accent').textContent : null,
			title: document.title, desc: (q('meta[name="description"]') || {}).content || '',
			sections: qa('main.ab > section').map(function (x) { return x.id; }),
			covered: qa('.ab-hero__svg path[data-state="covered"]').length,
			coveredFill: q('.ab-hero__svg path[data-state="covered"]') ? getComputedStyle(q('.ab-hero__svg path[data-state="covered"]')).fill : null,
			floats: qa('.ab-float').filter(function (f) { return f.getBoundingClientRect().width > 0; }).length,
			stats: qa('.ab-hero__stats dd').map(function (d) { return d.textContent.trim(); }),
			heroCtas: qa('.ab-hero__ctas a').map(function (a) { return [a.textContent.replace(/\\s+/g, ' ').trim(), a.getAttribute('href').replace(location.origin, ''), a.getAttribute('data-cta-position')]; }),
			why: qa('.ab-why__card').length,
			what: qa('#cosa-facciamo .dc-who__card').map(function (c) { var a = c.querySelector('a'); return [c.querySelector('.dc-who__badge').textContent.trim(), a.getAttribute('href').replace(location.origin, ''), a.getAttribute('data-cta-position')]; }),
			rules: qa('.ab-rules__card').length, rulesCols: rowCols('.ab-rules__card'),
			big: qa('.ab-team__card--big .ab-team__name').map(function (n) { return n.textContent; }),
			small: qa('.ab-team__card--small .ab-team__name').map(function (n) { return n.textContent; }),
			photosOk: photos.length && photos.every(function (i) { return i.complete && i.naturalWidth >= 440; }),
			steps: qa('.ab-story__step').length, current: qa('.ab-story__step.is-current .ab-story__title').map(function (t) { return t.textContent; }),
			companyFirst: co && ch ? (co.getBoundingClientRect().left < ch.getBoundingClientRect().left - 1 || co.getBoundingClientRect().top < ch.getBoundingClientRect().top - 1) : null,
			wa: ch && ch.querySelector('a[href*="wa.me"]') ? ch.querySelector('a[href*="wa.me"]').getAttribute('href') : null,
			mail: ch && ch.querySelector('a[href^="mailto:"]') ? ch.querySelector('a[href^="mailto:"]').getAttribute('href') : null,
			cta: q('.cta-final__button') ? [q('.cta-final__button').getAttribute('href').replace(location.origin, ''), q('.cta-final__button').getAttribute('data-cta-position')] : null,
			org: ld.filter(function (x) { return x['@type'] === 'Organization'; }),
			hidden: qa('.ab-why__card, .ab-rules__card, .ab-team__card, .ab-story__step').filter(function (el) { return getComputedStyle(el).opacity < 0.99; }).length,
			scrollWidth: document.documentElement.scrollWidth, vw: window.innerWidth
		});
	})()`));

	check('main.ab con data-landing chi-siamo', s.landing === 'chi-siamo', s.landing);
	check('un solo H1 con "ordine" in verde', s.h1.length === 1 && s.h1[0] === 'Mettiamo ordine nelle pulizie di Roma.' && s.accent === 'ordine', [s.h1, s.accent]);
	check('title e description della SPEC', s.title === 'Chi siamo — Trovapulizie' && /^Trovapulizie è la startup romana/.test(s.desc), [s.title, s.desc.slice(0, 50)]);
	check('sezioni nell\'ordine del Figma, poi la CTA finale', JSON.stringify(s.sections.slice(0, 7)) === JSON.stringify(['hero', 'perche', 'cosa-facciamo', 'come-lavoriamo', 'team', 'storia', 'dati-aziendali']) && s.sections.length === 8, s.sections);
	check('mappa: 26 CAP coperti in verde', s.covered === 26 && s.coveredFill === 'rgb(127, 223, 192)', [s.covered, s.coveredFill]);
	check('card flottanti visibili', s.floats === (width < 640 ? 1 : 2), s.floats);
	check('numeri della copertura', JSON.stringify(s.stats) === '["26","5"]', s.stats);
	check('bottoni della hero', s.heroCtas.length === 2 && /\/#hero$/.test(s.heroCtas[0][1]) && s.heroCtas[0][2] === 'hero' && /\/contatti\/$/.test(s.heroCtas[1][1]) && s.heroCtas[1][2] === 'hero_contatti', s.heroCtas);
	check('tre card di Perche\' esistiamo', s.why === 3, s.why);
	check('Cosa facciamo: due card con eyebrow e CTA', s.what.length === 2 && s.what[0][0] === 'PER CHI CERCA' && /\/#hero$/.test(s.what[0][1]) && s.what[0][2] === 'cosa_facciamo_cerca' && s.what[1][0] === 'PER CHI PULISCE' && /\/diventa-cleaner\/$/.test(s.what[1][1]) && s.what[1][2] === 'cosa_facciamo_partner', s.what);
	const cols = width >= 1024 ? 4 : width >= 640 ? 2 : 1;
	check('quattro regole su ' + cols + ' colonne', s.rules === 4 && s.rulesCols === cols, [s.rules, s.rulesCols]);
	check('team: fondatori con foto, prodotto con iniziale', JSON.stringify(s.big) === '["Christian Pascolini","Guido Spanu"]' && JSON.stringify(s.small) === '["Niccolò","Mattia"]' && s.photosOk, [s.big, s.small, s.photosOk]);
	check('cinque tappe, corrente l\'ultima', s.steps === 5 && JSON.stringify(s.current) === '["Il nuovo sito"]', [s.steps, s.current]);
	check('dati aziendali prima dei contatti', s.companyFirst === true, s.companyFirst);
	check('contatti: WhatsApp ed email', /^https:\/\/wa\.me\/393200980144$/.test(s.wa || '') && s.mail === 'mailto:help@trovapulizie.it', [s.wa, s.mail]);
	check('CTA finale verso la ricerca', s.cta && /\/#hero$/.test(s.cta[0]) && s.cta[1] === 'final', s.cta);
	const o = s.org[0] || {};
	check('un Organization con fondatori e ContactPoint', s.org.length === 1 && o.foundingDate === '2026-02-19' && (o.founder || []).length === 2 && o.contactPoint && o.contactPoint.telephone === '+393200980144', o);
	check('nessuna card rimasta nascosta dopo lo scroll', s.hidden === 0, s.hidden);
	check('nessuno scroll orizzontale', s.scrollWidth <= s.vw, [s.scrollWidth, s.vw]);

	const evs = JSON.parse(await ev(`(function(){ window.addEventListener('click', function (e) { e.preventDefault(); }, true); var before=(window.dataLayer||[]).length;
		Array.prototype.forEach.call(document.querySelectorAll('main.ab a[data-cta-position]'), function(a){ a.click(); });
		return JSON.stringify((window.dataLayer||[]).slice(before).filter(function(x){ return x && /^marketing_/.test(x.event); }).map(function(x){ return x.event+':'+(x.landing||'')+':'+(x.cta_position||''); })); })()`));
	const want = ['marketing_cta_click:chi-siamo:hero', 'marketing_cta_click:chi-siamo:hero_contatti', 'marketing_cta_click:chi-siamo:cosa_facciamo_cerca', 'marketing_cta_click:chi-siamo:cosa_facciamo_partner', 'marketing_click_whatsapp:chi-siamo:chi_siamo_contatti', 'marketing_cta_click:chi-siamo:final'];
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
