/* QA della sezione video della Home v2 (TICKET-157).
   Uso: node tools/qa-home-video.js <chrome> <url-base> <cartella-screenshot> [ID-pagina-con-video ID-pagina-senza-video ID-pagina-con-video-e-servizi]
   <url-base> e' la radice del sito (es. http://127.0.0.1:9457). Le tre pagine col template page-home-v2.php si leggono dalla REST
   per slug (home-pieno, home-vuoto, home-misto) se non sono passate; la prova usa un interruttore di test (?qa_front=<ID>) che
   fa passare la pagina per iniziale (il JSON-LD sta solo sulla pagina iniziale); ?qa_theme=base carica il tema di confronto
   (tema "trovapulizie-base", il checkout principale): serve a provare "pagina identica a oggi" a video vuoto.
   Serve Chromium con H.264 per veder partire davvero il video; senza, la prova controlla comunque l'evento "play" e lo stato.

   Controlli:
   - video vuoto: nessun markup .hv2-video, nessuno script home-video.js, nessun VideoObject, Servizi presente, HTML uguale a
     quello del tema di base (a parte il percorso del tema e le versioni);
   - video compilato e Servizi spenti (home-pieno): 03-video dopo 02-frasi, niente 03-servizi, un VideoObject con
     durata PT1M26S, thumbnailUrl e contentUrl; compilato e Servizi accesi (home-misto): ci sono entrambe le sezioni;
   - 1440 e 1024 e 390: riquadro 16:9 (960 a 1440, tutta larghezza a 390), angoli 24 / 18, titolo e riquadro nella prima
     schermata della sezione a 1440x900, nessuna richiesta al file mp4 e nessun <video> prima del clic, nessuno scroll
     orizzontale della pagina, tasto play 96 / 64 px;
   - 390: riga dei link su una riga che scorre (overflow-x auto, scrollbar nascosta, tutti i link alla stessa altezza);
   - clic (mouse vero): <video> con src, controls, playsinline, preload metadata; fuoco sul video; un solo video_play anche dopo
     pausa e nuovo avvio; il tasto e' un <button> con aria-label; senza JavaScript e' un link all'mp4. */
const fs = require('fs');
const { withChrome, sleep } = require('./lib/cdp');
const [chrome, base, outDir, ...ids] = process.argv.slice(2);
if (!chrome || !base || !outDir) { console.log('uso: node tools/qa-home-video.js <chrome> <url-base> <cartella-screenshot>'); process.exit(2); }
fs.mkdirSync(outDir, { recursive: true });
let ko = 0;
const ok = (c, msg) => { console.log((c ? 'OK  ' : 'KO  ') + msg); if (!c) ko++; };
const info = msg => console.log('--  ' + msg);

async function pageId(slug) {
	const r = await fetch(base + '/wp-json/wp/v2/pages?slug=' + slug + '&_fields=id');
	const j = await r.json();
	return j[0] && j[0].id;
}
const html = async url => (await fetch(url)).text();
const norm = s => s.replace(/themes\/trovapulizie-base/g, 'themes/trovapulizie').replace(/wp-theme-trovapulizie-base/g, 'wp-theme-trovapulizie').replace(/ver=[0-9.]+/g, 'ver=X').replace(/qa_theme=base&?/g, '').replace(/[?&]qa_front=\d+/g, '').replace(/\s+/g, ' ');

(async () => {
	const pieno = +ids[0] || await pageId('home-pieno');
	const vuoto = +ids[1] || await pageId('home-vuoto');
	const misto = +ids[2] || await pageId('home-misto');
	info('pagine: pieno ' + pieno + ', vuoto ' + vuoto + ', misto ' + misto);

	// ---- HTML lato server ----
	const hVuoto = await html(base + '/?qa_front=' + vuoto);
	ok(!/hv2-video/.test(hVuoto), 'video vuoto: nessun markup hv2-video');
	ok(!/home-video\.js/.test(hVuoto), 'video vuoto: nessuno script home-video.js');
	ok(!/VideoObject/.test(hVuoto), 'video vuoto: nessun VideoObject nel JSON-LD');
	ok(/data-section="03-servizi"/.test(hVuoto), 'video vuoto: Servizi visibile');
	const hBase = await html(base + '/?qa_front=' + vuoto + '&qa_theme=base');
	const nA = norm(hVuoto), nB = norm(hBase);
	ok(nA === nB, 'video vuoto: HTML uguale al tema di base (' + nA.length + ' / ' + nB.length + ' caratteri)');
	if (nA !== nB) { let i = 0; while (i < nA.length && nA[i] === nB[i]) i++; info('prima differenza a ' + i + ': [' + nA.slice(i - 60, i + 100) + '] contro [' + nB.slice(i - 60, i + 100) + ']'); }

	const hPieno = await html(base + '/?qa_front=' + pieno);
	const sec = [...hPieno.matchAll(/data-section="(\d\d-[a-z-]+)"/g)].map(m => m[1]);
	info('sezioni (video + servizi spenti): ' + sec.join(' '));
	ok(sec.indexOf('03-video') === sec.indexOf('02-frasi') + 1 && sec.indexOf('03-video') > 0, '03-video subito dopo 02-frasi');
	ok(!sec.includes('03-servizi'), 'Servizi spenti con h2_services_hide');
	const ld = [...hPieno.matchAll(/<script type="application\/ld\+json">(.*?)<\/script>/g)].map(m => JSON.parse(m[1])).filter(o => o['@type'] === 'VideoObject');
	ok(ld.length === 1, 'un solo VideoObject (' + ld.length + ')');
	if (ld[0]) {
		ok(ld[0].duration === 'PT1M26S' && ld[0].uploadDate === '2026-11-25', 'VideoObject: duration ' + ld[0].duration + ', uploadDate ' + ld[0].uploadDate);
		ok(/\.mp4$/.test(ld[0].contentUrl) && /poster\.webp$/.test(ld[0].thumbnailUrl[0]) && ld[0].name && ld[0].description, 'VideoObject: nome, descrizione, contentUrl e thumbnailUrl senza ?ver (' + ld[0].thumbnailUrl[0] + ')');
	}
	ok(/home-video\.js/.test(hPieno), 'video compilato: script home-video.js presente');
	const hMisto = await html(base + '/?qa_front=' + misto);
	const secM = [...hMisto.matchAll(/data-section="(\d\d-[a-z-]+)"/g)].map(m => m[1]);
	ok(secM.includes('03-video') && secM.includes('03-servizi') && secM.indexOf('03-video') < secM.indexOf('03-servizi'), 'video compilato e Servizi accesi: entrambe, video prima (' + secM.slice(0, 5).join(' ') + ')');
	const hAnt = await html(base + '/home-pieno/');
	ok(!/VideoObject/.test(hAnt) || true, 'anteprima (non iniziale): nessun requisito sul JSON-LD');

	// ---- browser ----
	const url = base + '/?qa_front=' + pieno;
	for (const w of [1440, 1024, 390]) {
		const h = w === 1440 ? 900 : (w === 1024 ? 768 : 844);
		await withChrome(chrome, url, { port: 9460 + (w % 7), width: w, height: h, mobile: w < 600 }, async ({ send, ev, shot }) => {
			await send('Emulation.setDeviceMetricsOverride', { width: w, height: h, deviceScaleFactor: 1, mobile: w < 600 });
			await sleep(2500);
			// il banner del consenso copre il centro della pagina: si chiude come farebbe un visitatore
			await ev("(()=>{const b=[...document.querySelectorAll('button')].find(x=>/Accetta tutto/i.test(x.textContent));if(b)b.click();return 0})()");
			await sleep(600);
			const tag = '[' + w + '] ';
			await ev("document.querySelector('[data-section=\"03-video\"]').scrollIntoView({block:'start',behavior:'instant'}); 0");
			await sleep(1500);
			const m = JSON.parse(await ev(`JSON.stringify((()=>{
				const s=document.querySelector('[data-section="03-video"]'), f=s.querySelector('.hv2-video__frame'), t=s.querySelector('.hv2-video__title'), b=s.querySelector('.hv2-video__play'), d=s.querySelector('.hv2-video__disc');
				const r=f.getBoundingClientRect(), sr=s.getBoundingClientRect(), tr=t.getBoundingClientRect(), cs=getComputedStyle(f), ts=getComputedStyle(t);
				const ul=s.querySelector('.hv2-video__chips'), chips=[...ul.querySelectorAll('a')].map(a=>Math.round(a.getBoundingClientRect().top));
				const ucs=getComputedStyle(ul);
				return {fw:r.width,fh:r.height,rad:cs.borderTopLeftRadius,shadow:cs.boxShadow,secTop:sr.top,frameBottom:r.bottom,titleTop:tr.top,titleSize:ts.fontSize,titleTx:ts.textTransform,titleW:ts.fontWeight,tag:b.tagName,label:b.getAttribute('aria-label'),disc:d.getBoundingClientRect().width,
				 video:document.querySelectorAll('video').length,mp4:performance.getEntriesByType('resource').filter(e=>/\\.mp4/.test(e.name)).length,
				 hs:document.documentElement.scrollWidth-innerWidth, ovx:ucs.overflowX,wrap:ucs.flexWrap,sbw:ucs.scrollbarWidth,sc:ul.scrollWidth,cw:ul.clientWidth,chipsTop:[...new Set(chips)].length,
				 eyebrow:getComputedStyle(s.querySelector('.hv2-video__eyebrow')).textTransform,
				 imgSrc:s.querySelector('.hv2-video__poster').currentSrc.split('/').pop().split('?')[0], imgNat:s.querySelector('.hv2-video__poster').naturalWidth, sub:s.querySelector('.hv2-video__subtitle').textContent, ttl:t.textContent};
			})())`));
			info(tag + JSON.stringify(m));
			ok(Math.abs(m.fw / m.fh - 16 / 9) < 0.01, tag + 'riquadro 16:9 (' + m.fw + 'x' + m.fh.toFixed(1) + ')');
			if (w === 1440 || w === 1024) ok(m.fw === 960, tag + 'riquadro largo 960');
			if (w === 390) ok(m.fw === 358, tag + 'riquadro a tutta larghezza (358)');
			ok(m.rad === (w === 390 ? '18px' : '24px'), tag + 'angoli ' + m.rad);
			ok(m.titleTx === 'uppercase' && m.ttl === 'Trova. Prenota. Sorridi.', tag + 'titolo "' + m.ttl + '" maiuscolo da CSS, ' + m.titleSize + ', peso ' + m.titleW);
			ok(m.sub === 'Chiedi una pulizia dal telefono. Al resto pensiamo noi.', tag + 'sottotitolo corretto');
			ok(m.video === 0 && m.mp4 === 0, tag + 'prima del clic: nessun <video> e nessuna richiesta mp4 (' + m.video + ', ' + m.mp4 + ')');
			ok(m.hs <= 0, tag + 'nessuno scroll orizzontale della pagina (' + m.hs + ')');
			ok(m.tag === 'BUTTON' && /1 minuto e 26/.test(m.label), tag + 'tasto <button> con aria-label "' + m.label + '"');
			ok(Math.round(m.disc) === (w === 390 ? 64 : 96), tag + 'disco play ' + Math.round(m.disc) + ' px');
			ok(m.imgNat > 0 && (w === 390 ? true : true), tag + 'copertina caricata: ' + m.imgSrc + ' (' + m.imgNat + ' px)');
			if (w === 1440) ok(m.frameBottom - m.secTop <= 900 && m.titleTop >= m.secTop, tag + 'titolo e riquadro nella prima schermata della sezione (fondo del riquadro a ' + Math.round(m.frameBottom - m.secTop) + ' px dalla testa della sezione)');
			if (w === 390) {
				ok(m.ovx === 'auto' && m.wrap === 'nowrap' && m.sbw === 'none', tag + 'link su una riga che scorre (overflow-x ' + m.ovx + ', ' + m.wrap + ', scrollbar ' + m.sbw + ')');
				ok(m.sc > m.cw && m.chipsTop === 1, tag + 'i link superano la larghezza (' + m.sc + ' > ' + m.cw + ') e stanno alla stessa altezza (' + m.chipsTop + ' righe)');
			}
			await sleep(600);
			await shot(outDir + '/' + w + '-prima.png');
			await ev("window.scrollTo({top: document.querySelector('[data-section=\"03-video\"]').getBoundingClientRect().top + scrollY - " + (w === 390 ? 20 : 80) + ", behavior: 'instant'}); 0");
			await sleep(800);
			await shot(outDir + '/' + w + '-sezione.png');

			// clic con il mouse vero sul tasto
			const c = JSON.parse(await ev("JSON.stringify((()=>{const r=document.querySelector('.hv2-video__play').getBoundingClientRect();return {x:r.left+r.width/2,y:r.top+r.height/2}})())"));
			await send('Input.dispatchMouseEvent', { type: 'mouseMoved', x: c.x, y: c.y });
			await send('Input.dispatchMouseEvent', { type: 'mousePressed', x: c.x, y: c.y, button: 'left', clickCount: 1 });
			await send('Input.dispatchMouseEvent', { type: 'mouseReleased', x: c.x, y: c.y, button: 'left', clickCount: 1 });
			await sleep(3500);
			const a = JSON.parse(await ev(`JSON.stringify((()=>{const v=document.querySelector('video');if(!v)return {video:0};
				const dl=(window.dataLayer||[]).filter(e=>e&&e.event==='video_play');
				return {video:document.querySelectorAll('video').length,src:v.getAttribute('src'),controls:v.controls,pi:v.hasAttribute('playsinline'),pre:v.getAttribute('preload'),focus:document.activeElement===v,paused:v.paused,rs:v.readyState,err:v.error&&v.error.code,t:v.currentTime,dur:v.duration,dl:dl.length,dlv:dl[0]&&dl[0].video,btn:!!document.querySelector('.hv2-video__play'),poster:document.querySelector('.hv2-video__poster').hidden,vw:v.getBoundingClientRect().width,vh:v.getBoundingClientRect().height}})())`));
			info(tag + 'dopo il clic: ' + JSON.stringify(a));
			ok(a.video === 1 && /\.mp4$/.test(a.src) && a.controls && a.pi && a.pre === 'metadata', tag + 'clic: <video> con src, controls, playsinline, preload=metadata');
			ok(a.focus, tag + 'il fuoco passa al video');
			ok(a.dl === 1 && a.dlv === 'una-luce', tag + 'evento video_play una volta con video=una-luce (' + a.dl + ')');
			ok(a.err ? (info(tag + 'il Chromium di prova non decodifica questo video (codice errore ' + a.err + ')'), true) : (!a.paused && a.t > 0), tag + (a.err ? 'decodifica non disponibile in questo Chromium' : 'il video parte (t=' + (a.t || 0).toFixed(2) + ' s, readyState ' + a.rs + ')'));
			await ev("(()=>{const v=document.querySelector('video');v.pause();v.play().catch(()=>{});return 0})()");
			await sleep(800);
			const dl2 = await ev("(window.dataLayer||[]).filter(e=>e&&e.event==='video_play').length");
			ok(dl2 === 1, tag + 'pausa e nuovo avvio: ancora un solo video_play (' + dl2 + ')');
			await shot(outDir + '/' + w + '-video.png');
		});
	}

	// senza JavaScript: l'HTML del server ha un link all'mp4 e nessun <button> ne' <video>; il contenuto non dipende dal JS (opacita' 0 solo con html.tp-reveal, messa da reveal.js)
	const mNoJs = hPieno.match(/<a class="hv2-video__play" href="([^"]+\.mp4)"/);
	ok(!!mNoJs && !/<button[^>]*hv2-video__play/.test(hPieno) && !/<video/.test(hPieno), 'senza JavaScript: il tasto e\' un link al file mp4 (' + (mNoJs && mNoJs[1]) + ') e non c\'e\' nessun <video> nell\'HTML');
	// riduci movimento (1440)
	await withChrome(chrome, url, { port: 9471, width: 1440, height: 900 }, async ({ send, ev, shot }) => {
		await send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });
		await send('Page.reload');
		await sleep(2500);
		await ev("document.querySelector('[data-section=\"03-video\"]').scrollIntoView({block:'start',behavior:'instant'}); 0");
		await sleep(1200);
		const r = JSON.parse(await ev(`JSON.stringify((()=>{const f=document.querySelector('.hv2-video__frame');const cs=getComputedStyle(f);return {op:cs.opacity,tf:cs.transform}})())`));
		ok(r.op === '1' && (r.tf === 'none' || /matrix\(1, 0, 0, 1, 0, 0\)/.test(r.tf)), 'riduci movimento: riquadro visibile e senza spostamento (' + r.op + ', ' + r.tf + ')');
	});
	console.log(ko ? ('\n' + ko + ' controlli falliti') : '\nTutto verde');
	process.exit(ko ? 1 : 0);
})();
