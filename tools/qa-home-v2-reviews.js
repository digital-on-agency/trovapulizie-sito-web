/* QA recensioni e fascia loghi della Home v2 (SPEC-homepage-v2, TICKET-124).
   Uso: node tools/qa-home-v2-reviews.js <chrome> <url-pagina> [larghezza=1440] [--recensioni N] [--loghi M]
   N = numero di foglietti attesi (0 = sezione 06 assente), M = numero di voci della fascia (0 = fascia assente). Default 0 e 0.
   Gruppo separato da qa-home-v2.js (che lo richiama a mano) per non toccare un file condiviso fra i ticket paralleli.

   Controlli (sempre):
   - nessun JSON-LD Review o AggregateRating nell'HTML, nessun errore PHP, nessuna barra di scorrimento orizzontale;
   - N = 0: nessun elemento .hv2-rev e nessun data-section="06-recensioni" nel DOM; M = 0: nessuna .hv2-rev__band;
   - tra 05-manifesto e la sezione successiva visibile (07-zone o 06-loghi) nessun buco (distanza 0, stessa dei 04-05 e 05-07);
   N > 0:
   - N foglietti (.hv2-rev__note) dentro un .tp-carousel con role="region"; a 1024+ in fila (griglia), stessa altezza nella stessa riga;
   - a < 1024: carosello che scorre in orizzontale con indicatore visibile; un h2 nella sezione;
   M > 0:
   - M voci nel primo gruppo della fascia (.tp-marquee__group), copie con aria-hidden, la fascia occupa tutta la larghezza. */
const { withChrome, sleep } = require('./lib/cdp.js');
const args = process.argv.slice(2);
const flag = (n, d) => { const i = args.indexOf('--' + n); return i >= 0 ? +args[i + 1] : d; };
const pos = args.filter((a, i) => !a.startsWith('--') && !(i > 0 && args[i - 1].startsWith('--')));
const [bin, url] = pos;
const width = +pos[2] || 1440;
const N = flag('recensioni', 0), M = flag('loghi', 0);
let fail = 0;
const check = (name, ok, info) => { console.log((ok ? 'OK   ' : 'FAIL ') + name + (ok ? '' : '  -> ' + info)); if (!ok) fail = 1; };
(async () => {
	const html = await (await fetch(url)).text();
	check('nessun JSON-LD Review/AggregateRating', !/AggregateRating|"@type"\s*:\s*"Review"/.test(html), 'trovato');
	check('nessun errore PHP', !/(Fatal error|Warning:|Notice:|Deprecated:)/.test(html), 'errore nell\'HTML');
	if (N === 0) check('N=0: sezione 06-recensioni non nel DOM e nessun .hv2-rev__note', !/data-section="06-recensioni"/.test(html) && !/hv2-rev__note/.test(html), 'presente');
	if (M === 0) check('M=0: nessuna fascia loghi', !/hv2-rev__band/.test(html), 'presente');
	if (N === 0 && M === 0) check('N=0 e M=0: nessun elemento .hv2-rev', !/hv2-rev/.test(html), 'presente');
	await withChrome(bin, url, { port: 9500 + (width % 100), width, height: width > 600 ? 1000 : 844, mobile: width < 600, profile: '/tmp/cdp-qa124-' + width }, async ({ ev }) => {
		await sleep(7000);
		await ev(`document.querySelectorAll('img[loading=lazy]').forEach(i=>i.loading='eager'); document.querySelectorAll('[data-reveal]').forEach(e=>e.classList.add('is-in')); 0`);
		await sleep(800);
		const hs = await ev('document.documentElement.scrollWidth <= document.documentElement.clientWidth');
		check('nessuna barra di scorrimento orizzontale', hs === true, hs);
		const secs = JSON.parse(await ev(`JSON.stringify([...document.querySelectorAll('main.hv2 > [data-section]')].map(s=>{var b=s.getBoundingClientRect();return [s.dataset.section,Math.round(b.top+scrollY),Math.round(b.bottom+scrollY)]}))`));
		const i5 = secs.findIndex(s => s[0] === '05-manifesto');
		const nxt = secs[i5 + 1];
		check('dopo 05-manifesto nessun buco (' + (nxt ? nxt[0] : '-') + ')', !!nxt && Math.abs(nxt[1] - secs[i5][2]) <= 1, JSON.stringify(secs.slice(i5, i5 + 2)));
		if (N > 0) {
			const r = JSON.parse(await ev(`(function(){var n=[...document.querySelectorAll('.hv2-rev__note')];var car=document.querySelector('.hv2-rev__carousel');var tr=car.querySelector('.tp-carousel__track');var nav=car.querySelector('[data-tp-carousel-nav]');var rows={};n.forEach(function(e){var li=e.closest('li');var t=Math.round(li.offsetTop);(rows[t]=rows[t]||[]).push(Math.round(li.getBoundingClientRect().height))});return JSON.stringify({n:n.length,region:car.getAttribute('role'),h2:document.querySelectorAll('[data-section="06-recensioni"] h2').length,rows:Object.values(rows),scrolls:tr.scrollWidth>tr.clientWidth+1,navVisible:nav?getComputedStyle(nav).display!=='none'&&!nav.hidden:false})})()`));
			check(N + ' foglietti', r.n === N, JSON.stringify(r));
			check('carosello con role="region" e un h2', r.region === 'region' && r.h2 === 1, JSON.stringify(r));
			if (width >= 1024) {
				check('1024+: in fila, nessuno scorrimento, stessa altezza per riga', !r.scrolls && r.rows.every(row => row.every(h => Math.abs(h - row[0]) <= 1)), JSON.stringify(r.rows));
			} else {
				check('<1024: scorre in orizzontale con indicatore', (N < 2 || r.scrolls) && (N < 2 || r.navVisible), JSON.stringify(r));
			}
		}
		if (M > 0) {
			const b = JSON.parse(await ev(`(function(){var band=document.querySelector('.hv2-rev__band');if(!band)return JSON.stringify(null);var g=band.querySelectorAll('.tp-marquee__group');var first=g[0].querySelectorAll('.tp-marquee__item').length;var hid=[...g].slice(1).every(x=>x.getAttribute('aria-hidden')==='true');var r=band.getBoundingClientRect();return JSON.stringify({first:first,hid:hid,w:Math.round(r.width),vw:innerWidth})})()`));
			check('fascia: ' + M + ' voci + la scritta nel primo gruppo, copie aria-hidden, a tutta larghezza', !!b && b.first === M + 1 && b.hid && b.w === b.vw, JSON.stringify(b));
		}
	});
	process.exit(fail);
})();
