/* Controlli a video del footer nuovo (TICKET-128), chiamati da `node tools/qa-site-v2.js footer-v2 <url> --chrome <bin>`.
   Opzioni: --width <px> (1440 o 390, default 1440), --shots <cartella>. Descrizione dei controlli in testa a qa-site-v2.js. */
const { withChrome } = require('./cdp.js');
const fs = require('fs');

module.exports = async function (argv) {
	const opt = n => (argv.includes(n) ? argv[argv.indexOf(n) + 1] : null);
	const url = argv.find((a, i) => !a.startsWith('--') && !['--chrome', '--width', '--shots'].includes(argv[i - 1]));
	const bin = opt('--chrome');
	const width = Number(opt('--width')) || 1440;
	const shots = opt('--shots');
	if (!url || !bin) { console.error('Uso: node tools/qa-site-v2.js footer-v2 <url> --chrome <chrome> [--width 1440|390] [--shots cartella]'); return 2; }
	if (shots) fs.mkdirSync(shots, { recursive: true });
	let ko = 0;
	const check = (nome, ok, d) => { if (!ok) ko++; console.log((ok ? '[ok] ' : '[KO] ') + nome + (ok || d === undefined ? '' : '  -> ' + d)); };
	const mobile = width < 1024;
	const J = JSON.stringify;
	const load = async (c, blocked, noJs) => {
		await c.send('Network.enable');
		if (blocked) await c.send('Network.setBlockedURLs', { urls: blocked });
		if (noJs) await c.send('Emulation.setScriptExecutionDisabled', { value: true });
		await c.send('Page.navigate', { url });
		for (let i = 0; i < 60; i++) { if (await c.ev('document.readyState === "complete"')) break; await c.sleep(500); }
		await c.sleep(1200);
	};
	const opts = { port: 9360 + Math.floor(Math.random() * 30), width, height: 900, mobile };

	// 1. Struttura, contenuti, scroll
	await withChrome(bin, 'about:blank', opts, async c => {
		await load(c);
		const r = JSON.parse(await c.ev(`(function(){
			var f = document.querySelectorAll('footer.site-footer-v2'), q = function(s){ return document.querySelector(s); };
			var groups = [].slice.call(document.querySelectorAll('.site-footer-v2__group'));
			var legal = (q('.site-footer-v2__legal-line') || {}).textContent || '';
			var tel = q('.site-footer-v2__contacts a[href^="tel:"]'), mail = q('.site-footer-v2__contacts a[href^="mailto:"]');
			var b2b = document.querySelectorAll('.site-footer-v2__b2b a[data-cta-position="footer_b2b"]');
			var word = q('.site-footer-v2__word');
			return JSON.stringify({
				n: f.length, old: document.querySelectorAll('footer.site-footer').length,
				logo: !!q('.site-footer-v2__logo img') && /Trovapulizie/.test(q('.site-footer-v2__logo').textContent),
				tel: tel && tel.getAttribute('href'), telText: tel && tel.textContent.trim(), mail: mail && mail.getAttribute('href'), mailText: mail && mail.textContent.trim(),
				titles: groups.map(function(g){ return g.querySelector('summary').textContent.trim(); }),
				empty: groups.filter(function(g){ return !g.querySelector('summary').textContent.trim() || !g.querySelectorAll('li a').length; }).length,
				links: groups.map(function(g){ return g.querySelectorAll('li a').length; }),
				b2b: b2b.length, b2bHref: b2b[0] && b2b[0].getAttribute('href'),
				legal: legal, legalLinks: [].map.call(document.querySelectorAll('.site-footer-v2__legal-links a'), function(a){ return a.textContent.trim(); }),
				word: word && word.getAttribute('aria-hidden'), wordText: word && word.textContent.trim(),
				scroll: document.documentElement.scrollWidth - document.documentElement.clientWidth
			});})()`));
		check('un solo footer.site-footer-v2 e nessun footer.site-footer', r.n === 1 && r.old === 0, r.n + '/' + r.old);
		check('marchio con icona e nome', r.logo);
		check('telefono con link tel: +39', r.tel === 'tel:+393200980144' && r.telText === '320 098 0144', r.tel + ' ' + r.telText);
		check('email con link mailto:', r.mail === 'mailto:help@trovapulizie.it' && r.mailText === 'help@trovapulizie.it', r.mail);
		check('tre colonne piu\' "Servizi a Roma", nessuna vuota', r.titles.length === 4 && r.titles[3] === 'Servizi a Roma' && r.empty === 0, J(r.titles) + ' vuote ' + r.empty);
		check('titoli attesi (Piattaforma, Per i professionisti, Azienda)', r.titles.slice(0, 3).join('|') === 'Piattaforma|Per i professionisti|Azienda', J(r.titles));
		check('riquadro aziende: un link con data-cta-position="footer_b2b"', r.b2b === 1 && !!r.b2bHref, r.b2b + ' ' + r.b2bHref);
		check('dati legali: sede, P.IVA, REA', /Via Paolo Albera 63, 00181 Roma/.test(r.legal) && /P\.IVA 18445521000/.test(r.legal) && /REA RM-1785357/.test(r.legal), r.legal);
		check('link legali (3)', r.legalLinks.length === 3, J(r.legalLinks));
		check('scritta grande TROVAPULIZIE con aria-hidden="true"', r.word === 'true' && r.wordText === 'TROVAPULIZIE', r.word + ' ' + r.wordText);
		check('nessuno scroll orizzontale', r.scroll <= 0, r.scroll);
		// Gruppi e titoli
		const g = JSON.parse(await c.ev(`(function(){
			var gs = [].slice.call(document.querySelectorAll('.site-footer-v2__group'));
			return JSON.stringify({ open: gs.map(function(x){ return x.open; }),
				pe: gs.map(function(x){ return getComputedStyle(x.querySelector('summary')).pointerEvents; }),
				h: gs.map(function(x){ return Math.round(x.querySelector('summary').getBoundingClientRect().height); }),
				vis: gs.map(function(x){ var u = x.querySelector('ul'); var r = u.getBoundingClientRect(); return r.height > 0 && getComputedStyle(u).visibility === 'visible'; }) });})()`));
		if (!mobile) {
			check('1440: gruppi aperti, elenchi visibili, titolo non interattivo', g.open.every(Boolean) && g.vis.every(Boolean) && g.pe.every(v => v === 'none'), J(g));
		} else {
			check('390: gruppi chiusi all\'avvio', g.open.every(v => !v), J(g.open));
			check('390: titolo alto almeno 44 px', g.h.every(v => v >= 44), J(g.h));
			await c.ev(`document.querySelectorAll('.site-footer-v2__group-title')[0].click()`); await c.sleep(500);
			let a = JSON.parse(await c.ev(`JSON.stringify([].map.call(document.querySelectorAll('.site-footer-v2__group'), function(x){ return x.open; }))`));
			check('390: un tocco sul primo titolo apre solo quello', J(a) === '[true,false,false,false]', J(a));
			await c.ev(`document.querySelectorAll('.site-footer-v2__group-title')[1].click()`); await c.sleep(500);
			a = JSON.parse(await c.ev(`JSON.stringify([].map.call(document.querySelectorAll('.site-footer-v2__group'), function(x){ return x.open; }))`));
			check('390: aprire il secondo chiude il primo', J(a) === '[false,true,false,false]', J(a));
			const vis = await c.ev(`(function(){ var u = document.querySelectorAll('.site-footer-v2__group')[1].querySelector('ul'); return u.getBoundingClientRect().height > 20; })()`);
			check('390: elenco del gruppo aperto visibile', vis === true, vis);
		}
		if (shots) {
			await c.ev(`(function(){ var b = document.getElementById('tp-consent'); if (b) { b.style.display = 'none'; } var h = document.querySelector('header'); if (h) { h.style.display = 'none'; } if (window.innerWidth < 1024) { [].forEach.call(document.querySelectorAll('.site-footer-v2__group'), function (x) { x.open = false; }); } })()`);
			await c.ev(`document.querySelector('footer.site-footer-v2').scrollIntoView({block:'end'})`); await c.sleep(600);
			const rect = JSON.parse(await c.ev(`JSON.stringify(document.querySelector('footer.site-footer-v2').getBoundingClientRect().toJSON())`));
			const y = await c.ev('window.scrollY');
			await c.shot(shots + '/footer-' + width + '.png', { x: 0, y: y + rect.top, width: width, height: rect.height, scale: 1 });
		}
	});

	// 2. Con reveal.js bloccato il footer e' visibile
	await withChrome(bin, 'about:blank', Object.assign({}, opts, { port: opts.port + 1 }), async c => {
		await load(c, ['*reveal.js*']);
		const r = JSON.parse(await c.ev(`(function(){ var f = document.querySelector('footer.site-footer-v2'), o = 1, n = f, hid = false;
			for (; n && n.nodeType === 1; n = n.parentElement) { o *= parseFloat(getComputedStyle(n).opacity); }
			return JSON.stringify({ o: o, reveal: !!document.querySelector('script[src*="reveal.js"]'), has: f.hasAttribute('data-reveal') || !!f.querySelector('[data-reveal]') }); })()`));
		check('reveal.js bloccato: footer a opacita\' 1 e senza data-reveal', r.o === 1 && !r.has, J(r));
	});

	// 3. Senza JavaScript i gruppi sono aperti
	await withChrome(bin, 'about:blank', Object.assign({}, opts, { port: opts.port + 2 }), async c => {
		await load(c, null, true);
		const r = JSON.parse(await c.ev(`JSON.stringify([].map.call(document.querySelectorAll('.site-footer-v2__group'), function(x){ var u = x.querySelector('ul'); return x.open && u.getBoundingClientRect().height > 20; }))`));
		check('senza JavaScript: tutti i gruppi aperti e leggibili', r.length === 4 && r.every(Boolean), J(r));
	});
	console.log(ko ? '\nFALLITO: ' + ko + ' controlli' : '\nTutti i controlli superati');
	return ko;
};
