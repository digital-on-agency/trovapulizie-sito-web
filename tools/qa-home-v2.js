/* QA della Home v2 (SPEC-homepage-v2, TICKET-119 e seguenti).
   Uso: node tools/qa-home-v2.js <chrome> <url-anteprima> [larghezza=1440] [--sezioni base|hero] [--google on|off]
   <url-anteprima> e' la pagina d'anteprima (es. https://trovapulizie.it/anteprima-pagina-iniziale/).

   Ogni ticket aggiunge la propria funzione di controllo in CHECKS e il proprio nome in SEZIONI. Con --sezioni <nome>
   si esegue solo quel gruppo (oggi: base, hero). Esce con 1 se un controllo fallisce.

   Gruppo "base" (TICKET-119):
   - la pagina risponde 200 senza redirect (nessun rimando ad app.trovapulizie.com), con meta robots noindex e header
     X-Robots-Tag noindex finche' non e' la pagina iniziale;
   - un solo <main class="hv2" data-landing="home"> con le dieci sezioni data-section nell'ordine, un solo h1;
   - nessun errore PHP nell'HTML, nessuna eccezione JavaScript;
   - fuori dalla sitemap delle pagine (/wp-sitemap-posts-page-1.xml), che pero' contiene ancora la radice;
   - con JavaScript spento ogni [data-reveal] e' visibile;
   - niente Three.js ne' GSAP richiesti (la v2 base non li carica).

   Gruppo "hero" (TICKET-120), anche a 390 e 1440; --google on|off dice se la pagina provata ha il riquadro Google
   acceso (voto compilato) o spento (default):
   - un solo h1; "Apri l'app" della hero (data-cta-position="hero") e della barra (data-cta-position="navbar") verso
     https://app.trovapulizie.com/ nella stessa scheda; "Come funziona" verso #come-funziona, che esiste;
   - QR: l'<img> punta a qr-app.svg, il cui <title> e' https://app.trovapulizie.com/ (se la variabile JSQR_PATH indica
     il modulo jsqr, il QR viene anche decodificato da uno screenshot); visibile da 1024 px, non visibile sotto;
   - riquadro Google presente solo con --google on (voto visibile), mai AggregateRating nell'HTML;
   - un solo preload image fetchpriority=high valido per la larghezza provata, uguale all'immagine LCP letta con
     PerformanceObserver (se l'elemento LCP e' un testo, uguale all'unica img fetchpriority=high); fetchpriority=high
     su una sola immagine della pagina; ogni img della hero ha width e height; niente data-reveal nella hero;
   - testo circolare: SVG aria-hidden con textPath, nessuna lettera sovrapposta (angoli di getStart/EndPositionOfChar
     in ordine crescente), un H1 solo; menu mobile: l'hamburger apre #nav-panel con le voci, Esc lo chiude. */
const { withChrome } = require('./lib/cdp.js');

const SECTIONS = ['01-hero', '02-frasi', '03-servizi', '04-come-funziona', '05-manifesto', '06-recensioni', '07-zone', '08-aziende', '09-faq', '10-chiusura'];
const args = process.argv.slice(2).filter((a, i, all) => !a.startsWith('--') && !(i > 0 && ['--sezioni', '--google'].includes(all[i - 1])));
const [bin, url, widthArg] = args;
const width = Number(widthArg) || 1440;
const gruppo = (process.argv.includes('--sezioni') ? process.argv[process.argv.indexOf('--sezioni') + 1] : 'base');
const google = (process.argv.includes('--google') ? process.argv[process.argv.indexOf('--google') + 1] : 'off');
const APP = 'https://app.trovapulizie.com/';
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

/* Gruppo hero (TICKET-120) */
async function checkHero() {
	const res = await fetch(url, { redirect: 'manual' });
	check('hero: risposta 200', res.status === 200, 'stato ' + res.status);
	if (res.status !== 200) return;
	const html = await res.text();
	const origin = new URL(url).origin;
	check('hero: un solo h1', (html.match(/<h1[\s>]/g) || []).length === 1);
	check('hero: nessun AggregateRating nell\'HTML', !/AggregateRating/i.test(html));
	check('hero: "Apri l\'app" della hero verso l\'app con data-cta-position="hero", stessa scheda', new RegExp('<a[^>]*class="hv2-hero__cta"[^>]*href="' + APP.replace(/[.\/]/g, '\\$&') + '"[^>]*data-cta-position="hero"[^>]*>').test(html) && !/<a[^>]*class="hv2-hero__cta"[^>]*target=/.test(html));
	check('hero: "Apri l\'app" della barra con data-cta-position="navbar", stessa scheda', new RegExp('<a[^>]*class="navbar__app"[^>]*data-cta-position="navbar"').test(html) && /<a href="https:\/\/app\.trovapulizie\.com\/"[^>]*class="navbar__app"/.test(html) && !/class="navbar__app"[^>]*target=/.test(html));
	check('hero: "Come funziona" verso #come-funziona e l\'ancora esiste', /class="hv2-hero__ghost" href="#come-funziona"/.test(html) && /id="come-funziona"/.test(html));
	check('hero: un solo fetchpriority="high" fra le immagini della pagina', (html.match(/<img[^>]*fetchpriority="high"/g) || []).length === 1);
	check('hero: niente data-reveal nella hero', !/<section[^>]*data-section="01-hero"[^>]*data-reveal/.test(html) && !/hv2-hero[\s\S]*?data-reveal[\s\S]*?<\/section>/.test(html.split('data-section="02-frasi"')[0]));
	const hero = (html.match(/<section class="hv2-hero"[\s\S]*?<\/section>/) || [''])[0];
	const imgs = hero.match(/<img[^>]*>/g) || [];
	check('hero: ogni immagine ha width e height espliciti', imgs.length > 0 && imgs.every(i => /\swidth="\d+"/.test(i) && /\sheight="\d+"/.test(i)), imgs.length + ' img');
	check('hero: riquadro Google ' + (google === 'on' ? 'presente' : 'assente dal DOM'), (hero.includes('hv2-hero__google"')) === (google === 'on'));
	const qr = await fetch(origin + new URL((hero.match(/src="([^"]*qr-app\.svg[^"]*)"/) || [, '/x'])[1], origin).pathname).then(r => r.text());
	check('hero: <title> dell\'SVG del QR = ' + APP, qr.includes('<title>' + APP + '</title>'), qr.slice(0, 120));
	const pre = [...html.matchAll(/<link rel="preload"[^>]*>/g)].map(m => m[0]).filter(l => /as="image"/.test(l) && /fetchpriority="high"/.test(l));
	return { pre };
}

async function checkHeroBrowser(pre) {
	await withChrome(bin, 'about:blank', { port: 9512, width, height: 900, mobile: width < 1024, profile: '/tmp/cdp-profile-home-v2-hero' }, async ({ send, ev, shot, sleep }) => {
		await send('Page.navigate', { url });
		await ev('0');
		await sleep(4500);
		const r = JSON.parse(await ev(`(async () => {
			const lcp = await new Promise(res => { let last = null; new PerformanceObserver(l => { last = l.getEntries().pop(); }).observe({ type: 'largest-contentful-paint', buffered: true }); setTimeout(() => res(last), 300); });
			const preloads = [...document.querySelectorAll('link[rel=preload][as=image][fetchpriority=high]')].filter(l => !l.media || matchMedia(l.media).matches).map(l => l.href);
			const fp = [...document.querySelectorAll('img[fetchpriority=high]')].map(i => i.currentSrc);
			const qr = document.querySelector('.hv2-hero__qr');
			const svg = document.querySelector('.hv2-hero svg.tp-orbit-text');
			let ov = null, n = 0, bad = 0, nodo = false;
			if (svg) {
				const t = svg.querySelector('text'); const tp = svg.querySelector('textPath');
				nodo = !!tp && svg.getAttribute('aria-hidden') === 'true';
				const c = 410; n = t.getNumberOfChars(); const ang = p => Math.atan2(p.y - 410, p.x - 410);
				let prev = null;
				for (let i = 0; i < n; i++) {
					let a = ang(t.getStartPositionOfChar(i)), b = ang(t.getEndPositionOfChar(i));
					const norm = x => ((x % (2 * Math.PI)) + 2 * Math.PI) % (2 * Math.PI);
					const s = norm(a), e = norm(b);
					if (prev !== null) { let d = s - prev; if (d < -Math.PI) d += 2 * Math.PI; if (d < -0.002) bad++; }
					let w = e - s; if (w < 0) w += 2 * Math.PI; if (w > 1) bad++;
					prev = e;
				}
				ov = { fs: t.getAttribute('font-size'), viewBox: svg.getAttribute('viewBox') };
			}
			return JSON.stringify({ lcp: lcp ? { url: lcp.url, tag: lcp.element && lcp.element.tagName } : null, preloads, fp,
				qrDisplay: qr ? getComputedStyle(qr).display : null, qrVisible: qr ? qr.getBoundingClientRect().width > 0 : false,
				google: !!document.querySelector('.hv2-hero__google'), n, bad, nodo, ov });
		})()`));
		check('hero: un solo preload fetchpriority=high valido per ' + width + ' px', r.preloads.length === 1, JSON.stringify(r.preloads));
		const lcpOk = r.lcp && r.lcp.url ? r.lcp.url === r.preloads[0] : (r.fp.length === 1 && r.fp[0] === r.preloads[0]);
		check('hero: preload = immagine LCP (' + (r.lcp && r.lcp.url ? 'LCP e\' l\'immagine' : 'LCP e\' un ' + (r.lcp && r.lcp.tag) + ': confronto con l\'unica img fetchpriority=high') + ')', lcpOk, JSON.stringify({ lcp: r.lcp, preloads: r.preloads, fp: r.fp }));
		if (width >= 1024) check('hero: QR visibile da 1024 px', r.qrVisible, 'display ' + r.qrDisplay);
		else check('hero: QR non visibile sotto 1024 px', !r.qrVisible, 'display ' + r.qrDisplay);
		check('hero: testo circolare SVG aria-hidden con textPath', r.nodo);
		check('hero: nessuna lettera del testo circolare sovrapposta (' + r.n + ' lettere, corpo ' + (r.ov && r.ov.fs) + ')', r.n > 0 && r.bad === 0, r.bad + ' lettere fuori ordine o troppo larghe');
		if (width < 1024) {
			const m = JSON.parse(await ev(`(async () => {
				const b = document.getElementById('nav-burger'), p = document.getElementById('nav-panel');
				const out = { burgerVisible: b.getBoundingClientRect().width >= 44 && b.getBoundingClientRect().height >= 44, hiddenAtStart: p.hidden };
				b.click(); await new Promise(r => setTimeout(r, 200));
				out.open = !p.hidden && b.getAttribute('aria-expanded') === 'true'; out.links = [...p.querySelectorAll('a')].map(a => a.textContent.trim());
				document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' })); await new Promise(r => setTimeout(r, 200));
				out.closed = p.hidden && b.getAttribute('aria-expanded') === 'false';
				return JSON.stringify(out); })()`));
			check('menu mobile: hamburger da 44 px, chiuso all\'inizio, si apre con le voci e Accedi, Esc lo chiude', m.burgerVisible && m.hiddenAtStart && m.open && m.closed && m.links.length >= 5 && m.links.includes('Accedi'), JSON.stringify(m));
		}
	});
}

(async () => {
	if (!['base', 'hero'].includes(gruppo)) { console.error('Gruppo sconosciuto: ' + gruppo); process.exit(2); }
	if (gruppo === 'hero') {
		const h = await checkHero();
		if (h) await checkHeroBrowser(h.pre);
	} else {
		const html = await checkHttp();
		if (html) await checkBrowser();
	}
	const ko = results.filter(r => !r.ok).length;
	console.log(ko ? '\nFALLITO: ' + ko + ' controlli su ' + results.length : '\nTutti i controlli ok (' + results.length + ')');
	process.exit(ko ? 1 : 0);
})();
