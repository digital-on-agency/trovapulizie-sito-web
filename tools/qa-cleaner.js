// QA della pagina Diventa cleaner (TICKET-54..57): node tools/qa-cleaner.js <chrome-bin> <url> [larghezza=1440]
// Verifica: formula del calcolatore (window.tpEarnings), aggiornamento della card allo slider e ai chip, eventi
// dataLayer delle CTA con cta_position (click intercettati con preventDefault: nessuna navigazione), destinazioni
// dei link (signup cleaner, WhatsApp in nuova scheda), sezione partner assente se il campo e' vuoto, un solo
// FAQPage. Esce con 1 se un controllo fallisce.
const { withChrome } = require('./lib/cdp');
const [BIN, URL, W] = process.argv.slice(2, 5);
const width = parseInt(W || '1440', 10);

withChrome(BIN, URL, { port: 9351, width: width, height: 900, mobile: width < 1024 }, async ({ ev, sleep }) => {
	await sleep(4000);
	const r = JSON.parse(await ev(`(function(){
		var out = {};
		var E = window.tpEarnings;
		out.formula = E ? [E.stima(22, 20, 4.33), E.stima(30, 30, 4.33), E.stima(15, 10, 4.33), E.fmt(3900)] : null;
		var total = document.querySelector('[data-earn-total]');
		out.totaleIniziale = total && total.textContent.trim();
		var range = document.querySelector('[data-earn-rate]');
		range.value = 30; range.dispatchEvent(new Event('input', { bubbles: true }));
		var chip = document.querySelector('[data-earn-hours="30"]'); chip.click();
		out.totaleDopo = total.textContent.trim();
		out.formulaDopo = document.querySelector('[data-earn-formula]').textContent.trim();
		out.chipAttivo = document.querySelector('.dc-calc__chip.is-on').getAttribute('data-earn-hours');
		window.addEventListener('click', function (e) { e.preventDefault(); }, true);
		var before = (window.dataLayer || []).length;
		var bar = document.querySelector('[data-sticky-cta]'); if (bar) bar.hidden = false;
		['hero', 'guadagni', 'per-chi', 'final', 'sticky'].forEach(function (p) {
			var a = document.querySelector('a[data-cta-position="' + p + '"][href*="cleaner-signup"]'); if (a) a.click();
		});
		var wa = document.querySelector('a[data-cta-position="per-chi"][href*="wa.me"]'); if (wa) wa.click();
		out.eventi = (window.dataLayer || []).slice(before).filter(function (x) { return x && /^marketing_/.test(x.event); })
			.map(function (x) { return x.event + ':' + x.landing + ':' + (x.cta_position || ''); });
		out.signup = Array.prototype.filter.call(document.querySelectorAll('main a[href]'), function (a) { return a.href.indexOf('/cleaner-signup') !== -1; }).length;
		out.wa = wa ? { target: wa.target, rel: wa.rel, href: wa.href.slice(0, 40) } : null;
		out.partner = !!document.querySelector('.dc-partners');
		out.faqPage = Array.prototype.filter.call(document.querySelectorAll('script[type="application/ld+json"]'), function (s) { return s.textContent.indexOf('FAQPage') !== -1; }).length;
		out.h1 = document.querySelectorAll('h1').length;
		return JSON.stringify(out);
	})()`));
	const checks = [
		['formula 22x20 / 30x30 / 15x10 e separatore', JSON.stringify(r.formula) === JSON.stringify([1900, 3900, 650, '3.900']), r.formula],
		['totale iniziale', r.totaleIniziale === '≈ 1.900 €', r.totaleIniziale],
		['totale dopo slider 30 e chip 30 h', r.totaleDopo === '≈ 3.900 €', r.totaleDopo],
		['formula aggiornata', /^= 30 €\/h × 30 h a settimana/.test(r.formulaDopo), r.formulaDopo],
		['chip attivo', r.chipAttivo === '30', r.chipAttivo],
		['eventi CTA con cta_position', ['hero', 'guadagni', 'per-chi', 'final', 'sticky'].every(p => r.eventi.indexOf('marketing_cta_click:diventa-cleaner:' + p) !== -1), r.eventi],
		['evento WhatsApp con cta_position', r.eventi.indexOf('marketing_click_whatsapp:diventa-cleaner:per-chi') !== -1, r.eventi],
		['link al signup cleaner nella pagina', r.signup >= 5, r.signup],
		['WhatsApp in nuova scheda', r.wa && r.wa.target === '_blank' && /noopener/.test(r.wa.rel), r.wa],
		['partner assenti (campo vuoto)', r.partner === false, r.partner],
		['un solo FAQPage', r.faqPage === 1, r.faqPage],
		['un solo H1', r.h1 === 1, r.h1],
	];
	let ko = 0;
	checks.forEach(([n, ok, v]) => { if (!ok) ko++; console.log((ok ? 'OK  ' : 'KO  ') + n + '  ' + JSON.stringify(v)); });
	console.log(ko ? 'ESITO: ' + ko + ' controlli falliti' : 'ESITO: verde');
	process.exitCode = ko ? 1 : 0;
}).catch(e => { console.error(e); process.exit(1); });
