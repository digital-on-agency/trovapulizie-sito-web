/* QA della mappa interattiva per CAP (ADR-003, TICKET-03).
   Uso: node tools/qa-zones-map.js <chrome> <url> [larghezza=1440]

   Verifica: passaggio del mouse e click sul CAP 00183, ricerca per nome di quartiere e per CAP dentro
   un indirizzo, testo senza corrispondenza, chip dei quartieri, stati della mappa (attiva / in arrivo /
   non coperta), CTA con il CAP in coda e stato di ripiego con la copertura bloccata via CDP.
   Esce con 1 se almeno un controllo fallisce. */
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
		persone: (p.querySelector('[data-count="individuals"]') || {}).textContent || null,
		imprese: (p.querySelector('[data-count="companies"]') || {}).textContent || null,
		verdetto: (p.querySelector('.zones-map__verdict') || {}).textContent || '',
		// Niente numeri dell'offerta a schermo in questa fase: si controlla sul testo visibile,
		// non sulla presenza degli elementi, cosi' il controllo regge anche a un markup diverso.
		testoPannello: p.innerText.replace(/\s+/g, ' ').trim(),
		cta: p.querySelector('.zones-map__cta').getAttribute('href'),
		ctaVisibile: getComputedStyle(p.querySelector('.zones-map__cta')).display !== 'none',
		scopertaVisibile: getComputedStyle(p.querySelector('.zones-map__uncovered')).display !== 'none',
		ripiegoVisibile: getComputedStyle(p.querySelector('.zones-map__fallback')).display !== 'none',
		stato183: path ? path.getAttribute('data-state') : null,
		attive: document.querySelectorAll('.zones-map__caps path[data-state="active"]').length,
		chipAttivi: document.querySelectorAll('.zones-map__chip[data-state="active"]').length,
		chip: document.querySelectorAll('.zones-map__chip').length,
		inputValore: (document.querySelector('.zones-map .tp-search__input') || {}).value,
		selezionato: !!(path && path.classList.contains('is-selected')),
		totali: [].map.call(p.querySelectorAll('[data-total]'), d => d.textContent),
		servizi: [].map.call(p.querySelectorAll('.zones-map__services li'), li => li.textContent),
		opzioni: document.querySelectorAll('#zones-map-input-list option').length,
		path: document.querySelectorAll('.zones-map__caps path[data-cap]').length,
		panelBox: Math.round(p.getBoundingClientRect().height)
	});
})()`;

// Scrive nel campo di ricerca e preme il bottone, come farebbe una persona.
// [ATTENZIONE] La barra e' la stessa della hero: senza restringere a .zones-map si clicca il "Cerca"
// della hero, che e' statico, e la ricerca sembra non reagire.
const cerca = testo => `(() => {
	const s = document.querySelector('.zones-map');
	s.querySelector('.tp-search__input').value = ${JSON.stringify(testo)};
	s.querySelector('.tp-search__submit').click();
	return 'ok';
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
		controlla('72 voci nella datalist', r.opzioni === 72, r.opzioni);
		controlla('26 chip di quartiere', r.chip === 26, r.chip);
		controlla('barra di ricerca presente', r.inputValore === '', r.inputValore);
		controlla('dati caricati', r.rete === null || r.rete === 'ready', r.rete);
		controlla('in evidenza solo il numero di zone attive', r.totali.join('/') === '26', r.totali);
		controlla('00183 attiva', r.stato183 === 'active', r.stato183);
		controlla('26 CAP attivi sulla mappa', r.attive === 26, r.attive);
		controlla('26 chip marcati attivi', r.chipAttivi === 26, r.chipAttivi);

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
		controlla('nessun contatore di persone o imprese nel DOM', r.persone === null && r.imprese === null, [r.persone, r.imprese]);
		controlla('nessun numero di professionisti nel testo del pannello', !/\b(persone|imprese)\b/i.test(r.testoPannello), r.testoPannello.slice(0, 120));
		controlla('verdetto esplicito sulla zona coperta', r.verdetto === 'Siamo attivi in questa zona.', r.verdetto);
		controlla('CTA verso l\'app con il CAP', /\\?cap=00183$/.test(r.cta || ''), r.cta);
		controlla('CTA visibile, non il bottone zona scoperta', r.ctaVisibile && !r.scopertaVisibile, [r.ctaVisibile, r.scopertaVisibile]);
		controlla('servizi elencati', r.servizi.length > 0, r.servizi);
		controlla('la colonna non cambia altezza fra hover e selezione', Math.abs(r.panelBox - altezzaHover) <= 1, [altezzaHover, r.panelBox]);

		// Ricerca per nome di quartiere.
		await ev(cerca('Trastevere'));
		await sleep(200);
		r = JSON.parse(await ev(LEGGI));
		controlla('ricerca per nome di quartiere', r.stato === 'selected' && /Trastevere/.test(r.zoneName), r.zoneName);

		// Ricerca con un indirizzo che contiene il CAP.
		await ev(cerca('Via Tuscolana 118, 00183 Roma'));
		await sleep(200);
		r = JSON.parse(await ev(LEGGI));
		controlla('CAP riconosciuto dentro un indirizzo', r.zoneName === 'San Giovanni - 00183', r.zoneName);

		// Testo senza corrispondenza: il pannello lo dice.
		await ev(cerca('qwerty'));
		await sleep(200);
		r = JSON.parse(await ev(LEGGI));
		controlla('testo senza corrispondenza: stato "zona non trovata"', r.stato === 'unknown', r.stato);

		// Chip di un quartiere.
		await ev(`(() => { document.querySelector('.zones-map__chip').click(); return 'ok'; })()`);
		await sleep(200);
		r = JSON.parse(await ev(LEGGI));
		controlla('chip seleziona la zona', r.stato === 'selected' && r.zoneName.length > 0, r.zoneName);

		// CAP senza copertura (00118 non e' in nessun profilo).
		await ev(`(() => { document.querySelector('path[data-cap="00118"]').dispatchEvent(new MouseEvent('click', { bubbles: true })); return 'ok'; })()`);
		await sleep(200);
		r = JSON.parse(await ev(LEGGI));
		controlla('zona scoperta: verdetto esplicito', r.verdetto === 'Non siamo ancora attivi qui.', r.verdetto);
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
		controlla('senza dati: nessun CAP marcato attivo', r.attive === 0, r.attive);
		controlla('senza dati: le zone censite restano "in arrivo"', r.stato183 === 'soon', r.stato183);
	});

	console.log(falliti ? '\nFALLITI ' + falliti + ' controlli' : '\nTutti i controlli ok');
	process.exit(falliti ? 1 : 0);
})();
