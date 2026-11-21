/* QA della Home v2 (SPEC-homepage-v2, TICKET-119 e seguenti).
   Uso: node tools/qa-home-v2.js <chrome> <url-anteprima> [larghezza=1440] [--sezioni base]
   <url-anteprima> e' la pagina d'anteprima (es. https://trovapulizie.it/anteprima-pagina-iniziale/).

   Ogni ticket aggiunge la propria funzione di controllo in CHECKS e il proprio nome in SEZIONI. Con --sezioni <nome>
   si esegue solo quel gruppo (oggi: base). Esce con 1 se un controllo fallisce.

   Gruppo "base" (TICKET-119):
   - la pagina risponde 200 senza redirect (nessun rimando ad app.trovapulizie.com), con meta robots noindex e header
     X-Robots-Tag noindex finche' non e' la pagina iniziale;
   - un solo <main class="hv2" data-landing="home"> con le dieci sezioni data-section nell'ordine, un solo h1;
   - nessun errore PHP nell'HTML, nessuna eccezione JavaScript;
   - fuori dalla sitemap delle pagine (/wp-sitemap-posts-page-1.xml), che pero' contiene ancora la radice;
   - con JavaScript spento ogni [data-reveal] e' visibile;
   - niente Three.js ne' GSAP richiesti (la v2 base non li carica). */
const { withChrome } = require('./lib/cdp.js');

const SECTIONS = ['01-hero', '02-frasi', '03-servizi', '04-come-funziona', '05-manifesto', '06-recensioni', '07-zone', '08-aziende', '09-faq', '10-chiusura'];
const args = process.argv.slice(2).filter(a => !a.startsWith('--'));
const [bin, url, widthArg] = args;
const width = Number(widthArg) || 1440;
const gruppo = (process.argv.includes('--sezioni') ? process.argv[process.argv.indexOf('--sezioni') + 1] : 'base');
if (!bin || !url) { console.error('Uso: node tools/qa-home-v2.js <chrome> <url-anteprima> [larghezza] [--sezioni base]'); process.exit(2); }

const results = [];
const check = (nome, ok, dettaglio) => { results.push({ nome, ok: !!ok, dettaglio }); console.log((ok ? '[ok] ' : '[KO] ') + nome + (ok || !dettaglio ? '' : '  -> ' + dettaglio)); };

/* Controlli del gruppo base: richieste HTTP dirette (senza browser) */
async function checkHttp() {
	const res = await fetch(url, { redirect: 'manual' });
	check('risposta 200 senza redirect', res.status === 200, 'stato ' + res.status + (res.headers.get('location') ? ' verso ' + res.headers.get('location') : ''));
	const xr = res.headers.get('x-robots-tag') || '';
	check('header X-Robots-Tag noindex', /noindex/i.test(xr), 'valore: "' + xr + '"');
	if (res.status !== 200) return null;
	const html = await res.text();
	check('meta robots noindex', /<meta name=['"]robots['"][^>]*noindex/i.test(html));
	check('nessun errore PHP nell\'HTML', !/(Fatal error|Parse error|Warning:|Notice:|Deprecated:)<?/i.test(html.replace(/<script[\s\S]*?<\/script>/g, '')), 'trovato un messaggio PHP');
	const mains = html.match(/<main[^>]*>/g) || [];
	check('un solo <main class="hv2" data-landing="home">', mains.length === 1 && /class="[^"]*\bhv2\b/.test(mains[0]) && /data-landing="home"/.test(mains[0]), JSON.stringify(mains));
	const found = [...html.matchAll(/data-section="([^"]+)"/g)].map(m => m[1]);
	check('dieci sezioni data-section nell\'ordine', JSON.stringify(found) === JSON.stringify(SECTIONS), found.join(','));
	check('un solo h1', (html.match(/<h1[\s>]/g) || []).length === 1, (html.match(/<h1[\s>]/g) || []).length + ' h1');
	const origin = new URL(url).origin;
	const slug = new URL(url).pathname;
	const sm = await fetch(origin + '/wp-sitemap-posts-page-1.xml').then(r => r.text());
	check('anteprima fuori dalla sitemap', !sm.includes(slug), 'la sitemap contiene ' + slug);
	check('la radice e le altre pagine restano in sitemap', /<loc>[^<]*\/<\/loc>/.test(sm) && (sm.match(/<loc>/g) || []).length >= 2, (sm.match(/<loc>/g) || []).length + ' voci');
	return html;
}

/* Controlli in browser */
async function checkBrowser() {
	const errors = [];
	const requests = [];
	await withChrome(bin, 'about:blank', { port: 9511, width, height: 900, mobile: width < 1024, profile: '/tmp/cdp-profile-home-v2' }, async ({ send, ev, sleep, on }) => {
		on('Runtime.exceptionThrown', p => errors.push(((p.exceptionDetails || {}).exception || {}).description || 'eccezione'));
		on('Network.requestWillBeSent', p => requests.push(p.request.url));
		await send('Network.enable');
		await send('Page.navigate', { url });
		await sleep(3500);
		check('nessuna eccezione JavaScript', errors.length === 0, errors.join(' | ').slice(0, 300));
		check('niente Three.js ne\' GSAP richiesti', !requests.some(u => /three|gsap/i.test(u)), requests.filter(u => /three|gsap/i.test(u)).join(', '));
		check('nessun scroll orizzontale', await ev('document.scrollingElement.scrollWidth <= document.scrollingElement.clientWidth'), '');
		// Reveal: con JavaScript attivo la classe tp-reveal e' sull'html e dopo lo scroll tutto e' visibile
		const reveal = await ev('(async()=>{const els=[...document.querySelectorAll("main.hv2 [data-reveal]")]; for(const e of els){e.scrollIntoView();await new Promise(r=>setTimeout(r,120));} await new Promise(r=>setTimeout(r,900)); return JSON.stringify({n:els.length,cls:document.documentElement.classList.contains("tp-reveal"),nascosti:els.filter(e=>getComputedStyle(e).opacity<0.99).length});})()');
		const r = JSON.parse(reveal);
		check('reveal.js: classe tp-reveal su html e tutto visibile dopo lo scroll', r.n > 0 && r.cls && r.nascosti === 0, reveal);
		// JavaScript spento
		await send('Emulation.setScriptExecutionDisabled', { value: true });
		await send('Page.navigate', { url });
		await sleep(2500);
		const noJs = JSON.parse(await ev('JSON.stringify({n:document.querySelectorAll("main.hv2 [data-reveal]").length,nascosti:[...document.querySelectorAll("main.hv2 [data-reveal]")].filter(e=>getComputedStyle(e).opacity<0.99).length})'));
		check('senza JavaScript ogni [data-reveal] e\' visibile', noJs.n > 0 && noJs.nascosti === 0, JSON.stringify(noJs));
	});
}

(async () => {
	if (gruppo !== 'base') { console.error('Gruppo sconosciuto: ' + gruppo); process.exit(2); }
	const html = await checkHttp();
	if (html) await checkBrowser();
	const ko = results.filter(r => !r.ok).length;
	console.log(ko ? '\nFALLITO: ' + ko + ' controlli su ' + results.length : '\nTutti i controlli ok (' + results.length + ')');
	process.exit(ko ? 1 : 0);
})();
