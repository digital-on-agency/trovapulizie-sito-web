/* QA della Home v2 (SPEC-homepage-v2, TICKET-119 e seguenti).
   Uso: node tools/qa-home-v2.js <chrome> <url-anteprima> [larghezza=1440] [--sezioni base|hero|frasi|servizi|manifesto|come-funziona|chiusura|aziende|faq|tracking] [--google on|off] [--servizi base|rotto] [--badges N] [--bottoni on|off] [--domande N] [--fondatori "Nomi|Ruolo"]
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
   cambio), pagina senza scroll orizzontale; con JavaScript spento la pista scorre ancora a scatti e l'indicatore non c'e'.

   Gruppo "aziende" (TICKET-126), a 390 e 1440; --badges N dice quante garanzie ha la pagina provata (default 2: "Sopralluogo
   gratuito" e "Imprese verificate", mai "Preventivo entro 24 ore" se non e' nel campo), --bottoni on|off se bottone e link
   escono (off = URL svuotati). Sezione 08-aziende con un solo h2; "Richiedi un preventivo" verso /roma/sgrosso-commerciale/
   con data-cta-position="home_b2b", "Scopri le pulizie per aziende" verso /roma/pulizie-uffici/, entrambi con risposta
   200; con --bottoni off nessuno dei due e nessun contenitore delle azioni. Colori e misure calcolati dal Figma (riquadro
   1248 px e raggio 28 a 1440, raggio 24 a 390; titolo 48/52 e 28/32 in maiuscolo; bottone #37B299 col testo bianco, alto
   almeno 44 px); sezione trasparente e senza overflow nascosto (sfondo continuo); testo e azioni su una riga a 1440 e
   impilati a 390 con il bottone a tutta larghezza; nessuno scroll orizzontale; foto decorativa (alt vuoto, lazy).

   Gruppo "faq" (TICKET-126), a 390 e 1440; --domande N dice quante domande escono (default 6 = le sei di default; 0 = sezione
   assente e nessun JSON-LD), --fondatori "Nomi|Ruolo" i valori attesi della card (default "Guido & Christian|Fondatori", quelli
   di Theme Options). Sezione 09-faq con classe faq--v2; la pagina espone un solo JSON-LD FAQPage con le stesse domande e
   risposte del DOM, ogni risposta e' nel DOM anche da chiusa; la prima domanda e' aperta (is-open) nel markup del server;
   card "Rispondiamo di persona" con nomi e ruolo come Theme Options e due foto, bottone "Scrivici su WhatsApp" verso wa.me con
   data-cta-position="faq_whatsapp"; nessun altro data-cta-position nella sezione. Nel browser, con clic reali: aprendo una
   domanda si chiude quella aperta (aria-expanded), un secondo clic la richiude; misure: titolo in maiuscolo 48 px a 1440 e 40 a
   390, domande a 14 px di raggio (16 a 390), card a sinistra sotto il titolo a 1440 e sotto la lista a 390, sottotitolo nascosto a
   390, bottone #37B299 col testo bianco; nessuno scroll orizzontale.
   Gruppo "chiusura" (TICKET-127), anche a 390 e 1440: la <section data-section="10-chiusura"> ha un h2 e nessun h1; "Apri l'app" e' un <a>
   verso https://app.trovapulizie.com/ (nessun parametro, nessun target) con data-cta-position="cta_final"; bottone BIANCO con testo
   #062A22 (eccezione di brand); contrasto dei testi piccoli sul verde #37B299 (blend con l'alfa) >= 4,5 (#062A22 atteso 5,8); il QR e'
   lo stesso SVG della hero (<title> = https://app.trovapulizie.com/). A 1440: QR 72 px visibile, immagini del tentacolo caricate
   (naturalWidth > 0), fascia alta 630. A 390: bottone a tutta larghezza (358), QR e tentacolo non visibili e NESSUNA richiesta di
   qr-app.svg ne' cta-tentacolo*.webp (performance.getEntriesByType('resource') dopo uno scroll completo), fascia alta 754; con "riduci
   movimento" nessuna transizione sul bottone. Nessuno scroll orizzontale.

   Gruppo "tracking" (TICKET-130), a 1440: ogni link con data-cta-position (barra, hero, servizi, come funziona, zone e, se
   ci sono, aziende, FAQ, chiusura) emette un solo marketing_cta_click con landing "home", cta_position uguale all'attributo
   e target uguale all'href originale (senza UTM); un link verso l'app o WhatsApp dentro main.hv2 senza attributo prende la
   posizione dalla sezione (prova con tre link sintetici nelle sezioni 08, 09, 10); WhatsApp con posizione emette
   marketing_cta_click e non marketing_click_whatsapp; senza consenso l'evento resta nel dataLayer e il link e' intatto;
   un link senza posizione e non verso app/WhatsApp non emette niente; tpTrackConfig.ctaAnywhere e' vero sull'anteprima.
   Il click e' reale (dispatchEvent su un click intercettato in coda con preventDefault: nessuna navigazione). */
const { withChrome } = require('./lib/cdp.js');

const SECTIONS = ['01-hero', '02-frasi', '03-servizi', '04-come-funziona', '05-manifesto', '06-recensioni', '07-zone', '08-aziende', '09-faq', '10-chiusura'];
const args = process.argv.slice(2).filter((a, i, all) => !a.startsWith('--') && !(i > 0 && ['--sezioni', '--google', '--servizi', '--frasi', '--voci', '--badges', '--bottoni', '--domande', '--fondatori'].includes(all[i - 1])));
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
		const r = JSON.parse(await ev(`const opt = (n, d) => (process.argv.includes(n) ? process.argv[process.argv.indexOf(n) + 1] : d);
const badgesArg = Number(opt('--badges', 2));
const bottoniArg = opt('--bottoni', 'on');
const domandeArg = Number(opt('--domande', 6));
const fondatoriArg = opt('--fondatori', 'Guido & Christian|Fondatori').split('|');
const stripTags = h => dec(h.replace(/<[^>]+>/g, '').replace(/\s+/g, ' ').trim());
async function headStatus(u) { try { const r = await fetch(u, { redirect: 'manual' }); return r.status; } catch (e) { return 0; } }
async function checkAziende() {
	const res = await fetch(url, { redirect: 'manual' });
	check('aziende: risposta 200', res.status === 200, 'stato ' + res.status);
	if (res.status !== 200) return;
	const html = await res.text();
	const sec = (html.match(/<section class="hv2-b2b"[\s\S]*?<\/section>/) || [''])[0];
	check('aziende: sezione 08-aziende presente', /data-section="08-aziende"/.test(sec), sec.slice(0, 80));
	check('aziende: un solo h2 e nessun h1', (sec.match(/<h2[ >]/g) || []).length === 1 && !/<h1/.test(sec));
	const badges = [...sec.matchAll(/<li class="hv2-b2b__badge">([\s\S]*?)<\/li>/g)].map(m => stripTags(m[1]));
	check('aziende: ' + badgesArg + ' garanzie', badges.length === badgesArg, JSON.stringify(badges));
	check('aziende: "Sopralluogo gratuito" confermato presente, "Preventivo entro 24 ore" solo se in elenco', (badgesArg === 0 || badges.includes('Sopralluogo gratuito')) && (badges.includes('Preventivo entro 24 ore') === (badgesArg === 3)), JSON.stringify(badges));
	const cta = (sec.match(/<a class="hv2-b2b__cta"[^>]*>/) || [''])[0];
	const link = (sec.match(/<a class="hv2-b2b__link"[^>]*>/) || [''])[0];
	if (bottoniArg === 'off') {
		check('aziende: con URL svuotati nessun bottone, nessun link, nessun contenitore delle azioni', !cta && !link && !/hv2-b2b__actions/.test(sec), cta + link);
	} else {
		const href = a => (a.match(/href="([^"]*)"/) || [0, ''])[1];
		check('aziende: bottone verso /roma/sgrosso-commerciale/ con data-cta-position="home_b2b"', /\/roma\/sgrosso-commerciale\/$/.test(href(cta)) && /data-cta-position="home_b2b"/.test(cta), cta);
		check('aziende: link verso /roma/pulizie-uffici/', /\/roma\/pulizie-uffici\/$/.test(href(link)), link);
		check('aziende: il bottone e il link rispondono 200', (await headStatus(href(cta))) === 200 && (await headStatus(href(link))) === 200);
		check('aziende: etichette "Richiedi un preventivo" e "Scopri le pulizie per aziende"', /Richiedi un preventivo/.test(sec) && /Scopri le pulizie per aziende/.test(sec));
	}
	const img = (sec.match(/<img[^>]*>/) || [''])[0];
	check('aziende: foto decorativa (alt vuoto, lazy, dimensioni)', /alt=""/.test(img) && /loading="lazy"/.test(img) && /width="\d+"/.test(img) && /height="\d+"/.test(img), img.slice(0, 160));
}
async function checkAziendeBrowser() {
	await withChrome(bin, 'about:blank', { port: 9561, width, height: 900, mobile: width < 1024, profile: '/tmp/cdp-profile-home-v2-azi' }, async ({ send, ev, sleep }) => {
		await send('Page.navigate', { url });
		await sleep(3500);
		await ev('document.querySelector("#tp-consent")&&(document.querySelector("#tp-consent").style.display="none")');
		await ev('document.querySelector(".hv2-b2b").scrollIntoView({block:"start"})');
		await sleep(1500);
		const info = JSON.parse(await ev(`(() => {
			const q = s => document.querySelector(s), cs = e => getComputedStyle(e);
			const r = e => { const b = e.getBoundingClientRect(); return { x: Math.round(b.x), y: Math.round(b.y), w: Math.round(b.width), h: Math.round(b.height) }; };
			const sec = q('.hv2-b2b'), box = q('.hv2-b2b__box'), cta = q('.hv2-b2b__cta'), link = q('.hv2-b2b__link');
			return JSON.stringify({
				secBg: cs(sec).backgroundColor, secOv: cs(sec).overflow, boxRadius: cs(box).borderTopLeftRadius, box: r(box),
				title: { fs: cs(q('.hv2-b2b__title')).fontSize, lh: cs(q('.hv2-b2b__title')).lineHeight, tt: cs(q('.hv2-b2b__title')).textTransform, fw: cs(q('.hv2-b2b__title')).fontWeight, c: cs(q('.hv2-b2b__title')).color },
				text: r(q('.hv2-b2b__text')), actions: q('.hv2-b2b__actions') ? r(q('.hv2-b2b__actions')) : null,
				cta: cta ? { bg: cs(cta).backgroundColor, c: cs(cta).color, r: r(cta), fs: cs(cta).fontSize } : null,
				link: link ? { c: cs(link).color, r: r(link) } : null,
				eyebrow: cs(q('.hv2-b2b__eyebrow')).color, imgFit: cs(q('.hv2-b2b__photo img')).objectFit,
				sw: document.scrollingElement.scrollWidth, cw: document.scrollingElement.clientWidth
			});
		})()`));
		const wide = width >= 1024;
		check('aziende: sezione trasparente e senza overflow nascosto (sfondo continuo)', info.secBg === 'rgba(0, 0, 0, 0)' && info.secOv === 'visible', info.secBg + ' ' + info.secOv);
		check('aziende: raggio ' + (wide ? 28 : 24) + ' px e titolo in maiuscolo', info.boxRadius === (wide ? '28px' : '24px') && info.title.tt === 'uppercase', info.boxRadius + ' ' + info.title.tt);
		check('aziende: titolo ' + (wide ? '48/52' : '28/32') + ' bianco, peso 900', info.title.fs === (wide ? '48px' : '28px') && info.title.lh === (wide ? '52px' : '32px') && info.title.c === 'rgb(255, 255, 255)' && info.title.fw === '900', JSON.stringify(info.title));
		check('aziende: etichetta #7FE3C9, foto object-fit cover', info.eyebrow === 'rgb(127, 227, 201)' && info.imgFit === 'cover', info.eyebrow + ' ' + info.imgFit);
		if (width === 1440) check('aziende: riquadro 1248 px da x 96', info.box.w === 1248 && info.box.x === 96, JSON.stringify(info.box));
		if (width === 390) check('aziende: riquadro 358 px da x 16', info.box.w === 358 && info.box.x === 16, JSON.stringify(info.box));
		if (bottoniArg === 'on') {
			check('aziende: bottone #37B299 con testo bianco, alto almeno 44 px', info.cta.bg === 'rgb(55, 178, 153)' && info.cta.c === 'rgb(255, 255, 255)' && info.cta.r.h >= 44, JSON.stringify(info.cta));
			check('aziende: link #7FE3C9', info.link.c === 'rgb(127, 227, 201)', info.link.c);
			if (wide) check('aziende: testo e azioni sulla stessa riga (azioni a destra del testo)', info.actions.x >= info.text.x + info.text.w - 2 && Math.abs((info.actions.y + info.actions.h / 2) - (info.text.y + info.text.h / 2)) < 30, JSON.stringify([info.text, info.actions]));
			else check('aziende: azioni sotto il testo, bottone a tutta larghezza (314 px)', info.actions.y >= info.text.y + info.text.h - 2 && info.cta.r.w === 314, JSON.stringify([info.text, info.actions, info.cta.r]));
		}
		check('aziende: nessuno scroll orizzontale', info.sw <= info.cw, info.sw + ' > ' + info.cw);
	});
}
const FAQ_ATTESE = 6;
async function checkFaq() {
	const res = await fetch(url, { redirect: 'manual' });
	check('faq: risposta 200', res.status === 200, 'stato ' + res.status);
	if (res.status !== 200) return;
	const html = await res.text();
	const lds = [...html.matchAll(/<script type="application\/ld\+json">([\s\S]*?)<\/script>/g)].map(m => m[1]).filter(t => /"FAQPage"/.test(t));
	if (domandeArg === 0) {
		check('faq: con zero domande la sezione 09-faq non e\' nel DOM e non c\'e\' JSON-LD FAQPage', !/data-section="09-faq"/.test(html) && !/class="faq faq--v2"/.test(html) && lds.length === 0, lds.length + ' FAQPage');
		return;
	}
	const sec = (html.match(/<div class="hv2-faq" data-section="09-faq">[\s\S]*?<\/section>/) || [''])[0];
	check('faq: sezione 09-faq con classe faq--v2', /data-section="09-faq"/.test(sec) && /<section class="faq faq--v2"/.test(sec), sec.slice(0, 120));
	check('faq: un solo h2 della sezione e nessun h1', (sec.match(/<h2[ >]/g) || []).length === 1 && !/<h1/.test(sec));
	check('faq: un solo JSON-LD FAQPage nella pagina', lds.length === 1, lds.length + ' FAQPage');
	const items = [...sec.matchAll(/<div class="faq__item[^"]*"[^>]*>[\s\S]*?<\/div>\s*<\/div>\s*<\/div>/g)].map(m => m[0]);
	check('faq: ' + domandeArg + ' domande nel DOM', items.length === domandeArg, items.length + ' item');
	const dom = items.map(h => [stripTags((h.match(/<span class="faq__q-text">([\s\S]*?)<\/span>/) || [0, ''])[1]), stripTags((h.match(/<div class="faq__a-inner">([\s\S]*?)<\/div>/) || [0, ''])[1])]);
	let ld = []; try { ld = JSON.parse(lds[0]).mainEntity.map(e => [e.name, e.acceptedAnswer.text]); } catch (e) { /* dettaglio sotto */ }
	check('faq: il JSON-LD ha le stesse domande e risposte del DOM, nello stesso ordine', ld.length === dom.length && ld.every((e, i) => dec(e[0]) === dom[i][0] && dec(e[1]) === dom[i][1]), JSON.stringify(ld).slice(0, 200) + ' / ' + JSON.stringify(dom).slice(0, 200));
	check('faq: ogni risposta e\' nel DOM anche da chiusa (testo non vuoto)', dom.length > 0 && dom.every(d => d[0] && d[1]));
	check('faq: la prima domanda e\' aperta (is-open, aria-expanded true) e le altre chiuse nel markup del server', /faq__item is-open/.test(items[0] || '') && /aria-expanded="true"/.test(items[0] || '') && items.slice(1).every(h => !/is-open/.test(h) && /aria-expanded="false"/.test(h)));
	const contact = (sec.match(/<aside class="faq__contact">[\s\S]*?<\/aside>/) || [''])[0];
	const nomi = stripTags((contact.match(/<strong>([\s\S]*?)<\/strong>/) || [0, ''])[1]), ruolo = stripTags((contact.match(/<\/strong><span>([\s\S]*?)<\/span>/) || [0, ''])[1]);
	check('faq: la card ha nomi e ruolo come Theme Options (' + fondatoriArg.join(' / ') + ')', nomi === fondatoriArg[0] && ruolo === fondatoriArg[1], nomi + ' / ' + ruolo);
	check('faq: la card ha due foto dei fondatori', (contact.match(/class="faq__avatar"/g) || []).length === 2);
	check('faq: titolo "Rispondiamo di persona"', /Rispondiamo di persona/.test(contact));
	const cta = (contact.match(/<a class="faq__contact-cta"[^>]*>[\s\S]*?<\/a>/) || [''])[0];
	check('faq: bottone "Scrivici su WhatsApp" verso wa.me con data-cta-position="faq_whatsapp"', /href="https:\/\/wa\.me\/39\d{9,10}"/.test(cta) && /data-cta-position="faq_whatsapp"/.test(cta) && /Scrivici su WhatsApp/.test(cta), cta.slice(0, 200));
	check('faq: nessun altro data-cta-position nella sezione', (sec.match(/data-cta-position=/g) || []).length === 1);
}
async function checkFaqBrowser() {
	if (domandeArg === 0) return;
	await withChrome(bin, 'about:blank', { port: 9562, width, height: 900, mobile: width < 1024, profile: '/tmp/cdp-profile-home-v2-faq' }, async ({ send, ev, sleep }) => {
		await send('Page.navigate', { url });
		await sleep(3500);
		await ev('document.querySelector("#tp-consent")&&(document.querySelector("#tp-consent").style.display="none")');
		await ev('document.querySelector(".faq--v2").scrollIntoView({block:"start"})');
		await sleep(1800);
		const info = JSON.parse(await ev(`(() => {
			const q = s => document.querySelector(s), cs = e => getComputedStyle(e);
			const r = e => { const b = e.getBoundingClientRect(); return { x: Math.round(b.x), y: Math.round(b.y), w: Math.round(b.width), h: Math.round(b.height) }; };
			const t = q('.faq--v2 .faq__title'), it = q('.faq--v2 .faq__item'), cta = q('.faq--v2 .faq__contact-cta');
			return JSON.stringify({
				title: { fs: cs(t).fontSize, lh: cs(t).lineHeight, tt: cs(t).textTransform, fw: cs(t).fontWeight, c: cs(t).color, r: r(t) },
				radius: cs(it).borderTopLeftRadius, sub: cs(q('.faq--v2 .faq__subtitle')).display, bg: cs(q('.faq--v2')).backgroundColor,
				list: r(q('.faq--v2 .faq__list')), contact: r(q('.faq--v2 .faq__contact')), aside: r(q('.faq--v2 .faq__aside')),
				cta: { bg: cs(cta).backgroundColor, c: cs(cta).color, h: r(cta).h },
				sw: document.scrollingElement.scrollWidth, cw: document.scrollingElement.clientWidth
			});
		})()`));
		const wide = width >= 1024;
		check('faq: titolo in maiuscolo ' + (wide ? '48/52' : '40/42') + ', peso 900, #062A22', info.title.tt === 'uppercase' && info.title.fs === (wide ? '48px' : '40px') && info.title.lh === (wide ? '52px' : '42px') && info.title.fw === '900' && info.title.c === 'rgb(6, 42, 34)', JSON.stringify(info.title));
		check('faq: domande con raggio ' + (wide ? 14 : 16) + ' px, fondo della sezione trasparente', info.radius === (wide ? '14px' : '16px') && info.bg === 'rgba(0, 0, 0, 0)', info.radius + ' ' + info.bg);
		check('faq: bottone #37B299 con testo bianco, alto almeno 44 px', info.cta.bg === 'rgb(55, 178, 153)' && info.cta.c === 'rgb(255, 255, 255)' && info.cta.h >= 44, JSON.stringify(info.cta));
		if (wide) check('faq: card a sinistra sotto il titolo, lista a destra', info.contact.x < info.list.x && info.contact.y >= info.aside.y + info.aside.h - 2 && info.list.x >= info.contact.x + info.contact.w, JSON.stringify([info.aside, info.contact, info.list]));
		else {
			check('faq: card sotto la lista, sottotitolo nascosto', info.contact.y >= info.list.y + info.list.h - 2 && info.sub === 'none', JSON.stringify([info.list, info.contact]) + ' ' + info.sub);
			check('faq: lista e card larghe 358 px da x 16', info.list.w === 358 && info.list.x === 16 && info.contact.w === 358, JSON.stringify([info.list, info.contact]));
		}
		if (width === 1440) check('faq: colonne 360 e 792 px da x 120', info.aside.w === 360 && info.aside.x === 120 && info.list.w === 792, JSON.stringify([info.aside, info.list]));
		// Apertura esclusiva con clic reali al centro del pulsante
		const stato = async () => JSON.parse(await ev('JSON.stringify([...document.querySelectorAll(".faq--v2 .faq__q")].map(b=>b.getAttribute("aria-expanded")==="true"))'));
		const clic = async i => {
			await ev('document.querySelectorAll(".faq--v2 .faq__q")[' + i + '].scrollIntoView({block:"center"})');
			await sleep(400);
			const c = JSON.parse(await ev('(()=>{const b=document.querySelectorAll(".faq--v2 .faq__q")[' + i + '].getBoundingClientRect();return JSON.stringify({x:Math.round(b.x+b.width/2),y:Math.round(b.y+b.height/2)})})()'));
			await send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: c.x, y: c.y });
			await send('Input.dispatchMouseEvent', { type: 'mousePressed', x: c.x, y: c.y, button: 'left', clickCount: 1 });
			await send('Input.dispatchMouseEvent', { type: 'mouseReleased', x: c.x, y: c.y, button: 'left', clickCount: 1 });
			await sleep(700);
		};
		const s0 = await stato();
		check('faq: all\'avvio e\' aperta solo la prima', s0[0] && s0.slice(1).every(v => !v), JSON.stringify(s0));
		await clic(2);
		const s1 = await stato();
		check('faq: aprendo la terza si chiude la prima (una sola aperta)', !s1[0] && s1[2] && s1.filter(Boolean).length === 1, JSON.stringify(s1));
		let h = 0; // l'altezza e' animata (0,3 s): con la macchina carica puo' servire piu' tempo
		for (let k = 0; k < 10 && !(h > 20); k++) { h = JSON.parse(await ev('(()=>{const a=document.querySelectorAll(".faq--v2 .faq__a")[2];return JSON.stringify(Math.round(a.getBoundingClientRect().height))})()')); if (!(h > 20)) await sleep(300); }
		check('faq: la risposta aperta ha altezza visibile (' + h + ' px) e il segno e\' "−"', h > 20 && (await ev('getComputedStyle(document.querySelectorAll(".faq--v2 .faq__toggle")[2],"::before").content')) === '"\u2212"', h + ' px');
		await clic(2);
		const s2 = await stato();
		check('faq: un secondo clic sulla stessa domanda la richiude', s2.every(v => !v), JSON.stringify(s2));
		await clic(0);
		const s3 = await stato();
		check('faq: riaprendo la prima si apre solo lei', s3[0] && s3.filter(Boolean).length === 1, JSON.stringify(s3));
		check('faq: nessuno scroll orizzontale', info.sw <= info.cw, info.sw + ' > ' + info.cw);
	});
}

/* Gruppo tracking (TICKET-130) */
async function checkTracking() {
	await withChrome(bin, 'about:blank', { port: 9533, width, height: 900, mobile: width < 1024, profile: '/tmp/cdp-profile-home-v2-track' }, async ({ send, ev, sleep }) => {
		await send('Page.navigate', { url });
		await sleep(3500);
		const cfg = await ev('JSON.stringify(window.tpTrackConfig || null)');
		check('tracking: tpTrackConfig.ctaAnywhere vero sull\'anteprima', JSON.parse(cfg) && JSON.parse(cfg).ctaAnywhere === true, cfg);
		// Tre link sintetici nelle sezioni 08, 09 e 10 (anche se non ancora in questo ramo): senza data-cta-position.
		await ev(`(function(){ var main=document.querySelector('main.hv2');
			[['08-aziende','https://app.trovapulizie.com/'],['09-faq','https://wa.me/393200980144?text=Ciao'],['10-chiusura','https://app.trovapulizie.com/']].forEach(function(x){
				var s=main.querySelector('[data-section="'+x[0]+'"]'); if(!s){ s=document.createElement('section'); s.setAttribute('data-section',x[0]); s.setAttribute('data-qa-sintetica','1'); main.appendChild(s); }
				var a=document.createElement('a'); a.href=x[1]; a.textContent='qa'; a.setAttribute('data-qa-sintetico',x[0]); s.appendChild(a); });
			var n=document.createElement('a'); n.href='/privacy/'; n.setAttribute('data-qa-neutro','1'); n.textContent='n'; main.appendChild(n);
			document.addEventListener('click',function(e){ e.preventDefault(); },false); })()`);
		const clicks = JSON.parse(await ev(`(function(){
			var out=[]; var sel='a[data-cta-position],main.hv2 a[data-qa-sintetico]';
			var els=[].slice.call(document.querySelectorAll(sel));
			els.forEach(function(a){ var before=window.dataLayer.length; var href=a.getAttribute('href'); a.click();
				var ev=window.dataLayer.slice(before).filter(function(x){return x&&x.event&&x.event.indexOf('marketing_')===0;});
				out.push({pos:a.getAttribute('data-cta-position'),sec:(a.closest('[data-section]')||{getAttribute:function(){return ''}}).getAttribute('data-section'),inV2:!!a.closest('main.hv2'),land:!!a.closest('[data-landing]')||!!document.querySelector('[data-landing]'),href:href,hrefDopo:a.getAttribute('href'),sint:a.getAttribute('data-qa-sintetico'),events:ev}); });
			var nb=window.dataLayer.length; document.querySelector('[data-qa-neutro]').click();
			return JSON.stringify({clicks:out,neutro:window.dataLayer.slice(nb).filter(function(x){return x&&x.event&&x.event.indexOf('marketing_')===0;})}); })()`));
		const att = { '08-aziende': 'home_b2b', '09-faq': 'faq_whatsapp', '10-chiusura': 'cta_final' };
		let posViste = [];
		clicks.clicks.forEach(c => {
			const e = c.events;
			const atteso = c.sint ? att[c.sint] : c.pos;
			posViste.push(atteso);
			check('tracking: ' + (c.sint ? 'link sintetico ' + c.sint : 'link ' + c.pos) + ' emette un solo marketing_cta_click', e.length === 1 && e[0].event === 'marketing_cta_click', JSON.stringify(e));
			if (e.length === 1) check('tracking: ' + atteso + ' porta landing home, posizione e target originale', e[0].landing === 'home' && e[0].cta_position === atteso && e[0].target === c.href && c.hrefDopo === c.href, JSON.stringify(e[0]));
			if (/wa\.me|whatsapp/.test(c.href)) check('tracking: WhatsApp ' + atteso + ' non emette marketing_click_whatsapp', !e.some(x => x.event === 'marketing_click_whatsapp'), JSON.stringify(e));
		});
		// Zone: i due link nascono dal controllo del CAP (zones-check.js). Un CAP in lista -> "Apri l'app" (zones_check),
		// un CAP di Roma fuori lista -> "Scrivici su WhatsApp" (zones_whatsapp).
		for (const [cap, atteso] of [['00186', 'zones_check'], ['00150', 'zones_whatsapp']]) {
			const z = JSON.parse(await ev(`(function(){ var f=document.querySelector('.hv2-zones__check'); if(!f) return JSON.stringify({noform:true}); f.hidden=false; var i=f.querySelector('input'); i.value='${cap}'; f.dispatchEvent(new Event('submit',{cancelable:true,bubbles:true}));
				return new Promise(function(r){ setTimeout(function(){ var a=f.querySelector('a[data-cta-position]'); if(!a) return r(JSON.stringify({nolink:true,html:f.innerHTML.slice(0,200)}));
					var b=window.dataLayer.length, href=a.getAttribute('href'); a.click(); r(JSON.stringify({pos:a.getAttribute('data-cta-position'),href:href,hrefDopo:a.getAttribute('href'),events:window.dataLayer.slice(b).filter(function(x){return x&&x.event&&x.event.indexOf('marketing_')===0;})})); },400); }); })()`));
			if (z.noform) { check('tracking: controllo CAP assente (nessun CAP in lista), zone non provate', true); break; }
			check('tracking: ' + atteso + ' (CAP ' + cap + ') emette un solo marketing_cta_click con landing home e target originale', !z.nolink && z.pos === atteso && z.events.length === 1 && z.events[0].event === 'marketing_cta_click' && z.events[0].landing === 'home' && z.events[0].cta_position === atteso && z.events[0].target === z.href && z.hrefDopo === z.href, JSON.stringify(z));
		}
		check('tracking: coperte navbar e hero', ['navbar', 'hero'].every(p => posViste.includes(p)), posViste.join(','));
		check('tracking: un link senza posizione e non verso app/WhatsApp non emette niente', clicks.neutro.length === 0, JSON.stringify(clicks.neutro));
		// Senza consenso statistiche: l'evento resta nel dataLayer (GTM non invia, consent mode), il link e' intatto.
		const noc = JSON.parse(await ev(`(function(){ document.cookie='tp_consent=; path=/; max-age=0'; var a=document.querySelector('a[data-cta-position="hero"]'); var b=window.dataLayer.length; a.click(); return JSON.stringify({ev:window.dataLayer.slice(b).filter(function(x){return x&&x.event&&x.event.indexOf('marketing_')===0;}),href:a.getAttribute('href'),cons:window.tpConsent()}); })()`));
		check('tracking: senza consenso l\'evento resta nel dataLayer e il link non cambia', noc.cons === null && noc.ev.length === 1 && noc.ev[0].cta_position === 'hero' && /^https:\/\/app\.trovapulizie\.com\/?$/.test(noc.href), JSON.stringify(noc));
	});
}

(async () => {
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


/* Gruppo chiusura (TICKET-127) */
async function checkChiusura() {
	const res = await fetch(url, { redirect: 'manual' });
	check('chiusura: risposta 200', res.status === 200, 'stato ' + res.status);
	if (res.status !== 200) return;
	const html = await res.text();
	const sec = (html.match(/<section class="hv2-cta"[\s\S]*?<\/section>/) || [''])[0];
	check('chiusura: sezione 10-chiusura presente', /data-section="10-chiusura"/.test(sec), sec.slice(0, 100));
	check('chiusura: un solo h2 e nessun h1', (sec.match(/<h2[ >]/g) || []).length === 1 && !/<h1/.test(sec));
	const btn = (sec.match(/<a class="hv2-cta__btn"[^>]*>/) || [''])[0];
	check('chiusura: bottone verso l\'app, senza parametri e senza target, cta_final', /href="https:\/\/app\.trovapulizie\.com\/"/.test(btn) && /data-cta-position="cta_final"/.test(btn) && !/target=/.test(btn), btn);
	const qr = (sec.match(/<div class="hv2-cta__qr">\s*<img src="([^"]+)"/) || [0, ''])[1];
	check('chiusura: QR = qr-app.svg della hero', /home-v2\/qr-app\.svg/.test(qr), qr);
	const svg = qr ? await fetch(qr.replace(/&#038;/g, '&')).then(r => r.text()) : '';
	check('chiusura: <title> dell\'SVG del QR = https://app.trovapulizie.com/', /<title>https:\/\/app\.trovapulizie\.com\/<\/title>/.test(svg));
	const pics = [...sec.matchAll(/<picture class="hv2-cta__tent[^"]*"[\s\S]*?<\/picture>/g)].map(m => m[0]);
	check('chiusura: tentacolo in <picture> con source min-width 1024 e GIF 1x1 di ripiego, alt vuoto', pics.length === 2 && pics.every(p => /<source media="\(min-width: 1024px\)"/.test(p) && /<img src="data:image\/gif/.test(p) && /alt=""/.test(p)), pics.length + ' picture');
}
async function checkChiusuraBrowser() {
	await withChrome(bin, 'about:blank', { port: 9519, width, height: 900, mobile: width < 1024, profile: '/tmp/cdp-profile-home-v2-cta' }, async ({ send, ev, sleep }) => {
		await send('Page.navigate', { url });
		await sleep(3500);
		await ev('document.querySelector("#tp-consent")&&(document.querySelector("#tp-consent").style.display="none")');
		if (!(await ev('!!document.querySelector(".hv2-cta")'))) { check('chiusura: la sezione esiste nel browser', false, 'assente'); return; }
		// scroll completo: le immagini lazy che dovrebbero partire partono
		await ev('(async()=>{for(let y=0;y<=document.scrollingElement.scrollHeight;y+=500){scrollTo(0,y);await new Promise(r=>setTimeout(r,90));}})()');
		await ev('document.querySelector(".hv2-cta").scrollIntoView({block:"start"})');
		await sleep(2500);
		const info = JSON.parse(await ev(`(() => {
			const q = s => document.querySelector(s), cs = e => getComputedStyle(e);
			const rect = e => { const r = e.getBoundingClientRect(); return { w: Math.round(r.width), h: Math.round(r.height) }; };
			const vis = e => { const r = e.getBoundingClientRect(); return cs(e).display !== 'none' && r.width > 0 && r.height > 0; };
			const sec = q('.hv2-cta'), btn = q('.hv2-cta__btn'), qr = q('.hv2-cta__qr img'), tents = [...document.querySelectorAll('.hv2-cta__tent img')];
			const res = performance.getEntriesByType('resource').map(r => r.name);
			return JSON.stringify({
				secBg: cs(sec).backgroundColor, sec: rect(sec), btnBg: cs(btn).backgroundColor, btnC: cs(btn).color, btn: rect(btn), in: rect(q('.hv2-cta__text')),
				ink: { eyebrow: cs(q('.hv2-cta__eyebrow')).color, sub: cs(q('.hv2-cta__sub')).color, note: cs(q('.hv2-cta__note')).color, qrs: q('.hv2-cta__qr strong') ? cs(q('.hv2-cta__qr strong')).color : '', qrx: q('.hv2-cta__qr span') ? cs(q('.hv2-cta__qr span')).color : '' },
				qrVis: vis(qr), qrSize: rect(qr), tentVis: tents.map(vis), tentOk: tents.map(t => t.currentSrc.indexOf('cta-tentacolo') > -1 && t.naturalWidth > 100),
				reqQr: res.filter(u => /qr-app\.svg/.test(u)).length, reqTent: res.filter(u => /cta-tentacolo/.test(u)).length, reqScreen: res.filter(u => /cta-screen\.svg/.test(u)).length,
				phoneVis: vis(q('.hv2-cta__phone')), notifVis: vis(q('.hv2-cta__notif')),
				sw: document.scrollingElement.scrollWidth, cw: document.scrollingElement.clientWidth, dock: cs(q('.hv2-cta__btn')).transitionDuration
			});
		})()`));
		const rgb = c => c.match(/[\d.]+/g).map(Number);
		const lum = ([r, g, b]) => { const f = v => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); }; return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b); };
		const over = (c, bg) => { const [r, g, b, a = 1] = rgb(c), B = rgb(bg); return [r * a + B[0] * (1 - a), g * a + B[1] * (1 - a), b * a + B[2] * (1 - a)]; };
		const ratio = (c, bg) => { const l1 = lum(over(c, bg)), l2 = lum(rgb(bg)); return (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05); };
		check('chiusura: fondo verde #37B299', info.secBg === 'rgb(55, 178, 153)', info.secBg);
		check('chiusura: bottone bianco con testo #062A22 (eccezione di brand)', info.btnBg === 'rgb(255, 255, 255)' && info.btnC === 'rgb(6, 42, 34)', info.btnBg + ' / ' + info.btnC);
		for (const [nome, c] of Object.entries({ 'scritta sopra': info.ink.eyebrow, sottotitolo: info.ink.sub, nota: info.ink.note })) {
			const r = ratio(c, info.secBg);
			check('chiusura: contrasto ' + nome + ' ' + r.toFixed(2) + ':1 >= 4,5', r >= 4.5, c + ' su ' + info.secBg);
		}
		const solid = ratio('rgb(6, 42, 34)', info.secBg);
		check('chiusura: #062A22 su #37B299 ' + solid.toFixed(2) + ':1 (atteso 5,8)', solid >= 5.7 && solid <= 5.9);
		check('chiusura: nessuno scroll orizzontale', info.sw <= info.cw, info.sw + ' > ' + info.cw);
		check('chiusura: il telefono e l\'avviso ci sono a ogni larghezza', info.phoneVis && info.notifVis);
		check('chiusura: schermo del telefono richiesto', info.reqScreen === 1, info.reqScreen + ' richieste');
		if (width >= 1024) {
			check('chiusura: QR visibile da 1024 px, 72 x 72 a 1440', info.qrVis && (width !== 1440 || (info.qrSize.w === 72 && info.qrSize.h === 72)), JSON.stringify(info.qrSize));
			const rq = Math.min(ratio('rgb(6, 42, 34)', info.secBg), 99);
			check('chiusura: tentacolo visibile e immagini caricate (due)', info.tentVis.every(Boolean) && info.tentOk.every(Boolean) && info.reqTent === 2, JSON.stringify({ v: info.tentVis, ok: info.tentOk, req: info.reqTent }));
			if (width === 1440) check('chiusura: fascia alta 630 px, testo e bottone a larghezza propria', info.sec.h === 630 && info.btn.w < 260, JSON.stringify(info.sec) + ' btn ' + info.btn.w);
		} else {
			check('chiusura: QR non visibile sotto 1024 px', !info.qrVis, JSON.stringify(info.qrSize));
			check('chiusura: tentacolo non visibile sotto 1024 px', info.tentVis.every(v => !v) || info.tentVis.every(v => v === false), JSON.stringify(info.tentVis));
			check('chiusura: nessuna richiesta di qr-app.svg ne\' del tentacolo (Network)', info.reqQr === 0 && info.reqTent === 0, 'qr ' + info.reqQr + ', tentacolo ' + info.reqTent);
			if (width === 390) check('chiusura: bottone a tutta larghezza (358) e fascia alta 754', info.btn.w === 358 && info.sec.h === 754, 'btn ' + info.btn.w + ', fascia ' + info.sec.h);
			else check('chiusura: bottone a tutta larghezza del testo', info.btn.w === info.in.w, info.btn.w + ' vs ' + info.in.w);
		}
	});
}

(async () => {
	if (!['base', 'hero', 'frasi', 'servizi', 'manifesto', 'come-funziona', 'chiusura', 'aziende', 'faq', 'tracking'].includes(gruppo)) { console.error('Gruppo sconosciuto: ' + gruppo); process.exit(2); }
	if (gruppo === 'hero') {
		const h = await checkHero();
		if (h) await checkHeroBrowser(h.pre);
	} else if (gruppo === 'frasi') {
		await checkFrasi();
		await checkFrasiBrowser();
	} else if (gruppo === 'chiusura') {
		await checkChiusura();
		await checkChiusuraBrowser();
	} else if (gruppo === 'manifesto') {
		await checkManifesto();
		await checkManifestoBrowser();
	} else if (gruppo === 'servizi') {
		await checkServizi();
		await checkServiziBrowser();
	} else if (gruppo === 'tracking') {
		await checkTracking();
	} else if (gruppo === 'come-funziona') {
		await checkSteps();
		await checkStepsBrowser();
	} else if (gruppo === 'aziende') {
		await checkAziende();
		await checkAziendeBrowser();
	} else if (gruppo === 'faq') {
		await checkFaq();
		await checkFaqBrowser();
	} else {
		const html = await checkHttp();
		if (html) await checkBrowser();
	}
	const ko = results.filter(r => !r.ok).length;
	console.log(ko ? '\nFALLITO: ' + ko + ' controlli su ' + results.length : '\nTutti i controlli ok (' + results.length + ')');
	process.exit(ko ? 1 : 0);
})();
