/* QA mobile e tablet della Home (SPEC-home-mobile, TICKET-10 e seguenti).
   Uso: node tools/qa-mobile.js <chrome> <url> [larghezze separate da virgola]
   Default: 360,390,430,768,1023,1024

   Verifica per ogni larghezza:
   - nessuno scroll orizzontale (scrollWidth == larghezza) ed elenco degli elementi che sporgono;
   - la classe motion-ok compare solo da --tp-bp-desktop in su;
   - sotto la soglia non vengono richiesti GSAP (cdnjs) ne' Three.js (jsdelivr);
   - altezza della sezione Come funziona e geometrie utili ai ticket di sezione;
   - peso trasferito e risorse scaricate piu' di una volta (avviso finche' non si esegue TICKET-20).
   Esce con codice 1 se almeno un controllo fallisce. */
const { withChrome } = require('./lib/cdp.js');

const bin = process.argv[2];
const url = process.argv[3];
const widths = (process.argv[4] || '360,390,430,768,1023,1024').split(',').map(Number);
if (!bin || !url) {
	console.error('Uso: node tools/qa-mobile.js <chrome> <url> [larghezze]');
	process.exit(2);
}

const PROBE = `(() => {
	const d = document.scrollingElement;
	const vw = d.clientWidth;
	const off = [];
	document.querySelectorAll('body *').forEach(el => {
		const r = el.getBoundingClientRect();
		if (r.width <= 0 || getComputedStyle(el).position === 'fixed') return;
		if (r.right > vw + 1 || r.left < -1) {
			const cls = typeof el.className === 'string' ? el.className.trim().split(/\\s+/).join('.') : '';
			off.push((el.tagName.toLowerCase() + (cls ? '.' + cls : '')) + ' [' + Math.round(r.left) + '..' + Math.round(r.right) + ']');
		}
	});
	const res = performance.getEntriesByType('resource');
	const seen = {}, dup = [];
	res.forEach(r => {
		const f = r.name.split('/').pop().split('?')[0];
		if (seen[f] && seen[f] !== r.name) { if (dup.indexOf(f) < 0) dup.push(f); } else seen[f] = r.name;
	});
	const hiw = document.querySelector('.how-it-works');
	const steps = [].map.call(document.querySelectorAll('.how-it-works__step'), el => {
		const ph = el.querySelector('.tp-phone');
		const txt = el.querySelector('.how-it-works__text p');
		return {
			w: Math.round(el.getBoundingClientRect().width),
			phone: ph ? Math.round(ph.getBoundingClientRect().width) + 'x' + Math.round(ph.getBoundingClientRect().height) : null,
			testo: txt ? Math.round(txt.getBoundingClientRect().width) : null
		};
	});
	const seqPhone = document.querySelector('.hiw-seq__phone');
	const seq = {
		on: document.documentElement.classList.contains('hiw-seq-ok'),
		schermate: document.querySelectorAll('.hiw-seq__screen').length,
		phone: seqPhone ? Math.round(seqPhone.getBoundingClientRect().width) + 'x' + Math.round(seqPhone.getBoundingClientRect().height) : null,
		sticky: (() => { const k = document.querySelector('.how-it-works__sticky'); return k ? getComputedStyle(k).position : null; })(),
		viewport: Math.round(window.innerHeight)
	};
	// Nessun video deve restare nella sezione: su mobile la sequenza e' di immagini (ADR-002 rev. 5)
	const videoSezione = document.querySelectorAll('.how-it-works video').length;
	const box = el => { if (!el) return null; const r = el.getBoundingClientRect(); return [Math.round(r.left), Math.round(r.top), Math.round(r.width), Math.round(r.height)]; };
	const burger = document.getElementById('nav-burger');
	const nav = {
		burger: burger ? box(burger) : null,
		aria: burger ? burger.getAttribute('aria-expanded') : null,
		panelHidden: document.getElementById('nav-panel') ? document.getElementById('nav-panel').hidden : null,
		accediVisibile: !!document.querySelector('.navbar__cta .tp-btn--secondary') && getComputedStyle(document.querySelector('.navbar__cta .tp-btn--secondary')).display !== 'none',
		h: Math.round(document.querySelector('.navbar').getBoundingClientRect().height)
	};
	const hero = { box: box(document.querySelector('.hero__box')), phone: box(document.querySelector('.hero .tp-phone')), bar: box(document.querySelector('.hero__search-bar')) };
	return JSON.stringify({
		vw: vw,
		scrollWidth: d.scrollWidth,
		motionOk: document.documentElement.classList.contains('motion-ok'),
		bp: getComputedStyle(document.documentElement).getPropertyValue('--tp-bp-desktop').trim(),
		hiwHeight: hiw ? Math.round(hiw.getBoundingClientRect().height) : null,
		cdn: res.filter(r => /cdnjs|jsdelivr/.test(r.name)).map(r => r.name.split('/').pop()),
		kb: Math.round(res.reduce((s, r) => s + r.transferSize, 0) / 1024),
		dup: dup,
		steps: steps,
		seq: seq,
		videoSezione: videoSezione,
		nav: nav,
		hero: hero,
		offenders: off.slice(0, 10)
	});
})()`;

(async () => {
	let failed = 0;
	for (const w of widths) {
		const desktop = w >= 1024;
		// eslint-disable-next-line no-await-in-loop
		await withChrome(bin, url, { port: 9800 + (w % 100), width: w, height: 844, mobile: !desktop }, async ({ ev, sleep }) => {
			await sleep(4500);
			const r = JSON.parse(await ev(PROBE));
			const errors = [];
			if (r.scrollWidth > r.vw) errors.push('scroll orizzontale: scrollWidth ' + r.scrollWidth + ' > ' + r.vw);
			if (r.motionOk !== desktop) errors.push('motion-ok ' + r.motionOk + ', atteso ' + desktop);
			if (!desktop && r.cdn.length) errors.push('risorse CDN richieste sotto soglia: ' + r.cdn.join(', '));
			// Sotto soglia la sezione vale 300dvh con la sequenza attiva, circa 2 viewport nel fallback
			// impilato; i 550vh del pin desktop (oltre 5 viewport) non devono comparire in nessuno dei due.
			if (!desktop) {
				const atteso = r.seq.on ? 3 * r.seq.viewport : null;
				if (atteso && Math.abs(r.hiwHeight - atteso) > atteso * 0.1) errors.push('Come funziona alta ' + r.hiwHeight + 'px, attesi circa ' + atteso + ' (300dvh)');
				if (!r.seq.on && r.hiwHeight > 2.5 * r.seq.viewport) errors.push('fallback impilato alto ' + r.hiwHeight + 'px: sembra la sezione pinnata');
			}
			// Come funziona sotto soglia (TICKET-13 rev. 2): sequenza di schermate o fallback impilato.
			if (!desktop) {
				if (r.steps.length !== 3) errors.push('passi di Come funziona: ' + r.steps.length + ', attesi 3');
				if (r.videoSezione) errors.push(r.videoSezione + ' video ancora nella sezione: su mobile la sequenza e\' di immagini');
				if (r.seq.on) {
					// Sequenza (ADR-002 rev. 5): un telefono solo, tre schermate, contenuto sticky.
					if (r.seq.schermate !== 3) errors.push('schermate della sequenza: ' + r.seq.schermate + ', attese 3');
					if (r.seq.phone !== '158x320') errors.push('telefono della sequenza ' + r.seq.phone + ', atteso 158x320');
					if (r.seq.sticky !== 'sticky') errors.push('il contenuto della sezione non e\' sticky');
				} else {
					r.steps.forEach((st, i) => {
						if (st.phone !== '158x320') errors.push('blocco ' + (i + 1) + ': telefono ' + st.phone + ', atteso 158x320');
					});
				}
				// Navbar mobile (TICKET-11): barra 65, hamburger 24x24 chiuso, Accedi solo nel pannello.
				if (r.nav.h !== 65) errors.push('navbar alta ' + r.nav.h + ', attesa 65');
				if (!r.nav.burger || r.nav.burger[2] !== 24) errors.push('hamburger assente o non 24px');
				if (r.nav.aria !== 'false' || r.nav.panelHidden !== true) errors.push('pannello non chiuso al caricamento');
				if (r.nav.accediVisibile) errors.push('Accedi visibile nella barra: va nel pannello');
				// Hero mobile (TICKET-12): box 270 di altezza e telefono 150x300 che ne sporge sotto.
				if (!r.hero.box || r.hero.box[3] !== 270) errors.push('box hero alto ' + (r.hero.box && r.hero.box[3]) + ', atteso 270');
				if (!r.hero.phone || r.hero.phone[2] !== 150 || r.hero.phone[3] !== 300) errors.push('telefono hero ' + JSON.stringify(r.hero.phone) + ', atteso 150x300');
				if (r.hero.phone && r.hero.box && r.hero.phone[1] + r.hero.phone[3] <= r.hero.box[1] + r.hero.box[3]) errors.push('il telefono non sporge sotto il box della hero');
			}
			// I doppioni restano un avviso finche' non viene eseguito TICKET-20 (peso dei media).
			const avvisi = r.dup.length ? ['scaricate piu\' volte: ' + r.dup.join(', ')] : [];
			const esito = errors.length ? 'KO' : 'ok';
			console.log('[' + esito + '] ' + w + 'px  scrollWidth ' + r.scrollWidth + '  motion-ok ' + r.motionOk + '  Come funziona ' + r.hiwHeight + 'px  ' + r.kb + ' KB');
			errors.forEach(e => console.log('      - ' + e));
			avvisi.forEach(a => console.log('      ~ avviso: ' + a));
			if (r.offenders.length) console.log('      fuori viewport: ' + r.offenders.join(' | '));
			if (errors.length) failed++;
		});
	}
	console.log(failed ? '\nFALLITO su ' + failed + ' larghezze su ' + widths.length : '\nTutte le larghezze ok (' + widths.length + ')');
	process.exit(failed ? 1 : 0);
})();
