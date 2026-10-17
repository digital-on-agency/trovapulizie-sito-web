/* QA della mappa interattiva per CAP (ADR-003, TICKET-03).
   Uso: node tools/qa-zones-map.js <chrome> <url> [larghezza=1440]

   Verifica i criteri del ticket: passaggio del mouse e click sul CAP 00183, scelta dal select di un CAP
   senza copertura, CTA con il CAP in coda, e lo stato di ripiego quando i dati non arrivano (la richiesta
   della copertura viene bloccata via CDP). Esce con 1 se almeno un controllo fallisce. */
const { withChrome } = require('./lib/cdp.js');

const bin = process.argv[2];
const url = process.argv[3];
const width = Number(process.argv[4] || 1440);
if (!bin || !url) {
	console.error('Uso: node tools/qa-zones-map.js <chrome> <url> [larghezza]');
	process.exit(2);
}

let falliti = 0;
function controlla(nome, ok, dettaglio) {
	if (!ok) falliti++;
	console.log((ok ? '[ok] ' : '[KO] ') + nome + (!ok && dettaglio !== undefined ? '  -> ' + JSON.stringify(dettaglio) : ''));
}

const LEGGI = `(() => {
	const p = document.querySelector('.zones-map__panel');
	const path = document.querySelector('path[data-cap="00183"]');
	return JSON.stringify({
		stato: p.dataset.state,
		rete: p.dataset.network || null,
		copertura: p.dataset.coverage || null,
		hoverName: p.querySelector('.zones-map__hover-name').textContent,
		zoneName: p.querySelector('.zones-map__zone-name').textContent,
		zoneMeta: p.querySelector('.zones-map__zone-meta').textContent,
		persone: p.querySelector('[data-count="individuals"]').textContent,
		imprese: p.querySelector('[data-count="companies"]').textContent,
		cta: p.querySelector('.zones-map__cta').getAttribute('href'),
		ctaVisibile: getComputedStyle(p.querySelector('.zones-map__cta')).display !== 'none',
		scopertaVisibile: getComputedStyle(p.querySelector('.zones-map__uncovered')).display !== 'none',
		ripiegoVisibile: getComputedStyle(p.querySelector('.zones-map__fallback')).display !== 'none',
		livello183: path ? path.getAttribute('data-level') : null,
		selezionato: !!(path && path.classList.contains('is-selected')),
		totali: [].map.call(p.querySelectorAll('[data-total]'), d => d.textContent),
		servizi: [].map.call(p.querySelectorAll('.zones-map__services li'), li => li.textContent),
		opzioni: document.querySelectorAll('.zones-map__select option').length,
		path: document.querySelectorAll('.zones-map__caps path[data-cap]').length,
		panelBox: Math.round(p.getBoundingClientRect().height)
	});
})()`;

const EVENTO = (sel, tipo) => `(() => {
	const el = document.querySelector('${sel}');
	el.dispatchEvent(new PointerEvent('${tipo}', { bubbles: true }));
	return 'ok';
})()`;

(async () => {
	// 1) Percorso normale: dati dalla fixture.
	await withChrome(bin, url, { port: 9821, width, height: 980, profile: '/tmp/cdp-zones' }, async ({ ev, sleep }) => {
		await sleep(3500);
		await ev(`document.querySelector('.zones-map').scrollIntoView()`);
		let r = JSON.parse(await ev(LEGGI));
		controlla('72 path nella mappa', r.path === 72, r.path);
		controlla('73 opzioni nel select (72 CAP piu\' "Tutte le zone")', r.opzioni === 73, r.opzioni);
		controlla('dati caricati', r.rete === null || r.rete === 'ready', r.rete);
		controlla('totali in evidenza', r.totali.join('/') === '2/2/26', r.totali);
		controlla('00183 al livello 1 (2 persone + 2 imprese = 4 -> livello 2)', r.livello183 === '2', r.livello183);

		await ev(EVENTO('path[data-cap="00183"]', 'pointerover'));
		await sleep(200);
		r = JSON.parse(await ev(LEGGI));
		controlla('pannello in stato hover', r.stato === 'hover', r.stato);
		controlla('nome della zona al passaggio', r.hoverName === 'San Giovanni - 00183', r.hoverName);

		const altezzaHover = r.panelBox;
		await ev(`document.querySelector('path[data-cap="00183"]').dispatchEvent(new MouseEvent('click', { bubbles: true }))`);
		await sleep(200);
		r = JSON.parse(await ev(LEGGI));
		controlla('pannello in stato selected', r.stato === 'selected', r.stato);
		controlla('path evidenziato', r.selezionato === true);
		controlla('nome della zona scelta', r.zoneName === 'San Giovanni - 00183', r.zoneName);
		controlla('CAP e macro zona', r.zoneMeta === 'CAP 00183 - Roma Sud-Est', r.zoneMeta);
		controlla('2 persone', r.persone === '2', r.persone);
		controlla('2 imprese', r.imprese === '2', r.imprese);
		controlla('CTA verso l\'app con il CAP', /\\?cap=00183$/.test(r.cta || ''), r.cta);
		controlla('CTA visibile, non il bottone zona scoperta', r.ctaVisibile && !r.scopertaVisibile, [r.ctaVisibile, r.scopertaVisibile]);
		controlla('servizi elencati', r.servizi.length > 0, r.servizi);
		controlla('la colonna non cambia altezza fra hover e selezione', Math.abs(r.panelBox - altezzaHover) <= 1, [altezzaHover, r.panelBox]);

		// CAP senza copertura scelto dal select (00118 non e' in nessun profilo).
		await ev(`(() => { const s = document.querySelector('.zones-map__select'); s.value = '00118'; s.dispatchEvent(new Event('change', { bubbles: true })); return 'ok'; })()`);
		await sleep(200);
		r = JSON.parse(await ev(LEGGI));
		controlla('zona scoperta: 0 persone', r.persone === '0', r.persone);
		controlla('zona scoperta: 0 imprese', r.imprese === '0', r.imprese);
		controlla('zona scoperta: bottone "Dicci che sei qui" al posto della CTA', r.scopertaVisibile && !r.ctaVisibile, [r.scopertaVisibile, r.ctaVisibile]);
	});

	// 2) Ripiego: la richiesta della copertura viene bloccata.
	await withChrome(bin, url, { port: 9822, width, height: 980, profile: '/tmp/cdp-zones-ko' }, async ({ send, ev, sleep }) => {
		await send('Network.enable');
		await send('Network.setBlockedURLs', { urls: ['*coverage*'] });
		await ev('location.reload()');
		await sleep(7000);
		await ev(`document.querySelector('.zones-map').scrollIntoView()`);
		await ev(`document.querySelector('path[data-cap="00183"]').dispatchEvent(new MouseEvent('click', { bubbles: true }))`);
		await sleep(300);
		const r = JSON.parse(await ev(LEGGI));
		controlla('senza dati: stato di ripiego', r.rete === 'fallback', r.rete);
		controlla('senza dati: la mappa resta navigabile (zona scelta)', r.stato === 'selected' && r.zoneName === 'San Giovanni - 00183', [r.stato, r.zoneName]);
		controlla('senza dati: avviso al posto dei numeri', r.ripiegoVisibile && !r.ctaVisibile, [r.ripiegoVisibile, r.ctaVisibile]);
		controlla('senza dati: tutti i CAP al livello 0', r.livello183 === '0', r.livello183);
	});

	console.log(falliti ? '\nFALLITI ' + falliti + ' controlli' : '\nTutti i controlli ok');
	process.exit(falliti ? 1 : 0);
})();
