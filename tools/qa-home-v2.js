/* QA della Home v2 (SPEC-homepage-v2, TICKET-119 e seguenti).
   Uso: node tools/qa-home-v2.js <chrome> <url-anteprima> [larghezza=1440] [--sezioni base|hero|frasi|servizi] [--google on|off] [--servizi base|rotto]
   <url-anteprima> e' la pagina d'anteprima (es. https://trovapulizie.it/anteprima-pagina-iniziale/).

   Ogni ticket aggiunge la propria funzione di controllo in CHECKS e il proprio nome in SEZIONI. Con --sezioni <nome>
   si esegue solo quel gruppo (oggi: base, hero, frasi, servizi). Esce con 1 se un controllo fallisce.

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

   Gruppo "servizi" (TICKET-121), --servizi base|rotto dice quale pagina si prova. "base": Casa, Aziende, Condomini,
   Disinfestazione sono <a> verso /roma/, /roma/pulizie-uffici/, /roma/pulizie-condomini/, /roma/zanzare-disinfestazione/;
   Affitti brevi e Manutenzione sono <span> (non link) con la parola "presto" visibile e non nascosta agli screen reader;
   "Scopri i servizi nell'app" verso l'app con data-cta-position="services"; la riga "Altri servizi a Roma" ha i sei link
   con gli anchor di tp_service_pages() (letti da inc/seo.php) e href coerenti; ogni href della sezione risponde 200 (richiesta
   HTTP diretta); un solo h2, nessun h1; riquadro largo 668,8 px a 1440 e 358 px a 390; hover sulle icone che sono
   link: +4 px verso l'alto (hover reale); nessuna icona-span con hover. "rotto" (pagina con Carbon: Casa senza URL,
   Aziende verso una pagina in bozza): Casa e Aziende sono <span> senza href, le altre restano link e nessun link porta a una 404. */
const { withChrome } = require('./lib/cdp.js');

const SECTIONS = ['01-hero', '02-frasi', '03-servizi', '04-come-funziona', '05-manifesto', '06-recensioni', '07-zone', '08-aziende', '09-faq', '10-chiusura'];
const args = process.argv.slice(2).filter((a, i, all) => !a.startsWith('--') && !(i > 0 && ['--sezioni', '--google', '--servizi', '--frasi'].includes(all[i - 1])));
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

(async () => {
	if (!['base', 'hero', 'frasi', 'servizi'].includes(gruppo)) { console.error('Gruppo sconosciuto: ' + gruppo); process.exit(2); }
	if (gruppo === 'hero') {
		const h = await checkHero();
		if (h) await checkHeroBrowser(h.pre);
	} else if (gruppo === 'frasi') {
		await checkFrasi();
		await checkFrasiBrowser();
	} else if (gruppo === 'servizi') {
		await checkServizi();
		await checkServiziBrowser();
	} else {
		const html = await checkHttp();
		if (html) await checkBrowser();
	}
	const ko = results.filter(r => !r.ok).length;
	console.log(ko ? '\nFALLITO: ' + ko + ' controlli su ' + results.length : '\nTutti i controlli ok (' + results.length + ')');
	process.exit(ko ? 1 : 0);
})();
