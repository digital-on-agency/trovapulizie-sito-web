/* QA della Home v2 (SPEC-homepage-v2, TICKET-119 e seguenti).
   Uso: node tools/qa-home-v2.js <chrome> <url-anteprima> [larghezza=1440] [--sezioni base|hero|frasi|servizi|manifesto|come-funziona] [--google on|off] [--servizi base|rotto]
   <url-anteprima> e' la pagina d'anteprima (es. https://trovapulizie.it/anteprima-pagina-iniziale/).

   Ogni ticket aggiunge la propria funzione di controllo in CHECKS e il proprio nome in SEZIONI. Con --sezioni <nome>
   si esegue solo quel gruppo (oggi: base, hero, frasi, servizi, manifesto). Esce con 1 se un controllo fallisce.

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
     in ordine crescente), un H1 solo; menu mobile: l'hamburger apre #nav-panel con le voci, Esc lo chiude.

   Gruppo "frasi" (TICKET-121), anche a 390 e 1440: la fascia ha un solo gruppo di frasi leggibile e gli altri con
   aria-hidden="true"; le frasi sono le tre attese (o quelle del campo Carbon: --frasi "a|b|c"); testo #062A22 su
   #37B299 con contrasto calcolato >= 4,5 (atteso 5,8); la pista si muove (transform diverso fra due letture) e ha
   animation-name tp-marquee-left; con "riduci movimento" emulato la pista e' ferma (animation-name none); fuori dal
   viewport riceve .is-paused e al ritorno la perde; col mouse sopra e' in pausa (animation-play-state paused);
   nessuno scroll orizzontale; un solo h2 (nascosto agli occhi) e nessun h1 nuovo.

   Gruppo "manifesto" (TICKET-123), anche a 390 e 1440; --voci N dice quante promesse ha la pagina provata (default 4 =
   le quattro del Figma e dei campi Carbon precompilati; 0 = sezione assente). Un solo h2 e nessun h1 nella sezione;
   le promesse sono N <li> di una <ol>; timbro (testo circolare con textPath) aria-hidden; con 4 promesse i testi sono
   quelli del Figma (lead "Niente", parola chiave, frase, prova); colori calcolati: "NIENTE" #008866, parola chiave
   #0F1F1B, fondo #F3FAF7; contrasto AA su ogni coppia testo/fondo (3:1 per testo grande, 4,5:1 negli altri casi);
   disposizione: due colonne a 1440, una a 390, con meno di quattro (o dispari) l'ultima promessa occupa la riga
   intera; nessuno scroll orizzontale; una pillola di prova con icona per promessa (28 px, tondo #E6FFF5).

   Gruppo "servizi" (TICKET-121), --servizi base|rotto dice quale pagina si prova. "base": Casa, Aziende, Condomini,
   Disinfestazione sono <a> verso /roma/, /roma/pulizie-uffici/, /roma/pulizie-condomini/, /roma/zanzare-disinfestazione/;
   Affitti brevi e Manutenzione sono <span> (non link) con la parola "presto" visibile e non nascosta agli screen reader;
   "Scopri i servizi nell'app" verso l'app con data-cta-position="services"; la riga "Altri servizi a Roma" ha i sei link
   con gli anchor di tp_service_pages() (letti da inc/seo.php) e href coerenti; ogni href della sezione risponde 200 (richiesta
   HTTP diretta); un solo h2, nessun h1; riquadro largo 668,8 px a 1440 e 358 px a 390; hover sulle icone che sono
   link: +4 px verso l'alto (hover reale); nessuna icona-span con hover. "rotto" (pagina con Carbon: Casa senza URL,
   Aziende verso una pagina in bozza): Casa e Aziende sono <span> senza href, le altre restano link e nessun link porta a una 404.

   Gruppo "come-funziona" (TICKET-122), a 1440 (pila) e a 390 (carosello): la <section id="come-funziona" data-section="04-come-funziona">
   ha un h2 e nessun h1, quattro schede (<li class="tp-carousel__slide">) nel componente con role="region",
   aria-roledescription="carosello" e aria-label, indicatore con l'attributo hidden nell'HTML del server e aria-live="polite";
   ogni immagine ha width, height e loading="lazy"; nessun !important nel CSS del blocco. A 1440, dopo uno scroll programmato:
   le schede sono sticky con top crescente (--tp-navbar-h + 24 + 16 x indice), la precedente scala a 0,96 e l'ultima no,
   l'ancora #come-funziona porta la sezione sotto la barra (top uguale a --tp-navbar-h), l'indicatore non si vede; con
   "riduci movimento" nessuna scheda scala; con JavaScript spento le schede restano sticky. A 390: scroll-snap-type x
   mandatory, indicatore visibile con quattro puntini e bottoni alti almeno 44 px, "1 di 4" in aria-live="polite" che
   avanza dopo uno scroll, un clic su "avanti" e il tasto freccia destra sulla pista (evento tp:carousel-change a ogni
   cambio), pagina senza scroll orizzontale; con JavaScript spento la pista scorre ancora a scatti e l'indicatore non c'e'. */
const { withChrome } = require('./lib/cdp.js');

const SECTIONS = ['01-hero', '02-frasi', '03-servizi', '04-come-funziona', '05-manifesto', '06-recensioni', '07-zone', '08-aziende', '09-faq', '10-chiusura'];
const args = process.argv.slice(2).filter((a, i, all) => !a.startsWith('--') && !(i > 0 && ['--sezioni', '--google', '--servizi', '--frasi', '--voci'].includes(all[i - 1])));
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


/* Gruppo frasi (TICKET-121) */
const frasiArg = process.argv.includes('--frasi') ? process.argv[process.argv.indexOf('--frasi') + 1] : '';
const FRASI = frasiArg ? frasiArg.split('|') : ['La casa pulita. Il weekend libero.', 'Il divano ringrazia.', 'Lo sgrosso non è un hobby.'];
async function checkFrasi() {
	const res = await fetch(url, { redirect: 'manual' });
	check('frasi: risposta 200', res.status === 200, 'stato ' + res.status);
	if (res.status !== 200) return;
	const html = await res.text();
	const sec = (html.match(/<section class="hv2-frasi"[\s\S]*?<\/section>/) || [''])[0];
	check('frasi: sezione 02-frasi con h2 e fascia .tp-marquee', /data-section="02-frasi"/.test(sec) && /<h2 /.test(sec) && /class="tp-marquee /.test(sec), sec.slice(0, 120));
	check('frasi: nessun h1 e un solo h2 nella sezione', !/<h1/.test(sec) && (sec.match(/<h2 /g) || []).length === 1);
	const groups = [...sec.matchAll(/<ul class="tp-marquee__group"([^>]*)>([\s\S]*?)<\/ul>/g)];
	check('frasi: almeno due gruppi di frasi', groups.length >= 2, groups.length + ' gruppi');
	check('frasi: il primo gruppo e\' letto, tutti gli altri aria-hidden="true"', groups.length >= 2 && !/aria-hidden/.test(groups[0][1]) && groups.slice(1).every(g => /aria-hidden="true"/.test(g[1])));
	const testi = g => [...g[2].matchAll(/<span class="tp-marquee__text">([^<]*)<\/span>/g)].map(m => m[1].replace(/&#0?39;|&#8217;/g, '’').replace(/&amp;/g, '&'));
	const norm = a => a.map(t => t.replace(/[’']/g, '’'));
	check('frasi: il primo gruppo ha le frasi attese, in ordine', groups.length > 0 && JSON.stringify(norm(testi(groups[0]))) === JSON.stringify(norm(FRASI)), JSON.stringify(testi(groups[0] || [0, 0, ''])));
	check('frasi: ogni gruppo ha le stesse frasi', groups.length > 0 && groups.every(g => JSON.stringify(testi(g)) === JSON.stringify(testi(groups[0]))));
	check('frasi: i separatori sono aria-hidden', (sec.match(/<span class="tp-marquee__dot" aria-hidden="true">/g) || []).length === (sec.match(/tp-marquee__dot/g) || []).length);
}
async function checkFrasiBrowser() {
	await withChrome(bin, 'about:blank', { port: 9513, width, height: 900, mobile: width < 1024, profile: '/tmp/cdp-profile-home-v2-frasi', args: ['--blink-settings=primaryHoverType=2,availableHoverTypes=2,primaryPointerType=4,availablePointerTypes=4'] }, async ({ send, ev, sleep }) => {
		await send('Page.navigate', { url });
		await sleep(3500);
		await ev('document.querySelector("#tp-consent")&&(document.querySelector("#tp-consent").style.display="none")');
		if (!(await ev('!!document.querySelector(".hv2-frasi .tp-marquee__track")'))) { check('frasi: la fascia esiste nel browser', false, 'assente'); return; }
		await ev('document.querySelector(".hv2-frasi").scrollIntoView({block:"center"})');
		await sleep(600);
		const info = JSON.parse(await ev(`(() => {
			const t = document.querySelector('.hv2-frasi__marquee .tp-marquee__text'), m = document.querySelector('.hv2-frasi'), tr = document.querySelector('.tp-marquee__track');
			const cs = getComputedStyle(t), bg = getComputedStyle(m).backgroundColor, ts = getComputedStyle(tr);
			return JSON.stringify({ color: cs.color, bg, fs: cs.fontSize, fw: cs.fontWeight, h: Math.round(m.getBoundingClientRect().height), name: ts.animationName, state: ts.animationPlayState,
				border: getComputedStyle(m).borderTopColor, dot: getComputedStyle(document.querySelector('.tp-marquee__dot')).width,
				x1: new DOMMatrix(ts.transform).m41, sw: document.scrollingElement.scrollWidth, cw: document.scrollingElement.clientWidth });
		})()`));
		const rgb = c => c.match(/\d+/g).slice(0, 3).map(Number);
		const lum = c => { const [r, g, b] = rgb(c).map(v => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); }); return 0.2126 * r + 0.7152 * g + 0.0722 * b; };
		const ratio = (a, b) => { const l1 = lum(a), l2 = lum(b); return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05); };
		check('frasi: testo ' + info.color + ' su ' + info.bg + ' (attesi #062A22 su #37B299)', info.color === 'rgb(6, 42, 34)' && info.bg === 'rgb(55, 178, 153)', JSON.stringify(info));
		const cr = ratio(info.color, info.bg);
		check('frasi: contrasto calcolato ' + cr.toFixed(2) + ':1 >= 4,5', cr >= 4.5, cr.toFixed(2));
		check('frasi: bordo sopra #062A22, corpo ' + info.fs + ' peso ' + info.fw + ', altezza ' + info.h, info.border === 'rgb(6, 42, 34)' && info.fw === '800' && (width >= 1024 ? info.fs === '32px' && Math.abs(info.h - 85) <= 2 : info.fs === '20px' && Math.abs(info.h - 59) <= 2), JSON.stringify(info));
		check('frasi: animazione tp-marquee-left in corso', info.name === 'tp-marquee-left' && info.state === 'running', info.name + ' ' + info.state);
		await sleep(1200);
		const x2 = Number(await ev('new DOMMatrix(getComputedStyle(document.querySelector(".tp-marquee__track")).transform).m41'));
		check('frasi: la pista si muove verso sinistra (' + info.x1.toFixed(1) + ' -> ' + x2.toFixed(1) + ' px)', x2 < info.x1 - 5, info.x1 + ' ' + x2);
		check('frasi: nessuno scroll orizzontale', info.sw <= info.cw, info.sw + ' > ' + info.cw);
		// Fuori dal viewport: .is-paused
		await ev('window.scrollTo(0, document.body.scrollHeight)');
		await sleep(700);
		const out = await ev('document.querySelector(".tp-marquee").classList.contains("is-paused") && getComputedStyle(document.querySelector(".tp-marquee__track")).animationPlayState');
		check('frasi: fuori dal viewport la fascia e\' in pausa (.is-paused)', out === 'paused', String(out));
		await ev('document.querySelector(".hv2-frasi").scrollIntoView({block:"center"})');
		await sleep(700);
		const back = JSON.parse(await ev('JSON.stringify({p: document.querySelector(".tp-marquee").classList.contains("is-paused"), s: getComputedStyle(document.querySelector(".tp-marquee__track")).animationPlayState})'));
		check('frasi: al rientro nel viewport riparte', !back.p && back.s === 'running', JSON.stringify(back));
		// Hover: pausa (solo con mouse)
		if (width >= 1024) {
			const b = JSON.parse(await ev('JSON.stringify(document.querySelector(".hv2-frasi").getBoundingClientRect())'));
			await send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: 400, y: Math.round(b.y + b.height / 2) });
			await sleep(300);
			const hv = await ev('getComputedStyle(document.querySelector(".tp-marquee__track")).animationPlayState');
			check('frasi: col mouse sopra la fascia e\' in pausa', hv === 'paused', hv);
			await send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: 5, y: 5 });
		}
		// Riduci movimento
		await send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });
		await sleep(400);
		const r1 = Number(await ev('new DOMMatrix(getComputedStyle(document.querySelector(".tp-marquee__track")).transform).m41'));
		await sleep(900);
		const rr = JSON.parse(await ev('JSON.stringify({n: getComputedStyle(document.querySelector(".tp-marquee__track")).animationName, x: new DOMMatrix(getComputedStyle(document.querySelector(".tp-marquee__track")).transform).m41})'));
		check('frasi: con "riduci movimento" la fascia e\' ferma (animation-name ' + rr.n + ', x ' + r1 + ' -> ' + rr.x + ')', rr.n === 'none' && r1 === rr.x, JSON.stringify(rr));
	});
}

/* Gruppo servizi (TICKET-121) */
const serviziArg = process.argv.includes('--servizi') ? process.argv[process.argv.indexOf('--servizi') + 1] : 'base';
function anchorsAttesi() {
	const src = require('fs').readFileSync(require('path').join(__dirname, '..', 'inc', 'seo.php'), 'utf8');
	const body = (src.match(/function tp_service_pages\(\) \{[\s\S]*?\n\t\);/) || [''])[0];
	const m = {};
	for (const r of body.matchAll(/'(\/roma\/[^']*)'\s*=>\s*'([^']*)'/g)) m[r[1]] = r[2];
	return m;
}
const ALTRI = ['/roma/sgrosso-casa/', '/roma/sgrosso-post-ristrutturazione/', '/roma/sgrosso-trasloco/', '/roma/sgrosso-fine-affitto/', '/roma/sgrosso-commerciale/', '/roma/prezzi-pulizie/'];
async function checkServizi() {
	const res = await fetch(url, { redirect: 'manual' });
	check('servizi: risposta 200', res.status === 200, 'stato ' + res.status);
	if (res.status !== 200) return null;
	const html = await res.text();
	const origin = new URL(url).origin;
	const sec = (html.match(/<section class="hv2-section hv2-services"[\s\S]*?<\/section>/) || [''])[0];
	check('servizi: sezione 03-servizi presente, un solo h2, nessun h1', /data-section="03-servizi"/.test(sec) && (sec.match(/<h2 /g) || []).length === 1 && !/<h1/.test(sec), sec.slice(0, 100));
	const items = [...sec.matchAll(/<(a|span) class="hv2-services__item[^"]*"([^>]*)>([\s\S]*?)<\/\1>\s*<\/li>/g)].map(m => ({ tag: m[1], attrs: m[2], inner: m[3], name: (m[3].match(/hv2-services__name">([^<]*)</) || [, ''])[1], href: (m[2].match(/href="([^"]*)"/) || [, ''])[1], soon: /hv2-services__soon">presto</.test(m[3]) }));
	check('servizi: sei icone nell\'ordine Casa, Affitti brevi, Aziende, Condomini, Disinfestazione, Manutenzione', JSON.stringify(items.map(i => i.name)) === JSON.stringify(['Casa', 'Affitti brevi', 'Aziende', 'Condomini', 'Disinfestazione', 'Manutenzione']), JSON.stringify(items.map(i => i.name)));
	const per = n => items.find(i => i.name === n) || { tag: '?', href: '' };
	const path = u => { try { return new URL(u).pathname; } catch (e) { return u; } };
	const attesi = { Casa: '/roma/', Aziende: '/roma/pulizie-uffici/', Condomini: '/roma/pulizie-condomini/', Disinfestazione: '/roma/zanzare-disinfestazione/' };
	if (serviziArg === 'base') {
		for (const [n, p] of Object.entries(attesi)) check('servizi: ' + n + ' e\' un link verso ' + p, per(n).tag === 'a' && path(per(n).href) === p, per(n).tag + ' ' + per(n).href);
	} else {
		check('servizi (rotto): Casa senza URL in Carbon e\' uno span senza href', per('Casa').tag === 'span' && !per('Casa').href, per('Casa').tag + ' ' + per('Casa').href);
		check('servizi (rotto): Aziende verso una pagina in bozza e\' uno span senza href', per('Aziende').tag === 'span' && !per('Aziende').href, per('Aziende').tag + ' ' + per('Aziende').href);
		for (const n of ['Condomini', 'Disinfestazione']) check('servizi (rotto): ' + n + ' resta un link verso ' + attesi[n], per(n).tag === 'a' && path(per(n).href) === attesi[n], per(n).tag + ' ' + per(n).href);
	}
	for (const n of ['Affitti brevi', 'Manutenzione']) check('servizi: ' + n + ' e\' uno span, non un link, con "presto"', per(n).tag === 'span' && !per(n).href && per(n).soon && !/aria-hidden/.test(per(n).inner.match(/<span class="hv2-services__label">[\s\S]*$/)[0]), JSON.stringify(per(n)).slice(0, 200));
	const cta = (sec.match(/<a class="hv2-services__cta"[^>]*>/) || [''])[0];
	check('servizi: "Scopri i servizi nell\'app" verso l\'app con data-cta-position="services", stessa scheda', cta.includes('href="' + APP + '"') && cta.includes('data-cta-position="services"') && !/target=/.test(cta), cta);
	const altri = [...sec.matchAll(/<a class="hv2-services__chip" href="([^"]*)">([^<]*)<\/a>/g)].map(m => [path(m[1]), m[2]]);
	const anch = anchorsAttesi();
	check('servizi: tp_service_pages() letta da inc/seo.php (' + Object.keys(anch).length + ' voci)', Object.keys(anch).length >= 10);
	const attAltri = ALTRI.map(p => [p, anch[p]]);
	check('servizi: la riga "Altri servizi a Roma" ha le sei pagine con gli anchor di tp_service_pages()', JSON.stringify(altri) === JSON.stringify(attAltri), JSON.stringify(altri));
	const tutti = [...items.map(i => i.href), ...altri.map(a => origin + a[0])].filter(Boolean);
	let ko = [];
	for (const u of tutti) { const r = await fetch(u.startsWith('http') ? u : origin + u, { method: 'HEAD', redirect: 'follow' }); if (r.status !== 200) ko.push(u + ' ' + r.status); }
	check('servizi: ogni href della sezione risponde 200 (' + tutti.length + ' link)', ko.length === 0, ko.join(', '));
	const tpl = require('fs').readFileSync(require('path').join(__dirname, '..', 'template-parts', 'home-services-v2.php'), 'utf8').replace(/\/\*\*[\s\S]*?\*\//, '');
	check('servizi: nessun anchor ne\' URL della riga "Altri servizi" scritto nel template', !/sgrosso-|prezzi-pulizie|Pulizia post|Sgrosso casa/.test(tpl));
	return html;
}
async function checkServiziBrowser() {
	await withChrome(bin, 'about:blank', { port: 9514, width, height: 900, mobile: width < 1024, profile: '/tmp/cdp-profile-home-v2-servizi', args: ['--blink-settings=primaryHoverType=2,availableHoverTypes=2,primaryPointerType=4,availablePointerTypes=4'] }, async ({ send, ev, sleep }) => {
		await send('Page.navigate', { url });
		await sleep(3500);
		await ev('document.querySelector("#tp-consent")&&(document.querySelector("#tp-consent").style.display="none")');
		if (!(await ev('!!document.querySelector(".hv2-services__panel")'))) { check('servizi: il riquadro esiste nel browser', false, 'assente'); return; }
		await ev('document.querySelector(".hv2-services").scrollIntoView({block:"start"})');
		await sleep(1500);
		const m = JSON.parse(await ev(`(() => {
			const r = e => { const b = document.querySelector(e).getBoundingClientRect(); return { x: Math.round(b.x), y: Math.round(b.y), w: Math.round(b.width * 10) / 10, h: Math.round(b.height * 10) / 10 }; };
			const tiles = [...document.querySelectorAll('.hv2-services__tile')].map(t => Math.round(t.getBoundingClientRect().width * 10) / 10);
			return JSON.stringify({ sec: r('.hv2-services'), panel: r('.hv2-services__panel'), tiles, sw: document.scrollingElement.scrollWidth, cw: document.scrollingElement.clientWidth,
				title: getComputedStyle(document.querySelector('.hv2-services__title')).fontSize, op: getComputedStyle(document.querySelector('.hv2-services__panel')).opacity });
		})()`));
		if (width >= 1440) check('servizi: riquadro 668,8 px e icone 115,2 px a ' + width + ' px (letto ' + m.panel.w + ' e ' + m.tiles[0] + ')', Math.abs(m.panel.w - 668.8) <= 1 && m.tiles.every(t => Math.abs(t - 115.2) <= 1), JSON.stringify(m));
		if (width <= 430) check('servizi: riquadro 358 px e icone 61,7 px a ' + width + ' px (letto ' + m.panel.w + ' e ' + m.tiles[0] + ')', Math.abs(m.panel.w - 358) <= 1 && m.tiles.every(t => Math.abs(t - 61.7) <= 1), JSON.stringify(m));
		// La sezione e' piu' alta del Figma (607 e 818) perche' i chip portano gli anchor SEO di tp_service_pages(), piu' lunghi
		// delle etichette del disegno: si misura il riquadro (460,8 px desktop, 264,7 mobile), non la sezione.
		if (width >= 1440) check('servizi: riquadro alto ~460,8 px (letto ' + m.panel.h + ')', Math.abs(m.panel.h - 460.8) <= 2, JSON.stringify(m.panel));
		if (width <= 430) check('servizi: riquadro alto ~264,7 px (letto ' + m.panel.h + ')', Math.abs(m.panel.h - 264.7) <= 3, JSON.stringify(m.panel));
		check('servizi: nessuno scroll orizzontale', m.sw <= m.cw, m.sw + ' > ' + m.cw);
		check('servizi: il riquadro e\' entrato (opacita\' 1 dopo il reveal)', m.op === '1', m.op);
		if (width >= 1024) {
			const link = JSON.parse(await ev('(()=>{const a=document.querySelector("a.hv2-services__item .hv2-services__tile"); if(!a) return JSON.stringify(null); const b=a.getBoundingClientRect(); return JSON.stringify({x:b.x+b.width/2,y:b.y+b.height/2});})()'));
			if (link) {
				await send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: Math.round(link.x), y: Math.round(link.y) });
				await sleep(500);
				const h = JSON.parse(await ev('JSON.stringify({ty:new DOMMatrix(getComputedStyle(document.querySelector("a.hv2-services__item .hv2-services__tile")).transform).m42, u:getComputedStyle(document.querySelector("a.hv2-services__item .hv2-services__name")).textDecorationLine})'));
				check('servizi: hover su un\'icona-link: icona su di 4 px e nome sottolineato', Math.round(h.ty) === -4 && /underline/.test(h.u), JSON.stringify(h));
				await send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: 5, y: 5 });
			}
			const sp = JSON.parse(await ev('(()=>{const a=document.querySelector("span.hv2-services__item .hv2-services__tile"); if(!a) return JSON.stringify(null); const b=a.getBoundingClientRect(); return JSON.stringify({x:b.x+b.width/2,y:b.y+b.height/2});})()'));
			if (sp) {
				await send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: Math.round(sp.x), y: Math.round(sp.y) });
				await sleep(400);
				const t = Number(await ev('new DOMMatrix(getComputedStyle(document.querySelector("span.hv2-services__item .hv2-services__tile")).transform).m42'));
				check('servizi: nessun hover sulle icone senza link', t === 0, String(t));
				await send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: 5, y: 5 });
			}
		}
	});
}


/* Gruppo come-funziona (TICKET-122) */
async function checkSteps() {
	const res = await fetch(url, { redirect: 'manual' });
	check('come-funziona: risposta 200', res.status === 200, 'stato ' + res.status);
	if (res.status !== 200) return null;
	const html = await res.text();
	const sec = (html.match(/<section class="hv2-section hv2-steps"[\s\S]*?<\/section>/) || [''])[0];
	check('come-funziona: <section id="come-funziona" data-section="04-come-funziona"> con un h2 e nessun h1', /id="come-funziona"/.test(sec) && /data-section="04-come-funziona"/.test(sec) && (sec.match(/<h2 /g) || []).length === 1 && !/<h1/.test(sec), sec.slice(0, 120));
	check('come-funziona: un solo id="come-funziona" nella pagina', (html.match(/id="come-funziona"/g) || []).length === 1, (html.match(/id="come-funziona"/g) || []).length + ' id');
	const slides = [...sec.matchAll(/<li class="tp-carousel__slide"[^>]*>/g)].map(m => m[0]);
	check('come-funziona: quattro schede <li class="tp-carousel__slide"> con role="group" e "N di 4"', slides.length === 4 && slides.every((t, i) => /role="group"/.test(t) && t.includes('aria-label="' + (i + 1) + ' di 4"')), slides.join(' '));
	const car = (sec.match(/<div class="tp-carousel[^"]*"[^>]*>/) || [''])[0];
	check('come-funziona: componente con role="region", aria-roledescription="carosello", aria-label e data-tp-carousel', /role="region"/.test(car) && /aria-roledescription="carosello"/.test(car) && /aria-label="[^"]+"/.test(car) && /data-tp-carousel/.test(car), car);
	check('come-funziona: indicatore con hidden nell\'HTML del server e "N di M" in aria-live="polite"', /data-tp-carousel-nav hidden/.test(sec) && /aria-live="polite"[^>]*data-tp-carousel-count/.test(sec) && /data-tp-carousel-count[^>]*>1 di 4</.test(sec), 'nav/count');
	const imgs = [...sec.matchAll(/<img [^>]*>/g)].map(m => m[0]);
	check('come-funziona: ' + imgs.length + ' immagini WebP con width, height, loading="lazy"', imgs.length === 8 && imgs.every(t => /\.webp/.test(t) && /width="\d+"/.test(t) && /height="\d+"/.test(t) && /loading="lazy"/.test(t)), imgs.filter(t => !(/width="\d+"/.test(t) && /height="\d+"/.test(t) && /loading="lazy"/.test(t))).join(' ').slice(0, 200));
	check('come-funziona: foto con srcset 720w e sizes esplicito (senza auto)', imgs.filter(t => /hv2-steps__photo/.test(t)).every(t => /srcset="[^"]*720w/.test(t) && /sizes="\(max-width: 1023px\)/.test(t) && !/sizes="auto/.test(t)));
	const css = require('fs').readFileSync(require('path').join(__dirname, '..', 'style.css'), 'utf8');
	const i0 = css.indexOf('Carosello generico (TICKET-122)');
	check('come-funziona: nessun !important nei blocchi CSS nuovi (tp-carousel e hv2-steps)', i0 > 0 && !/!important/.test(css.slice(i0)), 'blocco da ' + i0);
	const stepsJs = require('fs').readFileSync(require('path').join(__dirname, '..', 'assets', 'js', 'home-steps-stack.js'), 'utf8');
	check('come-funziona: la pila non usa GSAP ne\' Three.js', !/gsap|three/i.test(stepsJs.replace(/\/\*[\s\S]*?\*\//, '')));
	return html;
}
async function checkStepsBrowser() {
	await withChrome(bin, 'about:blank', { port: 9515, width, height: 900, mobile: width < 1024, profile: '/tmp/cdp-profile-home-v2-steps' }, async ({ send, ev, sleep }) => {
		const errors = [];
		await send('Page.navigate', { url });
		await sleep(3500);
		await ev('document.querySelector("#tp-consent")&&(document.querySelector("#tp-consent").style.display="none")');
		const J = async e => JSON.parse(await ev('JSON.stringify(' + e + ')'));
		const info = await J('({navh: parseFloat(getComputedStyle(document.body).getPropertyValue("--tp-navbar-h")), nav: (()=>{const n=document.querySelector("[data-tp-carousel-nav]");const cs=getComputedStyle(n);return {hidden:n.hidden,display:cs.display}})(), role: document.querySelector("[data-tp-carousel]").getAttribute("aria-roledescription"), sc: document.scrollingElement.scrollWidth <= document.scrollingElement.clientWidth})');
		check('come-funziona: nessuno scroll orizzontale della pagina', info.sc);
		if (width >= 1024) {
			check('come-funziona: l\'indicatore non si vede su desktop (display none)', info.nav.display === 'none', JSON.stringify(info.nav));
			await ev('window.scrollTo(0, document.querySelector("#come-funziona").getBoundingClientRect().top + scrollY + 1400)');
			await sleep(900);
			const st = await J('[...document.querySelectorAll(".tp-carousel__slide")].map(l=>({pos:getComputedStyle(l).position, top:Math.round(l.getBoundingClientRect().top*10)/10, topcss:getComputedStyle(l).top, m:new DOMMatrix(getComputedStyle(l.firstElementChild).transform).a}))');
			check('come-funziona: le quattro schede sono position sticky', st.length === 4 && st.every(s => s.pos === 'sticky'), JSON.stringify(st));
			const tops = st.map(s => s.top);
			check('come-funziona: top crescente di 16 px da scheda a scheda, ' + tops.slice(0, 3).join(' < '), tops[0] < tops[1] && tops[1] < tops[2] && Math.abs(tops[1] - tops[0] - 16) <= 1.5 && Math.abs(tops[2] - tops[1] - 16) <= 1.5 && Math.abs(tops[0] - (info.navh + 24)) <= 1.5, JSON.stringify(st));
			check('come-funziona: la scheda precedente scala a 0,96 (' + st.slice(0, 3).map(s => s.m.toFixed(3)).join(', ') + ') e l\'ultima resta a 1', st.slice(0, 3).every(s => Math.abs(s.m - 0.96) <= 0.006) && st[3].m === 1, JSON.stringify(st.map(s => s.m)));
			await ev('window.scrollTo(0, document.querySelector("#come-funziona").getBoundingClientRect().top + scrollY + 400)');
			await sleep(500);
			const mid = await J('[...document.querySelectorAll(".tp-carousel__slide")].map(l=>new DOMMatrix(getComputedStyle(l.firstElementChild).transform).a)');
			check('come-funziona: durante l\'arrivo della seconda scheda la prima e\' fra 0,96 e 1 (' + mid[0].toFixed(3) + ')', mid[0] > 0.96 && mid[0] < 1 && mid[3] === 1, JSON.stringify(mid));
			// Ancora: la sezione sta sotto la barra
			await send('Page.navigate', { url: url.split('#')[0] + '#come-funziona' });
			await sleep(2500);
			await ev('location.hash = ""; location.hash = "#come-funziona"');
			await sleep(1200);
			const anc = await J('({top: document.querySelector("#come-funziona").getBoundingClientRect().top, navh: document.querySelector("header.navbar") ? document.querySelector("header.navbar").getBoundingClientRect().height : 0})');
			check('come-funziona: l\'ancora #come-funziona porta l\'inizio sotto la barra (top ' + anc.top.toFixed(1) + ', barra ' + anc.navh + ', --tp-navbar-h ' + info.navh + ')', Math.abs(anc.top - info.navh) <= 2 && Math.abs(anc.top - anc.navh) <= 4, JSON.stringify(anc));
			// Riduci movimento: nessuna scheda scala
			await send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });
			await ev('window.scrollTo(0, document.querySelector("#come-funziona").getBoundingClientRect().top + scrollY + 1400)');
			await sleep(700);
			const rm = await J('[...document.querySelectorAll(".tp-carousel__slide")].map(l=>new DOMMatrix(getComputedStyle(l.firstElementChild).transform).a)');
			check('come-funziona: con "riduci movimento" nessuna scheda scala', rm.every(a => a === 1), JSON.stringify(rm));
			await send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'no-preference' }] });
		} else {
			const car = await J('(()=>{const t=document.querySelector(".tp-carousel__track"), cs=getComputedStyle(t); const sl=[...t.children].map(l=>Math.round(l.getBoundingClientRect().width)); const d=[...document.querySelectorAll("[data-tp-carousel-dot]")].map(b=>Math.round(b.getBoundingClientRect().height)); const a=[...document.querySelectorAll(".tp-carousel__arrow")].map(b=>[Math.round(b.getBoundingClientRect().width),Math.round(b.getBoundingClientRect().height)]); const n=document.querySelector("[data-tp-carousel-nav]"); return {snap:cs.scrollSnapType, ox:cs.overflowX, sw:t.scrollWidth, cw:t.clientWidth, slides:sl, dots:d, arrows:a, navHidden:n.hidden, navDisplay:getComputedStyle(n).display, count:document.querySelector("[data-tp-carousel-count]").textContent, live:document.querySelector("[data-tp-carousel-count]").getAttribute("aria-live"), tab:t.tabIndex, pos:getComputedStyle(t.children[0]).position}})()');
			check('come-funziona: carosello con scroll-snap x mandatory e pista che scorre (' + car.snap + ', ' + car.sw + ' > ' + car.cw + ')', /x mandatory/.test(car.snap) && car.ox === 'auto' && car.sw > car.cw && car.pos === 'static', JSON.stringify(car));
			check('come-funziona: schede da 300 px (o la larghezza meno 56)', car.slides.every(w => Math.abs(w - Math.min(300, width - 56)) <= 1), JSON.stringify(car.slides));
			check('come-funziona: indicatore visibile, "1 di 4" in aria-live="polite", pista raggiungibile da tastiera (tabindex 0)', !car.navHidden && car.navDisplay !== 'none' && car.count === '1 di 4' && car.live === 'polite' && car.tab === 0, JSON.stringify(car));
			check('come-funziona: quattro puntini e frecce alti e larghi almeno 44 px (' + car.dots.join(',') + ' / ' + car.arrows.map(a => a.join('x')).join(',') + ')', car.dots.length === 4 && car.dots.every(h => h >= 44) && car.arrows.length === 2 && car.arrows.every(a => a[0] >= 44 && a[1] >= 44), JSON.stringify(car));
			check('come-funziona: componente ancora con aria-roledescription="carosello" (la pista scorre)', (await ev('document.querySelector("[data-tp-carousel]").getAttribute("aria-roledescription")')) === 'carosello');
			await ev('window.__ev = 0; document.querySelector("[data-tp-carousel]").addEventListener("tp:carousel-change", function(e){ window.__ev++; window.__last = e.detail; })');
			await ev('document.querySelector("#come-funziona").scrollIntoView()');
			await sleep(400);
			const w = await J('document.querySelector(".tp-carousel__slide").getBoundingClientRect().width + 12');
			await ev('document.querySelector(".tp-carousel__track").scrollBy(' + w + ', 0)');
			await sleep(1500);
			const s1 = await J('({count: document.querySelector("[data-tp-carousel-count]").textContent, act: [...document.querySelectorAll("[data-tp-carousel-dot]")].map(b=>b.classList.contains("is-active")&&b.getAttribute("aria-current")==="true"), ev: window.__ev, last: window.__last})');
			check('come-funziona: dopo uno scroll di una scheda l\'indicatore dice "2 di 4" e il secondo puntino e\' attivo (aria-current)', s1.count === '2 di 4' && JSON.stringify(s1.act) === '[false,true,false,false]' && s1.ev >= 1 && s1.last.index === 1, JSON.stringify(s1));
			await ev('document.querySelector("[data-tp-carousel-next]").click()');
			await sleep(2000);
			const s2 = await J('({count: document.querySelector("[data-tp-carousel-count]").textContent, pr: document.querySelector("[data-tp-carousel-prev]").disabled})');
			check('come-funziona: il clic su "avanti" porta a "3 di 4"', s2.count === '3 di 4' && s2.pr === false, JSON.stringify(s2));
			await ev('document.querySelector(".tp-carousel__track").focus()');
			await send('Input.dispatchKeyEvent', { type: 'keyDown', key: 'ArrowRight', code: 'ArrowRight', windowsVirtualKeyCode: 39 });
			await send('Input.dispatchKeyEvent', { type: 'keyUp', key: 'ArrowRight', code: 'ArrowRight', windowsVirtualKeyCode: 39 });
			await sleep(2000);
			const s3 = await J('({count: document.querySelector("[data-tp-carousel-count]").textContent, nx: document.querySelector("[data-tp-carousel-next]").disabled})');
			check('come-funziona: il tasto freccia destra sulla pista porta a "4 di 4" e disabilita "avanti"', s3.count === '4 di 4' && s3.nx === true, JSON.stringify(s3));
			await ev('document.querySelector("[data-tp-carousel-dot=\\"0\\"]").click()');
			await sleep(2000);
			check('come-funziona: il clic sul primo puntino torna a "1 di 4"', (await ev('document.querySelector("[data-tp-carousel-count]").textContent')) === '1 di 4');
		}
		// JavaScript spento
		await send('Emulation.setScriptExecutionDisabled', { value: true });
		await send('Page.navigate', { url });
		await sleep(2500);
		const nj = await J('(()=>{const t=document.querySelector(".tp-carousel__track"), n=document.querySelector("[data-tp-carousel-nav]"); const l=document.querySelector(".tp-carousel__slide"); return {snap:getComputedStyle(t).scrollSnapType, ox:getComputedStyle(t).overflowX, navHidden:n.hidden, navDisplay:getComputedStyle(n).display, pos:getComputedStyle(l).position, op:getComputedStyle(document.querySelector(".hv2-steps__card")).opacity}})()');
		if (width >= 1024) check('come-funziona: senza JavaScript le schede restano sticky e visibili, indicatore assente', nj.pos === 'sticky' && nj.navDisplay === 'none' && nj.op === '1', JSON.stringify(nj));
		else check('come-funziona: senza JavaScript la pista scorre a scatti (scroll-snap CSS) e l\'indicatore non c\'e\'', /x mandatory/.test(nj.snap) && nj.ox === 'auto' && nj.navHidden === true && nj.navDisplay === 'none', JSON.stringify(nj));
	});
}
/* Gruppo manifesto (TICKET-123) */
const vociArg = process.argv.includes('--voci') ? Number(process.argv[process.argv.indexOf('--voci') + 1]) : 4;
const MANIFESTO = [
	['Niente', 'sconosciuti.', 'Controlliamo documenti e identità di ognuno.', 'Documento verificato · Laura B.'],
	['Niente', 'sorprese.', 'Il prezzo lo vedi prima di inviare la richiesta.', 'Totale 76 € · 4 ore'],
	['Niente', 'anticipi a vuoto.', 'L’addebito parte solo quando il professionista accetta.', 'Addebito dopo l’accettazione'],
	['Niente', 'buchi.', 'Se chi hai scelto non può, te ne proponiamo un altro.', 'Ti proponiamo Marco, stesse condizioni'],
];
const dec = t => t.replace(/&#0?39;|&#8217;|&rsquo;/g, '’').replace(/&amp;/g, '&').replace(/&#8364;/g, '€').replace(/&euro;/g, '€').replace(/&middot;|&#183;/g, '·').replace(/&nbsp;/g, ' ');
async function checkManifesto() {
	const res = await fetch(url, { redirect: 'manual' });
	check('manifesto: risposta 200', res.status === 200, 'stato ' + res.status);
	if (res.status !== 200) return;
	const html = await res.text();
	const sec = (html.match(/<section class="hv2-man"[\s\S]*?<\/section>/) || [''])[0];
	if (vociArg === 0) { check('manifesto: con zero promesse la sezione non e\' stampata', sec === '' && !/data-section="05-manifesto"/.test(html), sec.slice(0, 80)); return; }
	check('manifesto: sezione 05-manifesto presente', /data-section="05-manifesto"/.test(sec), sec.slice(0, 120));
	check('manifesto: un solo h2 e nessun h1 nella sezione', (sec.match(/<h2[ >]/g) || []).length === 1 && !/<h1/.test(sec));
	const items = [...sec.matchAll(/<li class="hv2-man__item"[\s\S]*?<\/li>/g)].map(m => m[0]);
	check('manifesto: le promesse sono ' + vociArg + ' <li> di una <ol>', /<ol class="hv2-man__list">/.test(sec) && items.length === vociArg, items.length + ' li');
	const stamp = (sec.match(/<div class="hv2-man__stamp"[^>]*>/) || [''])[0];
	check('manifesto: timbro aria-hidden con testo circolare (textPath)', /aria-hidden="true"/.test(stamp) && /<textPath/.test(sec), stamp);
	check('manifesto: numeri aria-hidden', (sec.match(/class="hv2-man__num" aria-hidden="true"/g) || []).length === vociArg);
	if (vociArg === 4) {
		const got = items.map(h => {
			const t = c => dec((h.match(new RegExp('class="hv2-man__' + c + '">([^<]*)<')) || [0, ''])[1]);
			return [t('lead'), t('key'), t('text'), t('proof-text')];
		});
		check('manifesto: lead, parola chiave, frase e prova del Figma, in ordine', JSON.stringify(got) === JSON.stringify(MANIFESTO), JSON.stringify(got));
	}
	check('manifesto: una pillola di prova con icona per promessa', (sec.match(/class="hv2-man__ico"/g) || []).length === (sec.match(/class="hv2-man__proof"/g) || []).length && (sec.match(/class="hv2-man__proof"/g) || []).length >= 1);
}
async function checkManifestoBrowser() {
	if (vociArg === 0) return;
	await withChrome(bin, 'about:blank', { port: 9517, width, height: 900, mobile: width < 1024, profile: '/tmp/cdp-profile-home-v2-man' }, async ({ send, ev, sleep }) => {
		await send('Page.navigate', { url });
		await sleep(3500);
		await ev('document.querySelector("#tp-consent")&&(document.querySelector("#tp-consent").style.display="none")');
		if (!(await ev('!!document.querySelector(".hv2-man__list")'))) { check('manifesto: la sezione esiste nel browser', false, 'assente'); return; }
		await ev('document.querySelector(".hv2-man").scrollIntoView({block:"start"})');
		await sleep(1500);
		const info = JSON.parse(await ev(`(() => {
			const q = s => document.querySelector(s), cs = e => getComputedStyle(e);
			const sec = q('.hv2-man'), items = [...document.querySelectorAll('.hv2-man__item')], list = q('.hv2-man__list').getBoundingClientRect();
			const rect = e => { const r = e.getBoundingClientRect(); return { x: Math.round(r.x), y: Math.round(r.y), w: Math.round(r.width), h: Math.round(r.height) }; };
			const pair = (e, bg) => ({ c: cs(e).color, bg: bg || cs(sec).backgroundColor, fs: parseFloat(cs(e).fontSize), fw: +cs(e).fontWeight });
			return JSON.stringify({
				bg: cs(sec).backgroundColor, lead: pair(q('.hv2-man__lead')), key: pair(q('.hv2-man__key')), title: pair(q('.hv2-man__title')), eyebrow: pair(q('.hv2-man__eyebrow')),
				sub: pair(q('.hv2-man__subtitle')), num: pair(q('.hv2-man__num')), text: pair(q('.hv2-man__text')), proof: pair(q('.hv2-man__proof-text'), cs(q('.hv2-man__proof')).backgroundColor),
				ico: { bg: cs(q('.hv2-man__ico')).backgroundColor, w: rect(q('.hv2-man__ico')).w, h: rect(q('.hv2-man__ico')).h, c: cs(q('.hv2-man__ico')).color },
				items: items.map(rect), list: { x: Math.round(list.x), w: Math.round(list.width) },
				titleW: rect(q('.hv2-man__title')), titleRight: (() => { const r = document.createRange(); r.selectNodeContents(q('.hv2-man__title')); return Math.round(r.getBoundingClientRect().right); })(), stamp: rect(q('.hv2-man__stamp')), secRect: rect(sec),
				sw: document.scrollingElement.scrollWidth, cw: document.scrollingElement.clientWidth,
				up: [...document.querySelectorAll('.hv2-man__lead, .hv2-man__key, .hv2-man__title')].every(e => cs(e).textTransform === 'uppercase')
			});
		})()`));
		const rgb = c => c.match(/\d+/g).slice(0, 3).map(Number);
		const lum = c => { const [r, g, b] = rgb(c).map(v => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); }); return 0.2126 * r + 0.7152 * g + 0.0722 * b; };
		const ratio = (a, b) => { const l1 = lum(a), l2 = lum(b); return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05); };
		check('manifesto: fondo #F3FAF7', info.bg === 'rgb(243, 250, 247)', info.bg);
		check('manifesto: "NIENTE" #008866', info.lead.c === 'rgb(0, 136, 102)', info.lead.c);
		check('manifesto: parola chiave #0F1F1B', info.key.c === 'rgb(15, 31, 27)', info.key.c);
		check('manifesto: lead e parola chiave in maiuscolo', info.up);
		const grande = p => p.fs >= 24 || (p.fs >= 18.66 && p.fw >= 700);
		for (const [nome, p] of Object.entries({ lead: info.lead, key: info.key, titolo: info.title, eyebrow: info.eyebrow, sottotitolo: info.sub, numero: info.num, frase: info.text, prova: info.proof })) {
			const r = ratio(p.c, p.bg), soglia = grande(p) ? 3 : 4.5;
			check('manifesto: contrasto ' + nome + ' ' + r.toFixed(2) + ':1 >= ' + soglia + ' (' + p.fs + ' px, peso ' + p.fw + ')', r >= soglia, p.c + ' su ' + p.bg);
		}
		check('manifesto: icona prova 28 px, tondo #E6FFF5, glifo #008866', info.ico.w === 28 && info.ico.h === 28 && info.ico.bg === 'rgb(230, 255, 245)' && info.ico.c === 'rgb(0, 136, 102)', JSON.stringify(info.ico));
		const n = info.items.length, xs = [...new Set(info.items.map(i => i.x))];
		if (width >= 1024) {
			check('manifesto: due colonne a ' + width + ' (' + xs.length + ' x diverse)', xs.length === 2 || n === 1, JSON.stringify(xs));
			if (width === 1440) check('manifesto: lista di 1248 px da x 96, colonne da 600 px', info.list.w === 1248 && info.list.x === 96 && info.items[0].w === 600, JSON.stringify(info.list) + ' ' + info.items[0].w);
		} else {
			check('manifesto: una colonna a ' + width, xs.length === 1, JSON.stringify(xs));
		}
		const ultima = info.items[n - 1];
		check('manifesto: nessun buco, l\'ultima promessa ' + ((n % 2 === 1 && width >= 1024) ? 'occupa la riga intera' : 'sta nel suo posto'), (n % 2 === 1 && width >= 1024) ? ultima.w === info.list.w : ultima.w <= info.list.w, JSON.stringify(ultima));
		check('manifesto: il timbro (da x ' + info.stamp.x + ') non copre il titolo (che finisce a x ' + info.titleRight + ')', width < 768 || info.stamp.x >= info.titleRight, info.stamp.x + ' < ' + info.titleRight);
		check('manifesto: nessuno scroll orizzontale', info.sw <= info.cw, info.sw + ' > ' + info.cw);
		if (width === 1440 && vociArg === 4) check('manifesto: titolo 88 px su una riga', Math.round(info.title.fs) === 88 && info.titleW.h <= 90, JSON.stringify(info.titleW) + ' ' + info.title.fs);
		if (width === 390 && vociArg === 4) check('manifesto: titolo 48 px su due righe, padding 16', info.title.fs === 48 && info.titleW.h === 100 && info.titleW.x === 16, JSON.stringify(info.titleW));
	});
}

(async () => {
	if (!['base', 'hero', 'frasi', 'servizi', 'manifesto', 'come-funziona'].includes(gruppo)) { console.error('Gruppo sconosciuto: ' + gruppo); process.exit(2); }
	if (gruppo === 'hero') {
		const h = await checkHero();
		if (h) await checkHeroBrowser(h.pre);
	} else if (gruppo === 'frasi') {
		await checkFrasi();
		await checkFrasiBrowser();
	} else if (gruppo === 'manifesto') {
		await checkManifesto();
		await checkManifestoBrowser();
	} else if (gruppo === 'servizi') {
		await checkServizi();
		await checkServiziBrowser();
	} else if (gruppo === 'come-funziona') {
		await checkSteps();
		await checkStepsBrowser();
	} else {
		const html = await checkHttp();
		if (html) await checkBrowser();
	}
	const ko = results.filter(r => !r.ok).length;
	console.log(ko ? '\nFALLITO: ' + ko + ' controlli su ' + results.length : '\nTutti i controlli ok (' + results.length + ')');
	process.exit(ko ? 1 : 0);
})();
