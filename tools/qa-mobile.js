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
	return JSON.stringify({
		vw: vw,
		scrollWidth: d.scrollWidth,
		motionOk: document.documentElement.classList.contains('motion-ok'),
		bp: getComputedStyle(document.documentElement).getPropertyValue('--tp-bp-desktop').trim(),
		hiwHeight: hiw ? Math.round(hiw.getBoundingClientRect().height) : null,
		cdn: res.filter(r => /cdnjs|jsdelivr/.test(r.name)).map(r => r.name.split('/').pop()),
		kb: Math.round(res.reduce((s, r) => s + r.transferSize, 0) / 1024),
		dup: dup,
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
			// 550vh di sezione pinnata sono oltre 5 viewport; la versione impilata del Figma ne misura circa 2.
			if (!desktop && r.hiwHeight > 2.5 * 844) errors.push('Come funziona alta ' + r.hiwHeight + 'px: sembra ancora la sezione pinnata');
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
