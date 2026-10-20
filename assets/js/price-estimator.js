/**
 * Calcolatore della stima — sezione "Quanto costa" dell'hub (TICKET-34).
 *
 * La formula non e' inventata: e' ricostruita dai due soli dati gia' pubblicati.
 * Il Figma mostra un unico stato, 80 m2 / Bilocale / una tantum -> 48-64 euro, e le FAQ
 * dichiarano una tariffa di 15-20 euro/ora. I due combaciano a 3,2 ore: 15 x 3,2 = 48 e
 * 20 x 3,2 = 64. Da li' le ore: m2 / 25, corrette per tipo di casa e frequenza.
 *
 * [ATTENZIONE] I coefficienti stanno tutti qui sotto e in nessun altro punto. La tariffa
 * oraria arriva dai campi WP (data-tariffa-min / -max): se cambia il prezzo, si cambia il
 * campo, non il codice. Se Guido conferma coefficienti diversi, si toccano solo queste
 * due tabelle.
 */
(function () {
	'use strict';

	var sezione = document.querySelector('[data-price-estimator]');
	if (!sezione) return;

	var MQ_PER_ORA = 25;
	var TIPO = { monolocale: 0.9, bilocale: 1, trilocale: 1.1, 'quattro-piu': 1.2 };
	// Il ricorrente costa meno a visita: la casa parte gia' in ordine.
	var FREQUENZA = { 'una-tantum': 1, settimanale: 0.85, quindicinale: 0.9 };

	var tariffaMin = parseFloat(sezione.dataset.tariffaMin) || 15;
	var tariffaMax = parseFloat(sezione.dataset.tariffaMax) || 20;

	var slider = sezione.querySelector('[data-mq]');
	var mqOut = sezione.querySelector('[data-mq-out]');
	var stimaOut = sezione.querySelector('[data-stima]');
	var riepilogo = sezione.querySelector('[data-riepilogo]');
	if (!slider || !stimaOut) return;

	/* Funzione pura: stessa risposta a parita' di argomenti, nessun accesso al DOM.
	   E' il punto in cui si verifica la formula senza dover aprire una pagina. */
	function stima(mq, tipo, frequenza) {
		var ore = (mq / MQ_PER_ORA) * (TIPO[tipo] || 1) * (FREQUENZA[frequenza] || 1);
		return { min: Math.round(ore * tariffaMin), max: Math.round(ore * tariffaMax), ore: ore };
	}

	function sceltaAttiva(gruppo) {
		var b = sezione.querySelector('[data-gruppo="' + gruppo + '"] .is-active');
		return b ? b.dataset.valore : '';
	}

	function etichettaAttiva(gruppo) {
		var b = sezione.querySelector('[data-gruppo="' + gruppo + '"] .is-active');
		return b ? b.textContent.trim().toLowerCase() : '';
	}

	function aggiorna() {
		var mq = parseInt(slider.value, 10);
		var r = stima(mq, sceltaAttiva('tipo'), sceltaAttiva('frequenza'));
		if (mqOut) mqOut.textContent = mq + ' m²';
		stimaOut.textContent = '€ ' + r.min + ' – ' + r.max;
		if (riepilogo) riepilogo.textContent = '≈ ' + mq + ' m² · ' + etichettaAttiva('tipo') + ' · ' + etichettaAttiva('frequenza');
		// Riempimento della traccia: il browser non lo fa da solo su input[type=range].
		var pct = ((mq - slider.min) / (slider.max - slider.min)) * 100;
		slider.style.setProperty('--riempimento', pct + '%');
	}

	slider.addEventListener('input', aggiorna);
	[].forEach.call(sezione.querySelectorAll('[data-gruppo] button'), function (b) {
		b.addEventListener('click', function () {
			var fratelli = b.parentNode.querySelectorAll('button');
			[].forEach.call(fratelli, function (f) {
				f.classList.toggle('is-active', f === b);
				f.setAttribute('aria-pressed', f === b ? 'true' : 'false');
			});
			aggiorna();
		});
	});

	// Senza JS la sezione mostra il testo di ripiego: da qui in poi i controlli sono vivi.
	sezione.classList.add('is-ready');
	aggiorna();
})();
