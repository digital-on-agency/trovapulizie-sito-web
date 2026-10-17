/* Mappa interattiva dei CAP di Roma (ADR-003, TICKET-03).

   Tre cose sole: colora i path in base alla copertura, tiene lo stato del pannello
   (riposo / passaggio del mouse / zona scelta) e costruisce la CTA con il CAP scelto.

   I dati arrivano da data-coverage-url (fixture in TICKET-03, proxy /wp-json/tp/v1/coverage in
   TICKET-05). Se la richiesta fallisce o supera i 4 secondi la mappa resta navigabile senza numeri:
   stato "fallback", nessun secondo tentativo. Il nome della zona non dipende dalla rete, e' gia' nei
   data-name dei path, scritti dal PHP dal file cap-names.json.

   Accessibilita': i path sono decorativi (aria-hidden sull'SVG) e il percorso da tastiera e' il
   select; il pannello e' aria-live="polite", quindi il cambio di zona viene annunciato. */
(function () {
	'use strict';

	var sezione = document.querySelector('.zones-map');
	if (!sezione) return;

	var svg = sezione.querySelector('svg');
	var gruppo = sezione.querySelector('.zones-map__caps');
	var select = sezione.querySelector('.zones-map__select');
	var pannello = sezione.querySelector('.zones-map__panel');
	if (!gruppo || !pannello) return;

	var etichetteServizi = {};
	try { etichetteServizi = JSON.parse(sezione.dataset.services || '{}') || {}; } catch (e) { etichetteServizi = {}; }

	var stato = { dati: null, hovered: null, selected: null, rete: 'loading' };
	var perCap = {};
	var paths = {};
	[].forEach.call(gruppo.querySelectorAll('path[data-cap]'), function (p) { paths[p.dataset.cap] = p; });
	if (svg) svg.setAttribute('aria-hidden', 'true');

	// Livelli della legenda: 0, 1-2, 3-5, 6 e oltre.
	function livello(n) { return n <= 0 ? 0 : (n <= 2 ? 1 : (n <= 5 ? 2 : 3)); }

	function nomeDi(cap) {
		var zona = perCap[cap];
		if (zona && zona.name) return zona.name;
		var p = paths[cap];
		return (p && p.dataset.name) || '';
	}

	// Etichetta unica per il select, il passaggio del mouse e il titolo della zona: "Nome - CAP",
	// o il solo CAP per i 46 senza nome (TICKET-02).
	function etichettaDi(cap) {
		var nome = nomeDi(cap);
		return nome ? nome + ' - ' + cap : cap;
	}

	function scrivi(selettore, testo) {
		var el = pannello.querySelector(selettore);
		if (el) el.textContent = testo;
	}

	function mostraTotali() {
		var totali = (stato.dati && stato.dati.totals) || null;
		['individuals', 'companies', 'zones_covered'].forEach(function (chiave) {
			var el = pannello.querySelector('[data-total="' + chiave + '"]');
			if (el) el.textContent = totali ? String(totali[chiave]) : '-';
		});
	}

	function applicaLivelli() {
		Object.keys(paths).forEach(function (cap) {
			var zona = perCap[cap];
			var n = zona ? (zona.individuals || 0) + (zona.companies || 0) : 0;
			paths[cap].setAttribute('data-level', String(stato.rete === 'ready' ? livello(n) : 0));
		});
	}

	function mostraZona(cap) {
		var zona = perCap[cap] || null;
		var persone = zona ? (zona.individuals || 0) : 0;
		var imprese = zona ? (zona.companies || 0) : 0;
		var coperta = stato.rete === 'ready' && (persone + imprese) > 0;

		scrivi('.zones-map__zone-name', etichettaDi(cap));
		var pathZona = paths[cap];
		var macro = pathZona && pathZona.dataset.macro ? ' - ' + pathZona.dataset.macro : '';
		scrivi('.zones-map__zone-meta', 'CAP ' + cap + macro);
		scrivi('[data-count="individuals"]', stato.rete === 'ready' ? String(persone) : '-');
		scrivi('[data-count="companies"]', stato.rete === 'ready' ? String(imprese) : '-');

		var lista = pannello.querySelector('.zones-map__services');
		if (lista) {
			lista.innerHTML = '';
			((zona && zona.services) || []).forEach(function (codice) {
				var li = document.createElement('li');
				li.textContent = etichetteServizi[codice] || codice;
				lista.appendChild(li);
			});
		}

		var cta = pannello.querySelector('.zones-map__cta');
		if (cta) {
			var base = sezione.dataset.appUrl || '';
			cta.href = base + (base.indexOf('?') >= 0 ? '&' : '?') + 'cap=' + encodeURIComponent(cap);
		}
		pannello.dataset.coverage = coperta ? 'yes' : 'no';
		pannello.dataset.network = stato.rete;
		pannello.dataset.state = 'selected';
	}

	function aggiornaPannello() {
		if (stato.selected) { mostraZona(stato.selected); return; }
		if (stato.hovered) {
			scrivi('.zones-map__hover-name', etichettaDi(stato.hovered));
			pannello.dataset.state = 'hover';
			return;
		}
		pannello.dataset.state = 'idle';
	}

	function seleziona(cap) {
		if (stato.selected && paths[stato.selected]) paths[stato.selected].classList.remove('is-selected');
		stato.selected = cap || null;
		if (cap && paths[cap]) {
			paths[cap].classList.add('is-selected');
			// Un path dipinto dopo gli altri non viene coperto dai vicini: lo stroke di selezione resta intero.
			gruppo.appendChild(paths[cap]);
		}
		if (select && select.value !== (cap || '')) select.value = cap || '';
		aggiornaPannello();
	}

	gruppo.addEventListener('pointerover', function (e) {
		var p = e.target.closest ? e.target.closest('path[data-cap]') : null;
		if (!p) return;
		stato.hovered = p.dataset.cap;
		if (!stato.selected) aggiornaPannello();
	});
	gruppo.addEventListener('pointerout', function (e) {
		var p = e.target.closest ? e.target.closest('path[data-cap]') : null;
		if (!p || p.dataset.cap !== stato.hovered) return;
		stato.hovered = null;
		if (!stato.selected) aggiornaPannello();
	});
	gruppo.addEventListener('click', function (e) {
		var p = e.target.closest ? e.target.closest('path[data-cap]') : null;
		if (p) seleziona(p.dataset.cap);
	});
	if (select) {
		select.addEventListener('change', function () { seleziona(select.value || null); });
	}

	var cta = pannello.querySelector('.zones-map__cta');
	if (cta) {
		cta.addEventListener('click', function () {
			var zona = stato.selected ? perCap[stato.selected] : null;
			var n = zona ? (zona.individuals || 0) + (zona.companies || 0) : 0;
			window.dataLayer = window.dataLayer || [];
			window.dataLayer.push({ event: 'coverage_zone_cta', cap: stato.selected, level: livello(n) });
		});
	}

	// Dati: un solo tentativo, 4 secondi. Senza URL (staging senza proxy) si va diretti al fallback.
	var url = sezione.dataset.coverageUrl;
	if (!url) {
		stato.rete = 'fallback';
		applicaLivelli();
		mostraTotali();
		return;
	}
	var taglia = typeof AbortController === 'function' ? new AbortController() : null;
	var scaduto = setTimeout(function () { if (taglia) taglia.abort(); }, 4000);
	fetch(url, taglia ? { signal: taglia.signal } : undefined)
		.then(function (r) { if (!r.ok) throw new Error('http ' + r.status); return r.json(); })
		.then(function (dati) {
			if (!dati || dati.v !== 1 || !Array.isArray(dati.zones)) throw new Error('formato');
			stato.dati = dati;
			dati.zones.forEach(function (z) { perCap[z.cap] = z; });
			stato.rete = 'ready';
		})
		.catch(function () { stato.rete = 'fallback'; })
		.then(function () {
			clearTimeout(scaduto);
			sezione.dataset.network = stato.rete;
			applicaLivelli();
			mostraTotali();
			aggiornaPannello();
		});
})();
