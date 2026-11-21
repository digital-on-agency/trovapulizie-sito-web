/* QA della sezione zone della Home v2 (SPEC-homepage-v2 US-04, TICKET-125): mappa dei CAP, radar, controllo del CAP.
   Uso: node tools/qa-zones-check.js <chrome> <url-anteprima> [larghezza=1440] [--vuoto <url>] [--lista <url>] [--shots <cartella>]
   <url-anteprima>: pagina col template page-home-v2.php con h2_zones_caps mai salvato (lista del tema, 44 CAP).
   --vuoto: pagina con h2_zones_caps salvato vuoto (il controllo non deve esserci). --lista: pagina con una lista propria
   (es. "00133 | Tor Vergata" e "00195"): il controllo deve usare quella, scartare i CAP non validi.
   Esce con 1 se un controllo fallisce.

   Controlli sulla pagina principale:
   - etichetta del campo "Il tuo CAP" (label associata, testo esatto); 44 chip/zone attive nel testo, 44 voci in data-caps;
   - 00195: "Ci siamo: sei in Della Vittoria / Mazzini", link all'app con data-cta-position="zones_check", path 00195 .is-selected;
   - 00133 (CAP di Roma fuori lista): "Non ancora da te", link WhatsApp wa.me con data-cta-position="zones_whatsapp", nessun campo email,
     nessun path selezionato;
   - non validi ("0019", "abcde", "00100", "00200"): "Scrivi un CAP di Roma (5 cifre)", campo ancora compilato, focus sul campo;
   - 00194: esito positivo col nome, nessun poligono, nessun errore in console;
   - nessuna richiesta di rete partita dal controllo (Network domain, dopo il caricamento);
   - il messaggio sta in un contenitore aria-live; mappa, monumenti e radar aria-hidden;
   - con "riduci movimento" emulato il raggio e gli impulsi sono fermi (animation-name none / display none);
   - con JavaScript spento il form e' hidden e le zone restano come testo;
   - nessuno scroll orizzontale.
   Con --vuoto: il form non c'e' ma titolo, mappa e chip si'. Con --lista: il form usa solo la lista data. */
const { withChrome } = require('./lib/cdp.js');
const fs = require('fs');
const args = process.argv.slice(2);
const opt = n => (args.includes(n) ? args[args.indexOf(n) + 1] : null);
const pos = args.filter((a, i) => !a.startsWith('--') && !(i > 0 && ['--vuoto', '--lista', '--shots'].includes(args[i - 1])));
const [bin, url, widthArg] = pos;
const width = Number(widthArg) || 1440;
if (!bin || !url) { console.error('Uso: node tools/qa-zones-check.js <chrome> <url> [larghezza] [--vuoto url] [--lista url] [--shots cartella]'); process.exit(2); }
const shots = opt('--shots');
if (shots) fs.mkdirSync(shots, { recursive: true });
let ko = 0, n = 0;
const check = (nome, ok, d) => { n++; if (!ok) ko++; console.log((ok ? '[ok] ' : '[KO] ') + nome + (ok || d === undefined ? '' : '  -> ' + d)); };

async function run(u, extra, fn) {
	await withChrome(bin, u, Object.assign({ port: 9340 + Math.floor(Math.random() * 40), width, height: 900, mobile: width < 700 }, extra || {}), async (c) => {
		await c.send('Network.enable');
		for (let i = 0; i < 40; i++) { if (await c.ev('document.readyState === "complete" && !!document.querySelector(".hv2-zones")')) break; await c.sleep(500); }
		await c.sleep(800);
		await fn(c);
	});
}
const J = JSON.stringify;
const sub = (ev, v) => ev(`(async()=>{const i=document.querySelector('.hv2-zones__input');i.value=${J(v)};document.querySelector('.hv2-zones__check').requestSubmit();await new Promise(r=>setTimeout(r,500));
 const f=document.querySelector('.hv2-zones__check');const o=document.querySelector('.hv2-zones__result');const a=o.querySelector('a');
 return JSON.stringify({state:f.getAttribute('data-state'),text:o.textContent.trim(),href:a&&a.href,pos:a&&a.getAttribute('data-cta-position'),sel:[...document.querySelectorAll('.hv2-zones__svg path.is-selected')].map(p=>p.dataset.cap),val:i.value,focus:document.activeElement===i,email:!!f.querySelector('input[type=email],input[name*=mail]')});})()`).then(JSON.parse);

(async () => {
	const requests = [];
	await run(url, null, async ({ ev, send, sleep, shot, on }) => {
		on('Network.requestWillBeSent', p => requests.push(p.request.url));
		const sec = 'document.querySelector(".hv2-zones")';
		await ev(sec + '.scrollIntoView({block:"start"})'); await sleep(1200);
		const base = JSON.parse(await ev(`JSON.stringify({label:(document.querySelector('label[for="hv2-zones-cap"]')||{}).textContent,ph:document.querySelector('.hv2-zones__input').placeholder,hidden:document.querySelector('.hv2-zones__check').hidden,caps:Object.keys(JSON.parse(document.querySelector('.hv2-zones__check').dataset.caps)).length,chips:document.querySelectorAll('.hv2-zones__chip').length,live:document.querySelector('.hv2-zones__result').getAttribute('aria-live'),mapHidden:document.querySelector('.hv2-zones__map').getAttribute('aria-hidden'),h2:document.querySelectorAll('.hv2-zones h2').length,paths:document.querySelectorAll('.hv2-zones__svg path').length,active:document.querySelectorAll('.hv2-zones__svg path[data-state=active]').length,sw:document.scrollingElement.scrollWidth,cw:document.scrollingElement.clientWidth,sweep:getComputedStyle(document.querySelector('.hv2-zones__sweep')).animationName})`));
		check('etichetta del campo "Il tuo CAP"', base.label === 'Il tuo CAP' && base.ph === 'Il tuo CAP', J(base));
		check('form mostrato dallo script (hidden tolto)', base.hidden === false);
		check('44 CAP in data-caps e 44 zone attive nel testo', base.caps === 44 && base.chips === 44, base.caps + ' / ' + base.chips);
		check('un solo h2, contenitore aria-live="polite", mappa aria-hidden', base.h2 === 1 && base.live === 'polite' && base.mapHidden === 'true');
		check('mappa: path stampati (solo quelli nel disco), 43 attivi con poligono (00194 manca)', base.paths > 40 && base.paths < 72 && base.active === 43, base.paths + ' path, ' + base.active + ' attivi');
		check('radar: raggio in rotazione (animation-name hv2-zones-sweep)', base.sweep === 'hv2-zones-sweep', base.sweep);
		check('nessuno scroll orizzontale', base.sw <= base.cw, base.sw + ' > ' + base.cw);
		const before = requests.length;
		let r = await sub(ev, '00195');
		check('00195: "Ci siamo: sei in Della Vittoria / Mazzini"', r.state === 'ok' && /Ci siamo: sei in Della Vittoria \/ Mazzini/.test(r.text) && /^\u271300195/.test(r.text), J(r));
		check('00195: link all\'app con data-cta-position="zones_check"', r.href === 'https://app.trovapulizie.com/' && r.pos === 'zones_check', J(r));
		check('00195: il CAP e\' evidenziato sulla mappa', r.sel.join() === '00195', J(r.sel));
		if (shots) { await sleep(600); await shot(shots + '/esito-ok-' + width + '.png', undefined); }
		r = await sub(ev, '00133');
		check('00133 (Roma, fuori lista): "Non ancora da te"', r.state === 'no' && /Non ancora da te/.test(r.text), J(r));
		check('00133: WhatsApp wa.me con data-cta-position="zones_whatsapp", nessun campo email', /^https:\/\/wa\.me\/\d+/.test(r.href || '') && r.pos === 'zones_whatsapp' && !r.email, J(r));
		check('00133: nessuna evidenziazione residua sulla mappa', r.sel.length === 0, J(r.sel));
		if (shots) { await sleep(600); await shot(shots + '/esito-no-' + width + '.png', undefined); }
		for (const v of ['0019', 'abcde', '00100', '00200']) {
			r = await sub(ev, v);
			check('"' + v + '": "Scrivi un CAP di Roma (5 cifre)", campo compilato e con il focus', r.state === 'errore' && r.text === 'Scrivi un CAP di Roma (5 cifre)' && r.val === v && r.focus, J(r));
		}
		if (shots) { await sleep(600); await shot(shots + '/esito-errore-' + width + '.png', undefined); }
		const exc = [];
		r = await sub(ev, '00194');
		check('00194 (senza poligono): esito positivo col nome, nessuna evidenziazione', r.state === 'ok' && /Ci siamo: sei in Della Vittoria(Apri l.app)?$/.test(r.text) && r.sel.length === 0, J(r));
		check('nessuna richiesta di rete partita dal controllo', requests.length === before, requests.slice(before).join(', '));
	});
	// riduci movimento
	await run(url, null, async ({ ev, send, sleep }) => {
		await send('Emulation.setEmulatedMedia', { features: [{ name: 'prefers-reduced-motion', value: 'reduce' }] });
		await ev('location.reload()'); await sleep(2500);
		await ev('document.querySelector(".hv2-zones").scrollIntoView({block:"start"})'); await sleep(800);
		const s = JSON.parse(await ev(`JSON.stringify({sweep:getComputedStyle(document.querySelector('.hv2-zones__sweep')).display,pulse:[...document.querySelectorAll('.hv2-zones__pulse > i')].map(e=>getComputedStyle(e).animationName+'/'+getComputedStyle(e).display)})`));
		check('riduci movimento: raggio assente e impulsi fermi', s.sweep === 'none' && s.pulse.every(p => p.startsWith('none/')), J(s));
	});
	// JavaScript spento
	await run(url, null, async ({ ev, send, sleep }) => {
		await send('Emulation.setScriptExecutionDisabled', { value: true });
		await ev('location.reload()'); await sleep(2500);
		await send('Emulation.setScriptExecutionDisabled', { value: false });
		const s = JSON.parse(await ev(`JSON.stringify({hidden:document.querySelector('.hv2-zones__check').hidden,vis:getComputedStyle(document.querySelector('.hv2-zones__check')).display,chips:document.querySelectorAll('.hv2-zones__chip').length})`));
		check('JavaScript spento: controllo nascosto, zone attive come testo', s.hidden === true && s.vis === 'none' && s.chips === 44, J(s));
	});
	if (opt('--vuoto')) {
		await run(opt('--vuoto'), null, async ({ ev }) => {
			const s = JSON.parse(await ev(`JSON.stringify({form:!!document.querySelector('.hv2-zones__check'),h2:!!document.querySelector('.hv2-zones h2'),svg:!!document.querySelector('.hv2-zones__svg'),chips:document.querySelectorAll('.hv2-zones__chip').length})`));
			check('campo salvato vuoto: nessun controllo, restano titolo, mappa e chip', !s.form && s.h2 && s.svg && s.chips === 0, J(s));
		});
	}
	if (opt('--lista')) {
		await run(opt('--lista'), null, async ({ ev }) => {
			const s = JSON.parse(await ev(`JSON.stringify(JSON.parse(document.querySelector('.hv2-zones__check').dataset.caps))`));
			check('lista propria: solo i CAP validi (00133, 00195), nomi presi dalla riga o dal tema', J(s) === J({ '00133': 'Tor Vergata', '00195': 'Della Vittoria / Mazzini' }), J(s));
			const r = await sub(ev, '00133');
			check('lista propria: 00133 ora e\' coperto', r.state === 'ok' && /Tor Vergata/.test(r.text), J(r));
		});
	}
	console.log(ko ? 'ESITO: ROSSO (' + ko + ' KO su ' + n + ')' : 'ESITO: verde (' + n + ' controlli)');
	process.exit(ko ? 1 : 0);
})();
