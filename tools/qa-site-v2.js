/* Confronto delle parti condivise del sito prima e dopo i deploy della Home v2 (SPEC-homepage-v2, TICKET-119).
   Con l'interruttore tp_site_v2 spento testata, pannello del menu e footer devono restare identici a oggi.

   Uso:
     node tools/qa-site-v2.js snapshot <base-url> <cartella> [--chrome <ignorato>]
     node tools/qa-site-v2.js compare  <base-url> <cartella> [--cache-bust]
     node tools/qa-site-v2.js footer-v2 <url> --chrome <chrome> [--width 1440|390] [--shots <cartella>]

   snapshot: per le cinque pagine (/, /roma/, /roma/sgrosso-casa/, /roma/sgrosso-commerciale/, /privacy/) salva
   nella cartella il markup di <header>, #nav-panel e <footer>, con ?ver= normalizzato, e l'elenco degli script
   del tema caricati. Anche l'origine (host e porta) e' normalizzata. Va preso PRIMA del primo deploy del ticket.
   compare: rifa' la lettura e confronta con la fotografia; esce con 1 se una parte differisce (stampa la prima
   riga diversa). Dal TICKET-126 la fotografia comprende anche la sezione FAQ (`<section class="faq"` fino a
   `</section>`, JSON-LD compreso; 'ASSENTE' se la pagina non ne ha una) e la pagina /contatti/: le altre pagine che
   usano faq-accordion.php devono restare identiche. Una fotografia presa prima del TICKET-126 non ha queste parti:
   il confronto le segnala e va rifatta. --cache-bust aggiunge ?v=<casuale> alla richiesta (LiteSpeed puo' tenere l'HTML vecchio).

   Scelta di progetto: si legge l'HTML che il server consegna (fetch), non l'outerHTML del browser. Le classi che
   i moduli JS aggiungono dopo il caricamento (is-in, motion-ok) rendono l'outerHTML instabile fra due letture;
   l'HTML del server e' esattamente il markup che il ticket vuole invariato. Il Chromium non serve.

   footer-v2 (TICKET-128): controlli sul footer nuovo di <url> (pagina d'anteprima, oppure qualunque pagina con
   tp_site_v2 acceso). Serve Chromium. Controlli: un solo footer.site-footer-v2 e nessun footer.site-footer; marchio, telefono
   (tel:), email (mailto:), tre colonne piu' "Servizi a Roma" con titolo e link (nessuna colonna vuota); riquadro aziende con
   a[data-cta-position="footer_b2b"]; dati legali con sede, P.IVA e REA; scritta grande aria-hidden; nessuno scroll
   orizzontale; footer visibile (opacita' calcolata 1 su lui e sugli antenati) con reveal.js bloccato (Network.setBlockedURLs);
   a 1440 i gruppi sono aperti e il titolo non e' interattivo; a 390 i gruppi sono chiusi, un tocco apre l'elenco e chiude
   gli altri, il titolo e' alto almeno 44 px; con JavaScript spento i gruppi sono tutti aperti. Esce con 1 se fallisce. */
const fs = require('fs');
const path = require('path');

const PAGES = ['/', '/roma/', '/roma/sgrosso-casa/', '/roma/sgrosso-commerciale/', '/privacy/', '/contatti/'];
const [mode, base, dir] = process.argv.slice(2, 5);
const bust = process.argv.includes('--cache-bust');
// Modalita' footer-v2: controlli a video sul footer nuovo (tools/lib/qa-footer-v2.js). Il return di primo livello e'
// valido nei moduli CommonJS: il resto del file (snapshot e compare) non viene eseguito.
if (mode === 'footer-v2') {
	require('./lib/qa-footer-v2.js')(process.argv.slice(3)).then(ko => process.exit(ko ? 1 : 0));
	return;
}
if (!['snapshot', 'compare'].includes(mode) || !base || !dir) {
	console.error('Uso: node tools/qa-site-v2.js snapshot|compare <base-url> <cartella> [--cache-bust]');
	process.exit(2);
}

const ORIGIN = new URL(base).origin;
// Normalizza ?ver= e l'origine: la fotografia presa su un host (o una porta) si confronta con un altro host.
const norm = s => s.split(ORIGIN).join('{ORIGIN}').replace(/([?&])ver=[^"'&\s>]*/g, '$1ver=X').replace(/\r\n/g, '\n');
const cut = (html, open, close) => {
	const i = html.search(open);
	if (i < 0) return 'ASSENTE';
	const j = html.indexOf(close, i);
	return j < 0 ? 'ASSENTE' : html.slice(i, j + close.length);
};

async function read(p) {
	const url = base.replace(/\/$/, '') + p + (bust ? '?v=' + Math.random().toString(36).slice(2) : '');
	const res = await fetch(url, { redirect: 'follow' });
	const html = await res.text();
	const scripts = [...html.matchAll(/<script[^>]*\ssrc=["']([^"']+)["']/g)]
		.map(m => norm(m[1]).replace(/^https?:\/\/[^/]+/, ''))
		.filter(u => /wp-content\/themes\//.test(u))
		.sort();
	return {
		status: res.status,
		header: norm(cut(html, /<header[\s>]/, '</header>')),
		panel: norm(cut(html, /<nav[^>]*id="nav-panel"/, '</nav>')),
		footer: norm(cut(html, /<footer[\s>]/, '</footer>')),
		faq: norm(cut(html, /<section class="faq[ "]/, '</section>')),
		scripts: scripts.join('\n')
	};
}

const fname = (p, k) => path.join(dir, (p === '/' ? 'home' : p.replace(/\//g, '_').replace(/^_|_$/g, '')) + '.' + k + '.txt');
const KEYS = ['header', 'panel', 'footer', 'faq', 'scripts'];

(async () => {
	fs.mkdirSync(dir, { recursive: true });
	let ko = 0;
	for (const p of PAGES) {
		const cur = await read(p);
		if (cur.status !== 200) { console.log('[KO] ' + p + ' risponde ' + cur.status); ko++; continue; }
		if (mode === 'snapshot') {
			KEYS.forEach(k => fs.writeFileSync(fname(p, k), cur[k]));
			console.log('[ok] ' + p + ' salvata (header ' + cur.header.length + ', pannello ' + cur.panel.length + ', footer ' + cur.footer.length + ' caratteri, ' + cur.scripts.split('\n').filter(Boolean).length + ' script)');
			continue;
		}
		const diff = [];
		KEYS.forEach(k => {
			if (!fs.existsSync(fname(p, k))) { diff.push(k + ': manca nella fotografia (presa prima del TICKET-126): rifare lo snapshot'); return; }
			const old = fs.readFileSync(fname(p, k), 'utf8');
			if (old !== cur[k]) {
				const a = old.split('\n'), b = cur[k].split('\n');
				let n = 0; while (n < a.length && a[n] === b[n]) n++;
				diff.push(k + ' differisce alla riga ' + (n + 1) + ': prima "' + (a[n] || '').slice(0, 90) + '" ora "' + (b[n] || '').slice(0, 90) + '"');
			}
		});
		if (diff.length) { ko++; console.log('[KO] ' + p); diff.forEach(d => console.log('      - ' + d)); }
		else console.log('[ok] ' + p + ': header, pannello, footer e script identici');
	}
	console.log(ko ? '\nFALLITO su ' + ko + ' pagine' : '\n' + (mode === 'snapshot' ? 'Fotografia salvata in ' + dir : 'Nessuna differenza'));
	process.exit(ko ? 1 : 0);
})();
